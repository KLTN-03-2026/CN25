<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CourseQuiz;
use Illuminate\Http\Request;

class CourseQuizController extends Controller
{
    public function store(Request $request, $courseId)
    {
        $quiz = CourseQuiz::create([
            'course_id' => $courseId,
            'title' => $request->title,
            'duration' => $request->duration,
            'pass_score' => $request->pass_score ?? 50,
        ]);

        return response()->json($quiz, 201);
    }

    public function show($courseId)
    {
        $quiz = CourseQuiz::where('course_id', $courseId)
            ->with('questions')
            ->first();

        if (!$quiz) {
            return response()->json(['message' => 'Quiz not found'], 404);
        }

        return response()->json($quiz);
    }

    public function update(Request $request, $id)
    {
        $quiz = CourseQuiz::findOrFail($id);
        $quiz->update($request->only(['title', 'duration', 'pass_score']));
        return response()->json($quiz);
    }

    public function destroy($id)
    {
        CourseQuiz::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
