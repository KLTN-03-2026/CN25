<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->get('q', '');
        $type = $request->get('type', 'all');
        $userId = Auth::id();

        if (strlen($query) < 2) {
            return response()->json([
                'courses' => [],
                'lessons' => [],
                'documents' => [],
                'articles' => [],
                'vocabularies' => [],
                'grammars' => [],
                'my_courses' => [],
                'payments' => [],
                'progress' => [],
            ]);
        }

        $results = [];

        // ==============================
        // COURSES - với giá tiền
        // ==============================
        if ($type === 'all' || $type === 'courses') {
            $results['courses'] = DB::table('courses')
                ->select(
                    'id', 'title', 'slug', 'description', 'price',
                    'thumbnail', 'level', 'is_featured'
                )
                ->where('status', 'published')
                ->where(function ($q) use ($query) {
                    $q->where('title', 'LIKE', "%{$query}%")
                      ->orWhere('description', 'LIKE', "%{$query}%")
                      ->orWhere('level', 'LIKE', "%{$query}%");
                })
                ->limit(5)
                ->get()
                ->map(function ($item) {
                    $item->url = '/student/khoa-hoc/' . $item->id;
                    $item->category = 'Khóa học';
                    $item->price_formatted = $this->formatPrice($item->price);
                    $item->is_free = $item->price == 0;
                    $item->image_url = $item->thumbnail
                        ? (str_starts_with($item->thumbnail, 'http') ? $item->thumbnail : asset('storage/' . $item->thumbnail))
                        : null;
                    return $item;
                });
        }

        // ==============================
        // MY COURSES - khóa học đã đăng ký
        // ==============================
        if ($type === 'all' || $type === 'my_courses') {
            $myCourses = DB::table('user_courses')
                ->select(
                    'courses.id', 'courses.title', 'courses.thumbnail',
                    'courses.level', 'courses.price',
                    'user_courses.enrolled_at'
                )
                ->join('courses', 'user_courses.course_id', '=', 'courses.id')
                ->where('user_courses.user_id', $userId)
                ->where('courses.status', 'published')
                ->where(function ($q) use ($query) {
                    $q->where('courses.title', 'LIKE', "%{$query}%")
                      ->orWhere('courses.level', 'LIKE', "%{$query}%");
                })
                ->limit(5)
                ->get()
                ->map(function ($item) {
                    $item->url = '/student/khoa-hoc/' . $item->id;
                    $item->category = 'Khóa của tôi';
                    $item->progress_percent = 0;
                    $item->image_url = $item->thumbnail
                        ? (str_starts_with($item->thumbnail, 'http') ? $item->thumbnail : asset('storage/' . $item->thumbnail))
                        : null;
                    return $item;
                });

            if ($myCourses->isNotEmpty()) {
                $results['my_courses'] = $myCourses;
            }
        }

        // ==============================
        // LESSONS
        // ==============================
        if ($type === 'all' || $type === 'lessons') {
            $results['lessons'] = DB::table('lessons')
                ->select(
                    'lessons.id', 'lessons.title', 'lessons.slug',
                    'lessons.type', 'lessons.description',
                    'chapters.title as chapter_title', 'chapters.id as chapter_id',
                    'courses.title as course_title', 'courses.id as course_id'
                )
                ->join('chapters', 'lessons.chapter_id', '=', 'chapters.id')
                ->join('courses', 'chapters.course_id', '=', 'courses.id')
                ->where('lessons.status', 'published')
                ->where('courses.status', 'published')
                ->where(function ($q) use ($query) {
                    $q->where('lessons.title', 'LIKE', "%{$query}%")
                      ->orWhere('lessons.description', 'LIKE', "%{$query}%")
                      ->orWhere('lessons.type', 'LIKE', "%{$query}%")
                      ->orWhere('chapters.title', 'LIKE', "%{$query}%")
                      ->orWhere('courses.title', 'LIKE', "%{$query}%");
                })
                ->limit(5)
                ->get()
                ->map(function ($item) {
                    $item->url = '/student/bai-hoc/' . $item->id;
                    $item->category = 'Bài học';
                    return $item;
                });
        }

        // ==============================
        // DOCUMENTS
        // ==============================
        if ($type === 'all' || $type === 'documents') {
            $results['documents'] = DB::table('documents')
                ->select('id', 'title', 'description', 'file_url', 'file_type', 'author', 'download_count')
                ->where('is_active', true)
                ->where(function ($q) use ($query) {
                    $q->where('title', 'LIKE', "%{$query}%")
                      ->orWhere('description', 'LIKE', "%{$query}%")
                      ->orWhere('author', 'LIKE', "%{$query}%");
                })
                ->limit(4)
                ->get()
                ->map(function ($item) {
                    $item->url = '/student/tai-lieu/' . $item->id;
                    $item->category = 'Tài liệu';
                    return $item;
                });
        }

        // ==============================
        // ARTICLES
        // ==============================
        if ($type === 'all' || $type === 'articles') {
            $results['articles'] = DB::table('articles')
                ->select('id', 'title', 'slug', 'summary', 'category', 'thumbnail', 'view_count', 'author')
                ->where('is_active', true)
                ->where(function ($q) use ($query) {
                    $q->where('title', 'LIKE', "%{$query}%")
                      ->orWhere('summary', 'LIKE', "%{$query}%")
                      ->orWhere('category', 'LIKE', "%{$query}%")
                      ->orWhere('content', 'LIKE', "%{$query}%");
                })
                ->limit(4)
                ->get()
                ->map(function ($item) {
                    $item->url = '/student/bai-viet/' . $item->id;
                    $item->category = 'Bài viết';
                    $item->excerpt = $item->summary;
                    $item->image_url = $item->thumbnail
                        ? (str_starts_with($item->thumbnail, 'http') ? $item->thumbnail : asset('storage/' . $item->thumbnail))
                        : null;
                    return $item;
                });
        }

        // ==============================
        // VOCABULARIES
        // ==============================
        if ($type === 'all' || $type === 'vocabularies') {
            $results['vocabularies'] = DB::table('vocabularies')
                ->select(
                    'vocabularies.id', 'vocabularies.word', 'vocabularies.pronunciation',
                    'vocabularies.meaning', 'vocabularies.example',
                    'lessons.title as lesson_title', 'lessons.id as lesson_id',
                    'courses.title as course_title'
                )
                ->join('lessons', 'vocabularies.lesson_id', '=', 'lessons.id')
                ->join('chapters', 'lessons.chapter_id', '=', 'chapters.id')
                ->join('courses', 'chapters.course_id', '=', 'courses.id')
                ->where('courses.status', 'published')
                ->where(function ($q) use ($query) {
                    $q->where('vocabularies.word', 'LIKE', "%{$query}%")
                      ->orWhere('vocabularies.pronunciation', 'LIKE', "%{$query}%")
                      ->orWhere('vocabularies.meaning', 'LIKE', "%{$query}%")
                      ->orWhere('vocabularies.example', 'LIKE', "%{$query}%");
                })
                ->limit(4)
                ->get()
                ->map(function ($item) {
                    $item->url = '/student/tu-vung/' . $item->id;
                    $item->category = 'Từ vựng';
                    $item->phonetic = $item->pronunciation;
                    return $item;
                });
        }

        // ==============================
        // GRAMMARS
        // ==============================
        if ($type === 'all' || $type === 'grammars') {
            $results['grammars'] = DB::table('grammars')
                ->select(
                    'grammars.id', 'grammars.title', 'grammars.explanation',
                    'grammars.example', 'grammars.youtube_url', 'grammars.signals',
                    'lessons.title as lesson_title', 'lessons.id as lesson_id',
                    'courses.title as course_title'
                )
                ->join('lessons', 'grammars.lesson_id', '=', 'lessons.id')
                ->join('chapters', 'lessons.chapter_id', '=', 'chapters.id')
                ->join('courses', 'chapters.course_id', '=', 'courses.id')
                ->where('courses.status', 'published')
                ->where(function ($q) use ($query) {
                    $q->where('grammars.title', 'LIKE', "%{$query}%")
                      ->orWhere('grammars.explanation', 'LIKE', "%{$query}%")
                      ->orWhere('grammars.example', 'LIKE', "%{$query}%");
                })
                ->limit(4)
                ->get()
                ->map(function ($item) {
                    $item->url = '/student/ngu-phap/' . $item->id;
                    $item->category = 'Ngữ pháp';
                    $item->structure = $item->explanation;
                    return $item;
                });
        }

        // ==============================
        // PAYMENTS - lịch sử thanh toán
        // ==============================
        if ($type === 'all' || $type === 'payments') {
            $results['payments'] = DB::table('payments')
                ->select(
                    'payments.id', 'payments.amount', 'payments.status',
                    'payments.payment_method', 'payments.transaction_id',
                    'payments.created_at', 'courses.title as course_title',
                    'courses.id as course_id', 'courses.thumbnail as course_image'
                )
                ->join('courses', 'payments.course_id', '=', 'courses.id')
                ->where('payments.user_id', $userId)
                ->where(function ($q) use ($query) {
                    $q->where('courses.title', 'LIKE', "%{$query}%")
                      ->orWhere('payments.transaction_id', 'LIKE', "%{$query}%")
                      ->orWhere('payments.payment_method', 'LIKE', "%{$query}%");
                })
                ->limit(4)
                ->get()
                ->map(function ($item) {
                    $item->url = '/student/lich-su-thanh-toan';
                    $item->category = 'Thanh toán';
                    $item->amount_formatted = $this->formatPrice($item->amount);
                    $item->date_formatted = \Carbon\Carbon::parse($item->created_at)->format('d/m/Y');
                    $item->course_image_url = $item->course_image
                        ? (str_starts_with($item->course_image, 'http') ? $item->course_image : asset('storage/' . $item->course_image))
                        : null;
                    return $item;
                });
        }

        // ==============================
        // PROGRESS - tiến độ học tập
        // ==============================
        if ($type === 'all' || $type === 'progress') {
            $results['progress'] = DB::table('lesson_progress')
                ->select(
                    'lesson_progress.id',
                    'lessons.title as lesson_title', 'lessons.id as lesson_id',
                    'lessons.type as lesson_type',
                    'courses.title as course_title', 'courses.id as course_id',
                    'chapters.title as chapter_title',
                    'lesson_progress.completed', 'lesson_progress.time_spent',
                    'lesson_progress.score', 'lesson_progress.completed_at',
                    'lesson_progress.updated_at as last_accessed'
                )
                ->join('lessons', 'lesson_progress.lesson_id', '=', 'lessons.id')
                ->join('chapters', 'lessons.chapter_id', '=', 'chapters.id')
                ->join('courses', 'chapters.course_id', '=', 'courses.id')
                ->where('lesson_progress.user_id', $userId)
                ->where(function ($q) use ($query) {
                    $q->where('lessons.title', 'LIKE', "%{$query}%")
                      ->orWhere('courses.title', 'LIKE', "%{$query}%")
                      ->orWhere('chapters.title', 'LIKE', "%{$query}%");
                })
                ->orderBy('lesson_progress.updated_at', 'desc')
                ->limit(4)
                ->get()
                ->map(function ($item) {
                    $item->url = '/student/bai-hoc/' . $item->lesson_id;
                    $item->category = 'Tiến độ';
                    $item->time_formatted = $this->formatTime($item->time_spent ?? 0);
                    $item->progress_percent = $item->completed ? 100 : (int) (($item->score ?? 0) / 10 * 100);
                    $item->last_accessed_formatted = $item->last_accessed
                        ? \Carbon\Carbon::parse($item->last_accessed)->diffForHumans()
                        : null;
                    return $item;
                });
        }

        // ==============================
        // REVIEWS - đánh giá khóa học
        // ==============================
        if ($type === 'all' || $type === 'reviews') {
            $results['reviews'] = DB::table('reviews')
                ->select(
                    'reviews.id', 'reviews.rating', 'reviews.content as comment',
                    'reviews.created_at', 'reviews.status',
                    'courses.title as course_title', 'courses.id as course_id',
                    'users.name as user_name', 'users.avatar as user_avatar'
                )
                ->join('courses', 'reviews.course_id', '=', 'courses.id')
                ->join('users', 'reviews.user_id', '=', 'users.id')
                ->where('reviews.status', 'approved')
                ->where(function ($q) use ($query) {
                    $q->where('courses.title', 'LIKE', "%{$query}%")
                      ->orWhere('reviews.content', 'LIKE', "%{$query}%")
                      ->orWhere('users.name', 'LIKE', "%{$query}%");
                })
                ->limit(3)
                ->get()
                ->map(function ($item) {
                    $item->url = '/student/khoa-hoc/' . $item->course_id;
                    $item->category = 'Đánh giá';
                    $item->user_avatar_url = $item->user_avatar
                        ? (str_starts_with($item->user_avatar, 'http') ? $item->user_avatar : asset('storage/' . $item->user_avatar))
                        : null;
                    return $item;
                });
        }

        return response()->json($results);
    }

    private function formatPrice($price)
    {
        if ($price == 0) {
            return 'Miễn phí';
        }
        return number_format((float) $price, 0, ',', '.') . ' đ';
    }

    private function formatTime($seconds)
    {
        if ($seconds < 60) {
            return $seconds . ' giây';
        }
        $minutes = floor($seconds / 60);
        if ($minutes < 60) {
            return $minutes . ' phút';
        }
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        if ($hours < 24) {
            return $hours . ' giờ ' . ($remainingMinutes > 0 ? $remainingMinutes . ' phút' : '');
        }
        $days = floor($hours / 24);
        return $days . ' ngày';
    }
}
