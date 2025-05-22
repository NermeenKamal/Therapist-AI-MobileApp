<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Doctor;
use App\Models\LicensedDoctor;
use App\Services\EmailVerificationService;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    protected $emailService;
    protected $cloudinaryService;

    public function __construct(EmailVerificationService $emailService, CloudinaryService $cloudinaryService)
    {
        $this->emailService = $emailService;
        $this->cloudinaryService = $cloudinaryService;
    }

    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
            'verification_code' => 'required|string|size:6'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // أولاً نبحث في جدول الأطباء
        $doctor = Doctor::where('email', $request->email)->first();
        if ($doctor && Hash::check($request->password, $doctor->password)) {
            // التحقق من كود التفعيل
            if (!$this->emailService->verifyCode($request->email, $request->verification_code)) {
                return response()->json([
                    'message' => 'Invalid verification code',
                    'status' => 'invalid_code'
                ], 400);
            }

            // التحقق من حالة الترخيص في جدول licensed_doctors
            $licensedDoctor = LicensedDoctor::where('email', $doctor->email)->first();
            if (!$licensedDoctor) {
                return response()->json([
                    'message' => 'Doctor not found in licensed doctors database',
                    'status' => 'not_licensed'
                ], 403);
            }

            if (!$licensedDoctor->verified) {
                return response()->json([
                    'message' => 'Your account is pending verification by the Ministry of Health',
                    'status' => 'pending_ministry_verification'
                ], 403);
            }

            $token = $doctor->createToken('auth_token')->plainTextToken;
            return response()->json([
                'message' => 'Logged in successfully as doctor',
                'user' => $doctor,
                'token' => $token
            ]);
        }

        // ثم نبحث في جدول المرضى
        $patient = Patient::where('email', $request->email)->first();
        if ($patient && Hash::check($request->password, $patient->password)) {
            // التحقق من كود التفعيل
            if (!$this->emailService->verifyCode($request->email, $request->verification_code)) {
                return response()->json([
                    'message' => 'Invalid verification code',
                    'status' => 'invalid_code'
                ], 400);
            }

            $token = $patient->createToken('auth_token')->plainTextToken;
            return response()->json([
                'message' => 'Logged in successfully as patient',
                'user' => $patient,
                'token' => $token
            ]);
        }

        return response()->json([
            'message' => 'Invalid credentials'
        ], 401);
    }

    public function registerPatient(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:patients,email',
            'password' => 'required|string|min:8|confirmed',
            'mobile_number' => 'required|string',
            'national_id' => 'required|string|unique:patients,national_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $patient = new Patient();
            $patient->name = $request->input('name');
            $patient->email = $request->input('email');
            $patient->password = Hash::make($request->input('password'));
            $patient->mobile_number = $request->input('mobile_number');
            $patient->national_id = $request->input('national_id');
            $patient->email_verified = false; // يحتاج تفعيل الإيميل
            $patient->save();

            // إرسال كود التفعيل
            $this->emailService->sendVerificationCode($patient->email);

            DB::commit();

            return response()->json([
                'message' => 'Patient registered successfully. Please check your email for verification code.',
                'user' => $patient,
                'status' => 'pending_email_verification'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Patient registration failed:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function registerDoctor(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:doctors,email',
            'password' => 'required|string|min:8|confirmed',
            'mobile_number' => 'required|string',
            'national_id' => 'required|string|unique:doctors,national_id',
            'license_number' => 'required|string',
            'medical_license_path' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'specialization' => 'required|string|in:Behavioral,Mindfulness & Acceptance,Talk Supportive,Relationship & Family,Solution Focused & Goal Oriented',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            // التحقق من وجود الطبيب في جدول licensed_doctors
            $licensedDoctor = LicensedDoctor::where('email', $request->email)
                ->where('license_number', $request->license_number)
                ->where('national_id', $request->national_id)
                ->where('specialization', $request->specialization)
                ->first();

            if (!$licensedDoctor) {
                return response()->json([
                    'message' => 'Doctor information does not match our licensed doctors database. Please verify your information.',
                    'status' => 'not_licensed'
                ], 403);
            }

            // التحقق من حالة التفعيل
            if (!$licensedDoctor->verified) {
                return response()->json([
                    'message' => 'Your license is pending verification by the Ministry of Health',
                    'status' => 'pending_ministry_verification'
                ], 403);
            }

            // رفع الصورة على Cloudinary
            $medicalLicensePath = null;
            if ($request->hasFile('medical_license_path')) {
                $file = $request->file('medical_license_path');
                $medicalLicensePath = $this->cloudinaryService->uploadImage($file, 'medical_licenses');
            }

            // إنشاء حساب الطبيب
            $doctor = new Doctor();
            $doctor->name = $request->input('name');
            $doctor->email = $request->input('email');
            $doctor->password = Hash::make($request->input('password'));
            $doctor->mobile_number = $request->input('mobile_number');
            $doctor->national_id = $request->input('national_id');
            $doctor->license_number = $request->input('license_number');
            $doctor->specialization = $request->input('specialization');
            $doctor->medical_license_path = $medicalLicensePath;
            $doctor->email_verified = false; // يحتاج تفعيل الإيميل
            $doctor->save();

            // إرسال كود التفعيل
            $this->emailService->sendVerificationCode($doctor->email);

            DB::commit();

            return response()->json([
                'message' => 'Doctor registered successfully. Please check your email for verification code.',
                'user' => $doctor,
                'status' => 'pending_email_verification'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Doctor registration failed:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function verifyEmail(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'verification_code' => 'required|string|size:6'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            if (!$this->emailService->verifyCode($request->email, $request->verification_code)) {
                return response()->json([
                    'message' => 'Invalid verification code'
                ], 400);
            }

            // تحديث حالة التفعيل للمستخدم
            $doctor = Doctor::where('email', $request->email)->first();
            if ($doctor) {
                $doctor->email_verified = true;
                $doctor->save();
            } else {
                $patient = Patient::where('email', $request->email)->first();
                if ($patient) {
                    $patient->email_verified = true;
                    $patient->save();
                }
            }

            return response()->json([
                'message' => 'Email verified successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Email verification failed:', [
                'error' => $e->getMessage(),
                'email' => $request->email
            ]);

            return response()->json([
                'message' => 'Email verification failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function resendVerificationCode(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $this->emailService->sendVerificationCode($request->email);

            return response()->json([
                'message' => 'Verification code sent successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Resend verification code failed:', [
                'error' => $e->getMessage(),
                'email' => $request->email
            ]);

            return response()->json([
                'message' => 'Failed to send verification code',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $request->user()->currentAccessToken()->delete();
            return response()->json(['message' => 'Successfully logged out']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Logout failed', 'error' => $e->getMessage()], 500);
        }
    }
}
