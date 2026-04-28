<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\UpdateOwnerProfileRequest;
use App\Http\Requests\UpdateStudentProfileRequest;
use App\Models\HousingOwner;
use App\Models\Notification;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * متحكم المصادقة (Authentication Controller)
 * مسؤول عن عمليات تسجيل الحساب، تسجيل الدخول، وتسجيل الخروج لكل من الطلاب وأصحاب السكنات.
 */
class AuthController extends Controller
{
    /**
     * تسجيل حساب جديد.
     * يدعم تسجيل الطلاب وأصحاب السكنات مع تحقق مخصص لكل دور.
     */
    public function register(RegisterRequest $request)
    {
        $role = Role::where('name', $request->role_name)->first();

        try {
            // بدء عملية قاعدة البيانات لضمان حفظ البيانات بشكل متكامل أو التراجع عنها في حال حدوث خطأ
            DB::beginTransaction();

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role_id' => $role->id,
                'fcm_token' => $request->fcm_token,
            ]);

            if ($role->name === 'Student') {
                // حفظ الصور في storage
                $personalIdImagePath = $request->file('personal_id_image')->store('students/personal_id_images', 'public');
                $fatherIdImagePath = $request->file('father_id_image')->store('students/father_id_images', 'public');
                $universityCardImagePath = $request->file('university_card_image')->store('students/university_card_images', 'public');
                $proofOfEnrollmentPath = $request->file('proof_of_enrollment')->store('students/proof_of_enrollment', 'public');
                $profileImagePath = $request->hasFile('image') ? $request->file('image')->store('students/profile_images', 'public') : null;

                Student::create([
                    'user_id' => $user->id,
                    'full_name' => $request->full_name,
                    'personal_id_image' => $personalIdImagePath,
                    'father_id_image' => $fatherIdImagePath,
                    'university_name' => $request->university_name,
                    'major' => $request->major,
                    'university_card_image' => $universityCardImagePath,
                    'academic_level' => $request->academic_level,
                    'image' => $profileImagePath,
                    'phone_number' => $request->phone_number,
                    'address' => $request->address,
                    'nationality' => $request->nationality,
                    'proof_of_enrollment' => $proofOfEnrollmentPath,
                ]);
            } elseif ($role->name === 'Housing Owner') {
                $commercialRegisterPath = $request->hasFile('commercial_register') ? $request->file('commercial_register')->store('owner_commercial_registers', 'public') : null;

                HousingOwner::create([
                    'user_id' => $user->id,
                    'id_number' => $request->id_number,
                    'phone_number' => $request->phone_number,
                    'commercial_register' => $commercialRegisterPath,
                ]);

                // إنشاء إشعار للمالك عند تسجيل الحساب
                Notification::create([
                    'user_id' => $user->id,
                    'title' => 'تم إنشاء حساب المالك',
                    'message' => 'تم إنشاء حسابك كمالك سكن، وسيتم مراجعته من الإدارة قريباً.',
                    'type' => 'info',
                ]);

                // إرسال إشعار للإدارة بأن هناك مالك جديد بحاجة للمراجعة
                $admins = User::whereHas('role', function ($query) {
                    $query->where('name', 'Admin');
                })->get();

                foreach ($admins as $admin) {
                    Notification::create([
                        'user_id' => $admin->id,
                        'title' => 'مالك جديد بانتظار التوثيق',
                        'message' => 'تم إنشاء حساب مالك جديد بعنوان: ' . $user->name . '. الرجاء التوجه إلى صفحة التوثيق.',
                        'type' => 'info',
                    ]);
                }
            }

