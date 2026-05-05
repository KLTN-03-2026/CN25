<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CourseQuiz;
use App\Models\CourseQuizQuestion;
use Illuminate\Http\Request;

class CourseQuizQuestionController extends Controller
{
    public function store(Request $request, $quizId)
    {
        $quiz = CourseQuiz::find($quizId);
        if (!$quiz) {
            return response()->json(['message' => 'Quiz không tìm thấy'], 404);
        }

        $validated = $request->validate([
            'type' => 'required|in:multiple_choice,fill_blank,listening,speaking',
            'question' => 'required|string',
            'options' => 'nullable',
            'correct_answer' => 'nullable|string',
            'audio_url' => 'nullable|string',
            'sample_answer' => 'nullable|string',
            'audio' => 'nullable|file|mimes:mp3,wav,ogg,m4a,aac,flac,webm|max:51200',
            'order' => 'nullable|integer|min:0',
        ]);

        $data = [
            'quiz_id' => $quizId,
            'type' => $validated['type'],
            'question' => $validated['question'],
            'order' => $validated['order'] ?? 1,
        ];

        if (isset($validated['options'])) {
            $data['options'] = is_string($validated['options'])
                ? json_decode($validated['options'], true)
                : $validated['options'];
        }

        if (isset($validated['correct_answer'])) {
            $data['correct_answer'] = $validated['correct_answer'];
        }

        if (isset($validated['sample_answer'])) {
            $data['sample_answer'] = $validated['sample_answer'];
        }

        if ($request->hasFile('audio')) {
            $audioPath = $request->file('audio')->store('audio/quiz', 'public');
            $data['audio_url'] = '/storage/' . $audioPath;
        } elseif (isset($validated['audio_url']) && !empty($validated['audio_url'])) {
            $data['audio_url'] = $validated['audio_url'];
        }

        $question = CourseQuizQuestion::create($data);

        return response()->json([
            'message' => 'Tạo câu hỏi thành công',
            'data' => $question
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $question = CourseQuizQuestion::findOrFail($id);

        $validated = $request->validate([
            'type' => 'sometimes|required|in:multiple_choice,fill_blank,listening,speaking',
            'question' => 'sometimes|required|string',
            'options' => 'nullable',
            'correct_answer' => 'nullable|string',
            'audio_url' => 'nullable|string',
            'sample_answer' => 'nullable|string',
            'audio' => 'nullable|file|mimes:mp3,wav,ogg,m4a,aac,flac,webm|max:51200',
            'order' => 'nullable|integer|min:0',
        ]);

        $data = [];

        if (isset($validated['type'])) {
            $data['type'] = $validated['type'];
        }
        if (isset($validated['question'])) {
            $data['question'] = $validated['question'];
        }
        if (isset($validated['order'])) {
            $data['order'] = $validated['order'];
        }
        if (isset($validated['options'])) {
            $data['options'] = is_string($validated['options'])
                ? json_decode($validated['options'], true)
                : $validated['options'];
        }
        if (array_key_exists('correct_answer', $validated)) {
            $data['correct_answer'] = $validated['correct_answer'];
        }
        if (array_key_exists('sample_answer', $validated)) {
            $data['sample_answer'] = $validated['sample_answer'];
        }

        if ($request->hasFile('audio')) {
            $audioPath = $request->file('audio')->store('audio/quiz', 'public');
            $data['audio_url'] = '/storage/' . $audioPath;
        } elseif (array_key_exists('audio_url', $validated)) {
            $data['audio_url'] = $validated['audio_url'];
        }

        $question->update($data);

        return response()->json([
            'message' => 'Cập nhật câu hỏi thành công',
            'data' => $question->fresh()
        ]);
    }

    public function destroy($id)
    {
        $question = CourseQuizQuestion::findOrFail($id);
        $question->delete();

        return response()->json(['message' => 'Xóa câu hỏi thành công']);
    }
}
