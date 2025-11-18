<?php

namespace App\Http\Controllers;

use App\Models\Section;
use Illuminate\Http\JsonResponse;

class SectionController extends Controller
{
    public function getByLanguage($languageId): JsonResponse
    {
        $sections = Section::where('language_id', $languageId)
            ->where('is_active', true)
            ->orderBy('order_index')
            ->withCount('parts')
            ->get();
            
        return response()->json($sections);
    }

    public function show($id): JsonResponse
    {
        $section = Section::with(['parts' => function($query) {
            $query->where('is_active', true)->orderBy('order_index');
        }])->findOrFail($id);
        
        return response()->json($section);
    }
}