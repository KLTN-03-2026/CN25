<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Content;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    /**
     * Lay danh sach noi dung hoc (bai giang, video, tai lieu, audio)
     */
    public function index(Request $request)
    {
        $query = Content::query();

        // Filter theo loai
        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        // Filter theo khoa hoc
        if ($request->has('course_id') && $request->course_id) {
            $query->where('course_id', $request->course_id);
        }

        // Filter theo trang thai
        if ($request->has('status')) {
            $query->where('is_active', $request->boolean('status'));
        }

        // Tim kiem
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where('title', 'like', "%{$search}%");
        }

        $contents = $query->orderBy('id', 'desc')->paginate($request->get('per_page', 15));

        return response()->json($contents);
    }

    /**
     * Lay chi tiet noi dung
     */
    public function show($id)
    {
        $content = Content::with('course')->findOrFail($id);
        return response()->json($content);
    }

    /**
     * Tao noi dung moi
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:bai-giang,video,tai-lieu,audio'],
            'course_id' => ['required', 'exists:courses,id'],
            'content_url' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'duration' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $validated['is_active'] ?? true;

        $content = Content::create($validated);

        return response()->json([
            'message' => 'Tao noi dung thanh cong',
            'content' => $content
        ], 201);
    }

    /**
     * Cap nhat noi dung
     */
    public function update(Request $request, $id)
    {
        $content = Content::findOrFail($id);

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'in:bai-giang,video,tai-lieu,audio'],
            'course_id' => ['sometimes', 'exists:courses,id'],
            'content_url' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'duration' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ]);

        $content->update($validated);

        return response()->json([
            'message' => 'Cap nhat noi dung thanh cong',
            'content' => $content
        ]);
    }

    /**
     * Xoa noi dung
     */
    public function destroy($id)
    {
        $content = Content::findOrFail($id);
        $content->delete();

        return response()->json(['message' => 'Xoa noi dung thanh cong']);
    }
}
