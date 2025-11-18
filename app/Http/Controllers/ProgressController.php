<?php

namespace App\Http\Controllers;

use App\Models\UserProgress;
use App\Models\Part;
use App\Models\Exercise;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProgressController extends Controller
{
    // ✅ COMPLETE EXERCISE (User submit jawaban exercise)
public function completeExercise(Request $request)
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
        
        // ✅ CHECK CORRECTNESS
        $isCorrect = $this->checkAnswerCorrectness($exercise, $request->user_answer);
        
        // ✅ HANYA KASIH EXP KALAU BENAR
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

        // ✅ CHECK PART COMPLETION
        $partCompleted = false;
        $partExpEarned = 0;
        
        if ($isCorrect) {
            $partCompletion = $this->checkPartCompletion($exercise->part_id, $userId);
            $partCompleted = $partCompletion['completed'];
            $partExpEarned = $partCompletion['bonus_exp'];
            
            // ✅ ADD BONUS EXP TO USER - INI YANG PERLU DITAMBAH!
            if ($partCompleted && $partExpEarned > 0) {
                $user->total_exp += $partExpEarned;
                $user->save();
                $totalExpEarned += $partExpEarned;
                
                // ✅ LOG BONUS AWARD
                Log::info("🎉 PART COMPLETION BONUS AWARDED: User {$userId} earned {$partExpEarned} EXP for completing part {$exercise->part_id}");
                Log::info("💰 User total_exp before: " . ($user->total_exp - $partExpEarned) . ", after: " . $user->total_exp);
            }
        }

        return response()->json([
            'success' => true,
            'is_correct' => $isCorrect,
            'exp_earned' => $expEarned,
            'part_completed' => $partCompleted,
            'part_exp_earned' => $partExpEarned,
            'total_exp_earned' => $totalExpEarned, // ✅ TOTAL YANG DITAMBAHKAN
            'user_total_exp' => $user->total_exp, // ✅ TOTAL EXP USER SEKARANG
            'progress' => $progress
        ]);
    });
}

    /**
     * CHECK ANSWER CORRECTNESS BERDASARKAN EXERCISE TYPE
     */
private function checkAnswerCorrectness(Exercise $exercise, string $userAnswer): bool
{
    $solution = $exercise->solution;
    $userAnswer = trim($userAnswer);

    switch ($exercise->type) {
        case 'multiple_choice':
            return $this->validateMultipleChoice($solution, $userAnswer);
            
        case 'fill_blank':
            return $this->validateFillBlank($solution, $userAnswer);
            
        case 'code_test':
            // ✅ FIX: JANGAN LANGSUNG RETURN FALSE!
            // Untuk code_test, kita pake method terpisah di ExerciseController
            // Tapi tetep perlu handle di sini untuk backward compatibility
            $expectedOutput = $solution['expected_output'] ?? '';
            return trim($userAnswer) === trim($expectedOutput);
            
        default:
            $correctAnswer = $solution['correct_answer'] ?? '';
            return $userAnswer === trim($correctAnswer);
    }
}

    /**
     * ✅ FIXED MULTIPLE CHOICE VALIDATION
     */
