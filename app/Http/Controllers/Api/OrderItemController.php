<?php

namespace App\Http\Controllers\Api;

use App\Models\OrderItem;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;


class OrderItemController extends Controller
{
    // ✅ Get all order items
    public function index()
    {
        return response()->json(OrderItem::with('product')->get());
    }

    // ✅ Get single order item
    public function show($id)
    {
        $orderItem = OrderItem::with('product')->findOrFail($id);
        return response()->json($orderItem);
    }

    // ✅ Update an order item
    public function update(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $orderItem = OrderItem::findOrFail($id);
        $orderItem->quantity = $request->quantity;
        $orderItem->save();

        return response()->json([
            'message' => 'Order item updated successfully',
            'order_item' => $orderItem,
        ]);
    }

    // ✅ Delete an order item
    public function destroy($id)
    {
        $orderItem = OrderItem::findOrFail($id);
        $orderItem->delete();

        return response()->json([
            'message' => 'Order item deleted successfully'
        ]);
    }
}
