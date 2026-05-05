<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Listening;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ListeningController extends Controller
{
    /**
     * Lấy danh sách listening theo lesson
     */
    public function getByLesson($lessonId)
    {
        $lesson = Lesson::find($lessonId);
        if (!$lesson) {
            return response()->json(['message' => 'Lesson không tìm thấy'], 404);
        }

        $listenings = Listening::where('lesson_id', $lessonId)->get();

        return response()->json($listenings);
    }

    /**
     * Lấy chi tiết một listening
     */
    public function show($id)
    {
        $listening = Listening::with('lesson')->find($id);
        if (!$listening) {
            return response()->json(['message' => 'Listening không tìm thấy'], 404);
        }

        return response()->json($listening);
    }

    /**
     * Tạo mới listening (hỗ trợ upload audio file)
     */
    public function store(Request $request, $lessonId)
    {
        $lesson = Lesson::find($lessonId);
        if (!$lesson) {
            return response()->json(['message' => 'Lesson không tìm thấy'], 404);
        }

        $validated = $request->validate([
            'title'  => 'required|string|max:255',
            'script' => 'nullable|string',
            'audio'  => 'nullable|file|mimes:mp3,wav,m4a,ogg,webm|max:51200', // max 50MB
        ]);

        // Xử lý upload audio file
        if ($request->hasFile('audio')) {
            $path = $request->file('audio')->store('audio', 'public');
            $validated['audio'] = '/storage/' . $path;
        }

        $validated['lesson_id'] = $lessonId;

        $listening = Listening::create($validated);

        return response()->json([
            'message' => 'Tạo listening thành công',
            'data'    => $listening
        ], 201);
    }

    /**
     * Cập nhật listening
     */
    public function update(Request $request, $id)
    {
        $listening = Listening::find($id);
        if (!$listening) {
            return response()->json(['message' => 'Listening không tìm thấy'], 404);
        }

        $validated = $request->validate([
            'title'  => 'sometimes|required|string|max:255',
            'script' => 'nullable|string',
            'audio'  => 'nullable|file|mimes:mp3,wav,m4a,ogg,webm|max:51200',
        ]);

        // Xử lý upload audio file mới (nếu có)
        if ($request->hasFile('audio')) {
            // Xóa audio cũ nếu tồn tại
            if ($listening->audio && Storage::disk('public')->exists(str_replace('/storage/', '', $listening->audio))) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $listening->audio));
            }
            $path = $request->file('audio')->store('audio', 'public');
            $validated['audio'] = '/storage/' . $path;
        }

        $listening->update($validated);

        return response()->json([
            'message' => 'Cập nhật listening thành công',
            'data'    => $listening->fresh()
        ]);
    }

    /**
     * Xóa listening
     */
    public function destroy($id)
    {
        $listening = Listening::find($id);
        if (!$listening) {
            return response()->json(['message' => 'Listening không tìm thấy'], 404);
        }

        // Xóa audio file nếu tồn tại
        if ($listening->audio && Storage::disk('public')->exists(str_replace('/storage/', '', $listening->audio))) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $listening->audio));
        }

        $listening->delete();

        return response()->json(['message' => 'Xóa listening thành công']);
    }
}
