<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Order;

class AdminController extends Controller
{
    /**
     * List all users (customers, employers, admins) with optional filters
     */
    public function listUsers(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = User::orderBy('created_at', 'desc');

        // Filter for recent users (last 24 hours)
        if ($request->has('recent') && $request->recent == 'true') {
            $query->where('created_at', '>=', now()->subDay());
        }

        // Filter by role if specified
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        $users = $query->get(['id', 'name', 'email', 'phone', 'address', 'role', 'status', 'created_at']);

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    /**
     * Show a specific user by ID
     */
    public function showUser($id)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $user = User::where('id', $id)
            ->first(['id', 'name', 'email', 'phone', 'address', 'role', 'status', 'created_at']);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    /**
     * List all employers (users with role = 'employer') with optional status filter
     */
    public function listEmployers(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = User::where('role', 'employer')->orderBy('created_at', 'desc');

        // Filter by status if specified
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $employers = $query->get(['id', 'name', 'email', 'phone', 'address', 'status', 'created_at']);

        return response()->json([
            'success' => true,
            'data' => $employers,
        ]);
    }

    /**
     * List orders with optional status filter
     */
    public function listOrders(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = Order::orderBy('created_at', 'desc');

        // Filter by status if specified
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->get();

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }

    /**
     * Approve an employer
     */
    public function approveEmployer($id)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $user = User::where('id', $id)->where('role', 'employer')->first();

        if (!$user) {
            return response()->json(['error' => 'Employer not found'], 404);
        }

        $user->status = 'approved';
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Employer approved successfully.',
            'data' => $user,
        ]);
    }

    /**
     * Reject an employer
     */
    public function rejectEmployer($id)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $user = User::where('id', $id)->where('role', 'employer')->first();

        if (!$user) {
            return response()->json(['error' => 'Employer not found'], 404);
        }

        $user->status = 'rejected';
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Employer rejected successfully.',
            'data' => $user,
        ]);
    }

    public function deleteUser($id)
{
    try {
        $user = User::findOrFail($id);
        $user->delete();
        
        return response()->json([
            'message' => 'User deleted successfully'
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Error deleting user: ' . $e->getMessage()
        ], 500);
    }
}

public function newRegistrations(Request $request)
{
    try {
        // Get users registered in the last 24 hours
        $newUsers = User::where('created_at', '>=', now()->subDays(1))
            ->orderBy('created_at', 'desc')
            ->get();
            
        return response()->json([
            'data' => $newUsers
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'Error fetching new registrations: ' . $e->getMessage()
        ], 500);
    }
}


}