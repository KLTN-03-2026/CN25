<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exercise;
use Illuminate\Http\Request;

class ExerciseController extends Controller
{
    /**
     * Lay danh sach bai tap
     */
    public function index(Request $request)
    {
        $query = Exercise::query();

        // Filter theo loai
        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        // Filter theo bai hoc
        if ($request->has('lesson_id') && $request->lesson_id) {
            $query->where('lesson_id', $request->lesson_id);
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

        $exercises = $query->orderBy('id', 'desc')->paginate($request->get('per_page', 15));

        return response()->json($exercises);
    }

    /**
     * Lay chi tiet bai tap
     */
    public function show($id)
    {
        $exercise = Exercise::with('lesson')->findOrFail($id);
        return response()->json($exercise);
    }

    /**
     * Tao bai tap moi
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:trac-nghiem,dien-tu,noi,viet'],
            'lesson_id' => ['nullable', 'exists:lessons,id'],
            'course_id' => ['nullable', 'exists:courses,id'],
            'content' => ['nullable', 'string'],
            'options' => ['nullable', 'array'],
            'correct_answer' => ['nullable', 'string'],
            'points' => ['nullable', 'integer', 'min:1'],
            'order' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $validated['is_active'] ?? true;

        $exercise = Exercise::create($validated);

        return response()->json([
            'message' => 'Tao bai tap thanh cong',
            'exercise' => $exercise
        ], 201);
    }

    /**
     * Cap nhat bai tap
     */
    public function update(Request $request, $id)
    {
        $exercise = Exercise::findOrFail($id);

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'in:trac-nghiem,dien-tu,noi,viet'],
            'lesson_id' => ['nullable', 'exists:lessons,id'],
            'course_id' => ['nullable', 'exists:courses,id'],
            'content' => ['nullable', 'string'],
            'options' => ['nullable', 'array'],
            'correct_answer' => ['nullable', 'string'],
            'points' => ['nullable', 'integer', 'min:1'],
            'order' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['boolean'],
        ]);

        $exercise->update($validated);

        return response()->json([
            'message' => 'Cap nhat bai tap thanh cong',
            'exercise' => $exercise
        ]);
    }

    /**
     * Xoa bai tap
     */
    public function destroy($id)
    {
        $exercise = Exercise::findOrFail($id);
        $exercise->delete();

        return response()->json(['message' => 'Xoa bai tap thanh cong']);
    }
}
