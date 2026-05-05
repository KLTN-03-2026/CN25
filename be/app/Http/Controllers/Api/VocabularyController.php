<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Vocabulary;
use Illuminate\Http\Request;

class VocabularyController extends Controller
{
    public function getByLesson($lessonId)
    {
        $lesson = Lesson::find($lessonId);
        if (!$lesson) {
            return response()->json(['message' => 'Lesson không tìm thấy'], 404);
        }

        $vocabularies = Vocabulary::where('lesson_id', $lessonId)->get();

        return response()->json($vocabularies);
    }

    public function store(Request $request, $lessonId)
    {
        $lesson = Lesson::find($lessonId);
        if (!$lesson) {
            return response()->json(['message' => 'Lesson không tìm thấy'], 404);
        }

        $validated = $request->validate([
            'word'          => 'required|string|max:255',
            'meaning'       => 'required|string|max:255',
            'pronunciation' => 'nullable|string|max:255',
        ]);

        $vocabulary = $lesson->vocabularies()->create($validated);

        return response()->json([
            'message' => 'Tạo từ vựng thành công',
            'data'    => $vocabulary
        ], 201);
    }

    public function show($id)
    {
        $vocabulary = Vocabulary::with('lesson')->find($id);
        if (!$vocabulary) {
            return response()->json(['message' => 'Vocabulary không tìm thấy'], 404);
        }

        return response()->json($vocabulary);
    }

    public function update(Request $request, $id)
    {
        $vocabulary = Vocabulary::find($id);
        if (!$vocabulary) {
            return response()->json(['message' => 'Vocabulary không tìm thấy'], 404);
        }

        $validated = $request->validate([
            'word'          => 'sometimes|required|string|max:255',
            'meaning'       => 'sometimes|required|string|max:255',
            'pronunciation' => 'nullable|string|max:255',
        ]);

        $vocabulary->update($validated);

        return response()->json([
            'message' => 'Cập nhật từ vựng thành công',
            'data'    => $vocabulary
        ]);
    }

    public function destroy($id)
    {
        $vocabulary = Vocabulary::find($id);
        if (!$vocabulary) {
            return response()->json(['message' => 'Vocabulary không tìm thấy'], 404);
        }

        $vocabulary->delete();

        return response()->json(['message' => 'Xóa từ vựng thành công']);
    }
}