private function validateMultipleChoice(array $solution, string $userAnswer): bool
{
    // ✅ PRIORITAS 1: CEK correct_answer FIELD DULU
    if (isset($solution['correct_answer'])) {
        $correctAnswer = $solution['correct_answer'];
        
        // ✅ JIKA correct_answer ADALAH ID, CARI TEXT-NYA
        if (is_numeric($correctAnswer) && isset($solution['options'])) {
            foreach ($solution['options'] as $option) {
                if (isset($option['id']) && $option['id'] == $correctAnswer) {
                    $correctText = $option['text'] ?? $option['id'];
                    return trim($userAnswer) === trim($correctText);
                }
            }
        }
        
        // ✅ JIKA correct_answer ADALAH TEXT, LANGSUNG COMPARE
        return trim($userAnswer) === trim($correctAnswer);
    }
    
    // ✅ PRIORITAS 2: CEK OPTIONS YANG correct: true
    if (isset($solution['options'])) {
        foreach ($solution['options'] as $option) {
            if (isset($option['correct']) && $option['correct'] === true) {
                $correctText = $option['text'] ?? $option['id'] ?? '';
                return trim($userAnswer) === trim($correctText);
            }
        }
    }
    
    return false;
}

    /**
     * ✅ FIXED FILL BLANK VALIDATION  
     */
    private function validateFillBlank(array $solution, string $userAnswer): bool
    {
        $expectedAnswers = $solution['expected_answers'] ?? [];
        
        // ✅ HANDLE BOTH FORMATS: "answer1|answer2" OR ["answer1", "answer2"]
        if (is_string($userAnswer)) {
            $userAnswers = explode('|', $userAnswer);
        } else {
            $userAnswers = (array)$userAnswer;
        }
        
        // ✅ CHECK LENGTH FIRST
        if (count($userAnswers) !== count($expectedAnswers)) {
            return false;
        }
        
        // ✅ CHECK EACH ANSWER
        foreach ($userAnswers as $index => $userAns) {
            $expected = $expectedAnswers[$index] ?? '';
            if (trim($userAns) !== trim($expected)) {
                return false;
            }
        }
        
        return true;
    }

