<?php

namespace App\Controllers;

use App\Models\Patient;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Exception;

class ForgotPasswordController extends Controller
{
    public function sendResetCode(Request $request)
    {
        try {
            $request->validate(['email' => 'required|email']);

            Log::info('Password reset requested for email: ' . $request->email);

            // البحث عن المستخدم في كلا الجدولين
            $patient = Patient::where('email', $request->email)->first();
            $doctor = Doctor::where('email', $request->email)->first();
            
            if (!$patient && !$doctor) {
                Log::info('Email not found in database: ' . $request->email);
                return response()->json(['message' => 'Email not found.'], 404);
            }

            // إنشاء كود تحقق من 6 أرقام
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            
            Log::info('Generated reset code for email: ' . $request->email);

            // حفظ الكود في قاعدة البيانات
            DB::table('password_resets')->updateOrInsert(
                ['email' => $request->email],
                [
                    'token' => Hash::make($code),
                    'created_at' => Carbon::now()
                ]
            );

            Log::info('Reset code saved to database for email: ' . $request->email);

            // إرسال البريد باستخدام Laravel Mail
            $emailSent = false;
            try {
                Mail::html(
                    "<h2>Password Reset Code</h2>" .
                    "<p>Your verification code is: <strong>" . $code . "</strong></p>" .
                    "<p>This code will expire in 1 hour.</p>" .
                    "<p>If you did not request a password reset, please ignore this email.</p>",
                    function($message) use ($request) {
                        $message->to($request->email)
                                ->subject('Password Reset Verification Code');
                    }
                );
                
                $emailSent = true;
                Log::info('Reset code email sent successfully to: ' . $request->email);

            } catch (Exception $mailException) {
                Log::error('Failed to send reset code email', [
                    'email' => $request->email,
                    'error' => $mailException->getMessage(),
                    'trace' => $mailException->getTraceAsString()
                ]);
                
                // حذف الكود إذا فشل إرسال البريد
                DB::table('password_resets')->where('email', $request->email)->delete();
                
                throw $mailException;
            }

            // إرجاع رسالة نجاح
            return response()->json([
                'message' => 'Reset code has been sent to your email.',
                'status' => 'success',
                'email_sent' => $emailSent
            ]);

        } catch (Exception $e) {
            Log::error('Password reset process failed', [
                'email' => $request->email ?? 'not provided',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Could not process password reset request.',
                'error' => 'An error occurred while processing your request.',
                'details' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function reset(Request $request)
    {
        try {
            // Change validation to match the request parameters
            $validated = $request->validate([
                'email' => 'required|email',
                'token' => 'required|string|size:6', // changed from 'code' to 'token'
                'password' => 'required|min:8' // removed 'confirmed' if not using password_confirmation
            ]);

            Log::info('Attempting password reset for email: ' . $request->email);

            // Check if password_resets table exists and is accessible
            try {
                $tokenData = DB::table('password_resets')
                    ->where('email', $request->email)
                    ->first();
                
                Log::info('Password reset token data retrieved:', ['exists' => (bool)$tokenData]);
            } catch (Exception $dbException) {
                Log::error('Error accessing password_resets table', [
                    'error' => $dbException->getMessage()
                ]);
                return response()->json([
                    'message' => 'Database error occurred.',
                    'error' => 'Could not access reset tokens table.',
                    'details' => config('app.debug') ? $dbException->getMessage() : null
                ], 500);
            }

            // التحقق من الكود
            if (!$tokenData) {
                Log::warning('No token found for email: ' . $request->email);
                return response()->json(['message' => 'No reset code found for this email.'], 400);
            }

            // Check if token matches
            if (!Hash::check($request->token, $tokenData->token)) {
                Log::warning('Invalid reset code provided for email: ' . $request->email);
                return response()->json(['message' => 'Invalid reset code.'], 400);
            }

            // التحقق من وقت إنشاء الكود (صالح لمدة ساعة)
            if (Carbon::parse($tokenData->created_at)->addHour()->isPast()) {
                DB::table('password_resets')->where('email', $request->email)->delete();
                Log::warning('Expired reset code used for email: ' . $request->email);
                return response()->json(['message' => 'Reset code expired.'], 400);
            }

            // تحديث كلمة المرور
            $patient = Patient::where('email', $request->email)->first();
            $doctor = Doctor::where('email', $request->email)->first();

            if ($patient) {
                $patient->password = Hash::make($request->password);
                $patient->save();
                Log::info('Password reset successful for patient: ' . $request->email);
            } elseif ($doctor) {
                $doctor->password = Hash::make($request->password);
                $doctor->save();
                Log::info('Password reset successful for doctor: ' . $request->email);
            } else {
                Log::warning('Email not found during password reset: ' . $request->email);
                return response()->json(['message' => 'Email not found.'], 404);
            }

            // حذف الكود بعد الاستخدام
            DB::table('password_resets')->where('email', $request->email)->delete();
            Log::info('Reset token deleted after successful password reset for: ' . $request->email);

            return response()->json([
                'message' => 'Password has been reset successfully.',
                'status' => 'success'
            ]);
            
        } catch (Exception $e) {
            Log::error('Password reset process failed', [
                'email' => $request->email ?? 'not provided',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Could not process password reset request.',
                'error' => 'An error occurred while processing your request.',
                'details' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
