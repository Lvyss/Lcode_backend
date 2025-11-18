<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function getStats(Request $request)
    {
        $user = $request->user();
        
        return response()->json([
            'total_exp' => $user->total_exp,
            'current_streak' => $user->current_streak,
            'completed_parts' => $user->progress()->where('completed', true)->count(),
            'badges_count' => $user->badges()->count(),
        ]);
    }

    public function getProfile(Request $request)
    {
        $user = $request->user()->load(['tree', 'badges']);
        return response()->json($user);
    }

    public function getLeaderboard()
    {
        return response()->json(
            User::orderBy('total_exp', 'DESC')->take(10)->get()
        );
    }
}