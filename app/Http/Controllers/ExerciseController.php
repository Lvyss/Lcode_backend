<?php

namespace App\Http\Controllers;

use App\Models\Exercise;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use App\Models\UserProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Part;;
class ExerciseController extends Controller
{
    public function getByPart($partId): JsonResponse
    {
        $exercises = Exercise::where('part_id', $partId)
            ->where('is_active', true)
            ->orderBy('order_index')
            ->get();
            
        return response()->json($exercises);
    }

    public function show($id): JsonResponse
    {
        $exercise = Exercise::findOrFail($id);
        return response()->json($exercise);
    }
 
public function checkCodeTest(Request $request)
{
    $request->validate([
        'exercise_id' => 'required|exists:exercises,id',
        'user_answer' => 'required|string'
    ]);

    return DB::transaction(function () use ($request) {
        $exercise = Exercise::findOrFail($request->exercise_id);
        $userId = auth()->id();
        $user = User::find($userId);
        
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }
        
        if ($exercise->type !== 'code_test') {
            return response()->json(['error' => 'Invalid exercise type'], 400);
        }

        try {
            // ✅ CODE EXECUTION
            $fullCode = str_replace('____', $request->user_answer, $exercise->code_template);
            $executionResult = $this->executeCode($fullCode);
            
            $expectedOutput = $exercise->solution['expected_output'];
            $actualOutput = $executionResult['output'];
            $isCorrect = $actualOutput === trim($expectedOutput);
            
            // ✅ EXERCISE REWARD
            $expEarned = $isCorrect ? $exercise->exp_reward : 0;
            $totalExpEarned = $expEarned;
            
            // ✅ UPDATE USER TOTAL_EXP JIKA BENAR
            if ($isCorrect && $expEarned > 0) {
                $user->total_exp += $expEarned;
                $user->save();
            }
            
            // ✅ GET CURRENT ATTEMPTS
            $currentProgress = UserProgress::where([
                'user_id' => $userId,
                'exercise_id' => $exercise->id
            ])->first();

            $attempts = $currentProgress ? $currentProgress->attempts + 1 : 1;
            
            // ✅ SAVE PROGRESS
            $progress = UserProgress::updateOrCreate(
                [
                    'user_id' => $userId,
                    'exercise_id' => $exercise->id
                ],
                [
                    'part_id' => $exercise->part_id,
                    'completed' => true,
                    'user_answer' => $request->user_answer,
                    'is_correct' => $isCorrect,
                    'exp_earned' => $expEarned,
                    'attempts' => $attempts,
                    'completed_at' => now()
                ]
            );

            // ✅ CHECK PART COMPLETION BONUS
            $partCompleted = false;
            $partExpEarned = 0;
            
            if ($isCorrect) {
                $partCompletion = $this->checkPartCompletion($exercise->part_id, $userId);
                $partCompleted = $partCompletion['completed'];
                $partExpEarned = $partCompletion['bonus_exp'];
                
                // ✅ ADD PART COMPLETION BONUS - INI YANG PERLU DITAMBAH!
                if ($partCompleted && $partExpEarned > 0) {
                    $user->total_exp += $partExpEarned;
                    $user->save();
                    $totalExpEarned += $partExpEarned;
                    
                    // ✅ LOG BONUS AWARD
                    Log::info("🎉 PART COMPLETION BONUS AWARDED (CODE TEST): User {$userId} earned {$partExpEarned} EXP for completing part {$exercise->part_id}");
                }
            }

            return response()->json([
                'success' => true,
                'is_correct' => $isCorrect,
                'actual_output' => $actualOutput,
                'expected_output' => $expectedOutput,
                'exp_earned' => $expEarned,
                'part_completed' => $partCompleted,
                'part_exp_earned' => $partExpEarned,
                'total_exp_earned' => $totalExpEarned,
                'user_total_exp' => $user->total_exp,
                'progress' => $progress
            ]);

        } catch (\Exception $e) {
            Log::error('Piston execution failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'is_correct' => false,
                'actual_output' => '',
                'expected_output' => $exercise->solution['expected_output'] ?? ''
            ], 500);
        }
    });
}
// ✅ TAMBAH METHOD INI DI ExerciseController
// ✅ UPDATE METHOD INI DI ExerciseController - PAKE exp_reward BUKAN exp_bonus
private function checkPartCompletion($partId, $userId)
{
    $part = Part::find($partId);
    if (!$part) {
        return ['completed' => false, 'bonus_exp' => 0];
    }

    $exercises = Exercise::where('part_id', $partId)->get();
    $totalExercises = $exercises->count();
    
    if ($totalExercises === 0) {
        return ['completed' => false, 'bonus_exp' => 0];
    }

    $completedCorrectExercises = UserProgress::where('user_id', $userId)
        ->whereIn('exercise_id', $exercises->pluck('id'))
        ->where('completed', true)
        ->where('is_correct', true)
        ->count();

    $allCompletedAndCorrect = ($completedCorrectExercises === $totalExercises);
    
    // ✅ FIX: PAKE part->exp_reward UNTUK BONUS PART COMPLETION
    $bonusExp = $allCompletedAndCorrect ? $part->exp_reward : 0;

    return [
        'completed' => $allCompletedAndCorrect,
        'bonus_exp' => $bonusExp
    ];
}
private function executeCode($code)
{
    try {
        // ✅ PAKE PISTON PUBLIC API - 100% FREE NO LIMITS
        $response = Http::post('https://emkc.org/api/v2/piston/execute', [
            'language' => 'cpp',
            'version' => '10.2.0',
            'files' => [
                [
                    'name' => 'main.cpp',
                    'content' => $code
                ]
            ],
            'stdin' => '',
            'args' => []
        ]);

        if (!$response->successful()) {
            throw new \Exception('Piston API request failed: ' . $response->status());
        }

        $result = $response->json();
        
        // ✅ CHECK EXECUTION RESULT
        if (isset($result['run']['stdout'])) {
            return [
                'output' => trim($result['run']['stdout']),
                'status' => 'success'
            ];
        } else if (isset($result['run']['stderr'])) {
            throw new \Exception('Execution error: ' . $result['run']['stderr']);
        } else {
            throw new \Exception('Unknown execution error');
        }

    } catch (\Exception $e) {
        throw new \Exception('Code execution failed: ' . $e->getMessage());
    }
}
}