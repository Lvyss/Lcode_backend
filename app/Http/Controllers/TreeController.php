<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class TreeController extends Controller
{
// app/Http/Controllers/TreeController.php
public function getUserTreeProgress()
{
    try {
        $user = auth()->user();
        $totalExp = $user->total_exp ?? 0; // ✅ PAKE total_exp
        
        // TREE GROWTH STAGES berdasarkan EXP
        $stages = [
            ['min_exp' => 0, 'max_exp' => 100, 'stage' => 'seed', 'name' => 'Benih', 'description' => 'Baru mulai petualangan coding!'],
            ['min_exp' => 100, 'max_exp' => 500, 'stage' => 'sprout', 'name' => 'Kecambah', 'description' => 'Sedang bertumbuh dengan cepat!'],
            ['min_exp' => 500, 'max_exp' => 1000, 'stage' => 'small_tree', 'name' => 'Pohon Kecil', 'description' => 'Sudah mulai kuat!'],
            ['min_exp' => 1000, 'max_exp' => 5000, 'stage' => 'big_tree', 'name' => 'Pohon Besar', 'description' => 'Pengalaman coding yang solid!'],
            ['min_exp' => 5000, 'max_exp' => 10000, 'stage' => 'ancient_tree', 'name' => 'Pohon Legendaris', 'description' => 'Master programmer!'],
        ];
        
        $currentStage = null;
        $nextStage = null;
        $progressToNext = 0;
        
        foreach ($stages as $index => $stage) {
            if ($totalExp >= $stage['min_exp'] && $totalExp < $stage['max_exp']) {
                $currentStage = $stage;
                $nextStage = $stages[$index + 1] ?? null;
                
                if ($nextStage) {
                    $range = $stage['max_exp'] - $stage['min_exp'];
                    $currentProgress = $totalExp - $stage['min_exp'];
                    $progressToNext = ($currentProgress / $range) * 100;
                }
                break;
            }
        }
        
        // Jika EXP melebihi stage tertinggi
        if (!$currentStage && $totalExp >= end($stages)['max_exp']) {
            $currentStage = end($stages);
            $progressToNext = 100;
        }
        
        return response()->json([
            'current_stage' => $currentStage,
            'next_stage' => $nextStage,
            'progress_to_next' => round($progressToNext, 2),
            'total_exp' => $totalExp
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Failed to load tree progress',
            'message' => $e->getMessage()
        ], 500);
    }
}
}