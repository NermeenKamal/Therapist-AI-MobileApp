<?php

namespace App\Controllers;

use App\Models\Patient;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Exception;
use SendGrid;
use SendGrid\Mail\Mail as SendGridMail;

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

            // إرسال البريد باستخدام SendGrid
            $emailSent = false;
            try {
                $email = new SendGridMail();
                $email->setFrom(config('mail.from.address'), config('mail.from.name'));
                $email->setSubject('Password Reset Verification Code');
                $email->addTo($request->email);
                $email->addContent(
                    "text/plain",
                    "Your password reset verification code is: " . $code . "\n\n" .
                    "This code will expire in 1 hour.\n" .
                    "If you did not request a password reset, please ignore this email."
                );
                $email->addContent(
                    "text/html",
                    "<h2>Password Reset Code</h2>" .
                    "<p>Your verification code is: <strong>" . $code . "</strong></p>" .
                    "<p>This code will expire in 1 hour.</p>" .
                    "<p>If you did not request a password reset, please ignore this email.</p>"
                );

                $sendgrid = new SendGrid(config('services.sendgrid.key'));
                $response = $sendgrid->send($email);

                if ($response->statusCode() >= 200 && $response->statusCode() < 300) {
                    $emailSent = true;
                    Log::info('Reset code email sent successfully to: ' . $request->email);
                } else {
                    throw new Exception('SendGrid API returned status code: ' . $response->statusCode());
                }

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
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
            'password' => 'required|confirmed|min:8'
        ]);

        // التحقق من الكود
        $tokenData = DB::table('password_resets')
            ->where('email', $request->email)
            ->first();

        if (!$tokenData || !Hash::check($request->code, $tokenData->token)) {
            return response()->json(['message' => 'Invalid code.'], 400);
        }

        // التحقق من وقت إنشاء الكود (صالح لمدة ساعة)
        if (Carbon::parse($tokenData->created_at)->addHour()->isPast()) {
            DB::table('password_resets')->where('email', $request->email)->delete();
            return response()->json(['message' => 'Code expired.'], 400);
        }

        // تحديث كلمة المرور
        $patient = Patient::where('email', $request->email)->first();
        $doctor = Doctor::where('email', $request->email)->first();

        if ($patient) {
            $patient->password = Hash::make($request->password);
            $patient->save();
        } elseif ($doctor) {
            $doctor->password = Hash::make($request->password);
            $doctor->save();
        } else {
            return response()->json(['message' => 'Email not found.'], 404);
        }

        // حذف الكود بعد الاستخدام
        DB::table('password_resets')->where('email', $request->email)->delete();

        return response()->json([
            'message' => 'Password has been reset successfully.',
            'status' => 'success'
        ]);
    }
}
