<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExerciseScore;
use Illuminate\Http\Request;

class ResultController extends Controller
{
    /**
     * Lay danh sach ket qua hoc tap
     */
    public function index(Request $request)
    {
        $query = ExerciseScore::with(['user:id,name,email', 'course:id,title']);

        // Filter theo loai
        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        // Filter theo khoa hoc
        if ($request->has('course_id') && $request->course_id) {
            $query->where('course_id', $request->course_id);
        }

        // Filter theo nguoi dung
        if ($request->has('user_id') && $request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        // Loc ket qua dat / khong dat
        if ($request->has('passed')) {
            $query->where('passed', $request->boolean('passed'));
        }

        // Tim kiem
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%");
                });
            });
        }

        $results = $query->orderBy('id', 'desc')->paginate($request->get('per_page', 15));

        return response()->json($results);
    }

    /**
     * Lay chi tiet ket qua
     */
    public function show($id)
    {
        $result = ExerciseScore::with(['user', 'course'])->findOrFail($id);
        return response()->json($result);
    }

    /**
     * Tao / luu ket qua bai kiem tra
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'course_id' => ['nullable', 'exists:courses,id'],
            'lesson_id' => ['nullable', 'exists:lessons,id'],
            'type' => ['required', 'in:kiem-tra,bai-tap,thi'],
            'score' => ['required', 'numeric', 'min:0'],
            'max_score' => ['required', 'numeric', 'min:1'],
            'time_spent' => ['nullable', 'integer'], // phut
            'answers' => ['nullable', 'array'],
            'passed' => ['boolean'],
        ]);

        // Tinh ti le phan tram
        $validated['percentage'] = $validated['max_score'] > 0
            ? round(($validated['score'] / $validated['max_score']) * 100, 2)
            : 0;

        $result = ExerciseScore::create($validated);

        return response()->json([
            'message' => 'Luu ket qua thanh cong',
            'result' => $result
        ], 201);
    }

    /**
     * Cap nhat ket qua
     */
    public function update(Request $request, $id)
    {
        $result = ExerciseScore::findOrFail($id);

        $validated = $request->validate([
            'score' => ['sometimes', 'numeric', 'min:0'],
            'max_score' => ['sometimes', 'numeric', 'min:1'],
            'time_spent' => ['nullable', 'integer'],
            'answers' => ['nullable', 'array'],
            'passed' => ['boolean'],
        ]);

        if (isset($validated['score']) && isset($validated['max_score'])) {
            $validated['percentage'] = $validated['max_score'] > 0
                ? round(($validated['score'] / $validated['max_score']) * 100, 2)
                : 0;
        }

        $result->update($validated);

        return response()->json([
            'message' => 'Cap nhat thanh cong',
            'result' => $result
        ]);
    }

    /**
     * Xoa ket qua
     */
    public function destroy($id)
    {
        $result = ExerciseScore::findOrFail($id);
        $result->delete();

        return response()->json(['message' => 'Xoa ket qua thanh cong']);
    }

    /**
     * Thong ke ket qua
     */
    public function stats(Request $request)
    {
        $query = ExerciseScore::query();

        if ($request->has('course_id') && $request->course_id) {
            $query->where('course_id', $request->course_id);
        }

        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        $total = (clone $query)->count();
        $passed = (clone $query)->where('passed', true)->count();
        $avgScore = (clone $query)->avg('percentage') ?? 0;
        $maxScore = (clone $query)->max('percentage') ?? 0;

        $stats = [
            'total' => $total,
            'passed_count' => $passed,
            'pass_rate' => $total > 0 ? round(($passed / $total) * 100, 2) : 0,
            'avg_score' => round($avgScore, 2),
            'max_score' => round($maxScore, 2),
        ];

        return response()->json($stats);
    }
}
