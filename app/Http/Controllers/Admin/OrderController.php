<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with('items');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->latest()->paginate(15);
        return response()->json($orders);
    }

    public function show($id)
    {
        return response()->json(
            Order::with('items')->findOrFail($id)
        );
    }

    public function update(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $data = $request->validate([
            'status' => 'required|in:pending,processing,completed,cancelled',
        ]);

        $order->update($data);
        return response()->json($order);
    }

    public function destroy($id)
    {
        $order = Order::findOrFail($id);
        
        // Tùy thuộc vào thiết kế DB, nếu bạn chưa thiết lập khóa ngoại cascade (onDelete('cascade'))
        // thì cần phải xóa các order_items trước
        $order->items()->delete(); 
        
        $order->delete();

        return response()->json(['message' => 'Đã xóa đơn hàng thành công']);
    }
}