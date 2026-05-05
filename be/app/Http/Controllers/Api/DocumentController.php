<?php

namespace App\Http\Controllers\Api;

use App\Models\Document;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DocumentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Document::orderBy('order')->orderByDesc('id');

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('file_type') && $request->file_type) {
            $query->where('file_type', $request->file_type);
        }

        if ($request->boolean('active_only', false)) {
            $query->where('is_active', true);
        }

        $documents = $query->get();

        return response()->json([
            'success' => true,
            'data' => $documents,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $document = Document::find($id);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Khong tim thay tai lieu',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $document,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'file_type' => 'nullable|string|max:50',
            'file_url' => 'nullable|string|max:500',
            'content' => 'nullable|string',
            'author' => 'nullable|string|max:100',
            'download_count' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'order' => 'nullable|integer|min:0',
        ]);

        $document = Document::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tao tai lieu thanh cong',
            'data' => $document,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $document = Document::find($id);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Khong tim thay tai lieu',
            ], 404);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'file_type' => 'nullable|string|max:50',
            'file_url' => 'nullable|string|max:500',
            'content' => 'nullable|string',
            'author' => 'nullable|string|max:100',
            'download_count' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'order' => 'nullable|integer|min:0',
        ]);

        $document->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cap nhat tai lieu thanh cong',
            'data' => $document->fresh(),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $document = Document::find($id);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Khong tim thay tai lieu',
            ], 404);
        }

        $document->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xoa tai lieu thanh cong',
        ]);
    }
}