// ✅ UPDATE checkPartCompletion METHOD
private function checkPartCompletion($partId, $userId)
{
    Log::info("🔍 CHECKING PART COMPLETION: part_id={$partId}, user_id={$userId}");
    
    $part = Part::find($partId);
    if (!$part) {
        Log::warning("❌ Part not found: {$partId}");
        return ['completed' => false, 'bonus_exp' => 0];
    }

    // ✅ GET ALL EXERCISES IN PART
    $exercises = Exercise::where('part_id', $partId)->get();
    $totalExercises = $exercises->count();
    
    Log::info("📊 Part {$partId} has {$totalExercises} exercises");
    
    if ($totalExercises === 0) {
        Log::warning("❌ No exercises found for part: {$partId}");
        return ['completed' => false, 'bonus_exp' => 0];
    }

    // ✅ COUNT COMPLETED & CORRECT EXERCISES
    $completedCorrectExercises = UserProgress::where('user_id', $userId)
        ->whereIn('exercise_id', $exercises->pluck('id'))
        ->where('completed', true)
        ->where('is_correct', true)
        ->count();

    Log::info("✅ User {$userId} completed {$completedCorrectExercises}/{$totalExercises} exercises correctly in part {$partId}");

    $allCompletedAndCorrect = ($completedCorrectExercises === $totalExercises);
    
    // ✅ KASIH BONUS EXP JIKA SEMUA SELESAI DAN BENAR
    $bonusExp = $allCompletedAndCorrect ? $part->exp_reward : 0;

    Log::info("🎯 Part completion result: completed={$allCompletedAndCorrect}, bonus_exp={$bonusExp}, part->exp_reward={$part->exp_reward}");

    // ✅ DEBUG: CEK DETAIL SETIAP EXERCISE
    foreach ($exercises as $exercise) {
        $progress = UserProgress::where('user_id', $userId)
            ->where('exercise_id', $exercise->id)
            ->first();
            
        Log::info("📝 Exercise {$exercise->id}: completed=" . ($progress->completed ?? 'false') . ", is_correct=" . ($progress->is_correct ?? 'false'));
    }

    return [
        'completed' => $allCompletedAndCorrect,
        'bonus_exp' => $bonusExp,
        'completed_exercises' => $completedCorrectExercises,
        'total_exercises' => $totalExercises
    ];
}

    // ✅ GET PART PROGRESS (Untuk indicator di section)
    public function getPartProgress(Request $request, $partId): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'part_id' => $partId,
                    'total_exercises' => 0,
                    'completed_exercises' => 0,
                    'progress_percentage' => 0,
                    'part_completed' => false
                ]);
            }

            // ✅ FIX: PASTIKAN PART EXISTS
            $part = Part::find($partId);
            if (!$part) {
                return response()->json([
                    'part_id' => $partId,
                    'total_exercises' => 0,
                    'completed_exercises' => 0,
                    'progress_percentage' => 0,
                    'part_completed' => false,
                    'error' => 'Part not found'
                ], 404);
            }

            $totalExercises = Exercise::where('part_id', $partId)
                ->where('is_active', true)
                ->count();
                
            $completedExercises = UserProgress::where('user_id', $user->id)
                ->where('part_id', $partId)
                ->where('completed', true)
                ->whereNotNull('exercise_id')
                ->count();
                
            $partCompleted = UserProgress::where('user_id', $user->id)
                ->where('part_id', $partId)
                ->where('completed', true)
                ->whereNull('exercise_id')
                ->exists();
                
            $progressPercentage = $totalExercises > 0 
                ? round(($completedExercises / $totalExercises) * 100) 
                : 0;
                
            return response()->json([
                'part_id' => $partId,
                'total_exercises' => $totalExercises,
                'completed_exercises' => $completedExercises,
                'progress_percentage' => $progressPercentage,
                'part_completed' => $partCompleted
            ]);
            
        } catch (\Exception $e) {
            // ✅ LOG ERROR
            Log::error('getPartProgress Error: ' . $e->getMessage(), [
                'part_id' => $partId,
                'user_id' => $request->user()?->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'part_id' => $partId,
                'total_exercises' => 0,
                'completed_exercises' => 0,
                'progress_percentage' => 0,
                'part_completed' => false,
                'error' => 'Server error'
            ], 500);
        }
    }

    // ✅ GET USER PROGRESS (Dashboard)
    public function getUserProgress(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'total_exp' => 0,
                    'level' => 1,
                    'completed_parts' => 0,
                    'completed_exercises' => 0,
                    'streak' => 0
                ]);
            }
            
            $progress = [
                'total_exp' => $user->total_exp ?? 0,
                'level' => $this->calculateLevel($user->total_exp ?? 0),
                'completed_parts' => UserProgress::where('user_id', $user->id)
                    ->where('completed', true)
                    ->whereNull('exercise_id') // ✅ HANYA PART COMPLETION
                    ->count(),
                'completed_exercises' => UserProgress::where('user_id', $user->id)
                    ->where('completed', true)
                    ->whereNotNull('exercise_id') // ✅ HANYA EXERCISE COMPLETION
                    ->count(),
                'streak' => $user->current_streak ?? 0
            ];
            
            return response()->json($progress);
            
        } catch (\Exception $e) {
            return response()->json([
                'total_exp' => 0,
                'level' => 1,
                'completed_parts' => 0,
                'completed_exercises' => 0,
                'streak' => 0
            ]);
        }
    }

    private function calculateLevel($totalExp): int
    {
        return floor($totalExp / 100) + 1;
    }

    // ✅ GET EXERCISE STATUS
    public function getExerciseStatus($exerciseId)
    {
        try {
            $userId = auth()->id();
            
            if (!$userId) {
                return response()->json([
                    'completed' => false,
                    'user_answer' => null,
                    'is_correct' => false,
                    'completed_at' => null
                ]);
            }

            $progress = UserProgress::where('user_id', $userId)
                ->where('exercise_id', $exerciseId)
                ->first();

            return response()->json([
                'completed' => !is_null($progress) && $progress->completed,
                'user_answer' => $progress->user_answer ?? null,
                'is_correct' => $progress->is_correct ?? false,
                'completed_at' => $progress->completed_at ?? null
            ]);
            
        } catch (\Exception $e) {
            // ✅ LOG ERROR UNTUK DEBUG
            Log::error('getExerciseStatus Error: ' . $e->getMessage(), [
                'exercise_id' => $exerciseId,
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'completed' => false,
                'user_answer' => null,
                'is_correct' => false,
                'completed_at' => null,
                'error' => 'Failed to fetch exercise status'
            ], 500);
        }
    }
} // ✅ INI CLOSING BRACKET YANG MISSING!