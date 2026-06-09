<?php
// app/Http/Controllers/Admin/PaymentController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\BankTransferService;
use App\Services\PaymentStateMachine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    // ✅ BankTransferService vẫn inject bình thường
    // ✅ PaymentStateMachine inject OK vì constructor giờ là private,
    //    Laravel sẽ resolve qua ::class nhưng ta chỉ dùng static methods.
    //    → Tốt hơn: KHÔNG inject FSM vào constructor, dùng static trực tiếp.
    public function __construct(
        private BankTransferService $bankService,
    ) {}

    // GET /api/admin/payments
    public function index(Request $request)
    {
        $query = Payment::with(['order:id,customer_name,customer_phone,tracking_number,status']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('method')) {
            $query->where('payment_method', strtoupper($request->input('method')));
        }
        if ($request->filled('search')) {
            $query->whereHas('order', function ($q) use ($request) {
                $q->where('tracking_number', 'like', '%' . $request->search . '%')
                  ->orWhere('customer_name',   'like', '%' . $request->search . '%')
                  ->orWhere('customer_phone',  'like', '%' . $request->search . '%');
            });
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $sortBy    = in_array($request->input('sort_by'), ['created_at', 'amount', 'status'])
                ? $request->input('sort_by') : 'created_at';
        $sortOrder = $request->input('sort_order') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortOrder);

        $payments = $query->paginate($request->get('per_page', 20));

        // ✅ Dùng static methods — không cần instance
        $payments->getCollection()->transform(function ($p) {
            $p->status_label          = PaymentStateMachine::label($p->status);
            $p->status_color          = PaymentStateMachine::color($p->status);
            $p->available_transitions = PaymentStateMachine::availableTransitions($p);
            return $p;
        });

        return response()->json($payments);
    }

    // GET /api/admin/payments/{id}
    public function show($id)
    {
        $payment = Payment::with([
            'order.items.product',
            'order.shipment.carrier',
            'order.vouchers',
        ])->findOrFail($id);

        $payment->status_label          = PaymentStateMachine::label($payment->status);
        $payment->status_color          = PaymentStateMachine::color($payment->status);
        $payment->available_transitions = PaymentStateMachine::availableTransitions($payment);

        if (strtolower($payment->payment_method) === 'bank') {
            $payment->bank_transfer_info = array_merge(
            $this->bankService->getBankInfo(),
            $this->bankService->generateQrCode($payment->order),
    );
        }

        return response()->json($payment);
    }

    // POST /api/admin/payments/{id}/verify-bank
    public function verifyBank(Request $request, $id)
    {
        $payment = Payment::with('order')->findOrFail($id);

        if (strtolower($payment->payment_method) !== 'bank') {
            return response()->json(['message' => 'Chỉ xác nhận giao dịch chuyển khoản'], 422);
        }

        $request->validate([
            'transaction_id' => 'nullable|string|max:100',
            'note'           => 'nullable|string|max:500',
        ]);

        $result = $this->bankService->adminVerify($payment, [
            'transaction_id' => $request->input('transaction_id'),
            'note'           => $request->input('note'),
            'confirmed_by'   => Auth::user()->name ?? 'admin',
        ]);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    // POST /api/admin/payments/{id}/reject-bank
    public function rejectBank(Request $request, $id)
    {
        $payment = Payment::with('order')->findOrFail($id);

        $request->validate(['reason' => 'required|string|max:500']);

       $result = $this->bankService->adminReject($payment, $request->input('reason'));

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    // POST /api/admin/payments/{id}/transition
    public function transition(Request $request, $id)
    {
        $payment = Payment::with('order')->findOrFail($id);

        $request->validate([
            'status'         => 'required|string',
            'transaction_id' => 'nullable|string|max:100',
            'reason'         => 'nullable|string|max:500',
            'refund_txn'     => 'nullable|string|max:100',
        ]);

        try {
            // ✅ Dùng factory pattern: ::for($payment)->transition(...)
            $updated = PaymentStateMachine::for($payment)->transition(
                $payment,
                $request->status,
                $request->only(['transaction_id', 'reason', 'refund_txn'])
            );

            return response()->json([
                'success' => true,
                'message' => 'Đã chuyển trạng thái thành ' . PaymentStateMachine::label($request->status),
                'payment' => $updated,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // GET /api/admin/payments/{id}/bank-info
    public function bankInfo($id)
    {
        $payment = Payment::with('order')->findOrFail($id);

        if (strtolower($payment->payment_method) !== 'bank') {
            return response()->json(['message' => 'Không phải giao dịch chuyển khoản'], 422);
        }

        return response()->json($payment->bank_transfer_info = $this->bankService->getBankInfo($payment->order));
    }

    // GET /api/admin/payments/stats
    public function stats()
    {
        $byStatus = Payment::selectRaw('status, count(*) as count, COALESCE(sum(amount),0) as total')
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn ($r) => [$r->status => [
                'count' => $r->count,
                'total' => $r->total,
                'label' => PaymentStateMachine::label($r->status),
                'color' => PaymentStateMachine::color($r->status),
            ]]);

        $byMethod = Payment::selectRaw('payment_method, count(*) as count, COALESCE(sum(amount),0) as total')
            ->groupBy('payment_method')
            ->get();

        $pendingBank = Payment::where('payment_method', 'bank')
            ->where('status', 'pending')
            ->count();

        return response()->json([
            'by_status'    => $byStatus,
            'by_method'    => $byMethod,
            'pending_bank' => $pendingBank,
        ]);
    }
}