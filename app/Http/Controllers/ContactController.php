<?php

namespace App\Http\Controllers;

use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|max:255',
            'phone'   => 'nullable|string|max:20',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string|max:5000',
        ], [
            'name.required'    => 'Vui lòng nhập họ và tên.',
            'email.required'   => 'Vui lòng nhập email.',
            'email.email'      => 'Email không đúng định dạng.',
            'message.required' => 'Vui lòng nhập nội dung lời nhắn.',
        ]);

        // Gắn tài khoản đang đăng nhập (nếu có) vào tin nhắn, độc lập với
        // tên/email khách tự điền trong form (khách có thể điền thông tin khác)
        $data['user_id'] = Auth::id();

        $contactMessage = ContactMessage::create($data);

        // Gửi email thông báo cho admin (không chặn luồng nếu gửi mail lỗi,
        // vì tin nhắn đã được lưu vào DB an toàn)
        try {
            $adminEmail = env('CONTACT_ADMIN_EMAIL', 'hotro@electro.vn');
            Mail::to($adminEmail)->queue(new ContactMessageReceived($contactMessage));
        } catch (\Exception $e) {
            Log::error('Contact message email failed: ' . $e->getMessage());
        }

        return back()->with('contact_success', 'Cảm ơn bạn! Tin nhắn của bạn đã được gửi thành công, chúng tôi sẽ phản hồi sớm nhất.');
    }
}