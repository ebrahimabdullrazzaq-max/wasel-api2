<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Notifications\EmployerAssignedNotification;

class AdminOrderController extends Controller
{
    /**
     * Display a filtered list of orders
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status'     => 'sometimes|in:pending,confirmed,preparing,on_the_way,delivered,cancelled',
            'user_id'    => 'sometimes|exists:users,id',
            'from_date'  => 'sometimes|date_format:Y-m-d',
            'to_date'    => 'sometimes|date_format:Y-m-d|after_or_equal:from_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        $query = Order::with(['orderItems.product', 'user', 'employer', 'store']);

        foreach ($validator->validated() as $key => $value) {
            match ($key) {
                'status'    => $query->where('status', $value),
                'user_id'   => $query->where('user_id', $value),
                'from_date' => $query->whereDate('created_at', '>=', $value),
                'to_date'   => $query->whereDate('created_at', '<=', $value),
                default     => null,
            };
        }

        $orders = $query->latest()->get();

        return response()->json([
            'success' => true,
            'data'    => $orders
        ]);
    }

    /**
     * Assign an employer to an order
     */
    public function assignEmployer(Request $request, $id)
    {
        $request->validate([
            'employer_id' => 'required|exists:users,id'
        ]);

        $order    = Order::findOrFail($id);
        $employer = User::findOrFail($request->employer_id);

        if ($employer->role !== 'employer') {
            return response()->json([
                'success' => false,
                'message' => 'Selected user is not an employer'
            ], 422);
        }

        $order->employer_id = $employer->id;
        $order->assigned_at = now();
        $order->save();

        // Send notification to employer
        $employer->notify(new EmployerAssignedNotification($order));

        return response()->json([
            'success' => true,
            'message' => 'Employer assigned successfully',
            'order'   => $order->load('employer')
        ]);
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:pending,confirmed,preparing,on_the_way,delivered,cancelled'
        ]);

        $order      = Order::with(['user', 'orderItems.product'])->findOrFail($id);
        $oldStatus  = $order->status;
        $order->status = $request->status;
        $order->save();

        // Optional: Notify customer (if you add a notification class)
        // if ($order->user) {
        //     $order->user->notify(new OrderStatusUpdatedNotification($order, $oldStatus));
        // }

        return response()->json([
            'success' => true,
            'message' => 'Order status updated successfully',
            'order'   => $order
        ]);
    }

    /**
     * Delete an order
     */
    public function destroy($id)
    {
        $order = Order::find($id);
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        $order->delete();

        return response()->json([
            'success' => true,
            'message' => 'Order deleted successfully'
        ]);
    }

    /**
     * Get details of a specific order
     */
    public function show($id)
    {
        $order = Order::with(['user', 'store', 'orderItems.product', 'employer'])
            ->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'order'   => $order
        ]);
    }

    /**
     * Get updated orders since timestamp
     */
    public function getOrderUpdates(Request $request)
    {
        $since = $request->query('since');

        $query = Order::with(['store', 'user', 'employer'])
            ->orderBy('updated_at', 'desc');

        if ($since) {
            $query->where('updated_at', '>=', $since);
        }

        return response()->json([
            'success' => true,
            'data'    => $query->get()
        ]);
    }

    /**
     * Get admin notifications (pending orders, employers, users)
     */
    public function getAdminNotifications(Request $request)
    {
        $admin = Auth::user();

        if (!$admin || $admin->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $pendingOrders = Order::where('status', 'pending')
            ->with(['store', 'user'])
            ->orderBy('created_at', 'desc')
            ->get();

        $pendingEmployers = User::where('role', 'employer')
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        $recentUsers = User::where('created_at', '>=', now()->subDay())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success'       => true,
            'notifications' => [
                'orders'    => $pendingOrders,
                'employers' => $pendingEmployers,
                'users'     => $recentUsers,
            ]
        ]);
    }
}
