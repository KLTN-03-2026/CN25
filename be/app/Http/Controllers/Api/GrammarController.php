<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Grammar;
use App\Models\Lesson;
use Illuminate\Http\Request;

class GrammarController extends Controller
{
    /**
     * Lấy danh sách ngữ pháp theo lesson
     */
    public function getByLesson($lessonId)
    {
        $lesson = Lesson::find($lessonId);
        if (!$lesson) {
            return response()->json(['message' => 'Lesson không tìm thấy'], 404);
        }

        $grammars = Grammar::where('lesson_id', $lessonId)->get();

        return response()->json($grammars);
    }

    /**
     * Lấy chi tiết một ngữ pháp
     */
    public function show($id)
    {
        $grammar = Grammar::with('lesson')->find($id);
        if (!$grammar) {
            return response()->json(['message' => 'Grammar không tìm thấy'], 404);
        }

        return response()->json($grammar);
    }

    /**
     * Tạo mới ngữ pháp
     */
    public function store(Request $request, $lessonId)
    {
        $lesson = Lesson::find($lessonId);
        if (!$lesson) {
            return response()->json(['message' => 'Lesson không tìm thấy'], 404);
        }

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'explanation' => 'required|string',
            'structure'   => 'nullable|string',
            'example'     => 'nullable|string',
            'youtube_url' => 'nullable|string',
            'signals' => 'nullable|string',
        ]);

        $grammar = $lesson->grammars()->create($validated);

        return response()->json([
            'message' => 'Tạo ngữ pháp thành công',
            'data'    => $grammar
        ], 201);
    }

    /**
     * Cập nhật ngữ pháp
     */
    public function update(Request $request, $id)
    {
        $grammar = Grammar::find($id);
        if (!$grammar) {
            return response()->json(['message' => 'Grammar không tìm thấy'], 404);
        }

        $validated = $request->validate([
            'title'       => 'sometimes|required|string|max:255',
            'explanation' => 'sometimes|required|string',
            'structure'   => 'nullable|string',
            'example'     => 'nullable|string',
            'youtube_url' => 'nullable|string',
            'signals' => 'nullable|string',
        ]);

        $grammar->update($validated);

        return response()->json([
            'message' => 'Cập nhật ngữ pháp thành công',
            'data'    => $grammar
        ]);
    }

    /**
     * Xóa ngữ pháp
     */
    public function destroy($id)
    {
        $grammar = Grammar::find($id);
        if (!$grammar) {
            return response()->json(['message' => 'Grammar không tìm thấy'], 404);
        }

        $grammar->delete();

        return response()->json(['message' => 'Xóa ngữ pháp thành công']);
    }
}
