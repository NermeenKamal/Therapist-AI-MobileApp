<?php

namespace App\Controllers;

use App\Models\Patient;
use App\Models\Doctor;
use App\Models\LicensedDoctor;
use App\Services\EmailVerificationService;
use App\Services\CloudinaryService;
use App\Rules\ValidLicensedDoctor;
use App\Rules\EgyptianNationalId;
use App\Rules\EgyptianMobileNumber;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

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
    $key = 'login.' . $request->ip();
    if (RateLimiter::tooManyAttempts($key, 5)) {
        return response()->json([
            'message' => 'Too many login attempts. Please try again later.',
            'retry_after' => RateLimiter::availableIn($key)
        ], 429);
    }

    $validator = Validator::make($request->all(), [
        'email' => 'required|email',
        'password' => 'required'
    ]);

    if ($validator->fails()) {
        RateLimiter::hit($key);
        return response()->json(['errors' => $validator->errors()], 422);
    }

    try {
        Log::info('Login attempt started:', [
            'email' => $request->email,
            'ip' => $request->ip()
        ]);

        // تسجيل دخول الدكتور
        $doctor = Doctor::where('email', $request->email)->first();
        if ($doctor && Hash::check($request->password, $doctor->password)) {
            
            Log::info('Doctor login attempt:', [
                'doctor_id' => $doctor->id,
                'email' => $doctor->email,
                'email_verified' => $doctor->email_verified,
                'email_verified_at' => $doctor->email_verified_at,
                'is_verified_by_ocr' => $doctor->is_verified_by_ocr,
                'ocr_verified_at' => $doctor->ocr_verified_at ?? 'Not set'
            ]);

            // شرط الدكتور: email_verified = true و is_verified_by_ocr = true
            if (!$doctor->email_verified) {
                Log::warning('Doctor login failed - email not verified:', [
                    'doctor_id' => $doctor->id,
                    'email' => $doctor->email
                ]);
                
                return response()->json([
                    'message' => 'Please verify your email first.',
                    'status' => 'email_not_verified'
                ], 403);
            }

            if (!$doctor->is_verified_by_ocr) {
                Log::warning('Doctor login failed - OCR not verified:', [
                    'doctor_id' => $doctor->id,
                    'email' => $doctor->email,
                    'is_verified_by_ocr' => $doctor->is_verified_by_ocr
                ]);
                
                return response()->json([
                    'message' => 'Please complete OCR verification before logging in.',
                    'status' => 'ocr_not_verified'
                ], 403);
            }

            RateLimiter::clear($key);
            $token = $doctor->createToken('auth_token')->plainTextToken;

            Log::info('Doctor login successful:', [
                'doctor_id' => $doctor->id,
                'email' => $doctor->email
            ]);

            return response()->json([
                'message' => 'Logged in successfully as doctor.',
                'user' => $doctor->makeHidden(['password']),
                'user_type' => 'doctor',
                'token' => $token
            ]);
        }

        // تسجيل دخول المريض
        $patient = Patient::where('email', $request->email)->first();
        if ($patient && Hash::check($request->password, $patient->password)) {

            Log::info('Patient login attempt:', [
                'patient_id' => $patient->id,
                'email' => $patient->email,
                'email_verified' => $patient->email_verified,
                'email_verified_at' => $patient->email_verified_at
            ]);

            // شرط المريض: email_verified = true فقط
            if (!$patient->email_verified) {
                Log::warning('Patient login failed - email not verified:', [
                    'patient_id' => $patient->id,
                    'email' => $patient->email
                ]);
                
                return response()->json([
                    'message' => 'Please verify your email first.',
                    'status' => 'email_not_verified'
                ], 403);
            }

            RateLimiter::clear($key);
            $token = $patient->createToken('auth_token')->plainTextToken;

            Log::info('Patient login successful:', [
                'patient_id' => $patient->id,
                'email' => $patient->email
            ]);

            return response()->json([
                'message' => 'Logged in successfully as patient.',
                'user' => $patient->makeHidden(['password']),
                'user_type' => 'patient',
                'token' => $token
            ]);
        }

        Log::warning('Login failed - invalid credentials:', [
            'email' => $request->email,
            'doctor_exists' => $doctor ? 'Yes' : 'No',
            'patient_exists' => $patient ? 'Yes' : 'No'
        ]);

        RateLimiter::hit($key);
        return response()->json([
            'message' => 'Invalid credentials.'
        ], 401);

    } catch (\Exception $e) {
        Log::error('Login failed:', [
            'error' => $e->getMessage(),
            'email' => $request->email,
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'message' => 'Login failed. Please try again.'
        ], 500);
    }
}

    public function registerPatient(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|min:3',
            'email' => 'required|string|email|max:255|unique:patients,email|unique:doctors,email',
            'password' => 'required|string|min:8|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
            'mobile_number' => ['required', 'string', new EgyptianMobileNumber(), 'unique:patients,mobile_number'],
            'national_id' => ['required', 'string', new EgyptianNationalId(), 'unique:patients,national_id'],
        ], [
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, and one number.',
            'email.unique' => 'This email is already registered.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $patient = new Patient();
            $patient->name = trim($request->input('name'));
            $patient->email = strtolower(trim($request->input('email')));
            $patient->password = Hash::make($request->input('password'));
            $patient->mobile_number = preg_replace('/[^\d]/', '', $request->input('mobile_number'));
            $patient->national_id = $request->input('national_id');
            $patient->email_verified = false;
            $patient->save();

            // إرسال كود التفعيل
            if (!$this->emailService->sendVerificationCode($patient->email)) {
                throw new \Exception('Failed to send verification email');
            }

            DB::commit();

            return response()->json([
                'message' => 'Patient registered successfully. Please check your email for verification code.',
                'user' => $patient->makeHidden(['password']),
                'status' => 'pending_email_verification'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Patient registration failed:', [
                'error' => $e->getMessage(),
                'email' => $request->email
            ]);

            return response()->json([
                'message' => 'Registration failed. Please try again.',
                'error' => app()->environment('local') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function registerDoctor(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|min:3',
            'email' => 'required|string|email|max:255|unique:doctors,email|unique:patients,email',
            'password' => 'required|string|min:8|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
            'mobile_number' => ['required', 'string', new EgyptianMobileNumber(), 'unique:doctors,mobile_number'],
            'national_id' => ['required', 'string', new EgyptianNationalId(), 'unique:doctors,national_id'],
            'license_number' => ['required', 'string', new ValidLicensedDoctor(
                $request->email,
                $request->national_id,
                $request->specialization
            )],
            'medical_license_path' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'specialization' => 'required|string|in:Behavioral,Mindfulness & Acceptance,Talk Supportive,Relationship & Family,Solution Focused & Goal Oriented',
        ], [
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, and one number.',
            'email.unique' => 'This email is already registered.',
        ]);

        if ($validator->fails()) {
            Log::error('Doctor validation failed', [
                'errors' => $validator->errors()
            ]);

            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            Log::info('Doctor validation passed. Starting registration process.');

            // تأكيد الترخيص (دوبل تشيك)
            $licensedDoctor = LicensedDoctor::where('email', $request->email)
                ->where('license_number', $request->license_number)
                ->where('national_id', $request->national_id)
                ->where('specialization', $request->specialization)
                ->where('verified', true)
                ->first();

            if (!$licensedDoctor) {
                Log::warning('License verification failed', [
                    'email' => $request->email,
                    'license' => $request->license_number,
                    'nid' => $request->national_id
                ]);

                return response()->json([
                    'message' => 'Doctor information does not match our verified licensed doctors database.',
                    'status' => 'not_licensed'
                ], 403);
            }

            // رفع الملف على Cloudinary
            $medicalLicensePath = null;
            if ($request->hasFile('medical_license_path')) {
                try {
                    $file = $request->file('medical_license_path');
                    $medicalLicensePath = $this->cloudinaryService->uploadFile($file, 'medical_licenses');
                    Log::info('Cloudinary upload successful', [
                        'url' => $medicalLicensePath
                    ]);
                } catch (\Exception $e) {
                    Log::error('Cloudinary upload failed', [
                        'error' => $e->getMessage()
                    ]);
                    throw new \Exception('Failed to upload medical license');
                }
            }

            // إنشاء حساب الطبيب
            $doctor = new Doctor();
            $doctor->name = trim($request->input('name'));
            $doctor->email = strtolower(trim($request->input('email')));
            $doctor->password = Hash::make($request->input('password'));
            $doctor->mobile_number = preg_replace('/[^\d]/', '', $request->input('mobile_number'));
            $doctor->national_id = $request->input('national_id');
            $doctor->license_number = $request->input('license_number');
            $doctor->specialization = $request->input('specialization');
            $doctor->medical_license_path = $medicalLicensePath;
            $doctor->email_verified = false;
            $doctor->is_verified_by_ocr = false; // تبدأ false ويتم تغييرها بعد OCR
            $doctor->save();

            // إرسال كود التفعيل
            if (!$this->emailService->sendVerificationCode($doctor->email)) {
                throw new \Exception('Failed to send verification email');
            }

            DB::commit();

            return response()->json([
                'message' => 'Doctor registered successfully. Please check your email for verification code.',
                'user' => $doctor->makeHidden(['password']),
                'status' => 'pending_email_verification'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Doctor registration failed', [
                'error' => $e->getMessage(),
                'email' => $request->email
            ]);

            return response()->json([
                'message' => 'Registration failed. Please try again.',
                'error' => $e->getMessage() // فقط للـ debug، احذفها في الإنتاج
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
                    'message' => 'Invalid or expired verification code'
                ], 400);
            }

            // تحديث حالة التفعيل
            $updated = false;
            
            $doctor = Doctor::where('email', $request->email)->first();
            if ($doctor) {
                $doctor->email_verified = true;
                $doctor->email_verified_at = now();
                $doctor->save();
                $updated = true;
            }

            if (!$updated) {
                $patient = Patient::where('email', $request->email)->first();
                if ($patient) {
                    $patient->email_verified = true;
                    $patient->email_verified_at = now();
                    $patient->save();
                    $updated = true;
                }
            }

            if (!$updated) {
                return response()->json([
                    'message' => 'User not found'
                ], 404);
            }

            return response()->json([
                'message' => 'Email verified successfully. You can now log in.'
            ]);

        } catch (\Exception $e) {
            Log::error('Email verification failed:', [
                'error' => $e->getMessage(),
                'email' => $request->email
            ]);

            return response()->json([
                'message' => 'Email verification failed. Please try again.'
            ], 500);
        }
    }

    public function resendVerificationCode(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // تحقق من وجود المستخدم
            $doctor = Doctor::where('email', $request->email)->first();
            $patient = Patient::where('email', $request->email)->first();

            if (!$doctor && !$patient) {
                return response()->json(['message' => 'User not found'], 404);
            }

            // تحقق من أن الإيميل غير مُفعل
            $user = $doctor ?: $patient;
            if ($user->email_verified) {
                return response()->json(['message' => 'Email is already verified'], 400);
            }

            // إرسال كود التحقق الجديد
            if (!$this->emailService->sendVerificationCode($request->email)) {
                throw new \Exception('Failed to send verification email');
            }

            return response()->json(['message' => 'Verification code sent successfully']);

        } catch (\Exception $e) {
            Log::error('Resend verification code failed:', [
                'error' => $e->getMessage(),
                'email' => $request->email
            ]);

            return response()->json([
                'message' => 'Failed to send verification code. Please try again.'
            ], 500);
        }
    }

    public function sendLoginCode(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Rate limiting
        $key = 'login_code.' . $request->email;
        if (RateLimiter::tooManyAttempts($key, 3)) {
            return response()->json([
                'message' => 'Too many requests. Please wait before trying again.',
                'retry_after' => RateLimiter::availableIn($key)
            ], 429);
        }

        try {
            // التحقق من وجود المستخدم وأنه مُفعل
            $doctor = Doctor::where('email', $request->email)->where('email_verified', true)->first();
            $patient = Patient::where('email', $request->email)->where('email_verified', true)->first();

            if (!$doctor && !$patient) {
                return response()->json([
                    'message' => 'User not found or email not verified'
                ], 404);
            }

            // للطبيب: التحقق من OCR
            if ($doctor && !$doctor->is_verified_by_ocr) {
                return response()->json([
                    'message' => 'Please complete OCR verification first',
                    'status' => 'ocr_not_verified'
                ], 403);
            }

            if (!$this->emailService->sendVerificationCode($request->email)) {
                throw new \Exception('Failed to send login code');
            }

            RateLimiter::hit($key, 300); // 5 minutes

            return response()->json([
                'message' => 'Login code sent to your email'
            ]);

        } catch (\Exception $e) {
            Log::error('Send login code failed:', [
                'error' => $e->getMessage(),
                'email' => $request->email
            ]);

            return response()->json([
                'message' => 'Failed to send login code. Please try again.'
            ], 500);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $request->user()->currentAccessToken()->delete();
            return response()->json(['message' => 'Successfully logged out']);
        } catch (\Exception $e) {
            Log::error('Logout failed:', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()?->id
            ]);
            
            return response()->json([
                'message' => 'Logout failed. Please try again.'
            ], 500);
        }
    }

    public function checkAccess(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // شرط المريض: email_verified = true فقط
        if ($user instanceof \App\Models\Patient) {
            if (!$user->email_verified) {
                return response()->json([
                    'message' => 'Your email address is not verified. Please verify your email to continue.',
                    'status' => 'email_not_verified'
                ], 403);
            }
        }

        // شرط الدكتور: email_verified = true و is_verified_by_ocr = true
        if ($user instanceof \App\Models\Doctor) {
            if (!$user->email_verified) {
                return response()->json([
                    'message' => 'Your email address is not verified. Please verify your email to continue.',
                    'status' => 'email_not_verified'
                ], 403);
            }

            if (!$user->is_verified_by_ocr) {
                return response()->json([
                    'message' => 'Your identity verification is pending. Please complete OCR verification.',
                    'status' => 'ocr_not_verified'
                ], 403);
            }
        }

        return response()->json([
            'message' => 'Access granted!',
            'user_type' => $user instanceof \App\Models\Doctor ? 'doctor' : 'patient'
        ]);
    }
}
