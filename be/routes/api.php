<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\ChapterController;
use App\Http\Controllers\Api\LessonController;
use App\Http\Controllers\Api\VocabularyController;
use App\Http\Controllers\Api\GrammarController;
use App\Http\Controllers\Api\ListeningController;
use App\Http\Controllers\Api\ListeningExerciseController;
use App\Http\Controllers\Api\SpeakingExerciseController;
use App\Http\Controllers\Api\LessonQuizController;
use App\Http\Controllers\Api\CourseQuizController;
use App\Http\Controllers\Api\CourseQuizQuestionController;
use App\Http\Controllers\Api\ProgressController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\ResultController;
use App\Http\Controllers\Api\ContentController;
use App\Http\Controllers\Api\ExerciseController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\PublicStatsController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\ChatbotController;
use App\Http\Controllers\Api\SearchController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// =============================================
// AUTH ROUTES (PUBLIC)
// =============================================
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
});

// =============================================
// PUBLIC ROUTES (Không cần đăng nhập)
// =============================================

// Public Stats - Thống kê tổng quan cho landing page
Route::get('/public/stats', [PublicStatsController::class, 'stats']);

// Search - Tìm kiếm toàn hệ thống (public)
Route::get('/search', [SearchController::class, 'search']);

// Courses - Xem danh sách và chi tiết khóa học
Route::get('/courses', [CourseController::class, 'index']);
Route::get('/courses/slug/{slug}', [CourseController::class, 'showBySlug']);
Route::get('/courses/{id}', [CourseController::class, 'show']);

// Chapters - Xem danh sách chương và bài học
Route::get('/courses/{courseId}/chapters', [ChapterController::class, 'indexByCourse']);
Route::get('/chapters/{id}', [ChapterController::class, 'show']);

// Lessons - Xem chi tiết bài học
Route::get('/lessons/{id}', [LessonController::class, 'show']);

// Chapters Lessons - Xem lessons theo chapter (không cần đăng nhập)
Route::get('/chapters/{chapterId}/lessons', [LessonController::class, 'index']);

// Articles - Xem danh sách và chi tiết bài viết (public)
Route::get('/articles', [ArticleController::class, 'index']);
Route::get('/articles/latest', [ArticleController::class, 'latest']);
Route::get('/articles/{id}', [ArticleController::class, 'show']);

// Reviews - Xem reviews cua khoa hoc (public - chi hien thi da duyet)
Route::get('/courses/{courseId}/reviews', [ReviewController::class, 'getByCourse']);

// Reviews - Lay reviews noi bat cho trang chu (public)
Route::get('/reviews/featured', [ReviewController::class, 'getFeatured']);

