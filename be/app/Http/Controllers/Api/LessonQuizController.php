<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonQuiz;
use Illuminate\Http\Request;

class LessonQuizController extends Controller
{
    public function getByCourse($courseId)
    {
        $course = Course::find($courseId);
        if (!$course) {
            return response()->json(['message' => 'Course không tìm thấy'], 404);
        }

        $quizzes = LessonQuiz::where('course_id', $courseId)
            ->with('course')
            ->orderBy('order')
            ->orderBy('id')
            ->get();

        return response()->json($quizzes);
    }

    public function getByLesson($lessonId)
    {
        $lesson = Lesson::with('chapter')->find($lessonId);
        if (!$lesson) {
            return response()->json(['message' => 'Lesson không tìm thấy'], 404);
        }

        if (!$lesson->chapter) {
            return response()->json(['message' => 'Lesson chưa có chapter'], 400);
        }

        $quizzes = LessonQuiz::where('course_id', $lesson->chapter->course_id)
            ->orderBy('order')
            ->orderBy('id')
            ->get();

        return response()->json($quizzes);
    }

    public function show($id)
    {
        $quiz = LessonQuiz::with('course')->find($id);
        if (!$quiz) {
            return response()->json(['message' => 'Quiz không tìm thấy'], 404);
        }

        return response()->json($quiz);
    }

    public function store(Request $request, $courseId)
    {
        $course = Course::find($courseId);
        if (!$course) {
            return response()->json(['message' => 'Course không tìm thấy'], 404);
        }

        $validated = $request->validate([
            'type' => 'required|in:multiple_choice,fill_blank,listening,speaking',
            'question' => 'required_if:type,multiple_choice,fill_blank,listening|nullable|string',
            'options' => 'nullable',
            'correct_answer' => 'nullable|string',
            'extra_data' => 'nullable',
            'audio' => 'nullable|file|mimes:mp3,wav,ogg,m4a,aac,flac|max:20480',
            'order' => 'nullable|integer|min:0',
        ]);

        $data = [
            'course_id' => $courseId,
            'type' => $validated['type'],
            'question' => $validated['question'] ?? null,
            'order' => $validated['order'] ?? 0,
        ];

        if (isset($validated['options'])) {
            $data['options'] = is_string($validated['options']) ? json_decode($validated['options'], true) : $validated['options'];
        }

        if (isset($validated['correct_answer'])) {
            $data['correct_answer'] = $validated['correct_answer'];
        }

        $extraData = [];
        if (isset($validated['extra_data'])) {
            $extraData = is_string($validated['extra_data']) ? json_decode($validated['extra_data'], true) : $validated['extra_data'];
        }

        if ($request->hasFile('audio')) {
            $audioPath = $request->file('audio')->store('listening', 'public');
            $extraData['audio'] = '/storage/' . $audioPath;
        }

        if (!empty($extraData)) {
            $data['extra_data'] = $extraData;
        }

        $quiz = LessonQuiz::create($data);

        return response()->json([
            'message' => 'Tạo quiz thành công',
            'data' => $quiz
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $quiz = LessonQuiz::find($id);
        if (!$quiz) {
            return response()->json(['message' => 'Quiz không tìm thấy'], 404);
        }

        $validated = $request->validate([
            'type' => 'sometimes|required|in:multiple_choice,fill_blank,listening,speaking',
            'course_id' => 'sometimes|required|exists:courses,id',
            'question' => 'required_if:type,multiple_choice,fill_blank,listening|nullable|string',
            'options' => 'nullable',
            'correct_answer' => 'nullable|string',
            'extra_data' => 'nullable',
            'audio' => 'nullable|file|mimes:mp3,wav,ogg,m4a,aac,flac|max:20480',
            'order' => 'nullable|integer|min:0',
        ]);

        $data = [];

        if (isset($validated['course_id'])) {
            $data['course_id'] = $validated['course_id'];
        }
        if (isset($validated['type'])) {
            $data['type'] = $validated['type'];
        }
        if (array_key_exists('question', $validated)) {
            $data['question'] = $validated['question'];
        }
        if (isset($validated['order'])) {
            $data['order'] = $validated['order'];
        }
        if (isset($validated['options'])) {
            $data['options'] = is_string($validated['options']) ? json_decode($validated['options'], true) : $validated['options'];
        }
        if (isset($validated['correct_answer'])) {
            $data['correct_answer'] = $validated['correct_answer'];
        }

        if (isset($validated['extra_data'])) {
            $extraData = is_string($validated['extra_data']) ? json_decode($validated['extra_data'], true) : $validated['extra_data'];
            $currentExtra = is_string($quiz->extra_data) ? json_decode($quiz->extra_data, true) : ($quiz->extra_data ?? []);
            $extraData = array_merge($currentExtra ?? [], $extraData);
        } else {
            $extraData = is_string($quiz->extra_data) ? json_decode($quiz->extra_data, true) : ($quiz->extra_data ?? []);
        }

        if ($request->hasFile('audio')) {
            $audioPath = $request->file('audio')->store('listening', 'public');
            $extraData['audio'] = '/storage/' . $audioPath;
        }

        if (!empty($extraData)) {
            $data['extra_data'] = $extraData;
        }

        $quiz->update($data);

        return response()->json([
            'message' => 'Cập nhật quiz thành công',
            'data' => $quiz->fresh()
        ]);
    }

    public function destroy($id)
    {
        $quiz = LessonQuiz::find($id);
        if (!$quiz) {
            return response()->json(['message' => 'Quiz không tìm thấy'], 404);
        }

        $quiz->delete();

        return response()->json(['message' => 'Xóa quiz thành công']);
    }
}
