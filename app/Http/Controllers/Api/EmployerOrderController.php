<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Notifications\OrderStatusUpdated;

class EmployerOrderController extends Controller
{
    // ✅ عرض الطلبات المعينة للموظف الحالي
    public function index(Request $request)
    {
        $orders = Order::with(['orderItems.product', 'user'])
                    ->where('employer_id', $request->user()->id)
                    ->latest()
                    ->get();

        return response()->json($orders);
    }

    // ✅ تحديث حالة الطلب من قبل الموظف
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,preparing,on_the_way,delivered,cancelled'
        ]);

        $order = Order::where('id', $id)
                    ->where('employer_id', $request->user()->id)
                    ->first();

        if (!$order) {
            return response()->json(['error' => 'Order not found or not assigned to you'], 404);
        }

        $order->status = $request->status;
        $order->save();

        // إشعار العميل (اختياري)
        $order->user->notify(new OrderStatusUpdated($order));

        return response()->json([
            'message' => 'Order status updated',
            'order' => $order
        ]);
    }
}
