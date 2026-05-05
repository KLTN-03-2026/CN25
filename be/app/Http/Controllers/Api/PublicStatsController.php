<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicStatsController extends Controller
{
    /**
     * Lay thong ke tong quan cho landing page (public, khong can auth)
     * GET /api/public/stats
     */
    public function stats()
    {
        $totalStudents = DB::table('users')->where('role', 'student')->count();

        $totalCourses = 0;
        try {
            if (DB::getSchemaBuilder()->hasTable('courses')) {
                $totalCourses = DB::table('courses')->where('status', 'published')->count();
            }
        } catch (\Exception $e) {}

        $avgRating = 4.9;
        try {
            if (DB::getSchemaBuilder()->hasTable('reviews')) {
                $rating = DB::table('reviews')->avg('rating');
                if ($rating) {
                    $avgRating = round($rating, 1);
                }
            }
        } catch (\Exception $e) {}

        $totalCertificates = 0;
        try {
            if (DB::getSchemaBuilder()->hasTable('user_courses')) {
                $totalCertificates = DB::table('user_courses')->where('completed', true)->count();
            }
        } catch (\Exception $e) {}

        $totalRevenue = 0;
        $successCount = 0;
        $pendingCount = 0;
        $failedCount = 0;
        try {
            if (DB::getSchemaBuilder()->hasTable('payments')) {
                $totalRevenue = DB::table('payments')->where('status', 'thanh-cong')->sum('amount');
                $successCount = DB::table('payments')->where('status', 'thanh-cong')->count();
                $pendingCount = DB::table('payments')->where('status', 'cho')->count();
                $failedCount = DB::table('payments')->where('status', 'that-bai')->count();
            }
        } catch (\Exception $e) {}

        return response()->json([
            'success' => true,
            'data' => [
                'total_courses' => $totalCourses,
                'total_students' => $totalStudents,
                'avg_rating' => $avgRating,
                'total_certificates' => $totalCertificates,
                'total_revenue' => $totalRevenue,
                'success_count' => $successCount,
                'pending_count' => $pendingCount,
                'failed_count' => $failedCount,
            ]
        ]);
    }
}
