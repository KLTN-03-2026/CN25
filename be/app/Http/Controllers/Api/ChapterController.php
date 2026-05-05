<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\Course;
use Illuminate\Http\Request;

class ChapterController extends Controller
{
    /**
     * Lấy danh sách chapters theo course
     * GET /api/courses/{course_id}/chapters
     */
    public function indexByCourse($courseId)
    {
        $course = Course::find($courseId);
        if (!$course) {
            return response()->json(['message' => 'Khóa học không tìm thấy'], 404);
        }

        $chapters = $course->chapters()->withCount('lessons')->orderBy('order')->get();

        return response()->json($chapters);
    }

    /**
     * Lấy chi tiết một chapter
     * GET /api/chapters/{id}
     */
    public function show($id)
    {
        $chapter = Chapter::with(['course', 'lessons' => function ($q) {
            $q->orderBy('order');
        }])->find($id);

        if (!$chapter) {
            return response()->json(['message' => 'Chapter không tìm thấy'], 404);
        }

        return response()->json($chapter);
    }

    /**
     * Cập nhật chapter (title, status, is_free)
     * PUT /api/chapters/{id}
     */
    public function update(Request $request, $id)
    {
        $chapter = Chapter::find($id);
        if (!$chapter) {
            return response()->json(['message' => 'Chapter không tìm thấy'], 404);
        }

        $validated = $request->validate([
            'title'       => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'type'        => 'sometimes|in:vocabulary,grammar,listening,speaking',
            'status'      => 'sometimes|in:draft,published',
            'order'       => 'sometimes|integer|min:1',
        ]);

        $chapter->update($validated);

        return response()->json([
            'message' => 'Cập nhật chapter thành công',
            'data'    => $chapter
        ]);
    }

    /**
     * Lấy lessons theo chapter
     * GET /api/chapters/{id}/lessons
     */
    public function lessons($id)
    {
        $chapter = Chapter::find($id);
        if (!$chapter) {
            return response()->json(['message' => 'Chapter không tìm thấy'], 404);
        }

        $lessons = $chapter->lessons()->orderBy('order')->get();

        return response()->json($lessons);
    }
}
