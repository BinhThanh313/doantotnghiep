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

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Email hoặc mật khẩu không đúng'
            ], 401);
        }

        /** @var \App\Models\User $user */
        $user  = Auth::user();
        if ($user->role !== 'admin') { 
            // Nếu không phải admin, thu hồi phiên đăng nhập vừa tạo
            Auth::logout(); 
            return response()->json([
                'message' => 'Tài khoản của bạn không có quyền truy cập trang quản trị!'
            ], 403);
        }

        // 3. Nếu là Admin, tạo token
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
}