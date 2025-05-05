<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * اختر دالة التسجيل حسب الدور.
     */
    public function register(Request $request)
    {
        if ($request->role === 'doctor') {
            return $this->registerDoctor($request);
        }

        return $this->registerPatient($request);
    }

    /**
     * تسجيل مريض جديد (مع رفع صورة شخصية).
     */
    public final function registerPatient(Request $request)
    {
        $request->validate([
            'name'           => 'required|string|max:255',
            'email'          => 'required|string|email|max:255|unique:users',
            'phone_number'   => 'required|string|max:20',
            'password'       => 'required|string|min:6',
            'profile_image'  => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $data = [
            'name'          => $request->name,
            'email'         => $request->email,
            'phone_number'  => $request->phone_number,
            'password'      => Hash::make($request->password),
            'role'          => 'patient',
        ];

        if ($request->hasFile('profile_image')) {
            $data['profile_image'] = $request->file('profile_image')->store('profile_images', 'public');
        }

        $user = User::create($data);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ], 201);
    }

    /**
     * تسجيل طبيب جديد (مع رفع صورة شخصية، رقم قومي وتخصص).
     */
    public final function registerDoctor(Request $request)
    {
        $specializations = [
            'Behavioral Therapy',
            'Mindfulness & Acceptance Therapy',
            'Talk Supportive Therapy',
            'Relationship & Family Therapy',
            'Solution Focused & Goal Oriented Therapy'
        ];

        $request->validate([
            'name'           => 'required|string|max:255',
            'email'          => 'required|string|email|max:255|unique:users',
            'phone_number'   => 'required|string|max:20',
            'password'       => 'required|string|min:6',
            'national_id'    => 'required|string|size:14',
            'specialization' => 'required|string|in:' . implode(',', $specializations),
            'profile_image'  => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $data = [
            'name'           => $request->name,
            'email'          => $request->email,
            'phone_number'   => $request->phone_number,
            'password'       => Hash::make($request->password),
            'role'           => 'doctor',
            'national_id'    => $request->national_id,
            'specialization' => $request->specialization,
            'is_verified'    => false,
        ];

        if ($request->hasFile('profile_image')) {
            $data['profile_image'] = $request->file('profile_image')->store('profile_images', 'public');
        }

        $user = User::create($data);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user'    => $user,
            'token'   => $token,
            'message' => 'Doctor registered successfully. Verification pending.',
        ], 201);
    }


    /**
     * تحقق بيانات الطبيب (رقم قومي + دور)، ويتم الموافقة عليه.
     */
    public function verifyDoctor(int $doctorId): \Illuminate\Http\JsonResponse
    {
        $doctor = User::find($doctorId);

        if (!$doctor || $doctor->role !== 'doctor') {
            return response()->json([
                'message' => 'Doctor not found or invalid role.'
            ], 404);
        }

        if (!$doctor->national_id || !$doctor->profile_image) {
            return response()->json([
                'message' => 'Missing required fields for verification.'
            ], 422);
        }

        // تم التحقق وتحديث الحالة
        $doctor->is_verified = true;
        $doctor->save();

        return response()->json([
            'message' => 'Doctor verified successfully.',
            'doctor'  => $doctor
        ]);
    }

    /**
     * تسجيل دخول المستخدم والتحقق من حالة الطبيب.
     */
    public final function login(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        if ($user->role === 'doctor' && ! $user->is_verified) {
            return response()->json([
                'message' => 'Account not verified yet. Please wait for verification.'
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user'    => $user,
            'token'   => $token,
            'message' => 'Logged in successfully'
        ]);
    }

    public function logout(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        $user->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ], 200);
    }
}
