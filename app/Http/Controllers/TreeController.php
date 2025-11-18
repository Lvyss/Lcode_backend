<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TreeController extends Controller
{
    public function getUserTree(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Simple tree growth logic based on EXP
        $treeStage = $this->calculateTreeStage($user->exp);
        
        return response()->json([
            'stage' => $treeStage,
            'exp' => $user->exp,
            'next_stage_exp' => $this->getNextStageExp($treeStage)
        ]);
    }

    private function calculateTreeStage($exp): string
    {
        if ($exp < 100) return 'seed';
        if ($exp < 500) return 'sprout';
        if ($exp < 1000) return 'small_tree';
        if ($exp < 5000) return 'big_tree';
        return 'legendary_tree';
    }

    private function getNextStageExp($stage): int
    {
        return match($stage) {
            'seed' => 100,
            'sprout' => 500,
            'small_tree' => 1000,
            'big_tree' => 5000,
            default => 0
        };
    }
}