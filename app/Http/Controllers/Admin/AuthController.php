<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        // QUAN TRỌNG: dùng Auth::validate() thay vì Auth::attempt() — chỉ
        // kiểm tra đúng email/mật khẩu, KHÔNG đăng nhập session 'web'.
        // Admin panel là SPA thuần token (Sanctum Bearer token), tách biệt
        // hoàn toàn với session 'web' mà storefront khách hàng đang dùng.
        // Trước đây dùng Auth::attempt() vô tình ghi đè session 'web' của
        // trình duyệt sang admin, khiến khách đang đăng nhập ở storefront
        // bị "biến thành" admin khi F5 lại trang.
        if (!Auth::validate($credentials)) {
            return response()->json([
                'message' => 'Email hoặc mật khẩu không đúng'
            ], 401);
        }

        /** @var \App\Models\User $user */
        $user = \App\Models\User::where('email', $credentials['email'])->first();

        if ($user->role !== 'admin') {
            return response()->json([
                'message' => 'Tài khoản của bạn không có quyền truy cập trang quản trị!'
            ], 403);
        }

        $token = $user->createToken('admin-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Đăng xuất thành công']);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
        ]);

        $user->update($data);

        return response()->json([
            'message' => 'Cập nhật thông tin thành công',
            'user'    => $user->fresh(),
        ]);
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'current_password'      => 'required|string',
            'password'              => 'required|string|min:8|confirmed',
        ]);

        if (!\Illuminate\Support\Facades\Hash::check($data['current_password'], $user->password)) {
            return response()->json(['message' => 'Mật khẩu hiện tại không đúng'], 422);
        }

        $user->update([
            'password' => \Illuminate\Support\Facades\Hash::make($data['password']),
        ]);

        return response()->json(['message' => 'Đổi mật khẩu thành công']);
    }
}