            // تأكيد حفظ البيانات في قاعدة البيانات
            DB::commit();

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'User created successfully',
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user->load('role', 'student', 'housingOwner'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => 'Registration failed: '.$e->getMessage()], 500);
        }
    }

    /**
     * تسجيل الدخول إلى النظام.
     * يتحقق من صحة البيانات ويصدر رمز وصول (Token).
     */
    public function login(LoginRequest $request)
    {
        if (! Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Invalid login details',
            ], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        
        // تحديث توكن الإشعارات إذا تم إرساله
        if ($request->has('fcm_token')) {
            $user->update(['fcm_token' => $request->fcm_token]);
        }

        // التحقق مما إذا كان المستخدم صاحب سكن وما إذا كان حسابه معتمداً من قبل الإدارة
        if ($user->role->name === 'Housing Owner') {
            if (! $user->housingOwner || ! $user->housingOwner->is_approved) {
                return response()->json([
                    'message' => 'Your account is pending admin approval.',
                ], 403);
            }
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->load('role', 'student', 'housingOwner'),
        ]);
    }

    /**
     * تسجيل الخروج من النظام.
     * إبطال رمز الوصول الحالي للمستخدم.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * تحديث بروفايل صاحب السكن: الاسم وصورة الملف الشخصي والسجل التجاري.
     */
    public function updateOwnerProfile(UpdateOwnerProfileRequest $request)
    {
        $user = $request->user();

        if (! $user->role || $user->role->name !== 'Housing Owner') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validated();

        if ($request->hasFile('image')) {
            $file = $request->file('image');

            if ($user->profile_image) {
                Storage::disk('public')->delete($user->profile_image);
            }

            $path = $file->store('owner_avatars', 'public');
            $user->profile_image = $path;
        }

        if ($request->hasFile('commercial_register')) {
            $file = $request->file('commercial_register');

            if ($user->housingOwner->commercial_register) {
                Storage::disk('public')->delete($user->housingOwner->commercial_register);
            }

            $path = $file->store('owner_commercial_registers', 'public');
            $user->housingOwner->commercial_register = $path;
            $user->housingOwner->save();
        }

        $user->name = $data['name'];
        $user->save();

        return response()->json(['message' => 'Owner profile updated', 'user' => $user->load('housingOwner')]);
    }

    /**
     * Retrieve the currently authenticated user (with relations).
     */
    public function user(Request $request)
    {
        return response()->json(['user' => $request->user()->load('role', 'student', 'housingOwner')]);
    }

    /**
     * Retrieve the authenticated student's profile.
     */
    public function studentProfile(Request $request)
    {
        $user = $request->user();

        if (! $user->role || $user->role->name !== 'Student') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json(['student' => $user->student]);
    }

    /**
     * Update authenticated student's profile (including images).
     */
    public function updateStudentProfile(UpdateStudentProfileRequest $request)
    {
        $user = $request->user();

        if (! $user->role || $user->role->name !== 'Student') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $student = $user->student;

        if (! $student) {
            return response()->json(['message' => 'Student profile not found'], 404);
        }

        $data = $request->validated();

        DB::beginTransaction();

        try {
            $user->name = $data['name'];
            $user->save();

            $student->full_name = $data['full_name'];
            $student->university_name = $data['university_name'];
            $student->major = $data['major'];
            $student->academic_level = $data['academic_level'];
            $student->phone_number = $data['phone_number'];
            $student->address = $data['address'];
            $student->nationality = $data['nationality'];

            $fileFields = [
                'personal_id_image' => 'students/personal_id_images',
                'father_id_image' => 'students/father_id_images',
                'university_card_image' => 'students/university_card_images',
                'image' => 'students/profile_images',
                'proof_of_enrollment' => 'students/proof_of_enrollment',
            ];

            foreach ($fileFields as $field => $folder) {
                if ($request->hasFile($field)) {
                    if ($student->$field) {
                        Storage::disk('public')->delete($student->$field);
                    }
                    $student->$field = $request->file($field)->store($folder, 'public');
                }
            }

            $student->save();

            DB::commit();

            return response()->json(['message' => 'Student profile updated', 'student' => $student->fresh()]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['message' => 'Failed to update profile', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * تحديث توكن FCM للمستخدم بشكل مستقل.
     */
    public function updateFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $request->user()->update([
            'fcm_token' => $request->fcm_token,
        ]);

        return response()->json([
            'message' => 'FCM Token updated successfully',
        ]);
    }
}
