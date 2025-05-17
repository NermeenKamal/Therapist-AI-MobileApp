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

class ForgotPasswordController extends Controller
{
    public function sendResetCode(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        // البحث عن المستخدم في كلا الجدولين
        $patient = Patient::where('email', $request->email)->first();
        $doctor = Doctor::where('email', $request->email)->first();
        
        if (!$patient && !$doctor) {
            return response()->json(['message' => 'Email not found.'], 404);
        }

        try {
            // إنشاء كود تحقق من 6 أرقام
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            
            // حفظ الكود في قاعدة البيانات
            DB::table('password_resets')->updateOrInsert(
                ['email' => $request->email],
                [
                    'token' => Hash::make($code),
                    'created_at' => Carbon::now()
                ]
            );

            // إرسال البريد مع الكود
            Mail::raw(
                "Your password reset verification code is: " . $code . "\n\n" .
                "This code will expire in 1 hour.\n" .
                "If you did not request a password reset, please ignore this email.",
                function($message) use ($request) {
                    $message->to($request->email)
                            ->subject('Password Reset Verification Code');
                }
            );

            // إرجاع رسالة نجاح بدون إظهار الكود
            return response()->json([
                'message' => 'Reset code has been sent to your email.',
                'status' => 'success'
            ]);

        } catch (\Exception $e) {
            // في حالة حدوث خطأ، نحذف أي كود تم إنشاؤه
            DB::table('password_resets')->where('email', $request->email)->delete();
            
            return response()->json([
                'message' => 'Could not send reset code.',
                'error' => 'An error occurred while sending the email.'
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
