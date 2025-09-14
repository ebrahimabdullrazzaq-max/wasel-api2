<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use App\Services\NotificationService;

class EmployerController extends Controller
{
    protected $notificationService;

    public function __construct()
    {
        $this->notificationService = new NotificationService();
    }

    /**
     * Display a list of orders for the authenticated employer.
     */
    public function myOrders()
    {
        $user = Auth::user();

        if ($user->role !== 'employer') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $orders = Order::where('employer_id', $user->id)
            ->with(['user', 'store', 'orderItems.product'])
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'orders' => $orders
        ]);
    }

    /**
     * ✅ NEW: Get employer dashboard statistics
     */
    public function dashboard()
    {
        $user = Auth::user();

        if ($user->role !== 'employer') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $totalOrders = Order::where('employer_id', $user->id)->count();
        $pendingOrders = Order::where('employer_id', $user->id)
            ->where('status', 'pending')
            ->count();
        $deliveredOrders = Order::where('employer_id', $user->id)
            ->where('status', 'delivered')
            ->count();
        $todayOrders = Order::where('employer_id', $user->id)
            ->whereDate('created_at', today())
            ->count();

        return response()->json([
            'status' => true,
            'stats' => [
                'total_orders' => $totalOrders,
                'pending_orders' => $pendingOrders,
                'delivered_orders' => $deliveredOrders,
                'today_orders' => $todayOrders,
            ]
        ]);
    }

    /**
     * ✅ NEW: Accept an order assignment
     */
    public function acceptOrder(Request $request, $orderId)
    {
        $user = Auth::user();

        if ($user->role !== 'employer') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $order = Order::where('id', $orderId)->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        if ($order->employer_id && $order->employer_id != $user->id) {
            return response()->json(['message' => 'Order already assigned to another employer.'], 400);
        }

        $order->employer_id = $user->id;
        $order->status = 'accepted';
        $order->accepted_at = now();
        $order->save();

        // Send notification to admin
        $this->sendOrderAcceptedNotification($order, $user);

        return response()->json([
            'status' => true,
            'message' => 'Order accepted successfully.',
            'order' => $order
        ]);
    }

    /**
     * ✅ NEW: Update order status (for delivery progress)
     */
    public function updateOrderStatus(Request $request, $orderId)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:picked_up,on_the_way,arrived,delivered'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], 422);
        }

        $user = Auth::user();

        if ($user->role !== 'employer') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $order = Order::where('id', $orderId)
            ->where('employer_id', $user->id)
            ->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found or not assigned to you.'], 404);
        }

        $oldStatus = $order->status;
        $order->status = $request->status;
        
        // Set timestamps for specific statuses
        if ($request->status == 'picked_up') {
            $order->picked_up_at = now();
        } elseif ($request->status == 'delivered') {
            $order->delivered_at = now();
        }
        
        $order->save();

        // Send status update notification
        $this->sendDeliveryStatusNotification($order, $oldStatus, $user);

        return response()->json([
            'status' => true,
            'message' => 'Order status updated successfully.',
            'order' => $order
        ]);
    }

    /**
     * ✅ NEW: Get current active delivery
     */
    public function activeDelivery()
    {
        $user = Auth::user();

        if ($user->role !== 'employer') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $activeOrder = Order::where('employer_id', $user->id)
            ->whereIn('status', ['accepted', 'picked_up', 'on_the_way', 'arrived'])
            ->with(['user', 'store', 'orderItems.product'])
            ->first();

        return response()->json([
            'status' => true,
            'active_order' => $activeOrder
        ]);
    }

    /**
     * ✅ NEW: Update employer's current location
     */
    public function updateLocation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'order_id' => 'nullable|exists:orders,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], 422);
        }

        $user = Auth::user();

        if ($user->role !== 'employer') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        // Store employer's location (you might want to create a separate table for this)
        $user->current_lat = $request->latitude;
        $user->current_lng = $request->longitude;
        $user->location_updated_at = now();
        $user->save();

        // If order_id is provided, also update order with delivery location
        if ($request->order_id) {
            $order = Order::where('id', $request->order_id)
                ->where('employer_id', $user->id)
                ->first();

            if ($order) {
                // You might want to create a delivery_locations table to track path
                // For simplicity, we'll just update the order
                $order->delivery_current_lat = $request->latitude;
                $order->delivery_current_lng = $request->longitude;
                $order->save();
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Location updated successfully.'
        ]);
    }

    /**
     * ✅ NEW: Mark order as delivered with proof
     */
    public function markAsDelivered(Request $request, $orderId)
    {
        $validator = Validator::make($request->all(), [
            'delivery_proof' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'customer_signature' => 'nullable|string',
            'notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first()
            ], 422);
        }

        $user = Auth::user();

        if ($user->role !== 'employer') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $order = Order::where('id', $orderId)
            ->where('employer_id', $user->id)
            ->first();

        if (!$order) {
            return response()->json(['message' => 'Order not found or not assigned to you.'], 404);
        }

        // Handle delivery proof image upload
        $deliveryProofPath = null;
        if ($request->hasFile('delivery_proof')) {
            $deliveryProofPath = $request->file('delivery_proof')->store('delivery-proofs', 'public');
        }

        $order->status = 'delivered';
        $order->delivered_at = now();
        $order->delivery_proof = $deliveryProofPath;
        $order->customer_signature = $request->customer_signature;
        $order->delivery_notes = $request->notes;
        $order->save();

        // Send delivery completion notifications
        $this->sendDeliveryCompleteNotification($order, $user);

        return response()->json([
            'status' => true,
            'message' => 'Order marked as delivered successfully.',
            'order' => $order
        ]);
    }

    /**
     * ✅ NOTIFICATION: Send order accepted notification
     */
    private function sendOrderAcceptedNotification(Order $order, User $employer)
    {
        try {
            $customerName = $order->user->name ?? 'Customer';
            $storeName = $order->store->name ?? 'Store';

            $title = "Order Accepted by Delivery Person";
            $body = "Order #{$order->id} accepted by {$employer->name}";

            // Notify admin
            $this->notificationService->sendToTopic(
                'admin_notifications',
                $title,
                $body,
                [
                    'type' => 'order_accepted',
                    'order_id' => $order->id,
                    'employer_name' => $employer->name,
                    'customer_name' => $customerName,
                    'store_name' => $storeName,
                    'screen' => 'orders'
                ]
            );

            // Notify customer
            if ($order->user->fcm_token) {
                $customerTitle = "Order Accepted";
                $customerBody = "Your order #{$order->id} has been accepted and will be delivered soon";

                $this->notificationService->sendToDevice(
                    $order->user->fcm_token,
                    $customerTitle,
                    $customerBody,
                    [
                        'type' => 'order_accepted',
                        'order_id' => $order->id,
                        'employer_name' => $employer->name,
                        'screen' => 'order_tracking'
                    ]
                );
            }

        } catch (\Exception $e) {
            \Log::error('Failed to send acceptance notification: ' . $e->getMessage());
        }
    }

    /**
     * ✅ NOTIFICATION: Send delivery status update
     */
    private function sendDeliveryStatusNotification(Order $order, $oldStatus, User $employer)
    {
        try {
            $statusMessages = [
                'picked_up' => "Order #{$order->id} has been picked up from the store",
                'on_the_way' => "Order #{$order->id} is on the way to you",
                'arrived' => "Your delivery has arrived at the location",
                'delivered' => "Order #{$order->id} has been delivered successfully"
            ];

            if (isset($statusMessages[$order->status])) {
                $title = "Order Status Update";
                $body = $statusMessages[$order->status];

                // Notify customer
                if ($order->user->fcm_token) {
                    $this->notificationService->sendToDevice(
                        $order->user->fcm_token,
                        $title,
                        $body,
                        [
                            'type' => 'delivery_status_update',
                            'order_id' => $order->id,
                            'status' => $order->status,
                            'employer_name' => $employer->name,
                            'screen' => 'order_tracking'
                        ]
                    );
                }

                // Notify admin
                $this->notificationService->sendToTopic(
                    'admin_notifications',
                    "Order #{$order->id} Status Update",
                    "Status changed to: {$order->status} by {$employer->name}",
                    [
                        'type' => 'delivery_progress',
                        'order_id' => $order->id,
                        'status' => $order->status,
                        'employer_name' => $employer->name,
                        'screen' => 'orders'
                    ]
                );
            }

        } catch (\Exception $e) {
            \Log::error('Failed to send status notification: ' . $e->getMessage());
        }
    }

    /**
     * ✅ NOTIFICATION: Send delivery completion notification
     */
    private function sendDeliveryCompleteNotification(Order $order, User $employer)
    {
        try {
            $customerName = $order->user->name ?? 'Customer';

            // Notify admin
            $title = "Order Delivered";
            $body = "Order #{$order->id} has been delivered by {$employer->name}";

            $this->notificationService->sendToTopic(
                'admin_notifications',
                $title,
                $body,
                [
                    'type' => 'order_delivered',
                    'order_id' => $order->id,
                    'employer_name' => $employer->name,
                    'customer_name' => $customerName,
                    'screen' => 'orders'
                ]
            );

            // Notify store owner if they have FCM token
            if ($order->store->owner && $order->store->owner->fcm_token) {
                $storeTitle = "Order Delivered";
                $storeBody = "Order #{$order->id} has been delivered to the customer";

                $this->notificationService->sendToDevice(
                    $order->store->owner->fcm_token,
                    $storeTitle,
                    $storeBody,
                    [
                        'type' => 'store_order_delivered',
                        'order_id' => $order->id,
                        'customer_name' => $customerName,
                        'screen' => 'store_orders'
                    ]
                );
            }

        } catch (\Exception $e) {
            \Log::error('Failed to send delivery completion notification: ' . $e->getMessage());
        }
    }

    /**
     * ✅ NEW: Get employer's delivery history
     */
    public function deliveryHistory()
    {
        $user = Auth::user();

        if ($user->role !== 'employer') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $orders = Order::where('employer_id', $user->id)
            ->where('status', 'delivered')
            ->with(['user', 'store', 'orderItems.product'])
            ->orderBy('delivered_at', 'desc')
            ->paginate(20);

        return response()->json([
            'status' => true,
            'orders' => $orders
        ]);
    }

    /**
     * ✅ NEW: Get employer's performance stats
     */
    public function performanceStats()
    {
        $user = Auth::user();

        if ($user->role !== 'employer') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $totalDeliveries = Order::where('employer_id', $user->id)
            ->where('status', 'delivered')
            ->count();

        $todayDeliveries = Order::where('employer_id', $user->id)
            ->where('status', 'delivered')
            ->whereDate('delivered_at', today())
            ->count();

        $avgDeliveryTime = Order::where('employer_id', $user->id)
            ->where('status', 'delivered')
            ->whereNotNull('accepted_at')
            ->whereNotNull('delivered_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, accepted_at, delivered_at)) as avg_time')
            ->first()->avg_time ?? 0;

        $rating = $user->delivery_rating ?? 0; // Assuming you have rating system

        return response()->json([
            'status' => true,
            'stats' => [
                'total_deliveries' => $totalDeliveries,
                'today_deliveries' => $todayDeliveries,
                'avg_delivery_time' => round($avgDeliveryTime, 1),
                'rating' => $rating,
            ]
        ]);
    }
}