<?php

namespace App\Http\Controllers;

use App\Models\Part;
use App\Models\Exercise;
use Illuminate\Http\JsonResponse;

class PartController extends Controller
{
    public function getBySection($sectionId): JsonResponse
    {
        $parts = Part::where('section_id', $sectionId)
            ->where('is_active', true)
            ->orderBy('order_index')
            ->get();
            
        return response()->json($parts);
    }

    public function show($id): JsonResponse
    {
        $part = Part::findOrFail($id);
        return response()->json($part);
    }

    public function getWithContent($id): JsonResponse
    {
        $part = Part::with(['contentBlocks' => function($query) {
            $query->orderBy('order_index');
        }])->findOrFail($id);
        
        return response()->json($part);
    }
        public function getByPart($partId): JsonResponse
    {
        $exercises = Exercise::where('part_id', $partId)
            ->where('is_active', true)
            ->orderBy('order_index')
            ->get();
            
        return response()->json($exercises);
    }
}