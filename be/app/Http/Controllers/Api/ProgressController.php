<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserCourse;
use App\Models\LessonProgress;
use App\Models\Lesson;
use App\Models\Course;
use App\Models\Chapter;
use App\Models\ExerciseScore;
use App\Models\LessonQuiz;
use App\Models\CourseQuiz;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProgressController extends Controller
{
    /**
     * Lay dashboard data (tong quan tien do hoc vien)
     * GET /api/progress/dashboard
     */
    public function getDashboard()
    {
        $userId = Auth::id();

        $enrolledCourses = UserCourse::with(['course' => function ($q) {
            $q->select('id', 'title', 'slug', 'thumbnail', 'price');
        }])
        ->where('user_id', $userId)
        ->get();

        $coursesWithProgress = $enrolledCourses->map(function ($enrollment) {
            $course = $enrollment->course;
            $totalLessons = Lesson::join('chapters', 'lessons.chapter_id', '=', 'chapters.id')
                ->where('chapters.course_id', $course->id)
                ->count();

            $completedLessons = LessonProgress::where('user_id', $enrollment->user_id)
                ->where('completed', true)
                ->whereIn('lesson_id', function ($q) use ($course) {
                    $q->select('lessons.id')
                        ->from('lessons')
                        ->join('chapters', 'lessons.chapter_id', '=', 'chapters.id')
                        ->where('chapters.course_id', $course->id);
                })->count();

            $progressPercent = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;

            // Tim bai hoc tiep theo chua hoan thanh
            $nextLesson = $this->getNextLesson($course->id, $enrollment->user_id);

            return [
                'id' => $course->id,
                'title' => $course->title,
                'slug' => $course->slug,
                'thumbnail' => $course->thumbnail,
                'price' => $course->price,
                'enrolled_at' => $enrollment->enrolled_at,
                'total_lessons' => $totalLessons,
                'completed_lessons' => $completedLessons,
                'progress_percent' => $progressPercent,
                'is_completed' => $completedLessons >= $totalLessons && $totalLessons > 0,
                'next_lesson_id' => $nextLesson['id'] ?? null,
                'next_lesson_title' => $nextLesson['title'] ?? null,
                'next_lesson_type' => $nextLesson['type'] ?? 'lesson',
            ];
        });

        $totalLessons = LessonProgress::where('user_id', $userId)->count();
        $completedLessons = LessonProgress::where('user_id', $userId)->where('completed', true)->count();
        $totalTimeSpent = LessonProgress::where('user_id', $userId)->sum('time_spent');

        // Lay hoat dong gan day (7 ngay gan nhat)
        $recentActivity = LessonProgress::with(['lesson' => function ($q) {
            $q->select('id', 'title', 'chapter_id')->with(['chapter' => function ($cq) {
                $cq->select('id', 'title', 'course_id');
            }]);
        }])
        ->where('user_id', $userId)
        ->whereNotNull('completed_at')
        ->orderByDesc('completed_at')
        ->limit(5)
        ->get()
        ->map(function ($lp) {
            $lesson = $lp->lesson;
            $chapter = $lesson?->chapter;
            return [
                'id' => $lp->id,
                'lesson_id' => $lp->lesson_id,
                'title' => $lesson?->title ?? 'Bài học',
                'course_id' => $chapter?->course_id,
                'chapter_title' => $chapter?->title,
                'score' => $lp->score,
                'completed_at' => $lp->completed_at,
                'type' => 'lesson',
            ];
        });

        // Tinh streak (so ngay lien tiep hoc)
        $streakDays = $this->calculateStreak($userId);

        // Bai hoc hom nay
        $today = now()->startOfDay();
        $lessonsToday = LessonProgress::where('user_id', $userId)
            ->where('completed', true)
            ->where('completed_at', '>=', $today)
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'enrolled_courses' => $coursesWithProgress,
                'total_lessons' => $totalLessons,
                'completed_lessons' => $completedLessons,
                'total_time_minutes' => (int) round($totalTimeSpent / 60),
                'streak_days' => $streakDays,
                'lessons_today' => $lessonsToday,
                'recent_activity' => $recentActivity,
            ]
        ]);
    }

    /**
     * Tinh so ngay lien tiep hoc (streak)
     */
    private function calculateStreak($userId)
    {
        $completedDates = LessonProgress::where('user_id', $userId)
            ->where('completed', true)
            ->whereNotNull('completed_at')
            ->selectRaw('DATE(completed_at) as date')
            ->distinct()
            ->orderByDesc('date')
            ->pluck('date')
            ->map(fn($d) => \Carbon\Carbon::parse($d))
            ->toArray();

        if (empty($completedDates)) return 0;

        $streak = 0;
        $checkDate = now()->startOfDay();

        // Neu hom nay chua hoc, bat dau tu hom qua
        $hasToday = false;
        foreach ($completedDates as $d) {
            if ($d->isSameDay($checkDate)) { $hasToday = true; break; }
        }
        if (!$hasToday) {
            $checkDate = now()->subDay()->startOfDay();
        }

        foreach ($completedDates as $d) {
            if ($d->isSameDay($checkDate)) {
                $streak++;
                $checkDate = $checkDate->copy()->subDay();
            } elseif ($d->lt($checkDate)) {
                break;
            }
        }

        return $streak;
    }

    /**
     * Lay thong ke cua hoc vien
     * GET /api/progress/stats
     */
    public function getStats()
    {
        $userId = Auth::id();

        $totalCourses = UserCourse::where('user_id', $userId)->count();
        $completedLessons = LessonProgress::where('user_id', $userId)->where('completed', true)->count();
        $totalTimeSpent = LessonProgress::where('user_id', $userId)->sum('time_spent');
        $avgScore = LessonProgress::where('user_id', $userId)->whereNotNull('score')->avg('score');
        $streakDays = $this->calculateStreak($userId);

        // Badges based on achievements
        $badges = 0;
        if ($completedLessons >= 1) $badges++;
        if ($streakDays >= 3) $badges++;
        if ($completedLessons >= 10) $badges++;
        if ($totalTimeSpent >= 3600) $badges++; // 1 hour
        if ($avgScore >= 80) $badges++;

        return response()->json([
            'success' => true,
            'data' => [
                'total_courses' => $totalCourses,
                'completed_lessons' => $completedLessons,
                'streak_days' => $streakDays,
                'total_time_spent' => (int) $totalTimeSpent,
                'total_time_spent_formatted' => $this->formatTime($totalTimeSpent),
                'total_time' => (int) round($totalTimeSpent / 3600, 1),
                'avg_score' => $avgScore ? round($avgScore, 1) : null,
                'badges' => $badges,
            ]
        ]);
    }

    /**
     * Lay danh sach khoa hoc da dang ky
     * GET /api/progress/my-courses
     */
    public function myCourses()
    {
        $userId = Auth::id();

        $courses = UserCourse::with(['course' => function ($q) {
            $q->select('id', 'title', 'slug', 'thumbnail', 'price', 'level');
        }])
        ->where('user_id', $userId)
        ->get()
        ->map(function ($enrollment) use ($userId) {
            $course = $enrollment->course;
            $totalLessons = Lesson::join('chapters', 'lessons.chapter_id', '=', 'chapters.id')
                ->where('chapters.course_id', $course->id)
                ->count();
            $completedLessons = LessonProgress::where('user_id', $userId)
                ->where('completed', true)
                ->whereIn('lesson_id', Lesson::join('chapters', 'lessons.chapter_id', '=', 'chapters.id')
                    ->where('chapters.course_id', $course->id)
                    ->pluck('lessons.id'))
                ->count();

            return [
                'id' => $course->id,
                'title' => $course->title,
                'slug' => $course->slug,
                'thumbnail' => $course->thumbnail,
                'price' => $course->price,
                'level' => $course->level,
                'enrolled_at' => $enrollment->enrolled_at,
                'progress_percent' => $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0,
            ];
        });

        return response()->json(['success' => true, 'data' => $courses]);
    }

    /**
     * Lay tien do chi tiet theo khoa hoc (Lesson 80% + Practice 10% + Final 10%)
     * GET /api/progress/course/{courseId}
     */
    public function getCourseProgress($courseId)
    {
        $userId = Auth::id();

        $course = Course::find($courseId);
        if (!$course) {
            return response()->json(['success' => false, 'message' => 'Khoa hoc khong ton tai'], 404);
        }

        $chapters = Chapter::where('course_id', $courseId)
            ->with(['lessons' => fn($q) => $q->select('id', 'chapter_id', 'title', 'order')])
            ->orderBy('order')
            ->get();

        $totalLessons = $chapters->sum(fn($ch) => $ch->lessons->count());
        $lessonIds = $chapters->flatMap(fn($ch) => $ch->lessons->pluck('id'))->toArray();

        $completedLessons = LessonProgress::where('user_id', $userId)
            ->where('completed', true)
            ->whereIn('lesson_id', $lessonIds)
            ->count();

        $lessonPercent = $totalLessons > 0 ? ($completedLessons / $totalLessons) * 80 : 0;

        $practiceDone = ExerciseScore::where('user_id', $userId)
            ->where('course_id', $courseId)
            ->where('type', 'practice')
            ->exists();

        // Lay thong tin chi tiet final exam (lay diem cao nhat)
        $finalScores = ExerciseScore::where('user_id', $userId)
            ->where('course_id', $courseId)
            ->where('type', 'final')
            ->orderBy('score', 'desc')
            ->get();

        $finalBest = $finalScores->first();
        $finalAttempts = $finalScores->count();
        $finalDone = $finalBest !== null;

        $progress = $lessonPercent + ($practiceDone ? 10 : 0) + ($finalDone ? 10 : 0);

        $chapterProgress = $chapters->map(function ($chapter) use ($userId) {
            $lessonIds = $chapter->lessons->pluck('id')->toArray();
            $completed = LessonProgress::where('user_id', $userId)
                ->where('completed', true)
                ->whereIn('lesson_id', $lessonIds)
                ->count();
            $total = count($lessonIds);

            return [
                'chapter_id' => $chapter->id,
                'title' => $chapter->title,
                'total_lessons' => $total,
                'completed_lessons' => $completed,
                'progress_percent' => $total > 0 ? round(($completed / $total) * 100) : 0,
            ];
        });

        $nextLesson = null;
        foreach ($chapters as $chapter) {
            foreach ($chapter->lessons->sortBy('order') as $lesson) {
                $isCompleted = LessonProgress::where('user_id', $userId)
                    ->where('lesson_id', $lesson->id)
                    ->where('completed', true)
                    ->exists();
                if (!$isCompleted) {
                    $nextLesson = [
                        'id' => $lesson->id,
                        'title' => $lesson->title,
                        'chapter_id' => $chapter->id,
                        'course_id' => $courseId,
                    ];
                    break 2;
                }
            }
        }

        $practiceScore = ExerciseScore::where('user_id', $userId)
            ->where('course_id', $courseId)
            ->where('type', 'practice')
            ->first();

        $completedLessonIds = LessonProgress::where('user_id', $userId)
            ->where('completed', true)
            ->whereIn('lesson_id', $lessonIds)
            ->pluck('lesson_id')
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => [
                'course_id' => $courseId,
                'progress' => round($progress),
                'lesson_percent' => round($lessonPercent),
                'completed_lessons' => $completedLessons,
                'total_lessons' => $totalLessons,
                'completed_lesson_ids' => $completedLessonIds,
                'practice_done' => $practiceDone,
                'practice_score' => $practiceScore ? (float) $practiceScore->score : null,
                'final_done' => $finalDone,
                'final_score' => $finalBest ? (float) $finalBest->score : null,
                'final_best_score' => $finalBest ? (float) $finalBest->score : null,
                'final_attempts' => $finalAttempts,
                'final_passed' => $finalBest && $finalBest->passed,
                'is_practice_unlocked' => $lessonPercent >= 80,
                'is_final_unlocked' => $practiceDone,
                'chapters' => $chapterProgress,
                'next_lesson' => $nextLesson,
            ]
        ]);
    }

    /**
     * Cap nhat progress_percent vao bang user_courses
     */
    private function updateUserCourseProgress($courseId, $userId)
    {
        $totalLessons = Lesson::join('chapters', 'lessons.chapter_id', '=', 'chapters.id')
            ->where('chapters.course_id', $courseId)
            ->count();
        $completedLessons = LessonProgress::where('user_id', $userId)
            ->where('completed', true)
            ->whereIn('lesson_id', Lesson::join('chapters', 'lessons.chapter_id', '=', 'chapters.id')
                ->where('chapters.course_id', $courseId)
                ->pluck('lessons.id'))
            ->count();

        $lessonPercent = $totalLessons > 0 ? ($completedLessons / $totalLessons) * 80 : 0;

        $practiceDone = ExerciseScore::where('user_id', $userId)
            ->where('course_id', $courseId)
            ->where('type', 'practice')
            ->exists();

        $finalDone = ExerciseScore::where('user_id', $userId)
            ->where('course_id', $courseId)
            ->where('type', 'final')
            ->exists();

        $finalPercent = $lessonPercent + ($practiceDone ? 10 : 0) + ($finalDone ? 10 : 0);

        UserCourse::updateOrCreate(
            ['user_id' => $userId, 'course_id' => $courseId],
            [
                'progress_percent' => round($finalPercent),
                'completed' => $finalPercent == 100,
            ]
        );
    }

    /**
     * Dang ky khoa hoc
     * POST /api/progress/enroll/{courseId}
     */
    public function enroll($courseId)
    {
        $userId = Auth::id();

        $course = Course::find($courseId);
        if (!$course) {
            return response()->json(['success' => false, 'message' => 'Khoa hoc khong ton tai'], 404);
        }

        $existing = UserCourse::where('user_id', $userId)->where('course_id', $courseId)->first();
        if ($existing) {
            return response()->json([
                'success' => true,
                'data' => ['enrolled' => true, 'enrolled_at' => $existing->enrolled_at]
            ]);
        }

        $enrollment = UserCourse::create([
            'user_id' => $userId,
            'course_id' => $courseId,
            'enrolled_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => ['enrolled' => true, 'enrolled_at' => $enrollment->enrolled_at]
        ], 201);
    }

    /**
     * Bat dau bai hoc
     * POST /api/progress/lesson/{lessonId}/start
     */
    public function startLesson($lessonId)
    {
        $userId = Auth::id();

        $lesson = Lesson::with('chapter')->find($lessonId);
        if (!$lesson) {
            return response()->json(['success' => false, 'message' => 'Bai hoc khong ton tai'], 404);
        }

        $progress = LessonProgress::updateOrCreate(
            ['user_id' => $userId, 'lesson_id' => $lessonId],
            ['completed' => false]
        );

        return response()->json([
            'success' => true,
            'data' => ['started' => true, 'progress_id' => $progress->id]
        ]);
    }

    /**
     * Hoan thanh bai hoc
     * POST /api/progress/lesson/{lessonId}/complete
     */
    public function completeLesson($lessonId, Request $request)
    {
        $userId = Auth::id();

        $lesson = Lesson::with('chapter')->find($lessonId);
        if (!$lesson) {
            return response()->json(['success' => false, 'message' => 'Bai hoc khong ton tai'], 404);
        }

        $validated = $request->validate([
            'time_spent' => 'nullable|integer|min:0',
            'score' => 'nullable|numeric|min:0|max:100',
        ]);

        $progress = LessonProgress::updateOrCreate(
            ['user_id' => $userId, 'lesson_id' => $lessonId],
            [
                'completed' => true,
                'completed_at' => now(),
                'time_spent' => $validated['time_spent'] ?? 0,
                'score' => $validated['score'] ?? null,
            ]
        );

        $courseId = $lesson->chapter->course_id;
        $this->updateUserCourseProgress($courseId, $userId);

        $nextLesson = $this->getNextLesson($courseId, $userId);

        return response()->json([
            'success' => true,
            'data' => [
                'completed' => true,
                'progress_id' => $progress->id,
                'course_id' => $courseId,
                'next_lesson' => $nextLesson,
            ]
        ]);
    }

    /**
     * Lay tien do cua mot bai hoc
     * GET /api/progress/lesson/{lessonId}
     */
    public function getLessonProgress($lessonId)
    {
        $userId = Auth::id();

        $progress = LessonProgress::where('user_id', $userId)
            ->where('lesson_id', $lessonId)
            ->first();

        if (!$progress) {
            return response()->json([
                'success' => true,
                'data' => [
                    'completed' => false,
                    'time_spent' => 0,
                    'score' => null,
                    'started' => false,
                ]
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'completed' => $progress->completed,
                'completed_at' => $progress->completed_at,
                'time_spent' => $progress->time_spent,
                'score' => $progress->score,
                'started' => true,
            ]
        ]);
    }

    /**
     * Luu diem luyen tap hoac thi cuoi khoa
     * POST /api/progress/exercise-score
     */
    public function saveExerciseScore(Request $request)
    {
        $userId = Auth::id();

        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'type' => 'required|in:practice,final',
            'score' => 'required|numeric|min:0|max:100',
            'time_spent' => 'nullable|integer|min:0',
        ]);

        ExerciseScore::updateOrCreate(
            [
                'user_id' => $userId,
                'course_id' => $validated['course_id'],
                'type' => $validated['type'],
            ],
            [
                'score' => $validated['score'],
                'percentage' => $validated['score'],
                'time_spent' => $validated['time_spent'] ?? 0,
                'passed' => $validated['score'] >= 50,
            ]
        );

        $this->updateUserCourseProgress($validated['course_id'], $userId);

        return response()->json([
            'success' => true,
            'message' => 'Lưu điểm thành công',
            'data' => ['saved' => true, 'type' => $validated['type']]
        ]);
    }

    /**
     * Nộp bài thi cuối khóa - với attempt tracking
     * POST /api/progress/submit-final
     *
     * Logic:
     * - Lưu mỗi lần thi vào exercise_scores với attempt tăng dần
     * - Pass >= 70% -> cập nhật user_courses.completed = true
     * - Fail < 70% -> KHÔNG hoàn thành khóa, cho thi lại không giới hạn
     * - Trả về điểm cao nhất
     */
    public function submitFinal(Request $request)
    {
        $userId = Auth::id();

        $validated = $request->validate([
            'course_id' => 'required|exists:courses,id',
            'quiz_id' => 'nullable|exists:course_quizzes,id',
            'score' => 'required|numeric|min:0|max:100',
            'time_spent' => 'nullable|integer|min:0',
        ]);

        $lastAttempt = ExerciseScore::where('user_id', $userId)
            ->where('course_id', $validated['course_id'])
            ->where('type', 'final')
            ->max('attempt');

        $nextAttempt = ($lastAttempt ?? 0) + 1;

        ExerciseScore::create([
            'user_id' => $userId,
            'course_id' => $validated['course_id'],
            'type' => 'final',
            'score' => $validated['score'],
            'percentage' => $validated['score'],
            'time_spent' => $validated['time_spent'] ?? 0,
            'passed' => $validated['score'] >= 70,
            'attempt' => $nextAttempt,
        ]);

        $bestScore = ExerciseScore::where('user_id', $userId)
            ->where('course_id', $validated['course_id'])
            ->where('type', 'final')
            ->max('score');

        $passed = $validated['score'] >= 70;

        if ($passed) {
            UserCourse::updateOrCreate(
                [
                    'user_id' => $userId,
                    'course_id' => $validated['course_id'],
                ],
                [
                    'completed' => true,
                    'progress_percent' => 100,
                ]
            );
            $this->updateUserCourseProgress($validated['course_id'], $userId);
        }

        return response()->json([
            'success' => true,
            'message' => $passed ? 'Chúc mừng! Bạn đã hoàn thành khóa học!' : 'Bạn chưa đạt. Hãy thử lại!',
            'data' => [
                'score' => $validated['score'],
                'passed' => $passed,
                'attempt' => $nextAttempt,
                'best_score' => $bestScore,
                'is_completed' => $passed,
            ]
        ]);
    }

    /**
     * Tim bai hoc tiep theo chua hoan thanh
     */
    private function getNextLesson($courseId, $userId)
    {
        $chapters = Chapter::where('course_id', $courseId)
            ->with(['lessons' => fn($q) => $q->orderBy('order')])
            ->orderBy('order')
            ->get();

        foreach ($chapters as $chapter) {
            foreach ($chapter->lessons as $lesson) {
                $isCompleted = LessonProgress::where('user_id', $userId)
                    ->where('lesson_id', $lesson->id)
                    ->where('completed', true)
                    ->exists();
                if (!$isCompleted) {
                    return [
                        'id' => $lesson->id,
                        'title' => $lesson->title,
                        'chapter_id' => $chapter->id,
                        'course_id' => $courseId,
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Format thoi gian tu giay sang gio:phut:giay
     */
    private function formatTime($seconds)
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }
        if ($minutes > 0) {
            return "{$minutes}m {$secs}s";
        }
        return "{$secs}s";
    }

    /**
     * Lay lich su hoc tap cua hoc vien hien tai
     * GET /api/progress/history
     */
    public function getHistory(Request $request)
    {
        $userId = Auth::id();
        $perPage = $request->input('per_page', 20);
        $type = $request->input('type'); // lesson, practice, final

        // Lay lich su bai hoc
        $lessonQuery = LessonProgress::with([
            'lesson' => function ($q) {
                $q->select('id', 'title', 'chapter_id', 'type')
                  ->with(['chapter' => function ($cq) {
                      $cq->select('id', 'title', 'course_id')
                         ->with(['course' => function ($ccq) {
                             $ccq->select('id', 'title', 'slug', 'thumbnail');
                         }]);
                  }]);
            }
        ])
        ->where('user_id', $userId)
        ->whereNotNull('completed_at')
        ->orderByDesc('completed_at');

        if ($type === 'lesson') {
            $lessonQuery->whereHas('lesson', fn($q) => $q->where('type', '!=', 'quiz'));
        }

        $lessonHistory = $lessonQuery->get()->map(function ($lp) {
            $lesson = $lp->lesson;
            $chapter = $lesson?->chapter;
            $course = $chapter?->course;
            return [
                'id' => $lp->id,
                'type' => 'lesson',
                'lesson_id' => $lp->lesson_id,
                'title' => $lesson?->title ?? 'Bài học',
                'course_id' => $course?->id,
                'course_title' => $course?->title,
                'course_slug' => $course?->slug,
                'course_thumbnail' => $course?->thumbnail,
                'chapter_title' => $chapter?->title,
                'score' => $lp->score,
                'time_spent' => $lp->time_spent,
                'completed' => $lp->completed,
                'completed_at' => $lp->completed_at,
                'lesson_type' => $lesson?->type,
            ];
        });

        // Lay lich su luyen tap
        $practiceQuery = ExerciseScore::with(['course' => function ($q) {
            $q->select('id', 'title', 'slug', 'thumbnail');
        }])
        ->where('user_id', $userId)
        ->where('type', 'practice')
        ->orderByDesc('created_at');

        $practiceHistory = $practiceQuery->get()->map(function ($es) {
            return [
                'id' => $es->id,
                'type' => 'practice',
                'course_id' => $es->course_id,
                'course_title' => $es->course?->title,
                'course_slug' => $es->course?->slug,
                'course_thumbnail' => $es->course?->thumbnail,
                'score' => $es->score,
                'time_spent' => $es->time_spent,
                'passed' => $es->passed,
                'created_at' => $es->created_at,
            ];
        });

        // Lay lich su thi cuoi khoa
        $finalQuery = ExerciseScore::with(['course' => function ($q) {
            $q->select('id', 'title', 'slug', 'thumbnail');
        }])
        ->where('user_id', $userId)
        ->where('type', 'final')
        ->orderByDesc('created_at');

        $finalHistory = $finalQuery->get()->map(function ($es) {
            return [
                'id' => $es->id,
                'type' => 'final',
                'course_id' => $es->course_id,
                'course_title' => $es->course?->title,
                'course_slug' => $es->course?->slug,
                'course_thumbnail' => $es->course?->thumbnail,
                'score' => $es->score,
                'time_spent' => $es->time_spent,
                'passed' => $es->passed,
                'attempt' => $es->attempt,
                'created_at' => $es->created_at,
            ];
        });

        // Filter theo type
        if ($type === 'practice') {
            $lessonHistory = collect([]);
        }
        if ($type === 'final') {
            $lessonHistory = collect([]);
            $practiceHistory = collect([]);
        }

        // Gop tat ca lich su va sap xep theo thoi gian
        $allHistory = $lessonHistory->concat($practiceHistory)->concat($finalHistory);
        $sortedHistory = $allHistory->sortByDesc(function ($item) {
            return $item['completed_at'] ?? $item['created_at'] ?? null;
        })->values();

        // Phan trang
        $page = $request->input('page', 1);
        $total = $sortedHistory->count();
        $paginated = $sortedHistory->slice(($page - 1) * $perPage, $perPage)->values();

        // Tong so
        $totalLessons = LessonProgress::where('user_id', $userId)->where('completed', true)->count();
        $totalPractice = ExerciseScore::where('user_id', $userId)->where('type', 'practice')->count();
        $totalFinal = ExerciseScore::where('user_id', $userId)->where('type', 'final')->count();
        $totalTimeSpent = LessonProgress::where('user_id', $userId)->sum('time_spent');
        $totalTimePractice = ExerciseScore::where('user_id', $userId)->where('type', 'practice')->sum('time_spent');
        $totalTimeFinal = ExerciseScore::where('user_id', $userId)->where('type', 'final')->sum('time_spent');
        $totalTimeSpent += ($totalTimePractice ?? 0) + ($totalTimeFinal ?? 0);

        // Thong ke theo ngay (7 ngay gan nhat) - gop tu ca lesson_progress va exercise_scores
        $lessonDaily = LessonProgress::where('user_id', $userId)
            ->where('completed', true)
            ->whereNotNull('completed_at')
            ->selectRaw('DATE(completed_at) as date, COUNT(*) as count')
            ->groupBy('date');

        $practiceDaily = ExerciseScore::where('user_id', $userId)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date');

        $dailyStats = \DB::query()->fromSub(
            $lessonDaily->unionAll($practiceDaily),
            'combined'
        )
        ->select('date', \DB::raw('SUM(count) as count'))
        ->groupBy('date')
        ->orderBy('date', 'asc')
        ->limit(7)
        ->get()
        ->map(fn($d) => [
            'date' => $d->date,
            'count' => (int) $d->count,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $paginated,
                'pagination' => [
                    'current_page' => (int) $page,
                    'per_page' => (int) $perPage,
                    'total' => $total,
                    'total_pages' => (int) ceil($total / $perPage),
                ],
                'stats' => [
                    'total_lessons_completed' => $totalLessons,
                    'total_practice_done' => $totalPractice,
                    'total_exams_done' => $totalFinal,
                    'total_time_spent' => (int) $totalTimeSpent,
                    'total_time_formatted' => $this->formatTime($totalTimeSpent),
                ],
                'daily_stats' => $dailyStats,
            ]
        ]);
    }

    /**
     * Lay danh sach tien do hoc tap cua TAT CA hoc vien (cho Admin)
     * GET /api/admin/progress/all
     */
    public function getAllProgress(Request $request)
    {
        $query = UserCourse::with(['user:id,name,email', 'course:id,title']);

        if ($request->has('course_id') && $request->course_id) {
            $query->where('course_id', $request->course_id);
        }

        if ($request->has('user_id') && $request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $enrollments = $query->get()->map(function ($enrollment) {
            $course = $enrollment->course;
            $user = $enrollment->user;
            $userId = $enrollment->user_id;
            $courseId = $enrollment->course_id;

            $totalLessons = Lesson::join('chapters', 'lessons.chapter_id', '=', 'chapters.id')
                ->where('chapters.course_id', $courseId)
                ->count();

            $completedLessons = LessonProgress::where('user_id', $userId)
                ->where('completed', true)
                ->whereIn('lesson_id', Lesson::join('chapters', 'lessons.chapter_id', '=', 'chapters.id')
                    ->where('chapters.course_id', $courseId)
                    ->pluck('lessons.id'))
                ->count();

            $totalTimeSpent = LessonProgress::where('user_id', $userId)
                ->whereIn('lesson_id', Lesson::join('chapters', 'lessons.chapter_id', '=', 'chapters.id')
                    ->where('chapters.course_id', $courseId)
                    ->pluck('lessons.id'))
                ->sum('time_spent');

            $avgScore = LessonProgress::where('user_id', $userId)
                ->whereIn('lesson_id', Lesson::join('chapters', 'lessons.chapter_id', '=', 'chapters.id')
                    ->where('chapters.course_id', $courseId)
                    ->pluck('lessons.id'))
                ->whereNotNull('score')
                ->avg('score');

            $practiceScores = ExerciseScore::where('user_id', $userId)
                ->where('course_id', $courseId)
                ->where('type', 'practice')
                ->first();

            $finalScores = ExerciseScore::where('user_id', $userId)
                ->where('course_id', $courseId)
                ->where('type', 'final')
                ->orderBy('score', 'desc')
                ->first();

            $progressPercent = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;
            $tinhTrang = 'chua-bat-dau';
            if ($completedLessons > 0 && $completedLessons < $totalLessons) {
                $tinhTrang = 'dang-hoc';
            } elseif ($completedLessons == $totalLessons && $totalLessons > 0) {
                $tinhTrang = 'hoan-thanh';
            }

            return [
                'id' => $enrollment->id,
                'user_id' => $userId,
                'user_name' => $user->name ?? '-',
                'user_email' => $user->email ?? '-',
                'course_id' => $courseId,
                'course_title' => $course->title ?? '-',
                'bai_da_hoc' => $completedLessons,
                'tong_bai' => $totalLessons,
                'progress_percent' => $progressPercent,
                'tinh_trang' => $tinhTrang,
                'enrolled_at' => $enrollment->enrolled_at,
                'completed_at' => $enrollment->completed_at,
                'thoi_gian_hoc' => $totalTimeSpent,
                'thoi_gian_hoc_formatted' => $this->formatTime($totalTimeSpent),
                'diem_trung_binh' => $avgScore ? round($avgScore, 1) : null,
                'practice_score' => $practiceScores ? (float) $practiceScores->score : null,
                'final_score' => $finalScores ? (float) $finalScores->score : null,
            ];
        });

        return response()->json($enrollments);
    }
}
