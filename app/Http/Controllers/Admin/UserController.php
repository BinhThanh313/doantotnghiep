<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Hỗ trợ tìm kiếm theo tên hoặc email
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
        }

        $users = $query->latest()->paginate(15);

        return response()->json($users);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            // 'role'  => 'nullable|string', // Bỏ comment nếu DB của bạn có trường role
        ]);

        // Mã hóa password trước khi lưu
        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        return response()->json($user, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::findOrFail($id);
        
        return response()->json($user);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        // Ghi log ra file debug.txt
        file_put_contents(storage_path('logs/debug.txt'), 
            "Method: " . $request->method() . "\n" .
            "Payload: " . json_encode($request->all()) . "\n" .
            "Time: " . now() . "\n\n", 
            FILE_APPEND
        );

        $data = $request->validate([
            'name'     => 'sometimes|string|max:255',
            'email'    => 'sometimes|string|email|max:255|unique:users,email,' . $id,
            'password' => 'nullable|string|min:8',
            // 'role'  => 'nullable|string',
        ]);

        \Illuminate\Support\Facades\Log::info('User Update Triggered for ID: ' . $id, $request->all());

        // Bỏ qua fillable, gán trực tiếp để đảm bảo luôn lưu được
        if ($request->has('name')) {
            $user->name = $request->input('name');
        }
        if ($request->has('email')) {
            $user->email = $request->input('email');
        }
        if ($request->filled('password')) {
            $user->password = Hash::make($request->input('password'));
        }

        \Illuminate\Support\Facades\Log::info('Saving user with new name: ' . $user->name);
        $user->save();
        \Illuminate\Support\Facades\Log::info('Saved user. DB name is now: ' . $user->fresh()->name);

        return response()->json($user);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        // Chặn tự xóa chính mình — tránh admin đang đăng nhập lỡ tay tự khóa
        // mình khỏi hệ thống (token vẫn còn hiệu lực nhưng tài khoản đã mất).
        if ($request->user() && (int) $request->user()->id === (int) $id) {
            return response()->json(['message' => 'Không thể tự xóa tài khoản của chính mình'], 422);
        }

        // Chặn xóa admin cuối cùng — tránh trường hợp không còn ai truy cập
        // được trang quản trị sau khi xóa.
        if ($user->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
            return response()->json(['message' => 'Không thể xóa tài khoản admin cuối cùng'], 422);
        }

        $user->delete();

        return response()->json(['message' => 'Đã xóa người dùng']);
    }
}