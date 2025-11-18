<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exercise;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class AdminExerciseController extends Controller
{
    public function index($partId): JsonResponse
    {
        $exercises = Exercise::where('part_id', $partId)
            ->orderBy('order_index')
            ->get();
            
        return response()->json($exercises);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'part_id' => 'required|exists:parts,id',
            'type' => 'required|in:fill_blank,multiple_choice,code_test',
            'question' => 'required|string',
            'solution' => 'required|array',
            'code_template' => 'nullable|string',
            'hint' => 'nullable|string',
            'difficulty' => 'sometimes|in:easy,medium,hard',
            'exp_reward' => 'required|integer|min:0',
            'order_index' => 'sometimes|integer',
            'is_active' => 'sometimes|boolean',
        ]);

        $data = $request->all();
        
        // ✅ FIX: CONVERT SOLUTION STRUCTURE UNTUK BACKEND
        $data['solution'] = $this->convertSolutionForBackend($data['type'], $data['solution']);
        
        // ✅ AUTO ORDER_INDEX
        if (!isset($data['order_index'])) {
            $maxOrder = Exercise::where('part_id', $data['part_id'])
                ->max('order_index') ?? 0;
            $data['order_index'] = $maxOrder + 1;
        }

        // ✅ DEFAULT is_active
        if (!isset($data['is_active'])) {
            $data['is_active'] = true;
        }

        // ✅ DEFAULT difficulty
        if (!isset($data['difficulty'])) {
            $data['difficulty'] = 'easy';
        }

        $exercise = Exercise::create($data);

        return response()->json($exercise, 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $exercise = Exercise::findOrFail($id);
        
        $request->validate([
            'type' => 'sometimes|in:fill_blank,multiple_choice,code_test',
            'question' => 'sometimes|string',
            'solution' => 'sometimes|array',
            'code_template' => 'nullable|string',
            'hint' => 'nullable|string',
            'difficulty' => 'sometimes|in:easy,medium,hard',
            'exp_reward' => 'sometimes|integer|min:0',
            'order_index' => 'sometimes|integer',
            'is_active' => 'sometimes|boolean',
        ]);

        $data = $request->all();
        
        // ✅ FIX: CONVERT SOLUTION STRUCTURE JIKA ADA UPDATE
        if (isset($data['solution']) && isset($data['type'])) {
            $data['solution'] = $this->convertSolutionForBackend($data['type'], $data['solution']);
        }

        $exercise->update($data);

        return response()->json($exercise);
    }

    // ✅ FIXED METHOD: CONVERT SOLUTION STRUCTURE UNTUK BACKEND
    private function convertSolutionForBackend($type, $solution)
    {
        switch ($type) {
            case 'multiple_choice':
                return $this->normalizeMultipleChoiceSolution($solution);
                
            case 'fill_blank':
                return $this->normalizeFillBlankSolution($solution); // ✅ FIXED!
                
            case 'code_test':
                return $solution;
                
            default:
                return $solution;
        }
    }

    // ✅ METHOD UNTUK MULTIPLE CHOICE
    private function normalizeMultipleChoiceSolution($solution)
    {
        Log::info('MultipleChoice Before normalization:', $solution);
        
        $correctAnswerFromOptions = null;
        
        if (isset($solution['options'])) {
            foreach ($solution['options'] as $option) {
                if (isset($option['correct']) && $option['correct'] === true) {
                    $correctAnswerFromOptions = $option['text'] ?? $option['id'];
                    break;
                }
            }
            
            foreach ($solution['options'] as &$option) {
                unset($option['correct']);
            }
        }
        
        if ($correctAnswerFromOptions) {
            $solution['correct_answer'] = $correctAnswerFromOptions;
        }
        
        Log::info('MultipleChoice After normalization:', $solution);
        
        return $solution;
    }

    // ✅ NEW METHOD UNTUK FILL_BLANK
    private function normalizeFillBlankSolution($solution)
    {
        Log::info('FillBlank Before normalization:', $solution);
        
        // ✅ CONVERT correct_answer KE expected_answers
        if (isset($solution['correct_answer']) && !isset($solution['expected_answers'])) {
            $solution['expected_answers'] = is_array($solution['correct_answer']) 
                ? $solution['correct_answer'] 
                : [$solution['correct_answer']];
            unset($solution['correct_answer']);
            Log::info('FillBlank Converted correct_answer to expected_answers');
        }
        
        // ✅ CONVERT STRING KE ARRAY
        if (isset($solution['expected_answers']) && is_string($solution['expected_answers'])) {
            $solution['expected_answers'] = [$solution['expected_answers']];
            Log::info('FillBlank Converted string expected_answers to array');
        }
        
        Log::info('FillBlank After normalization:', $solution);
        
        return $solution;
    }

    public function destroy($id): JsonResponse
    {
        $exercise = Exercise::findOrFail($id);
        $exercise->delete();

        return response()->json(['message' => 'Exercise deleted successfully']);
    }

    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'exercises' => 'required|array',
            'exercises.*.id' => 'required|exists:exercises,id',
            'exercises.*.order_index' => 'required|integer'
        ]);

        foreach ($request->exercises as $item) {
            Exercise::where('id', $item['id'])->update([
                'order_index' => $item['order_index']
            ]);
        }

        return response()->json(['message' => 'Exercises reordered successfully']);
    }

    // ✅ BONUS: FIX EXISTING DATA
    public function fixExistingData()
    {
        $fixedCount = 0;
        
        // ✅ FIX FILL_BLANK EXERCISES
        $fillBlankExercises = Exercise::where('type', 'fill_blank')->get();
        foreach ($fillBlankExercises as $exercise) {
            $fixedSolution = $this->normalizeFillBlankSolution($exercise->solution);
            if ($exercise->solution != $fixedSolution) {
                $exercise->solution = $fixedSolution;
                $exercise->save();
                $fixedCount++;
            }
        }
        
        return response()->json([
            'message' => 'Fixed ' . $fixedCount . ' exercises',
            'fixed_count' => $fixedCount
        ]);
    }
}