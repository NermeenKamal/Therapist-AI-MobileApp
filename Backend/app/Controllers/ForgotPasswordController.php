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
    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        // البحث عن المستخدم في كلا الجدولين
        $patient = Patient::where('email', $request->email)->first();
        $doctor = Doctor::where('email', $request->email)->first();
        
        if (!$patient && !$doctor) {
            return response()->json(['message' => 'Email not found.'], 404);
        }

        // إنشاء token
        $token = Str::random(60);
        
        // حفظ token في قاعدة البيانات
        DB::table('password_resets')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => Hash::make($token),
                'created_at' => Carbon::now()
            ]
        );

        // إرسال البريد
        try {
            $resetUrl = env('FRONTEND_URL', 'http://localhost:3000') . '/reset-password?token=' . $token . '&email=' . $request->email;
            
            Mail::send('emails.reset-password', ['url' => $resetUrl], function($message) use ($request) {
                $message->to($request->email)
                        ->subject('Reset Password Notification');
            });

            return response()->json(['message' => 'Reset link sent to your email.']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Could not send reset link.', 'error' => $e->getMessage()], 500);
        }
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:8'
        ]);

        // التحقق من token
        $tokenData = DB::table('password_resets')
            ->where('email', $request->email)
            ->first();

        if (!$tokenData || !Hash::check($request->token, $tokenData->token)) {
            return response()->json(['message' => 'Invalid token.'], 400);
        }

        // التحقق من وقت إنشاء token (صالح لمدة ساعة)
        if (Carbon::parse($tokenData->created_at)->addHour()->isPast()) {
            DB::table('password_resets')->where('email', $request->email)->delete();
            return response()->json(['message' => 'Token expired.'], 400);
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

        // حذف token
        DB::table('password_resets')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Password has been reset successfully.']);
    }
}
