<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// HANYA 2 ROUTES INI DI WEB.PHP - untuk Google OAuth
Route::get('/oauth/google', [AuthController::class, 'redirectToGoogle']);
Route::get('/oauth/google/callback', [AuthController::class, 'handleGoogleCallback']);

