<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminSectionController extends Controller
{
    public function index($languageId): JsonResponse
    {
        $sections = Section::where('language_id', $languageId)
            ->orderBy('order_index')
            ->get();
            
        return response()->json($sections);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'language_id' => 'required|exists:languages,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'order_index' => 'sometimes|integer', // ✅ UBAH KE sometimes
            'exp_reward' => 'required|integer|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        $data = $request->all();
        
        // ✅ AUTO ORDER_INDEX KALO GA DISEDIAKAN
        if (!isset($data['order_index'])) {
            $maxOrder = Section::where('language_id', $data['language_id'])
                ->max('order_index') ?? 0;
            $data['order_index'] = $maxOrder + 1;
        }

        // ✅ DEFAULT is_active KE TRUE
        if (!isset($data['is_active'])) {
            $data['is_active'] = true;
        }

        $section = Section::create($data);

        return response()->json($section, 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $section = Section::findOrFail($id);
        
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'order_index' => 'sometimes|integer',
            'exp_reward' => 'sometimes|integer|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        $section->update($request->all());

        return response()->json($section);
    }

    public function destroy($id): JsonResponse
    {
        $section = Section::findOrFail($id);
        
        // ✅ CEK APAKAH SECTION PUNYA PARTS SEBELUM DELETE
        if ($section->allParts()->exists()) {
            return response()->json([
                'message' => 'Cannot delete section with existing parts'
            ], 422);
        }

        $section->delete();

        return response()->json(['message' => 'Section deleted successfully']);
    }

    // ✅ BONUS: REORDER SECTIONS
    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'sections' => 'required|array',
            'sections.*.id' => 'required|exists:sections,id',
            'sections.*.order_index' => 'required|integer'
        ]);

        foreach ($request->sections as $item) {
            Section::where('id', $item['id'])->update([
                'order_index' => $item['order_index']
            ]);
        }

        return response()->json(['message' => 'Sections reordered successfully']);
    }
}