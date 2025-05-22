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
        // Rate limiting للحماية من هجمات القوة الغاشمة
        $key = 'login.' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json([
                'message' => 'Too many login attempts. Please try again later.',
                'retry_after' => RateLimiter::availableIn($key)
            ], 429);
        }

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
            'verification_code' => 'required|string|size:6'
        ]);

        if ($validator->fails()) {
            RateLimiter::hit($key);
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // التحقق من كود التفعيل أولاً
            if (!$this->emailService->verifyCode($request->email, $request->verification_code)) {
                RateLimiter::hit($key);
                return response()->json([
                    'message' => 'Invalid or expired verification code',
                    'status' => 'invalid_code'
                ], 400);
            }

            // البحث في جدول الأطباء
            $doctor = Doctor::where('email', $request->email)->first();
            if ($doctor && Hash::check($request->password, $doctor->password)) {
                
                // التحقق من تفعيل الإيميل
                if (!$doctor->email_verified) {
                    return response()->json([
                        'message' => 'Please verify your email first',
                        'status' => 'email_not_verified'
                    ], 403);
                }

                // التحقق من حالة الترخيص
                if (!$doctor->isLicenseVerified()) {
                    return response()->json([
                        'message' => 'Your account is pending verification by the Ministry of Health',
                        'status' => 'pending_ministry_verification'
                    ], 403);
                }

                RateLimiter::clear($key);
                $token = $doctor->createToken('auth_token')->plainTextToken;
                
                return response()->json([
                    'message' => 'Logged in successfully as doctor',
                    'user' => $doctor->makeHidden(['password']),
                    'user_type' => 'doctor',
                    'token' => $token
                ]);
            }

            // البحث في جدول المرضى
            $patient = Patient::where('email', $request->email)->first();
            if ($patient && Hash::check($request->password, $patient->password)) {
                
                // التحقق من تفعيل الإيميل
                if (!$patient->email_verified) {
                    return response()->json([
                        'message' => 'Please verify your email first',
                        'status' => 'email_not_verified'
                    ], 403);
                }

                RateLimiter::clear($key);
                $token = $patient->createToken('auth_token')->plainTextToken;
                
                return response()->json([
                    'message' => 'Logged in successfully as patient',
                    'user' => $patient->makeHidden(['password']),
                    'user_type' => 'patient',
                    'token' => $token
                ]);
            }

            RateLimiter::hit($key);
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);

        } catch (\Exception $e) {
            Log::error('Login failed:', [
                'error' => $e->getMessage(),
                'email' => $request->email
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
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            // التحقق المضاعف من الترخيص
            $licensedDoctor = LicensedDoctor::where('email', $request->email)
                ->where('license_number', $request->license_number)
                ->where('national_id', $request->national_id)
                ->where('specialization', $request->specialization)
                ->where('verified', true)
                ->first();

            if (!$licensedDoctor) {
                return response()->json([
                    'message' => 'Doctor information does not match our verified licensed doctors database.',
                    'status' => 'not_licensed'
                ], 403);
            }

            // رفع الصورة على Cloudinary
            $medicalLicensePath = null;
            if ($request->hasFile('medical_license_path')) {
                $file = $request->file('medical_license_path');
                $medicalLicensePath = $this->cloudinaryService->uploadFile($file, 'medical_licenses');
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
            Log::error('Doctor registration failed:', [
                'error' => $e->getMessage(),
                'email' => $request->email
            ]);

            return response()->json([
                'message' => 'Registration failed. Please try again.',
                'error' => app()->environment('local') ? $e->getMessage() : null
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
            'email' => 'required|email'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Rate limiting لإعادة الإرسال
        $key = 'resend.' . $request->email;
        if (RateLimiter::tooManyAttempts($key, 3)) {
            return response()->json([
                'message' => 'Too many resend attempts. Please wait before trying again.',
                'retry_after' => RateLimiter::availableIn($key)
            ], 429);
        }

        try {
            // التحقق من وجود المستخدم
            $userExists = Doctor::where('email', $request->email)->exists() || 
                         Patient::where('email', $request->email)->exists();

            if (!$userExists) {
                return response()->json([
                    'message' => 'User not found'
                ], 404);
            }

            if (!$this->emailService->sendVerificationCode($request->email)) {
                throw new \Exception('Failed to send verification email');
            }

            RateLimiter::hit($key, 300); // 5 minutes

            return response()->json([
                'message' => 'Verification code sent successfully'
            ]);

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

            // للطبيب: التحقق من الترخيص
            if ($doctor && !$doctor->isLicenseVerified()) {
                return response()->json([
                    'message' => 'Your account is pending verification by the Ministry of Health',
                    'status' => 'pending_ministry_verification'
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

    if (!$user->email_verified) {
        return response()->json([
            'message' => 'Your email address is not verified. Please verify your email to continue.',
            'status' => 'email_not_verified'
        ], 403);
    }

    if ($user instanceof \App\Models\Doctor && !$user->isLicenseVerified()) {
        return response()->json([
            'message' => 'Your license is pending verification by the Ministry of Health',
            'status' => 'license_not_verified'
        ], 403);
    }

    return response()->json(['message' => 'Access granted!']);
}

}
