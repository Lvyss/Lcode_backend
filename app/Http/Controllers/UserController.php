<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserProgress;
use App\Models\UserBadge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
// app/Http/Controllers/UserController.php
public function getProfileStats()
{
    try {
        $user = auth()->user();
        
        // ✅ PAKE total_exp, BUKAN exp
        $totalExp = $user->total_exp ?? 0;
        $level = floor($totalExp / 1000) + 1;

        // Progress stats
        $completedExercises = UserProgress::where('user_id', $user->id)->count();
        
        // Count unique completed parts
        $completedParts = UserProgress::where('user_id', $user->id)
            ->distinct('part_id')
            ->count();

        // Badge stats
        $totalBadges = UserBadge::where('user_id', $user->id)->count();

        // Streak 
        $streak = UserProgress::where('user_id', $user->id)
            ->where('completed_at', '>=', now()->subDays(7))
            ->distinct('completed_at')
            ->count();

        return response()->json([
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
            ],
            'stats' => [
                'total_exp' => $totalExp, // ✅ PAKE total_exp
                'level' => $level,
                'completed_exercises' => $completedExercises,
                'completed_parts' => $completedParts,
                'total_badges' => $totalBadges,
                'current_streak' => $streak,
            ]
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Failed to load profile stats',
            'message' => $e->getMessage()
        ], 500);
    }
}

public function getLeaderboard()
{
    try {
        // ✅ PAKE total_exp, BUKAN exp
        $users = User::select('id', 'name', 'avatar', 'total_exp')
            ->where('total_exp', '>', 0) // ✅ total_exp
            ->orderBy('total_exp', 'DESC') // ✅ total_exp
            ->limit(20)
            ->get()
            ->map(function($user, $index) {
                return [
                    'id' => $user->id,
                    'rank' => $index + 1,
                    'name' => $user->name,
                    'avatar' => $user->avatar,
                    'exp' => $user->total_exp, // ✅ total_exp
                    'level' => floor($user->total_exp / 1000) + 1 // ✅ total_exp
                ];
            });

        return response()->json($users);

    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Failed to load leaderboard',
            'message' => $e->getMessage()
        ], 500);
    }
}

    public function updateProfile(Request $request)
    {
        try {
            $user = auth()->user();
            
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'avatar' => 'sometimes|string',
            ]);

            $user->update($validated);

            return response()->json([
                'message' => 'Profile updated successfully',
                'user' => $user
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update profile',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}