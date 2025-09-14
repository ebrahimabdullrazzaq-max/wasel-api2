<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use App\Notifications\EmployerApprovedNotification;
use App\Notifications\EmployerRejectedNotification;
use Illuminate\Validation\ValidationException;

class AdminEmployerController extends Controller
{
    /**
     * Approve or reject an employer
     */
    public function updateStatus(Request $request, $id)
    {
        // ✅ Only allow admins
        $admin = auth()->user();
        if ($admin->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
  
        
        // ✅ Validate input
        $request->validate([
            'status' => 'required|in:approved,rejected',
        ]);

        // ✅ Find employer
        $employer = User::where('id', $id)
            ->where('role', 'employer')
            ->first();

        if (!$employer) {
            return response()->json(['error' => 'Employer not found'], 404);
        }

        // ✅ Update status
        $oldStatus = $employer->status;
        $employer->status = $request->status;
        $employer->save();

        // ✅ Send notification
        if ($request->status === 'approved' && $oldStatus !== 'approved') {
            $employer->notify(new EmployerApprovedNotification());
        } elseif ($request->status === 'rejected' && $oldStatus !== 'rejected') {
            $employer->notify(new EmployerRejectedNotification());
        }

        return response()->json([
            'success' => true,
            'message' => "Employer {$request->status} successfully.",
            'employer' => $employer,
        ]);
    }

    /**
     * Delete an employer
     */
    public function destroy($id)
    {
        // ✅ Only allow admins
        $admin = auth()->user();
        if ($admin->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // ✅ Find employer
        $employer = User::where('id', $id)
            ->where('role', 'employer')
            ->first();

        if (!$employer) {
            return response()->json(['error' => 'Employer not found'], 404);
        }

        // ✅ Delete employer
        $employer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Employer deleted successfully.',
        ]);
    }

    /**
     * List all employers (for admin dashboard)
     */
    public function index()
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $employers = User::where('role', 'employer')
            ->orderBy('created_at', 'desc')
            ->get(['id', 'name', 'email', 'phone', 'status', 'created_at']);

        return response()->json([
            'success' => true,
            'data' => $employers,
        ]);
    }
}