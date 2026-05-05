<?php

namespace App\Http\Controllers\Api;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\Lesson;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    public function index($chapterId)
    {
        $chapter = Chapter::find($chapterId);
        if (!$chapter) {
            return response()->json(['message' => 'Chapter không tìm thấy'], 404);
        }

        $lessons = $chapter->lessons()->orderBy('order')->get();

        return response()->json($lessons);
    }

   

public function store(Request $request, $chapterId)
{
    $chapter = Chapter::find($chapterId);
    if (!$chapter) {
        return response()->json(['message' => 'Chapter không tìm thấy'], 404);
    }

    $validated = $request->validate([
        'title'       => 'required|string|max:255',
        'description' => 'nullable|string',
        'slug'        => 'nullable|string|max:255|unique:lessons,slug',
        'order'       => 'nullable|integer|min:1',
        'status'      => 'nullable|string|in:draft,published',
    ]);

    // ✅ TỰ TẠO SLUG NẾU KHÔNG CÓ
    if (empty($validated['slug'])) {
        $slug = Str::slug($validated['title']);

        // đảm bảo unique
        $count = \App\Models\Lesson::where('slug', 'LIKE', "{$slug}%")->count();
        $validated['slug'] = $count ? "{$slug}-{$count}" : $slug;
    }

    // ✅ AUTO ORDER
    if (!isset($validated['order'])) {
        $validated['order'] = ($chapter->lessons()->max('order') ?? 0) + 1;
    }

    // ✅ DEFAULT STATUS
    if (!isset($validated['status'])) {
        $validated['status'] = 'draft';
    }

    $lesson = $chapter->lessons()->create($validated);

    return response()->json([
        'message' => 'Tạo bài học thành công',
        'data'    => $lesson
    ], 201);
}
    public function show($id)
    {
        $lesson = Lesson::with([
            'chapter',
            'vocabularies' => function ($q) { $q->orderBy('order'); },
            'grammars',
            'listenings',
            'speakingExercises' => function ($q) { $q->orderBy('order'); },
        ])->find($id);

        if (!$lesson) {
            return response()->json(['message' => 'Bài học không tìm thấy'], 404);
        }

        return response()->json($lesson);
    }

    public function update(Request $request, $id)
    {
        $lesson = Lesson::find($id);
        if (!$lesson) {
            return response()->json(['message' => 'Bài học không tìm thấy'], 404);
        }

        $validated = $request->validate([
            'title'       => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'slug'        => 'sometimes|required|string|max:255|unique:lessons,slug,' . $id,
            'order'       => 'sometimes|integer|min:1',
            'status'      => 'sometimes|string|in:draft,published',
        ], [
            'title.required' => 'Vui lòng nhập tiêu đề bài học',
            'title.max'     => 'Tiêu đề không được vượt quá 255 ký tự',
            'slug.unique'   => 'Slug đã tồn tại, vui lòng chọn slug khác',
            'order.min'    => 'Thứ tự phải lớn hơn 0',
            'status.in'   => 'Trạng thái phải là: draft hoặc published',
        ]);

        $lesson->update($validated);

        return response()->json([
            'message' => 'Cập nhật bài học thành công',
            'data'    => $lesson
        ]);
    }

    public function destroy($id)
    {
        $lesson = Lesson::find($id);
        if (!$lesson) {
            return response()->json(['message' => 'Bài học không tìm thấy'], 404);
        }

        $lesson->delete();

        return response()->json(['message' => 'Xóa bài học thành công']);
    }

    public function changeStatus(Request $request, $id)
    {
        $lesson = Lesson::find($id);
        if (!$lesson) {
            return response()->json(['message' => 'Bài học không tìm thấy'], 404);
        }

        $validated = $request->validate([
            'status' => 'required|string|in:draft,published',
        ]);

        $lesson->update(['status' => $validated['status']]);

        return response()->json([
            'message' => 'Cập nhật trạng thái thành công',
            'data'    => $lesson
        ]);
    }
}