<?php

namespace App\Http\Controllers;

use App\Models\Language;
use Illuminate\Http\JsonResponse;

class LanguageController extends Controller
{
    public function index(): JsonResponse
    {
        $languages = Language::where('is_active', true)
            ->orderBy('order_index')
            ->withCount('sections')
            ->get();
            
        return response()->json($languages);
    }

    public function show($id): JsonResponse
    {
        $language = Language::with(['sections' => function($query) {
            $query->where('is_active', true)->orderBy('order_index');
        }])->findOrFail($id);
        
        return response()->json($language);
    }

    // ✅ HAPUS METHOD getSections - SUDAH ADA DI SectionController
}