// =============================================
// PROTECTED ROUTES - STUDENT (Cần đăng nhập)
// =============================================
Route::middleware('auth:sanctum')->group(function () {

    // ---------- Auth ----------
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::put('/change-password', [AuthController::class, 'changePassword']);
        Route::post('/upload-avatar', [AuthController::class, 'uploadAvatar']);
    });

    // ---------- Progress Tracking (Student) ----------
    Route::prefix('progress')->group(function () {
        Route::get('/dashboard', [ProgressController::class, 'getDashboard']);
        Route::get('/stats', [ProgressController::class, 'getStats']);
        Route::get('/my-courses', [ProgressController::class, 'myCourses']);
        Route::get('/course/{courseId}', [ProgressController::class, 'getCourseProgress']);
        Route::post('/enroll/{courseId}', [ProgressController::class, 'enroll']);
        Route::post('/lesson/{lessonId}/start', [ProgressController::class, 'startLesson']);
        Route::post('/lesson/{lessonId}/complete', [ProgressController::class, 'completeLesson']);
        Route::get('/lesson/{lessonId}', [ProgressController::class, 'getLessonProgress']);
        Route::post('/exercise-score', [ProgressController::class, 'saveExerciseScore']);
        Route::post('/submit-final', [ProgressController::class, 'submitFinal']);
        Route::get('/all', [ProgressController::class, 'getAllProgress']);
        Route::get('/history', [ProgressController::class, 'getHistory']);
    });

    // ---------- Vocabulary (View) ----------
    Route::get('/lessons/{lessonId}/vocabularies', [VocabularyController::class, 'getByLesson']);

    // ---------- Grammar (View) ----------
    Route::get('/lessons/{lessonId}/grammars', [GrammarController::class, 'getByLesson']);

    // ---------- Listening (View) ----------
    Route::get('/lessons/{lessonId}/listenings', [ListeningController::class, 'getByLesson']);

    // ---------- Listening Exercise (View) ----------
    Route::get('/listenings/{listeningId}/exercises', [ListeningExerciseController::class, 'getByListening']);

    // ---------- Speaking Exercise (View) ----------
    Route::get('/lessons/{lessonId}/speaking-exercises', [SpeakingExerciseController::class, 'getByLesson']);

    // ---------- Quiz (View) ----------
    Route::get('/courses/{courseId}/quizzes', [LessonQuizController::class, 'getByCourse']);
    Route::get('/lessons/{lessonId}/quizzes', [LessonQuizController::class, 'getByLesson']);
    Route::get('/courses/{courseId}/course-quiz', [CourseQuizController::class, 'show']);

    // ---------- Reviews (Student CRUD) ----------
    Route::get('/reviews/my-review/{courseId}', [ReviewController::class, 'getMyReview']);
    Route::post('/reviews', [ReviewController::class, 'store']);
    Route::put('/reviews/{id}', [ReviewController::class, 'update']);
    Route::delete('/reviews/{id}', [ReviewController::class, 'destroy']);

    // ---------- Progress & Result (Student view) ----------
    Route::get('/results', [ResultController::class, 'index']);
    Route::get('/results/{id}', [ResultController::class, 'show']);
    Route::post('/results', [ResultController::class, 'store']);

    // ---------- Payment (Student) ----------
    Route::get('/payments', [PaymentController::class, 'index']);
    Route::get('/payments/{id}', [PaymentController::class, 'show']);
    Route::post('/payments', [PaymentController::class, 'store']);
    Route::post('/payments/manual', [PaymentController::class, 'manualPayment']);
    Route::get('/payments/pending', [PaymentController::class, 'pendingForUser']);

    // ---------- MoMo Payment Gateway ----------
    // Route::prefix('momo')->group(function () {
    //     Route::post('/create', [MoMoController::class, 'createPayment']);
    //     Route::post('/ipn', [MoMoController::class, 'ipn']);
    //     Route::get('/return', [MoMoController::class, 'return']);
    //     Route::get('/status/{orderId}', [MoMoController::class, 'checkStatus']);
    // });

    // ---------- Speaking Evaluation (Student) ----------
    Route::post('/speaking-exercises/{id}/evaluate', [SpeakingExerciseController::class, 'evaluate']);

    // ---------- Articles (Student View) ----------
    Route::get('/student/articles', [ArticleController::class, 'index']);
    Route::get('/student/articles/{id}', [ArticleController::class, 'show']);

    // ---------- Documents (Student View) ----------
    Route::get('/student/documents', [DocumentController::class, 'index']);
    Route::get('/student/documents/{id}', [DocumentController::class, 'show']);

    // ---------- Chatbot (Student) ----------
    Route::prefix('chatbot')->group(function () {
        Route::post('/chat', [ChatbotController::class, 'chat']);
        Route::get('/history', [ChatbotController::class, 'history']);
        Route::delete('/history', [ChatbotController::class, 'clearHistory']);
        Route::get('/status', [ChatbotController::class, 'checkStatus']);
    });
});

