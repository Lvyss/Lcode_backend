<?php

namespace App\Http\Controllers;

use App\Models\UserProgress;
use App\Models\Part;
use App\Models\Exercise;
use App\Models\User;
use App\Models\Section;
use App\Services\BadgeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
class ProgressController extends Controller
{

// app/Http/Controllers/ProgressController.php

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
        
        // ✅ UNIFIED ANSWER CHECKING - SUPPORT SEMUA TYPE!
        $validationResult = $this->checkAnswerCorrectness($exercise, $request->user_answer);
        $isCorrect = $validationResult['is_correct'];
        $actualOutput = $validationResult['actual_output'] ?? '';
        
        // ✅ EXERCISE REWARD - HANYA JIKA BENAR!
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

        // ✅ CHECK PART & SECTION COMPLETION - HANYA JIKA BENAR!
        $partCompleted = false;
        $partExpEarned = 0;
        $sectionCompleted = false;
        $sectionExpEarned = 0;
        $awardedBadges = [];
        
        if ($isCorrect) {
            // ✅ CHECK PART COMPLETION
            $partCompletion = $this->checkPartCompletion($exercise->part_id, $userId);
            $partCompleted = $partCompletion['completed'];
            $partExpEarned = $partCompletion['bonus_exp'];
            
            // ✅ ADD PART BONUS EXP
            if ($partCompleted && $partExpEarned > 0) {
                $user->total_exp += $partExpEarned;
                $user->save();
                $totalExpEarned += $partExpEarned;
                
                Log::info("🎉 PART COMPLETION BONUS: User {$userId} earned {$partExpEarned} EXP for part {$exercise->part_id}");
            }
            
            // ✅ CHECK SECTION COMPLETION
            if ($partCompleted) {
                $part = Part::find($exercise->part_id);
                if ($part) {
                    $sectionCompletion = $this->checkSectionCompletion($part->section_id, $userId);
                    $sectionCompleted = $sectionCompletion['completed'];
                    $sectionExpEarned = $sectionCompletion['bonus_exp'];
                    
                    // ✅ ADD SECTION BONUS EXP
                    if ($sectionCompleted && $sectionExpEarned > 0) {
                        $user->total_exp += $sectionExpEarned;
                        $user->save();
                        $totalExpEarned += $sectionExpEarned;
                        
                        Log::info("🏆 SECTION COMPLETION BONUS: User {$userId} earned {$sectionExpEarned} EXP for section {$part->section_id}");
                    }

                    // ✅ CHECK BADGES
                    $badgeService = new BadgeService();
                    $awardedBadges = $badgeService->checkAndAwardBadges($user, $part->section_id);
                }
            }
        } else {
            // ✅ LOG KALAU SALAH
            Log::info("❌ WRONG ANSWER: User {$userId} - Exercise {$exercise->id}, Type: {$exercise->type}");
        }

        // ✅ REFRESH USER DATA
        $user->refresh();

        // ✅ UNIFIED RESPONSE FOR ALL EXERCISE TYPES
        return response()->json([
            'success' => true,
            'is_correct' => $isCorrect,
            'actual_output' => $actualOutput, // ✅ UNTUK CODE TEST
            'expected_output' => $exercise->type === 'code_test' ? ($exercise->solution['expected_output'] ?? '') : '',
            'exp_earned' => $expEarned,
            'part_completed' => $partCompleted,
            'part_exp_earned' => $partExpEarned,
            'section_completed' => $sectionCompleted,
            'section_exp_earned' => $sectionExpEarned,
            'total_exp_earned' => $totalExpEarned,
            'user_total_exp' => $user->total_exp,
            'awarded_badges' => $awardedBadges,
            'progress' => $progress
        ]);
    });
}
    /**
     * CHECK ANSWER CORRECTNESS BERDASARKAN EXERCISE TYPE
     */
// app/Http/Controllers/ProgressController.php

/**
 * ✅ UNIFIED ANSWER VALIDATION - SUPPORT SEMUA TYPE!
 */
private function checkAnswerCorrectness(Exercise $exercise, string $userAnswer): array
{
    $solution = $exercise->solution;
    $userAnswer = trim($userAnswer);

    switch ($exercise->type) {
        case 'multiple_choice':
            $isCorrect = $this->validateMultipleChoice($solution, $userAnswer);
            return ['is_correct' => $isCorrect];
            
        case 'fill_blank':
            $isCorrect = $this->validateFillBlank($solution, $userAnswer);
            return ['is_correct' => $isCorrect];
            
        case 'code_test':
            // ✅ PAKE EXECUTION ENGINE UNTUK CODE TEST!
            return $this->validateCodeTest($exercise, $userAnswer);
            
        default:
            $correctAnswer = $solution['correct_answer'] ?? '';
            $isCorrect = $userAnswer === trim($correctAnswer);
            return ['is_correct' => $isCorrect];
    }
}

/**
 * ✅ CODE TEST VALIDATION DENGAN EXECUTION
 */
