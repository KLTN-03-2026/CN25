<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Payment;
use App\Models\UserCourse;
use App\Services\MoMoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MoMoController extends Controller
{
    private MoMoService $momoService;

    public function __construct(MoMoService $momoService)
    {
        $this->momoService = $momoService;
    }

    /**
     * Tạo yêu cầu thanh toán MoMo
     * POST /api/momo/create
     */
    public function createPayment(Request $request)
    {
        $validated = $request->validate([
            'course_id' => ['required', 'integer', 'exists:courses,id'],
        ]);

        $user = $request->user();
        $course = Course::findOrFail($validated['course_id']);

        // Kiểm tra đã mua chưa
        $alreadyEnrolled = UserCourse::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->exists();

        if ($alreadyEnrolled) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn đã đăng ký khóa học này rồi',
            ], 400);
        }

        // Kiểm tra khóa học miễn phí
        if ($course->price == 0) {
            // Khóa học miễn phí → tự động đăng ký luôn
            UserCourse::create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'enrolled_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Đăng ký khóa học miễn phí thành công',
                'enrolled' => true,
                'course' => $course,
            ]);
        }

        // Tạo mã đơn hàng
        $orderId = 'MOMO_' . $user->id . '_' . $course->id . '_' . time();

        // Tạo Payment record (status = 'cho')
        $payment = Payment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'amount' => $course->price,
            'payment_method' => 'momo',
            'transaction_id' => $orderId,
            'status' => 'cho',
        ]);

        // Tạo yêu cầu thanh toán MoMo
        $orderInfo = "Thanh toan khoa hoc: {$course->title}";
        $momoResponse = $this->momoService->createPayment(
            $orderId,
            (int) $course->price,
            $orderInfo,
            (string) $user->id
        );

        if (!$momoResponse['success']) {
            // Cập nhật payment thành failed
            $payment->update(['status' => 'that-bai', 'note' => $momoResponse['message']]);

            return response()->json([
                'success' => false,
                'message' => $momoResponse['message'],
                'resultCode' => $momoResponse['resultCode'] ?? null,
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tạo yêu cầu thanh toán thành công',
            'payUrl' => $momoResponse['payUrl'],
            'deeplink' => $momoResponse['deeplink'],
            'qrCodeUrl' => $momoResponse['qrCodeUrl'],
            'orderId' => $orderId,
            'payment' => $payment,
        ]);
    }

    /**
     * IPN - MoMo gọi về server khi thanh toán xong (phía server)
     * POST /api/momo/ipn
     */
    public function ipn(Request $request)
    {
        $data = $request->all();

        Log::info('MoMo IPN received', $data);

        // Xử lý IPN
        $result = $this->momoService->handleIpn($data);

        // Tìm payment record
        $orderId = $data['orderId'] ?? null;
        $payment = Payment::where('transaction_id', $orderId)->first();

        if (!$payment) {
            Log::error('MoMo IPN: Payment not found', ['orderId' => $orderId]);
            return response()->json(['message' => 'Payment not found'], 404);
        }

        // Nếu đã xử lý rồi thì bỏ qua (idempotent)
        if ($payment->status === 'thanh-cong') {
            return response()->json(['message' => 'Already processed'], 200);
        }

        // Cập nhật trạng thái payment
        $payment->update([
            'status' => $result['status'],
            'note' => $result['message'] ?? null,
        ]);

        // Nếu thành công → thêm vào user_courses
        if ($result['success']) {
            $this->enrollUserToCourse($payment);
        }

        // MoMo yêu cầu trả về HTTP 200
        return response()->json(['message' => 'OK'], 200);
    }

    /**
     * Redirect - User quay về sau khi thanh toán trên MoMo
     * GET /api/momo/return
     */
    public function return(Request $request)
    {
        $params = $request->all();
        $result = $this->momoService->handleRedirect($params);

        $orderId = $params['orderId'] ?? null;
        $payment = Payment::where('transaction_id', $orderId)->first();

        if ($payment) {
            // Cập nhật payment nếu chưa được IPN cập nhật
            if ($payment->status === 'cho') {
                $payment->update([
                    'status' => $result['status'],
                    'note' => $result['message'] ?? null,
                ]);

                if ($result['success']) {
                    $this->enrollUserToCourse($payment);
                }
            }

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'status' => $result['status'],
                'orderId' => $orderId,
                'course_id' => $payment->course_id,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Không tìm thấy đơn hàng',
            'status' => 'that-bai',
        ], 404);
    }

    /**
     * Kiểm tra trạng thái thanh toán
     * GET /api/momo/status/{orderId}
     */
    public function checkStatus($orderId)
    {
        $payment = Payment::with('course')->where('transaction_id', $orderId)->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy đơn hàng',
            ], 404);
        }

        return response()->json([
            'success' => $payment->status === 'thanh-cong',
            'status' => $payment->status,
            'payment' => $payment,
            'enrolled' => UserCourse::where('user_id', $payment->user_id)
                ->where('course_id', $payment->course_id)
                ->exists(),
        ]);
    }

    /**
     * Thêm user vào khóa học (enroll)
     */
    private function enrollUserToCourse(Payment $payment): void
    {
        $exists = UserCourse::where('user_id', $payment->user_id)
            ->where('course_id', $payment->course_id)
            ->exists();

        if (!$exists) {
            UserCourse::create([
                'user_id' => $payment->user_id,
                'course_id' => $payment->course_id,
                'enrolled_at' => now(),
            ]);

            Log::info('User enrolled to course via MoMo payment', [
                'user_id' => $payment->user_id,
                'course_id' => $payment->course_id,
                'payment_id' => $payment->id,
            ]);
        }
    }
}
