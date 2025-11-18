<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Tree;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use Exception;
use Illuminate\Support\Facades\Log;
class AuthController extends Controller
{
public function redirectToGoogle()
{
    return Socialite::driver('google')->redirect();
}

public function handleGoogleCallback()
{
    try {
        $googleUser = Socialite::driver('google')->user();
        
        $user = User::where('email', $googleUser->getEmail())->first();

        if (!$user) {
            $user = User::create([
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'username' => Str::slug($googleUser->getNickname() ?? $googleUser->getName()) . '_' . Str::random(4),
                'email_verified_at' => now(),
            ]);

            Tree::create([
                'user_id' => $user->id,
                'current_stage' => 'seed',
                'growth_percentage' => 0,
                'total_exp' => 0
            ]);
        }

        $token = $user->createToken('lcode-token')->plainTextToken;

        // ✅ JANGAN ENCODE - biarkan token asli
        $redirectUrl = 'http://localhost:5173/auth/callback?token=' . $token . '&user_id=' . $user->id;
        
        Log::info('🔀 Redirecting with RAW token', ['url' => $redirectUrl]);
        
        return redirect($redirectUrl);

    } catch (\Exception $e) {
        Log::error('Google OAuth Error: ' . $e->getMessage());
        return redirect('http://localhost:5173/login?error=auth_failed');
    }
}

    public function logoutApi(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }
}