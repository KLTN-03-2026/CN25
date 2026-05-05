<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Listening;
use App\Models\ListeningExercise;
use Illuminate\Http\Request;

class ListeningExerciseController extends Controller
{
    /**
     * Lấy danh sách bài tập theo listening
     */
    public function getByListening($listeningId)
    {
        $listening = Listening::find($listeningId);
        if (!$listening) {
            return response()->json(['message' => 'Listening không tìm thấy'], 404);
        }

        $exercises = ListeningExercise::where('listening_id', $listeningId)
            ->orderBy('id')
            ->get();

        return response()->json($exercises);
    }

    /**
     * Lấy chi tiết một bài tập
     */
    public function show($id)
    {
        $exercise = ListeningExercise::with('listening')->find($id);
        if (!$exercise) {
            return response()->json(['message' => 'Bài tập không tìm thấy'], 404);
        }

        return response()->json($exercise);
    }

    /**
     * Tạo mới bài tập
     */
    public function store(Request $request, $listeningId)
    {
        $listening = Listening::find($listeningId);
        if (!$listening) {
            return response()->json(['message' => 'Listening không tìm thấy'], 404);
        }

        $validated = $request->validate([
            'question'      => 'required|string',
            'type'         => 'required|in:multiple_choice,fill_blank,true_false',
            'options'      => 'nullable|array',
            'correct_answer' => 'required|string',
            'explanation'  => 'nullable|string',
        ]);

        $validated['listening_id'] = $listeningId;

        $exercise = ListeningExercise::create($validated);

        return response()->json([
            'message' => 'Tạo bài tập thành công',
            'data'    => $exercise
        ], 201);
    }

    /**
     * Cập nhật bài tập
     */
    public function update(Request $request, $id)
    {
        $exercise = ListeningExercise::find($id);
        if (!$exercise) {
            return response()->json(['message' => 'Bài tập không tìm thấy'], 404);
        }

        $validated = $request->validate([
            'question'      => 'sometimes|required|string',
            'type'         => 'sometimes|required|in:multiple_choice,fill_blank,true_false',
            'options'      => 'nullable|array',
            'correct_answer' => 'sometimes|required|string',
            'explanation'  => 'nullable|string',
        ]);

        $exercise->update($validated);

        return response()->json([
            'message' => 'Cập nhật bài tập thành công',
            'data'    => $exercise
        ]);
    }

    /**
     * Xóa bài tập
     */
    public function destroy($id)
    {
        $exercise = ListeningExercise::find($id);
        if (!$exercise) {
            return response()->json(['message' => 'Bài tập không tìm thấy'], 404);
        }

        $exercise->delete();

        return response()->json(['message' => 'Xóa bài tập thành công']);
    }
}
