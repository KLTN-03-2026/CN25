<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Review;
use App\Models\UserCourse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * CourseController - Xử lý các API liên quan đến khóa học
 * Cung cấp các endpoint để CRUD khóa học với cấu trúc chuẩn
 */
class CourseController extends Controller
{
    /**
     * Lấy danh sách tất cả khóa học
     * Hỗ trợ filter theo status, level
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = Course::query();

        // Lọc theo status nếu có
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Lọc theo level nếu có
        if ($request->has('level')) {
            $query->where('level', $request->level);
        }

        // Lọc khóa học đã publish
        if ($request->boolean('published_only')) {
            $query->published();
        }

        // Lọc khóa học nổi bật
        if ($request->boolean('featured_only')) {
            // Lấy top 6 khóa học đã publish có rating cao nhất
            $featuredIds = [];
            try {
                $featuredIds = Review::where('status', 'duyet')
                    ->whereHas('course', function ($q) {
                        $q->where('status', 'published');
                    })
                    ->select('course_id', DB::raw('AVG(rating) as avg_rating'))
                    ->groupBy('course_id')
                    ->orderByDesc('avg_rating')
                    ->limit(6)
                    ->pluck('course_id')
                    ->toArray();
            } catch (\Exception $e) {
                // Bảng reviews chưa có dữ liệu
            }

            // Nếu có review, giới hạn theo rating, không thì lấy tất cả published
            if (!empty($featuredIds)) {
                $query->whereIn('id', $featuredIds);
            }
        }

        // Lấy danh sách courses
        $courses = $query->withCount('chapters')->get();

        // Lay review stats cho tat ca khoa hoc
        $courseIds = $courses->pluck('id')->toArray();
        $reviewStats = Review::whereIn('course_id', $courseIds)
            ->where('status', 'duyet')
            ->select('course_id', DB::raw('AVG(rating) as avg_rating'), DB::raw('COUNT(*) as review_count'))
            ->groupBy('course_id')
            ->get()
            ->keyBy('course_id');

        // Thêm trạng thái đăng ký và avg_rating vào courses
        $user = auth('sanctum')->user();
        if ($user) {
            $enrolledCourseIds = DB::table('user_courses')
                ->where('user_id', $user->id)
                ->pluck('course_id')
                ->toArray();

            $courses = $courses->map(function ($course) use ($enrolledCourseIds, $reviewStats) {
                $course->is_enrolled = in_array($course->id, $enrolledCourseIds);
                $stats = $reviewStats->get($course->id);
                $course->avg_rating = $stats ? round($stats->avg_rating, 1) : 0;
                $course->review_count = $stats ? (int) $stats->review_count : 0;
                return $course;
            });
        } else {
            $courses = $courses->map(function ($course) use ($reviewStats) {
                $course->is_enrolled = false;
                $stats = $reviewStats->get($course->id);
                $course->avg_rating = $stats ? round($stats->avg_rating, 1) : 0;
                $course->review_count = $stats ? (int) $stats->review_count : 0;
                return $course;
            });
        }

        // Sắp xếp theo rating cao nhất nếu là featured
        if ($request->boolean('featured_only')) {
            $courses = $courses->sortByDesc('avg_rating')->values();
        } else {
            $courses = $courses->sortByDesc('created_at')->values();
        }

        return response()->json($courses);
    }

    /**
     * Lấy thông tin chi tiết một khóa học theo ID
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $course = Course::find($id);

        if (!$course) {
            return response()->json(['message' => 'Khóa học không tìm thấy'], 404);
        }

        $user = auth('sanctum')->user();
        if ($user) {
            $course->is_enrolled = UserCourse::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->exists();
        } else {
            $course->is_enrolled = false;
        }

        return response()->json($course);
    }

    /**
     * Lấy khóa học theo slug
     * 
     * @param string $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function showBySlug($slug)
    {
        $course = Course::where('slug', $slug)->first();

        if (!$course) {
            return response()->json(['message' => 'Khóa học không tìm thấy'], 404);
        }

        return response()->json($course);
    }

    /**
     * Tạo mới một khóa học
     * Validation đầy đủ theo cấu trúc chuẩn
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Validate dữ liệu đầu vào theo cấu trúc chuẩn
        $validated = $request->validate([
            'title'       => 'required|string|max:255',                 // Tên khóa học (bắt buộc)
            'slug'        => 'nullable|string|max:255|unique:courses,slug', // URL thân thiện (duy nhất)
            'description' => 'nullable|string',                          // Mô tả khóa học
            'thumbnail'   => 'nullable|string|max:500',                  // Ảnh đại diện
            'level'       => 'nullable|string|in:beginner,intermediate,advanced', // Trình độ
            'price'       => 'nullable|numeric|min:0',                   // Giá (>= 0)
            'status'      => 'nullable|string|in:draft,published',       // Trạng thái
            'is_featured' => 'nullable|boolean',                         // Khóa học nổi bật
        ], [
            // Thông báo lỗi tùy chỉnh
            'title.required'  => 'Vui lòng nhập tiêu đề khóa học',
            'title.max'      => 'Tiêu đề khóa học không được vượt quá 255 ký tự',
            'slug.unique'    => 'Slug đã tồn tại, vui lòng chọn slug khác',
            'level.in'      => 'Trình độ phải là: beginner, intermediate hoặc advanced',
            'price.min'     => 'Giá không được nhỏ hơn 0',
            'status.in'     => 'Trạng thái phải là: draft hoặc published',
        ]);

        // Tạo slug tự động nếu không cung cấp
        if (empty($validated['slug']) && !empty($validated['title'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        // Đảm bảo slug là duy nhất
        $originalSlug = $validated['slug'];
        $counter = 1;
        while (Course::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = $originalSlug . '-' . $counter;
            $counter++;
        }

        // Tạo mới khóa học
        $course = Course::create($validated);

        return response()->json([
            'message' => 'Tạo khóa học thành công',
            'data'    => $course
        ], 201);
    }

    /**
     * Cập nhật thông tin khóa học
     * 
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        // Tìm khóa học theo ID
        $course = Course::find($id);

        // Nếu không tìm thấy, trả về lỗi 404
        if (!$course) {
            return response()->json(['message' => 'Khóa học không tìm thấy'], 404);
        }

        // Validate dữ liệu đầu vào
        $validated = $request->validate([
            'title'       => 'sometimes|required|string|max:255',
            'slug'        => 'sometimes|required|string|max:255|unique:courses,slug,' . $id,
            'description' => 'nullable|string',
            'thumbnail'   => 'nullable|string|max:500',
            'level'       => 'nullable|string|in:beginner,intermediate,advanced',
            'price'       => 'nullable|numeric|min:0',
            'status'      => 'nullable|string|in:draft,published',
            'is_featured' => 'nullable|boolean',
        ], [
            'title.required'  => 'Vui lòng nhập tiêu đề khóa học',
            'title.max'      => 'Tiêu đề khóa học không được vượt quá 255 ký tự',
            'slug.unique'    => 'Slug đã tồn tại, vui lòng chọn slug khác',
            'level.in'      => 'Trình độ phải là: beginner, intermediate hoặc advanced',
            'price.min'     => 'Giá không được nhỏ hơn 0',
            'status.in'     => 'Trạng thái phải là: draft hoặc published',
        ]);

        // Cập nhật slug tự động nếu title thay đổi
        if (isset($validated['title']) && !isset($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['title']);
        }

        // Cập nhật thông tin khóa học
        $course->update($validated);

        return response()->json([
            'message' => 'Cập nhật khóa học thành công',
            'data'    => $course
        ]);
    }

    /**
     * Xóa một khóa học
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        // Tìm khóa học theo ID
        $course = Course::find($id);

        // Nếu không tìm thấy, trả về lỗi 404
        if (!$course) {
            return response()->json(['message' => 'Khóa học không tìm thấy'], 404);
        }

        // Xóa khóa học (các chapters, lessons liên quan sẽ tự động bị xóa do cascade)
        $course->delete();

        return response()->json(['message' => 'Xóa khóa học thành công']);
    }

    /**
     * Thay đổi trạng thái khóa học (publish/unpublish)
     * 
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function changeStatus(Request $request, $id)
    {
        $course = Course::find($id);

        if (!$course) {
            return response()->json(['message' => 'Khóa học không tìm thấy'], 404);
        }

        $validated = $request->validate([
            'status' => 'required|string|in:draft,published',
        ]);

        $course->update(['status' => $validated['status']]);

        return response()->json([
            'message' => 'Cập nhật trạng thái thành công',
            'data'    => $course
        ]);
    }

    /**
     * Tạo 4 chapters mặc định cho khóa học
     * Bao gồm: Từ vựng, Ngữ pháp, Nghe, Nói
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateChapters($id)
    {
        $course = Course::find($id);

        if (!$course) {
            return response()->json(['message' => 'Khóa học không tìm thấy'], 404);
        }

        // Kiểm tra đã có chapters chưa
        if ($course->chapters()->exists()) {
            return response()->json([
                'message' => 'Khóa học đã có chapters. Không thể tạo lại.'
            ], 400);
        }

        $chaptersData = [
            [
                'title' => 'Từ vựng',
                'type' => 'vocabulary',
                'description' => 'Học từ vựng theo chủ đề',
            ],
            [
                'title' => 'Ngữ pháp',
                'type' => 'grammar',
                'description' => 'Học cấu trúc và ngữ pháp',
            ],
            [
                'title' => 'Nghe',
                'type' => 'listening',
                'description' => 'Luyện nghe qua audio',
            ],
            [
                'title' => 'Nói',
                'type' => 'speaking',
                'description' => 'Luyện nói giao tiếp',
            ],
        ];

        $createdChapters = [];
        foreach ($chaptersData as $index => $chapterData) {
            $chapter = $course->chapters()->create([
                'title' => $chapterData['title'],
                'slug' => Str::slug($chapterData['type']) . '-' . $course->id,
                'type' => $chapterData['type'],
                'description' => $chapterData['description'],
                'order' => $index + 1,
                'status' => 'published',
            ]);
            $createdChapters[] = $chapter;
        }

        return response()->json([
            'message' => 'Tạo 4 chapters thành công',
            'data'    => $createdChapters
        ], 201);
    }
}
