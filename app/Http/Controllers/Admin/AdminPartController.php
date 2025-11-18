<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Part;
use App\Models\ContentBlock;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AdminPartController extends Controller
{
    public function index($sectionId): JsonResponse
    {
        $parts = Part::where('section_id', $sectionId)
            ->with(['contentBlocks', 'exercises'])
            ->orderBy('order_index')
            ->get();
            
        return response()->json($parts);
    }

    public function getContentBlocks($partId): JsonResponse
    {
        $contentBlocks = ContentBlock::where('part_id', $partId)
            ->orderBy('order_index')
            ->get();
            
        return response()->json($contentBlocks);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'section_id' => 'required|exists:sections,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'order_index' => 'sometimes|integer',
            'exp_reward' => 'required|integer|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        $data = $request->all();
        
        // ✅ AUTO ORDER_INDEX
        if (!isset($data['order_index'])) {
            $maxOrder = Part::where('section_id', $data['section_id'])
                ->max('order_index') ?? 0;
            $data['order_index'] = $maxOrder + 1;
        }

        // ✅ DEFAULT is_active
        if (!isset($data['is_active'])) {
            $data['is_active'] = true;
        }

        $part = Part::create($data);

        return response()->json($part, 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $part = Part::findOrFail($id);
        
        $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'order_index' => 'sometimes|integer',
            'exp_reward' => 'sometimes|integer|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        $part->update($request->all());

        return response()->json($part);
    }

    public function destroy($id): JsonResponse
    {
        $part = Part::findOrFail($id);
        
        // ✅ CEK APAKAH ADA CONTENT BLOCKS ATAU EXERCISES
        if ($part->contentBlocks()->exists() || $part->exercises()->exists()) {
            return response()->json([
                'message' => 'Cannot delete part with existing content blocks or exercises'
            ], 422);
        }

        $part->delete();

        return response()->json(['message' => 'Part deleted successfully']);
    }

    // ✅ FIX: CONTENT BLOCKS MANAGEMENT - UPDATE VALIDATION
    public function storeContentBlock(Request $request, $partId): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:heading,paragraph,code_block,exercise,image,html_content', // ✅ TAMBAH html_content
            'content' => 'nullable|array',
            'text_content' => 'nullable|string',
            'language' => 'nullable|string|max:50',
            'order_index' => 'sometimes|integer',
            'metadata' => 'nullable|array',
        ]);

        $data = $request->all();
        $data['part_id'] = $partId;

        // ✅ AUTO ORDER_INDEX
        if (!isset($data['order_index'])) {
            $maxOrder = ContentBlock::where('part_id', $partId)
                ->max('order_index') ?? 0;
            $data['order_index'] = $maxOrder + 1;
        }

        $contentBlock = ContentBlock::create($data);

        return response()->json($contentBlock, 201);
    }

    public function updateContentBlock(Request $request, $partId, $blockId): JsonResponse
    {
        $contentBlock = ContentBlock::where('part_id', $partId)
            ->findOrFail($blockId);
        
        $request->validate([
            'type' => 'sometimes|in:heading,paragraph,code_block,exercise,image,html_content', // ✅ TAMBAH html_content
            'content' => 'nullable|array',
            'text_content' => 'nullable|string',
            'language' => 'nullable|string|max:50',
            'order_index' => 'sometimes|integer',
            'metadata' => 'nullable|array',
        ]);

        $contentBlock->update($request->all());

        return response()->json($contentBlock);
    }

    // ✅ BULK UPDATE CONTENT BLOCKS
    public function updateContentBlocksOrder(Request $request, $partId): JsonResponse
    {
        $request->validate([
            'blocks' => 'required|array',
            'blocks.*.id' => 'required|exists:content_blocks,id',
            'blocks.*.order_index' => 'required|integer'
        ]);

        DB::transaction(function () use ($request, $partId) {
            foreach ($request->blocks as $block) {
                ContentBlock::where('id', $block['id'])
                    ->where('part_id', $partId)
                    ->update(['order_index' => $block['order_index']]);
            }
        });

        return response()->json(['message' => 'Content blocks order updated successfully']);
    }

    public function destroyContentBlock($partId, $blockId): JsonResponse
    {
        $contentBlock = ContentBlock::where('part_id', $partId)
            ->findOrFail($blockId);
            
        $contentBlock->delete();

        return response()->json(['message' => 'Content block deleted successfully']);
    }
}