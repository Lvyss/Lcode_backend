<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Badge;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class AdminBadgeController extends Controller
{
    public function index(): JsonResponse
    {
        $badges = Badge::with('section')->orderBy('order_index')->get();
        return response()->json($badges);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // ✅ FILE UPLOAD
            'color' => 'required|in:yellow,blue,green,purple,red,indigo',
            'description' => 'required|string',
            'section_id' => 'required|exists:sections,id',
            'required_parts' => 'required|integer|min:1',
            'order_index' => 'sometimes|integer',
            'is_active' => 'sometimes|boolean',
        ]);

        // ✅ HANDLE FILE UPLOAD
        if ($request->hasFile('icon')) {
            $iconPath = $request->file('icon')->store('badges', 'public');
        } else {
            return response()->json(['error' => 'Icon file is required'], 422);
        }

        $data = $request->all();
        $data['icon_path'] = $iconPath; // ✅ SIMPAN PATH FILE
        
        // Set defaults
        if (!isset($data['order_index'])) {
            $data['order_index'] = Badge::max('order_index') + 1;
        }
        if (!isset($data['is_active'])) {
            $data['is_active'] = true;
        }

        $badge = Badge::create($data);

        return response()->json($badge, 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $badge = Badge::findOrFail($id);
        
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'icon' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048', // ✅ OPTIONAL UPDATE
            'color' => 'sometimes|in:yellow,blue,green,purple,red,indigo',
            'description' => 'sometimes|string',
            'section_id' => 'sometimes|exists:sections,id',
            'required_parts' => 'sometimes|integer|min:1',
            'order_index' => 'sometimes|integer',
            'is_active' => 'sometimes|boolean',
        ]);

        $data = $request->all();

        // ✅ HANDLE FILE UPDATE JIKA ADA
        if ($request->hasFile('icon')) {
            // Delete old icon
            if ($badge->icon_path && Storage::disk('public')->exists($badge->icon_path)) {
                Storage::disk('public')->delete($badge->icon_path);
            }
            
            $iconPath = $request->file('icon')->store('badges', 'public');
            $data['icon_path'] = $iconPath;
        }

        $badge->update($data);

        return response()->json($badge);
    }

    public function destroy($id): JsonResponse
    {
        $badge = Badge::findOrFail($id);
        
        // ✅ DELETE FILE JIKA ADA
        if ($badge->icon_path && Storage::disk('public')->exists($badge->icon_path)) {
            Storage::disk('public')->delete($badge->icon_path);
        }
        
        $badge->delete();

        return response()->json(['message' => 'Badge deleted successfully']);
    }

    // ✅ GET SECTIONS FOR DROPDOWN
    public function getSections(): JsonResponse
    {
        $sections = Section::with('language')->get()->map(function($section) {
            return [
                'id' => $section->id,
                'name' => $section->name,
                'language_name' => $section->language->name,
                'total_parts' => $section->parts_count ?? 0
            ];
        });

        return response()->json($sections);
    }

    // ✅ BONUS: REORDER BADGES
    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'badges' => 'required|array',
            'badges.*.id' => 'required|exists:badges,id',
            'badges.*.order_index' => 'required|integer'
        ]);

        foreach ($request->badges as $item) {
            Badge::where('id', $item['id'])->update([
                'order_index' => $item['order_index']
            ]);
        }

        return response()->json(['message' => 'Badges reordered successfully']);
    }
}