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
        // الحصول على المريض مباشرة من نظام المصادقة
        $patient = Auth::user();
        
        if (!$patient) {
            return response()->json(['message' => 'There is no account for that patient'], 404);
        }
        
        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'profile_image' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120', // 5MB
        ]);
        
        // رفع صورة جديدة إذا كانت موجودة
        if ($request->hasFile('profile_image')) {
            $file = $request->file('profile_image');
            $path = $file->store('patient_profiles', 's3');
            $url = \Storage::disk('s3')->url($path);
            $data['profile_image'] = $url;
        }
        
        $patient->update($data);
        
        return response()->json([
            'message' => 'Profile updated successfully',
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
        $patient = Auth::user();
        
        if (!$patient) {
            return response()->json(['message' => 'There is no account for that patient'], 404);
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
        $patient = Auth::user();
        
        if (!$patient) {
            return response()->json(['message' => 'There is no account for that patient'], 404);
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
