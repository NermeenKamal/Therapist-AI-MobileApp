<?php

namespace App\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;

class PatientController extends Controller
{
    /**
     * تعديل بيانات البروفايل للمريض الحالي
     * يسمح فقط بتعديل الاسم وصورة الملف الشخصي
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        // التحقق من وجود المريض المرتبط بالمستخدم
        $patient = $user->patient;
        
        if (!$patient) {
            return response()->json(['message' => 'لم يتم العثور على سجل المريض المرتبط بهذا الحساب'], 404);
        }
        
        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'profile_image' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120', // 5MB
        ]);
        
        // رفع صورة جديدة إذا كانت موجودة
        if ($request->hasFile('profile_image')) {
            $file = $request->file('profile_image');
            $path = $file->store('patient_profiles', 'public');
            $data['profile_image'] = $path;
        }
        
        $patient->update($data);
        
        return response()->json([
            'message' => 'تم تحديث البيانات بنجاح',
            'patient' => $patient
        ]);
    }
    
    /**
     * عرض ملف المريض الشخصي
     *
     * @return JsonResponse
     */
    public function showProfile(): JsonResponse
    {
        $user = Auth::user();
        $patient = $user->patient;
        
        if (!$patient) {
            return response()->json(['message' => 'لم يتم العثور على سجل المريض المرتبط بهذا الحساب'], 404);
        }
        
        return response()->json([
            'id' => $patient->id,
            'name' => $patient->name,
            'profile_image' => $patient->profile_image,
        ]);
    }
    
    /**
     * جلب التاريخ الطبي للمريض
     *
     * @return JsonResponse
     */
    public function getMedicalHistory(): JsonResponse
    {
        $user = Auth::user();
        $patient = $user->patient;
        
        if (!$patient) {
            return response()->json(['message' => 'لم يتم العثور على سجل المريض المرتبط بهذا الحساب'], 404);
        }
        
        // هنا يمكنك جلب التاريخ الطبي (المواعيد السابقة، التشخيصات، إلخ)
        // على سبيل المثال:
        $medicalHistory = [
            'appointments' => $patient->appointments,
            'prescriptions' => $patient->prescriptions,
            'diagnoses' => $patient->diagnoses,
        ];
        
        return response()->json($medicalHistory);
    }
}
