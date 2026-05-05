<?php

namespace App\Http\Controllers\Api;

use App\Models\Article;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ArticleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Article::orderByDesc('is_featured')->orderByDesc('id');

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('summary', 'like', "%{$search}%");
            });
        }

        if ($request->has('category') && $request->category) {
            $query->where('category', $request->category);
        }

        if ($request->boolean('featured_only', false)) {
            $query->where('is_featured', true);
        }

        if ($request->boolean('active_only', false)) {
            $query->where('is_active', true);
        }

        $articles = $query->get();

        return response()->json([
            'success' => true,
            'data' => $articles,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $article = Article::find($id);

        if (!$article) {
            return response()->json([
                'success' => false,
                'message' => 'Khong tim thay bai viet',
            ], 404);
        }

        $article->increment('view_count');

        return response()->json([
            'success' => true,
            'data' => $article,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:articles,slug',
            'summary' => 'nullable|string',
            'content' => 'nullable|string',
            'thumbnail' => 'nullable|string|max:500',
            'category' => 'nullable|string|max:100',
            'author' => 'nullable|string|max:100',
            'author_avatar' => 'nullable|string|max:500',
            'is_featured' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        $article = Article::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tao bai viet thanh cong',
            'data' => $article,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $article = Article::find($id);

        if (!$article) {
            return response()->json([
                'success' => false,
                'message' => 'Khong tim thay bai viet',
            ], 404);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|string|unique:articles,slug,' . $id,
            'summary' => 'nullable|string',
            'content' => 'nullable|string',
            'thumbnail' => 'nullable|string|max:500',
            'category' => 'nullable|string|max:100',
            'author' => 'nullable|string|max:100',
            'author_avatar' => 'nullable|string|max:500',
            'is_featured' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        if (isset($validated['slug']) && empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        $article->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cap nhat bai viet thanh cong',
            'data' => $article->fresh(),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $article = Article::find($id);

        if (!$article) {
            return response()->json([
                'success' => false,
                'message' => 'Khong tim thay bai viet',
            ], 404);
        }

        $article->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xoa bai viet thanh cong',
        ]);
    }

    public function latest(Request $request): JsonResponse
    {
        $limit = $request->integer('limit', 4);

        $articles = Article::where('is_active', true)
            ->orderByDesc('is_featured')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $articles,
        ]);
    }
}
