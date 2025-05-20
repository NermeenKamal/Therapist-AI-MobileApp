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
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female',
            'height' => 'nullable|numeric|min:0',
            'weight' => 'nullable|numeric|min:0',
            'blood_type' => 'nullable|string|max:10',
            'chronic_diseases' => 'nullable|string',
            'allergies' => 'nullable|string',
            'current_medications' => 'nullable|string',
            'profile_image' => 'nullable|image|max:4096', // 4MB
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
            'date_of_birth' => $patient->date_of_birth,
            'gender' => $patient->gender,
            'height' => $patient->height,
            'weight' => $patient->weight,
            'blood_type' => $patient->blood_type,
            'chronic_diseases' => $patient->chronic_diseases,
            'allergies' => $patient->allergies,
            'current_medications' => $patient->current_medications,
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