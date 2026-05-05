<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Lay danh sach danh gia / binh luan
     */
    public function index(Request $request)
    {
        $query = Review::with(['user:id,name,email,avatar', 'course:id,title']);

        // Filter theo trang thai
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter theo khoa hoc
        if ($request->has('course_id') && $request->course_id) {
            $query->where('course_id', $request->course_id);
        }

        // Tim kiem
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('content', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $reviews = $query->orderBy('id', 'desc')->paginate($request->get('per_page', 15));

        return response()->json($reviews);
    }

    /**
     * Lay chi tiet mot danh gia
     */
    public function show($id)
    {
        $review = Review::with(['user', 'course'])->findOrFail($id);
        return response()->json($review);
    }

    /**
     * Lay reviews noi bat cho trang chu
     */
    public function getFeatured()
    {
        $reviews = Review::with(['user:id,name,avatar', 'course:id,title'])
            ->where('status', 'duyet')
            ->orderBy('created_at', 'desc')
            ->limit(6)
            ->get();

        return response()->json($reviews);
    }

    /**
     * Lay reviews cua mot khoa hoc (public - chi hien thi review da duyet)
     */
    public function getByCourse(Request $request, $courseId)
    {
        $perPage = $request->get('per_page', 10);
        $reviews = Review::with(['user:id,name,avatar'])
            ->where('course_id', $courseId)
            ->where('status', 'duyet')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $stats = [
            'avg_rating' => Review::where('course_id', $courseId)->where('status', 'duyet')->avg('rating') ?? 0,
            'total' => Review::where('course_id', $courseId)->where('status', 'duyet')->count(),
            'rating_distribution' => [
                5 => Review::where('course_id', $courseId)->where('status', 'duyet')->where('rating', 5)->count(),
                4 => Review::where('course_id', $courseId)->where('status', 'duyet')->where('rating', 4)->count(),
                3 => Review::where('course_id', $courseId)->where('status', 'duyet')->where('rating', 3)->count(),
                2 => Review::where('course_id', $courseId)->where('status', 'duyet')->where('rating', 2)->count(),
                1 => Review::where('course_id', $courseId)->where('status', 'duyet')->where('rating', 1)->count(),
            ],
        ];

        return response()->json([
            'reviews' => $reviews,
            'stats' => $stats,
        ]);
    }

    /**
     * Lay review cua user hien tai cho mot khoa hoc
     */
    public function getMyReview(Request $request, $courseId)
    {
        $user = $request->user();
        $review = Review::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->first();
        return response()->json($review);
    }

    /**
     * Tao danh gia / binh luan moi
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'content' => ['required', 'string', 'max:2000'],
        ]);

        // Kiem tra user da dang ky khoa hoc chua
        $enrolled = \App\Models\UserCourse::where('user_id', $user->id)
            ->where('course_id', $validated['course_id'])
            ->exists();

        if (!$enrolled) {
            return response()->json([
                'message' => 'Ban phai dang ky khoa hoc truoc khi danh gia.',
            ], 403);
        }

        $existingReview = Review::where('user_id', $user->id)
            ->where('course_id', $validated['course_id'])
            ->first();

        if ($existingReview) {
            return response()->json([
                'message' => 'Ban da danh gia khoa hoc nay',
                'review' => $existingReview,
            ], 422);
        }

        $review = Review::create([
            'user_id' => $user->id,
            'course_id' => $validated['course_id'],
            'rating' => $validated['rating'],
            'content' => $validated['content'],
            'status' => 'cho',
        ]);

        return response()->json([
            'message' => 'Gui danh gia thanh cong. Danh gia cua ban se duoc hien thi sau khi duoc duyet.',
            'review' => $review
        ], 201);
    }

    /**
     * Cap nhat trang thai duyet / an danh gia
     */
    public function update(Request $request, $id)
    {
        $review = Review::findOrFail($id);

        $validated = $request->validate([
            'status' => ['sometimes', 'in:cho,duyet,an'],
            'content' => ['sometimes', 'string', 'max:2000'],
            'rating' => ['sometimes', 'integer', 'min:1', 'max:5'],
            'admin_reply' => ['nullable', 'string', 'max:2000'],
        ]);

        $review->update($validated);

        return response()->json([
            'message' => 'Cap nhat thanh cong',
            'review' => $review
        ]);
    }

    /**
     * Xoa danh gia
     */
    public function destroy($id)
    {
        $review = Review::findOrFail($id);
        $review->delete();

        return response()->json(['message' => 'Xoa danh gia thanh cong']);
    }

    /**
     * Tra loi danh gia (admin)
     */
    public function reply(Request $request, $id)
    {
        $review = Review::findOrFail($id);

        $validated = $request->validate([
            'admin_reply' => ['required', 'string', 'max:2000'],
        ]);

        $review->update($validated);

        return response()->json([
            'message' => 'Tra loi thanh cong',
            'review' => $review
        ]);
    }

    /**
     * Duyet danh gia
     */
    public function approve($id)
    {
        $review = Review::findOrFail($id);
        $review->update(['status' => 'duyet']);

        return response()->json([
            'message' => 'Duyet danh gia thanh cong',
            'review' => $review
        ]);
    }

    /**
     * An danh gia
     */
    public function hide($id)
    {
        $review = Review::findOrFail($id);
        $review->update(['status' => 'an']);

        return response()->json([
            'message' => 'An danh gia thanh cong',
            'review' => $review
        ]);
    }

    /**
     * Thong ke danh gia
     */
    public function stats()
    {
        $stats = [
            'total' => Review::count(),
            'pending' => Review::where('status', 'cho')->count(),
            'approved' => Review::where('status', 'duyet')->count(),
            'hidden' => Review::where('status', 'an')->count(),
            'avg_rating' => Review::where('status', 'duyet')->avg('rating') ?? 0,
        ];

        return response()->json($stats);
    }
}