private function validateCodeTest(Exercise $exercise, string $userAnswer): array
{
    try {
        // ✅ CODE EXECUTION - SAMA DENGAN YANG DI ExerciseController
        $fullCode = str_replace('____', $userAnswer, $exercise->code_template);
        $executionResult = $this->executeCode($fullCode);
        
        $expectedOutput = $exercise->solution['expected_output'] ?? '';
        $actualOutput = $executionResult['output'];
        
        // ✅ CASE SENSITIVE COMPARISON
        $isCorrect = trim($actualOutput) === trim($expectedOutput);
        
        return [
            'is_correct' => $isCorrect,
            'actual_output' => $actualOutput
        ];
        
    } catch (\Exception $e) {
        Log::error('Code execution failed: ' . $e->getMessage());
        
        return [
            'is_correct' => false,
            'actual_output' => '(Execution Error)'
        ];
    }
}

/**
 * ✅ CODE EXECUTION ENGINE - COPY DARI ExerciseController
 */
private function executeCode($code)
{
    try {
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

// ✅ TAMBAHIN SETELAH checkPartCompletion METHOD
private function checkSectionCompletion($sectionId, $userId)
{
    Log::info("🔍 CHECKING SECTION COMPLETION: section_id={$sectionId}, user_id={$userId}");
    
    $section = Section::find($sectionId);
    if (!$section) {
        Log::warning("❌ Section not found: {$sectionId}");
        return ['completed' => false, 'bonus_exp' => 0];
    }

    // ✅ GET ALL PARTS IN SECTION
    $parts = Part::where('section_id', $sectionId)->get();
    $totalParts = $parts->count();
    
    Log::info("📊 Section {$sectionId} has {$totalParts} parts");
    
    if ($totalParts === 0) {
        Log::warning("❌ No parts found for section: {$sectionId}");
        return ['completed' => false, 'bonus_exp' => 0];
    }

    // ✅ COUNT COMPLETED PARTS (SEMUA EXERCISES DI PART SELESAI & BENAR)
    $completedParts = 0;
    
    foreach ($parts as $part) {
        $partCompletion = $this->checkPartCompletion($part->id, $userId);
        if ($partCompletion['completed']) {
            $completedParts++;
            Log::info("✅ Part {$part->id} completed for section {$sectionId}");
        }
    }

    Log::info("📈 User {$userId} completed {$completedParts}/{$totalParts} parts in section {$sectionId}");

    $allPartsCompleted = ($completedParts === $totalParts);
    
    // ✅ KASIH BONUS EXP JIKA SEMUA PART SELESAI
    $bonusExp = $allPartsCompleted ? $section->exp_reward : 0;

    Log::info("🎯 Section completion result: completed={$allPartsCompleted}, bonus_exp={$bonusExp}, section->exp_reward={$section->exp_reward}");

    return [
        'completed' => $allPartsCompleted,
        'bonus_exp' => $bonusExp,
        'completed_parts' => $completedParts,
        'total_parts' => $totalParts
    ];
}
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

    // ✅ TAMBAH METHOD BARU UNTUK GET SECTION PROGRESS
public function getSectionProgress(Request $request, $sectionId): JsonResponse
{
    try {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'section_id' => $sectionId,
                'total_parts' => 0,
                'completed_parts' => 0,
                'progress_percentage' => 0,
                'section_completed' => false
            ]);
        }

        // ✅ FIX: PASTIKAN SECTION EXISTS
        $section = Section::find($sectionId);
        if (!$section) {
            return response()->json([
                'section_id' => $sectionId,
                'total_parts' => 0,
                'completed_parts' => 0,
                'progress_percentage' => 0,
                'section_completed' => false,
                'error' => 'Section not found'
            ], 404);
        }

        $parts = Part::where('section_id', $sectionId)
            ->where('is_active', true)
            ->get();
            
        $totalParts = $parts->count();
        $completedParts = 0;
        
        // ✅ CHECK EACH PART COMPLETION
        foreach ($parts as $part) {
            $partCompletion = $this->checkPartCompletion($part->id, $user->id);
            if ($partCompletion['completed']) {
                $completedParts++;
            }
        }
        
        $progressPercentage = $totalParts > 0 
            ? round(($completedParts / $totalParts) * 100) 
            : 0;
            
        $sectionCompleted = ($completedParts === $totalParts);
        
        return response()->json([
            'section_id' => $sectionId,
            'section_name' => $section->name,
            'total_parts' => $totalParts,
            'completed_parts' => $completedParts,
            'progress_percentage' => $progressPercentage,
            'section_completed' => $sectionCompleted,
            'exp_reward' => $section->exp_reward
        ]);
        
    } catch (\Exception $e) {
        // ✅ LOG ERROR
        Log::error('getSectionProgress Error: ' . $e->getMessage(), [
            'section_id' => $sectionId,
            'user_id' => $request->user()?->id,
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'section_id' => $sectionId,
            'total_parts' => 0,
            'completed_parts' => 0,
            'progress_percentage' => 0,
            'section_completed' => false,
            'error' => 'Server error'
        ], 500);
    }
}
} // ✅ INI CLOSING BRACKET YANG MISSING!