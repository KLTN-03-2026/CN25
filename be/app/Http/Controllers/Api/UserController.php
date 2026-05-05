<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * Lay danh sach tat ca nguoi dung
     */
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->has('role') && $request->role) {
            $query->where('role', $request->role);
        }

        if ($request->has('status')) {
            $isActive = $request->status === 'active';
            $query->where('is_active', $isActive);
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->has('gender') && $request->gender) {
            $query->where('gender', $request->gender);
        }

        $users = $query->orderBy('id', 'desc')->paginate($request->get('per_page', 15));

        $users->getCollection()->transform(fn($u) => $this->formatUser($u));

        return response()->json($users);
    }

    /**
     * Lay chi tiet mot nguoi dung
     */
    public function show($id)
    {
        $user = User::findOrFail($id);
        return response()->json($this->formatUser($user));
    }

    /**
     * Tao nguoi dung moi
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::min(6)],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', 'in:admin,student'],
            'avatar' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
            'is_email_verified' => ['boolean'],
            'birthday' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:male,female,other'],
            'country' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'timezone' => ['nullable', 'string', 'max:50'],
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['is_active'] = $validated['is_active'] ?? true;
        $validated['is_email_verified'] = $validated['is_email_verified'] ?? false;

        $user = User::create($validated);

        return response()->json([
            'message' => 'Tao nguoi dung thanh cong',
            'user' => $this->formatUser($user)
        ], 201);
    }

    /**
     * Cap nhat nguoi dung
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', 'unique:users,email,' . $id],
            'password' => ['sometimes', 'confirmed', Password::min(6)],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['sometimes', 'in:admin,student'],
            'avatar' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
            'is_email_verified' => ['boolean'],
            'birthday' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:male,female,other'],
            'country' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'timezone' => ['nullable', 'string', 'max:50'],
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'Cap nhat nguoi dung thanh cong',
            'user' => $this->formatUser($user->fresh())
        ]);
    }

    /**
     * Xoa nguoi dung
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        $currentUser = auth()->user();
        if ($currentUser && $currentUser->id == $id) {
            return response()->json(['message' => 'Khong the xoa chinh ban than'], 403);
        }

        $user->delete();

        return response()->json(['message' => 'Xoa nguoi dung thanh cong']);
    }

    /**
     * Thong ke nguoi dung
     */
    public function stats()
    {
        $stats = [
            'total' => User::count(),
            'admins' => User::where('role', 'admin')->count(),
            'students' => User::where('role', 'student')->count(),
            'active' => User::where('is_active', true)->count(),
            'inactive' => User::where('is_active', false)->count(),
            'verified' => User::where('is_email_verified', true)->count(),
            'male' => User::where('gender', 'male')->count(),
            'female' => User::where('gender', 'female')->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Format thong tin user
     */
    private function formatUser($user)
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'phone' => $user->phone,
            'avatar' => $user->avatar,
            'is_active' => (bool) $user->is_active,
            'is_email_verified' => (bool) $user->is_email_verified,
            'birthday' => $user->birthday ? $user->birthday->format('Y-m-d') : null,
            'gender' => $user->gender,
            'country' => $user->country,
            'city' => $user->city,
            'timezone' => $user->timezone,
            'last_login_at' => $user->last_login_at ? $user->last_login_at->toISOString() : null,
            'last_login_ip' => $user->last_login_ip,
            'created_at' => $user->created_at ? $user->created_at->toISOString() : null,
        ];
    }
}
