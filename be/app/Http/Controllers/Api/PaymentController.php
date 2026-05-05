<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\UserCourse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * Lay danh sach giao dich thanh toan
     */
    public function index(Request $request)
    {
        $query = Payment::with(['user', 'course']);

        // Filter theo trang thai
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter theo phuong thuc thanh toan
        if ($request->has('method') && $request->method) {
            $query->where('payment_method', $request->method);
        }

        // Tim kiem theo ma giao dich, ten nguoi dung
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('transaction_id', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $payments = $query->orderBy('id', 'desc')->paginate($request->get('per_page', 15));

        return response()->json($payments);
    }

    /**
     * Lay danh sach yeu cau dang cho cua user hien tai
     */
    public function pendingForUser(Request $request)
    {
        $user = $request->user();

        $payments = Payment::with(['course'])
            ->where('user_id', $user->id)
            ->where('status', 'cho')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json($payments);
    }

    /**
     * Lay chi tiet mot giao dich
     */
    public function show($id)
    {
        $payment = Payment::with(['user', 'course'])->findOrFail($id);
        return response()->json($payment);
    }

    /**
     * Tao giao dich thanh toan moi (VNPay, MoMo callback)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'course_id' => ['required', 'exists:courses,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:vnpay,momo,banking'],
            'transaction_id' => ['required', 'string', 'unique:payments,transaction_id'],
            'status' => ['in:cho,thanh-cong,that-bai,hoan'],
        ]);

        $validated['status'] = $validated['status'] ?? 'cho';

        $payment = Payment::create($validated);

        return response()->json([
            'message' => 'Tao giao dich thanh cong',
            'payment' => $payment
        ], 201);
    }

    /**
     * Cap nhat trang thai thanh toan
     */
    public function update(Request $request, $id)
    {
        $payment = Payment::findOrFail($id);

        $validated = $request->validate([
            'status' => ['sometimes', 'in:cho,thanh-cong,that-bai,hoan'],
            'note' => ['nullable', 'string'],
        ]);

        $payment->update($validated);

        return response()->json([
            'message' => 'Cap nhat thanh cong',
            'payment' => $payment
        ]);
    }

    /**
     * Xoa giao dich
     */
    public function destroy($id)
    {
        $payment = Payment::findOrFail($id);
        $payment->delete();

        return response()->json(['message' => 'Xoa giao dich thanh cong']);
    }

    /**
     * Thong ke thanh toan
     */
    public function stats()
    {
        $payments = Payment::query();

        $totalRevenue = (clone $payments)->where('status', 'thanh-cong')->sum('amount');
        $successCount = (clone $payments)->where('status', 'thanh-cong')->count();
        $pendingCount = (clone $payments)->where('status', 'cho')->count();
        $failedCount = (clone $payments)->where('status', 'that-bai')->count();
        $refundedCount = (clone $payments)->where('status', 'hoan')->count();

        $stats = [
            'total_revenue' => $totalRevenue,
            'success_count' => $successCount,
            'pending_count' => $pendingCount,
            'failed_count' => $failedCount,
            'refunded_count' => $refundedCount,
            'avg_transaction' => $successCount > 0 ? round($totalRevenue / $successCount) : 0,
        ];

        return response()->json($stats);
    }

    /**
     * Tao yeu cau thanh toan thu cong (chuyen khoan ngan hang)
     */
    public function manualPayment(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'amount' => ['required', 'numeric', 'min:1000'],
            'transaction_id' => ['required', 'string', 'unique:payments,transaction_id'],
            'payment_method' => ['required', 'in:banking'],
        ]);

        $alreadyEnrolled = UserCourse::where('user_id', $user->id)
            ->where('course_id', $validated['course_id'])
            ->exists();

        if ($alreadyEnrolled) {
            return response()->json([
                'message' => 'Ban da dang ky khoa hoc nay roi',
            ], 400);
        }

        $existingPayment = Payment::where('user_id', $user->id)
            ->where('course_id', $validated['course_id'])
            ->whereIn('status', ['cho'])
            ->first();

        if ($existingPayment) {
            return response()->json([
                'message' => 'Ban da gui yeu cau thanh toan cho khoa hoc nay roi',
                'payment' => $existingPayment,
            ], 400);
        }

        $payment = Payment::create([
            'user_id' => $user->id,
            'course_id' => $validated['course_id'],
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'transaction_id' => $validated['transaction_id'],
            'status' => 'cho',
            'note' => 'Cho admin xac nhan',
        ]);

        return response()->json([
            'message' => 'Yeu cau thanh toan da duoc gui thanh cong',
            'payment' => $payment,
        ], 201);
    }

    /**
     * Admin duyet thanh toan thu cong
     */
    public function approve(Request $request, $id)
    {
        $payment = Payment::findOrFail($id);

        if ($payment->status !== 'cho') {
            return response()->json([
                'message' => 'Chi co the duyet yeu cau dang cho',
            ], 400);
        }

        $payment->update(['status' => 'thanh-cong']);

        UserCourse::firstOrCreate(
            ['user_id' => $payment->user_id, 'course_id' => $payment->course_id],
            ['enrolled_at' => now()]
        );

        return response()->json([
            'message' => 'Duyet thanh toan thanh cong',
            'payment' => $payment->fresh(),
        ]);
    }

    /**
     * Admin tu choi thanh toan thu cong
     */
    public function reject(Request $request, $id)
    {
        $validated = $request->validate([
            'note' => ['nullable', 'string'],
        ]);

        $payment = Payment::findOrFail($id);

        if ($payment->status !== 'cho') {
            return response()->json([
                'message' => 'Chi co the tu choi yeu cau dang cho',
            ], 400);
        }

        $payment->update([
            'status' => 'that-bai',
            'note' => $validated['note'] ?? 'Da bi tu choi boi admin',
        ]);

        return response()->json([
            'message' => 'Tu choi thanh toan thanh cong',
            'payment' => $payment->fresh(),
        ]);
    }
}
