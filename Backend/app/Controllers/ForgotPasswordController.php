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

        // إرسال البريد
        try {
            Mail::raw("Your password reset code is: " . $code . "\nThis code will expire in 1 hour.", function($message) use ($request) {
                $message->to($request->email)
                        ->subject('Password Reset Code');
            });

            return response()->json(['message' => 'Reset code sent to your email.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Could not send reset code.', 'error' => $e->getMessage()], 500);
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

        // حذف الكود
        DB::table('password_resets')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Password has been reset successfully.']);
    }
}
