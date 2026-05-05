<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\SpeakingExercise;
use App\Services\SpeakingAssessmentService;
use Illuminate\Http\Request;

class SpeakingExerciseController extends Controller
{
    public function getByLesson($lessonId)
    {
        $lesson = Lesson::find($lessonId);
        if (!$lesson) {
            return response()->json(['message' => 'Lesson không tìm thấy'], 404);
        }

        $speakingExercises = SpeakingExercise::where('lesson_id', $lessonId)
            ->orderBy('order')
            ->orderBy('id')
            ->get();

        return response()->json($speakingExercises);
    }

    /**
     * Đánh giá bài nói bằng AI (Whisper + GPT)
     */
    public function evaluate(Request $request, $id)
    {
        $speaking = SpeakingExercise::find($id);
        if (!$speaking) {
            return response()->json(['message' => 'Speaking exercise không tìm thấy'], 404);
        }

        $validated = $request->validate([
            'audio' => 'required|file|mimes:mp3,wav,m4a,ogg,webm|max:10240',
        ]);

        $audioFile = $request->file('audio');
        $expectedAnswer = $speaking->sample_answer ?? $speaking->content ?? '';
        $keywords = $speaking->keywords;
        $type = $speaking->type;

        if (empty($expectedAnswer)) {
            return response()->json([
                'success' => false,
                'error' => 'Bài tập này chưa có câu trả lời mẫu để so sánh.',
            ], 400);
        }

        $service = new SpeakingAssessmentService();
        $result = $service->assess($audioFile, $expectedAnswer, $type, $keywords);

        return response()->json($result);
    }

    public function show($id)
    {
        $speaking = SpeakingExercise::with('lesson')->find($id);
        if (!$speaking) {
            return response()->json(['message' => 'Speaking exercise không tìm thấy'], 404);
        }

        return response()->json($speaking);
    }

    public function store(Request $request, $lessonId)
    {
        $lesson = Lesson::find($lessonId);
        if (!$lesson) {
            return response()->json(['message' => 'Lesson không tìm thấy'], 404);
        }

        $validated = $request->validate([
            'type'           => 'required|in:repeat,read,describe,qa',
            'content'        => 'required_if:type,repeat,read,qa|string',
            'audio_url'      => 'nullable|file|max:10240|mimes:mp3,wav,m4a,ogg',
            'image_url'      => 'nullable|image|max:5120|mimes:jpeg,jpg,png,webp,gif',
            'keywords'       => 'nullable|string',
            'sample_answer'  => 'nullable|string',
            'order'          => 'nullable|integer|min:0',
        ]);

        $imagePath = null;
        $audioPath = null;

        if ($request->hasFile('image_url')) {
            $image = $request->file('image_url');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('uploads/speaking'), $imageName);
            $imagePath = '/uploads/speaking/' . $imageName;
        }

        if ($request->hasFile('audio_url')) {
            $audio = $request->file('audio_url');
            $audioName = time() . '_' . uniqid() . '.' . $audio->getClientOriginalExtension();
            $audio->move(public_path('uploads/speaking'), $audioName);
            $audioPath = '/uploads/speaking/' . $audioName;
        }

        $data = [
            'type' => $validated['type'],
            'content' => $validated['content'] ?? null,
            'keywords' => $validated['keywords'] ?? null,
            'sample_answer' => $validated['sample_answer'] ?? null,
            'order' => $validated['order'] ?? 0,
        ];

        if ($imagePath) {
            $data['image_url'] = $imagePath;
        }
        if ($audioPath) {
            $data['audio_url'] = $audioPath;
        }

        $speaking = $lesson->speakingExercises()->create($data);

        return response()->json([
            'message' => 'Tạo speaking exercise thành công',
            'data'    => $speaking
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $speaking = SpeakingExercise::find($id);
        if (!$speaking) {
            return response()->json(['message' => 'Speaking exercise không tìm thấy'], 404);
        }

        $validated = $request->validate([
            'type'           => 'sometimes|required|in:repeat,read,describe,qa',
            'content'        => 'required_if:type,repeat,read,qa|string',
            'audio_url'      => 'nullable|file|max:10240|mimes:mp3,wav,m4a,ogg',
            'image_url'      => 'nullable|image|max:5120|mimes:jpeg,jpg,png,webp,gif',
            'keywords'       => 'nullable|string',
            'sample_answer'  => 'nullable|string',
            'order'          => 'nullable|integer|min:0',
        ]);

        $data = [];

        if (isset($validated['type'])) {
            $data['type'] = $validated['type'];
        }
        if (isset($validated['content'])) {
            $data['content'] = $validated['content'];
        }
        if (isset($validated['keywords'])) {
            $data['keywords'] = $validated['keywords'];
        }
        if (isset($validated['sample_answer'])) {
            $data['sample_answer'] = $validated['sample_answer'];
        }
        if (isset($validated['order'])) {
            $data['order'] = $validated['order'];
        }

        if ($request->hasFile('image_url')) {
            if ($speaking->image_url && file_exists(public_path($speaking->image_url))) {
                unlink(public_path($speaking->image_url));
            }
            $image = $request->file('image_url');
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('uploads/speaking'), $imageName);
            $data['image_url'] = '/uploads/speaking/' . $imageName;
        }

        if ($request->hasFile('audio_url')) {
            if ($speaking->audio_url && file_exists(public_path($speaking->audio_url))) {
                unlink(public_path($speaking->audio_url));
            }
            $audio = $request->file('audio_url');
            $audioName = time() . '_' . uniqid() . '.' . $audio->getClientOriginalExtension();
            $audio->move(public_path('uploads/speaking'), $audioName);
            $data['audio_url'] = '/uploads/speaking/' . $audioName;
        }

        $speaking->update($data);

        return response()->json([
            'message' => 'Cập nhật speaking exercise thành công',
            'data'    => $speaking->fresh()
        ]);
    }

    public function destroy($id)
    {
        $speaking = SpeakingExercise::find($id);
        if (!$speaking) {
            return response()->json(['message' => 'Speaking exercise không tìm thấy'], 404);
        }

        $speaking->delete();

        return response()->json(['message' => 'Xóa speaking exercise thành công']);
    }
}
