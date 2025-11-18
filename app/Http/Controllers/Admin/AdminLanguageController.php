<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminLanguageController extends Controller
{
    public function index(): JsonResponse
    {
        $languages = Language::orderBy('order_index')->get();
        return response()->json($languages);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string', // ✅ HAPUS max:255
            'description' => 'nullable|string',
            'order_index' => 'sometimes|integer',
            'is_active' => 'sometimes|boolean',
        ]);

        $data = $request->all();
        
        // Auto order_index jika tidak disediakan
        if (!isset($data['order_index'])) {
            $maxOrder = Language::max('order_index') ?? 0;
            $data['order_index'] = $maxOrder + 1;
        }

        // Default is_active ke true jika tidak disediakan
        if (!isset($data['is_active'])) {
            $data['is_active'] = true;
        }

        $language = Language::create($data);

        return response()->json($language, 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $language = Language::findOrFail($id);
        
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'icon' => 'nullable|string', // ✅ HAPUS max:255 DI UPDATE JUGA
            'description' => 'nullable|string',
            'order_index' => 'sometimes|integer',
            'is_active' => 'sometimes|boolean',
        ]);

        $language->update($request->all());

        return response()->json($language);
    }

    public function destroy($id): JsonResponse
    {
        $language = Language::findOrFail($id);
        
        // Cek apakah language punya sections sebelum delete
        if ($language->sections()->exists()) {
            return response()->json([
                'message' => 'Cannot delete language with existing sections'
            ], 422);
        }

        $language->delete();

        return response()->json(['message' => 'Language deleted successfully']);
    }
}