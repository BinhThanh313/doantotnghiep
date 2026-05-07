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
            'password' => 'required',
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Sai email hoặc mật khẩu'], 401);
        }

        $user = Auth::user();

        // Kiểm tra role admin (tuỳ bạn có cột role không)
        // if ($user->role !== 'admin') abort(403);

        $token = $user->createToken('admin-token')->plainTextToken;

        return response()->json(['token' => $token, 'user' => $user]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Đã đăng xuất']);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }
}