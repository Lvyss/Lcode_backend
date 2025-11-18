<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BadgeController extends Controller
{
    public function getUserBadges(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $badges = $user->badges()
            ->with('section')
            ->orderBy('order_index')
            ->get();
            
        return response()->json($badges);
    }
}