// =============================================
// ADMIN ONLY ROUTES - Cần role: admin
// =============================================
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {

    // ---------- Course Admin CRUD ----------
    Route::post('/courses', [CourseController::class, 'store']);
    Route::put('/courses/{id}', [CourseController::class, 'update']);
    Route::delete('/courses/{id}', [CourseController::class, 'destroy']);
    Route::post('/courses/{id}/generate-chapters', [CourseController::class, 'generateChapters']);
    Route::post('/courses/{id}/change-status', [CourseController::class, 'changeStatus']);

    // ---------- Chapter Admin CRUD ----------
    Route::put('/chapters/{id}', [ChapterController::class, 'update']);

    // ---------- Lesson Admin CRUD ----------
    Route::post('/chapters/{chapterId}/lessons', [LessonController::class, 'store']);
    Route::put('/lessons/{id}', [LessonController::class, 'update']);
    Route::delete('/lessons/{id}', [LessonController::class, 'destroy']);
    Route::post('/lessons/{id}/change-status', [LessonController::class, 'changeStatus']);

    // ---------- Vocabulary Admin CRUD ----------
    Route::post('/lessons/{lessonId}/vocabularies', [VocabularyController::class, 'store']);
    Route::put('/vocabularies/{id}', [VocabularyController::class, 'update']);
    Route::delete('/vocabularies/{id}', [VocabularyController::class, 'destroy']);

    // ---------- Grammar Admin CRUD ----------
    Route::post('/lessons/{lessonId}/grammars', [GrammarController::class, 'store']);
    Route::put('/grammars/{id}', [GrammarController::class, 'update']);
    Route::delete('/grammars/{id}', [GrammarController::class, 'destroy']);

    // ---------- Listening Admin CRUD ----------
    Route::post('/lessons/{lessonId}/listenings', [ListeningController::class, 'store']);
    Route::put('/listenings/{id}', [ListeningController::class, 'update']);
    Route::delete('/listenings/{id}', [ListeningController::class, 'destroy']);

    // ---------- Listening Exercise Admin CRUD ----------
    Route::post('/listenings/{listeningId}/exercises', [ListeningExerciseController::class, 'store']);
    Route::put('/listening-exercises/{id}', [ListeningExerciseController::class, 'update']);
    Route::delete('/listening-exercises/{id}', [ListeningExerciseController::class, 'destroy']);

    // ---------- Speaking Exercise Admin CRUD ----------
    Route::post('/lessons/{lessonId}/speaking-exercises', [SpeakingExerciseController::class, 'store']);
    Route::put('/speaking-exercises/{id}', [SpeakingExerciseController::class, 'update']);
    Route::delete('/speaking-exercises/{id}', [SpeakingExerciseController::class, 'destroy']);

    // ---------- Lesson Quiz Admin CRUD ----------
    Route::post('/courses/{courseId}/quizzes', [LessonQuizController::class, 'store']);
    Route::put('/lesson-quizzes/{id}', [LessonQuizController::class, 'update']);
    Route::delete('/lesson-quizzes/{id}', [LessonQuizController::class, 'destroy']);

    // ---------- Course Quiz Admin CRUD ----------
    Route::post('/courses/{courseId}/course-quiz', [CourseQuizController::class, 'store']);
    Route::put('/course-quizzes/{id}', [CourseQuizController::class, 'update']);
    Route::delete('/course-quizzes/{id}', [CourseQuizController::class, 'destroy']);

    // ---------- Course Quiz Question Admin CRUD ----------
    Route::post('/course-quizzes/{quizId}/questions', [CourseQuizQuestionController::class, 'store']);
    Route::put('/course-quiz-questions/{id}', [CourseQuizQuestionController::class, 'update']);
    Route::delete('/course-quiz-questions/{id}', [CourseQuizQuestionController::class, 'destroy']);

    // ---------- User Management ----------
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/stats', [UserController::class, 'stats']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);

    // ---------- Payment Management ----------
    Route::get('/payments/stats', [PaymentController::class, 'stats']);
    Route::put('/payments/{id}', [PaymentController::class, 'update']);
    Route::delete('/payments/{id}', [PaymentController::class, 'destroy']);
    Route::post('/payments/{id}/approve', [PaymentController::class, 'approve']);
    Route::post('/payments/{id}/reject', [PaymentController::class, 'reject']);

    // ---------- Review Management ----------
    Route::get('/reviews', [ReviewController::class, 'index']);
    Route::get('/reviews/stats', [ReviewController::class, 'stats']);
    Route::get('/reviews/{id}', [ReviewController::class, 'show']);
    Route::post('/reviews/{id}/reply', [ReviewController::class, 'reply']);
    Route::post('/reviews/{id}/approve', [ReviewController::class, 'approve']);
    Route::post('/reviews/{id}/hide', [ReviewController::class, 'hide']);

    // ---------- Result Management ----------
    Route::get('/results/stats', [ResultController::class, 'stats']);
    Route::put('/results/{id}', [ResultController::class, 'update']);
    Route::delete('/results/{id}', [ResultController::class, 'destroy']);

    // ---------- Content Management ----------
    Route::get('/contents', [ContentController::class, 'index']);
    Route::get('/contents/{id}', [ContentController::class, 'show']);
    Route::post('/contents', [ContentController::class, 'store']);
    Route::put('/contents/{id}', [ContentController::class, 'update']);
    Route::delete('/contents/{id}', [ContentController::class, 'destroy']);

    // ---------- Exercise Management ----------
    Route::get('/exercises', [ExerciseController::class, 'index']);
    Route::get('/exercises/{id}', [ExerciseController::class, 'show']);
    Route::post('/exercises', [ExerciseController::class, 'store']);
    Route::put('/exercises/{id}', [ExerciseController::class, 'update']);
    Route::delete('/exercises/{id}', [ExerciseController::class, 'destroy']);

    // ---------- Settings ----------
    Route::get('/settings', [SettingController::class, 'index']);
    Route::post('/settings', [SettingController::class, 'store']);
    Route::get('/settings/{key}', [SettingController::class, 'show']);
    Route::put('/settings/{key}', [SettingController::class, 'update']);

    // ---------- Document Admin CRUD ----------
    Route::get('/documents', [DocumentController::class, 'index']);
    Route::post('/documents', [DocumentController::class, 'store']);
    Route::put('/documents/{id}', [DocumentController::class, 'update']);
    Route::delete('/documents/{id}', [DocumentController::class, 'destroy']);

    // ---------- Article Admin CRUD ----------
    Route::get('/articles', [ArticleController::class, 'index']);
    Route::post('/articles', [ArticleController::class, 'store']);
    Route::put('/articles/{id}', [ArticleController::class, 'update']);
    Route::delete('/articles/{id}', [ArticleController::class, 'destroy']);
});
