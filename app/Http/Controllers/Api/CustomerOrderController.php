<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;
use App\Models\Product;
use App\Models\Rating;
use Illuminate\Support\Facades\Validator;
use App\Models\Store;

class CustomerOrderController extends Controller
{
    /**
     * Get all orders for authenticated customer
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $orders = Order::where('user_id', $user->id)
            ->with(['store', 'orderItems.product']) 
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'orders' => $orders
        ]);
    }

    /**
     * Place a new order
     */
  public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'address' => 'required|string',
        'latitude' => 'required|numeric',
        'longitude' => 'required|numeric',
        'items' => 'required|array|min:1',
        'items.*.product_id' => 'nullable|exists:products,id',
        'items.*.custom_name' => 'nullable|string|max:255',
        'items.*.quantity' => 'required|integer|min:1',
        'items.*.price' => 'required|numeric|min:0',
        'total' => 'required|numeric|min:0',
        'delivery_fee' => 'required|numeric|min:0',
        'payment_method' => 'required|string',
        'phone' => 'required|string',
        'store_id' => 'required|exists:stores,id',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => $validator->errors()->first()
        ], 422);
    }

    $store = Store::find($request->store_id);
    if (!$store || !$store->latitude || !$store->longitude) {
        return response()->json([
            'message' => 'Store location is not available.'
        ], 422);
    }

    // Haversine formula
    $distance = $this->calculateDistance(
        $request->latitude,
        $request->longitude,
        $store->latitude,
        $store->longitude
    );

    if ($distance > 17) {
        return response()->json([
            'message' => 'Delivery is only available within 17 km. Your distance is ' . number_format($distance, 2) . ' km.'
        ], 422);
    }

    // Create the order...
    $user = $request->user();

    $order = Order::create([
        'user_id' => $user->id,
        'store_id' => $request->store_id,
        'address' => $request->address,
        'latitude' => $request->latitude,
        'longitude' => $request->longitude,
        'subtotal' => $request->subtotal ?? ($request->total - $request->delivery_fee),
        'delivery_fee' => $request->delivery_fee,
        'total' => $request->total,
        'payment_method' => $request->payment_method,
        'phone' => $request->phone,
        'notes' => $request->notes ?? null,
    ]);

    // Save order items
    foreach ($request->items as $item) {
        $order->orderItems()->create([
            'product_id' => $item['product_id'] ?? null,
            'custom_name' => $item['custom_name'] ?? null,
            'quantity' => $item['quantity'],
            'price' => $item['price'],
            'special_instructions' => $item['special_instructions'] ?? null,
        ]);
    }

    return response()->json([
        'success' => true,
        'order' => $order
    ], 201);
}

private function calculateDistance($lat1, $lon1, $lat2, $lon2)
{
    $earthRadius = 6371; // km
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);

    $a = sin($dLat/2) * sin($dLat/2) +
        cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
        sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));

    return $earthRadius * $c;
}


    /**
     * Show a specific order
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        $order = Order::where('id', $id)
            ->where('user_id', $user->id)
            ->with(['orderItems.product', 'store'])
            ->first();

        if (!$order) {
            return response()->json([
                'message' => 'Order not found or not authorized'
            ], 404);
        }

        // ✅ Add is_rated flag
        $order->is_rated = Rating::where('order_id', $id)->exists();

        return response()->json($order);
    }

    /**
     * Rate an order
     */
    public function rateOrder(Request $request, $orderId)
    {
        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|between:1,5',
            'review' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], 422);
        }

        $customer = Auth::user();

        $order = Order::where('id', $orderId)
            ->where('user_id', $customer->id)
            ->where('status', 'delivered')
            ->first();

        if (!$order) {
            return response()->json([
                'message' => 'Order not found or not delivered.'
            ], 404);
        }

        if (!$order->store_id) {
            return response()->json([
                'message' => 'Cannot rate: store information is missing for this order.'
            ], 400);
        }

        if (Rating::where('order_id', $orderId)->exists()) {
            return response()->json([
                'message' => 'You have already rated this order.'
            ], 400);
        }

        Rating::create([
            'order_id' => $orderId,
            'customer_id' => $customer->id,
            'store_id' => $order->store_id,
            'rating' => $request->rating,
            'review' => $request->review,
        ]);

        return response()->json([
            'message' => 'Thank you for your rating!'
        ], 200);
    }
}