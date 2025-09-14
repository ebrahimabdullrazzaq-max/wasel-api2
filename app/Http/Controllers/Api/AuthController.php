<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Notification;
use App\Notifications\NewEmployerRegistered;
use App\Notifications\EmployerApproved;
use App\Notifications\EmployerRejected;
use App\Notifications\EmployerPendingApprovalNotification;
use Illuminate\Support\Facades\Cache; // ✅ For OTP storage
use Twilio\Rest\Client as TwilioClient; // ✅ Twilio for WhatsApp
use Illuminate\Support\Facades\Auth; // ✅ Add this line

class AuthController extends Controller
{
    /**
     * Send OTP via WhatsApp (Twilio)
     */
    public function sendOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|max:15',
        ]);

        $phone = $request->phone;
        $otp = rand(100000, 999999);

        // ✅ Send OTP via WhatsApp
        $this->sendWhatsApp($phone, "Your verification code is: $otp");

        // ✅ Store OTP in cache for 10 minutes
        Cache::put("otp_$phone", $otp, now()->addMinutes(10));

        return response()->json([
            'success' => true,
            'message' => 'OTP sent via WhatsApp successfully.',
        ]);
    }

    /**
     * Verify OTP and Login/Register User (Phone Only)
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|max:15',
            'otp' => 'required|string|size:6',
        ]);

        $phone = $request->phone;
        $otp = $request->otp;

        // ✅ Get stored OTP
        $storedOtp = Cache::get("otp_$phone");

        if (!$storedOtp || $storedOtp != $otp) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP.',
            ], 400);
        }

        // ✅ Clear OTP
        Cache::forget("otp_$phone");

        // ✅ Create or get user (only phone)
        $user = User::firstOrCreate(
            ['phone' => $phone],
            [
                'name' => 'User', // ✅ Default name
                'email' => null,
                'password' => bcrypt(uniqid()),
                'role' => 'customer',
                'status' => 'approved',
            ]
        );

        // ✅ Generate token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Login with Email/Password
     */
 public function login(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json([
            'success' => false,
            'message' => 'The provided credentials are incorrect.',
        ], 401);
    }

    if ($user->role === 'employer' && $user->status !== 'approved') {
        return response()->json([
            'success' => false,
            'message' => 'Your account is still pending approval.',
        ], 403);
    }

    $token = $user->createToken('api_token')->plainTextToken;

    return response()->json([
        'success' => true,
        'message' => 'Login successful.',
        'user' => $user,
        'token' => $token,
    ]);

    
}

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Logged out successfully.']);
    }

    /**
     * Employer Self Registration
     */
    public function employerRegister(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:100',
                'email' => 'required|email|unique:users',
                'password' => 'required|min:8|confirmed',
            ]);

            $employer = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'employer',
                'status' => 'pending'
            ]);

            $admins = User::where('role', 'admin')->get();
            Notification::send($admins, new EmployerPendingApprovalNotification($employer));

            return response()->json([
                'message' => 'Your registration is submitted. Awaiting admin approval.',
                'user' => $employer
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Employer registration failed: '.$e->getMessage()
            ], 500);
        }
    }

    /**
     * List Employers (Admin)
     */
    public function listEmployers()
    {
        $employers = User::where('role', 'employer')->orderBy('created_at', 'desc')->get();
        return response()->json(['employers' => $employers]);
    }

    /**
     * Send OTP via WhatsApp (Twilio)
     */
    private function sendWhatsApp($to, $message)
    {
        $sid = env('TWILIO_SID');
        $token = env('TWILIO_TOKEN');
        $from = env('TWILIO_WHATSAPP_FROM');

        try {
            $client = new TwilioClient($sid, $token);

            $client->messages->create(
                "whatsapp:$to",
                [
                    'from' => $from,
                    'body' => $message
                ]
            );

            \Log::info("WhatsApp sent to $to: $message");
        } catch (\Exception $e) {
            \Log::error("WhatsApp Error: " . $e->getMessage());
        }
    }
}