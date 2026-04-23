<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminUpdateProfileRequest;
use App\Models\BookingRequest;
use App\Models\Housing;
use App\Models\HousingOwner;
use App\Models\Interview;
use App\Models\Notification;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * متحكم لوحة الإدارة (Admin Controller)
 * مسؤول عن العمليات التي يقوم بها المسؤول مثل الموافقة على أصحاب السكنات والسكنات الجديدة.
 */
class AdminController extends Controller
{
    /**
     * جلب قائمة بأصحاب السكنات الذين ينتظرون الموافقة.
     */
    public function getPendingOwners()
    {
        // Check if user is admin (this check can also be done via middleware)
        if (auth()->user()->role->name !== 'Admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $pendingOwners = User::whereHas('role', function ($query) {
            $query->where('name', 'Housing Owner');
        })->whereHas('housingOwner', function ($query) {
            $query->where('is_approved', false);
        })->with('housingOwner')->get();

        return response()->json($pendingOwners);
    }

    /**
     * الموافقة على طلب تسجيل صاحب سكن.
     *
     * @param  int  $id  معرف المستخدم (صاحب السكن)
     */
    public function approveOwner($id)
    {
        if (auth()->user()->role->name !== 'Admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $housingOwner = HousingOwner::where('user_id', $id)->firstOrFail();
        $housingOwner->is_approved = true;
        $housingOwner->save();

        // Send notification to the owner
        Notification::create([
            'user_id' => $id,
            'title' => 'تمت الموافقة على حسابك',
            'message' => 'تمت الموافقة على حسابك كصاحب سكن. يمكنك الآن إضافة سكناتك.',
            'type' => 'success',
        ]);

        return response()->json(['message' => 'Housing Owner approved successfully']);
    }

    /**
     * رفض طلب تسجيل صاحب سكن وحذف الحساب.
     *
     * @param  int  $id  معرف المستخدم (صاحب السكن)
     */
    public function rejectOwner($id)
    {
        if (auth()->user()->role->name !== 'Admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user = User::findOrFail($id);

        // التحقق من أن المستخدم هو صاحب سكن غير معتمد
        if ($user->role->name !== 'Housing Owner' || $user->housingOwner->is_approved) {
            return response()->json(['message' => 'Cannot reject this user'], 400);
        }

        // حذف الملفات المرفوعة إذا كانت موجودة
        if ($user->profile_image) {
            Storage::disk('public')->delete($user->profile_image);
        }
        if ($user->housingOwner->commercial_register) {
            Storage::disk('public')->delete($user->housingOwner->commercial_register);
        }

        // حذف الحساب
        $user->delete();

        return response()->json(['message' => 'Housing Owner account rejected and deleted successfully']);
    }

    /**
     * جلب قائمة بالسكنات التي تنتظر موافقة الإدارة.
     */
    public function getPendingHousings()
    {
        if (auth()->user()->role->name !== 'Admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $pendingHousings = \App\Models\Housing::where('is_approved', false)->with('owner.user', 'images')->get();

        return response()->json($pendingHousings);
    }

    /**
     * الموافقة على سكن جديد ونشره في التطبيق.
     *
     * @param  int  $id  معرف السكن
     */
    public function approveHousing($id)
    {
        if (auth()->user()->role->name !== 'Admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $housing = \App\Models\Housing::findOrFail($id);
        $housing->is_approved = true;
        $housing->save();

        // Send notification to the owner
        Notification::create([
            'user_id' => $housing->owner->user_id,
            'title' => 'تمت الموافقة على سكنك',
            'message' => 'تمت الموافقة على سكن: ' . $housing->name . ' وأصبح متاحاً للحجز.',
            'type' => 'success',
        ]);

        return response()->json(['message' => 'Housing approved successfully']);
    }

    /**
     * رفض سكن من قبل الإدارة: يحذف السكن وكل بياناته المتعلقة من قاعدة البيانات.
     *
     * @param  int  $id
     */
    public function rejectHousing($id)
    {
        if (auth()->user()->role->name !== 'Admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $housing = Housing::findOrFail($id);

        // حذف الصور
        $housing->images()->delete();

        // حذف طلبات الحجز أو الحجوزات فقط إذا كان الجدول موجودًا
        if (Schema::hasTable('booking_requests') && method_exists($housing, 'bookingRequests')) {
            $housing->bookingRequests()->delete();
        }

        if (Schema::hasTable('bookings') && method_exists($housing, 'bookings')) {
            $housing->bookings()->delete();
        }

        // حذف التقييمات إذا كانت العلاقة موجودة
        if (method_exists($housing, 'ratings')) {
            $housing->ratings()->delete();
        }

        // فصل الخدمات المرتبطة (pivot)
        if (method_exists($housing, 'services')) {
            $housing->services()->detach();
        }

        // حذف السكن
        $housing->delete();

        // Send notification to the owner
        Notification::create([
            'user_id' => $housing->owner->user_id,
            'title' => 'تم رفض سكنك',
            'message' => 'تم رفض سكن: ' . $housing->name . ' من قبل الإدارة.',
            'type' => 'error',
        ]);

        return response()->json(['message' => 'Housing rejected and deleted successfully']);
    }

    /**
     * جلب قائمة بجميع المستخدمين (للمسؤول).
     */
    public function getUsers()
    {
        if (auth()->user()->role->name !== 'Admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $users = User::with('role')->get();

        return response()->json($users);
    }

    /**
     * حذف مستخدم بواسطة المعرف (للمسؤول).
     *
     * @param  int  $id
     */
    public function deleteUser($id)
    {
        if (auth()->user()->role->name !== 'Admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user = User::findOrFail($id);

        // Perform deletion. Note: models don't use soft deletes here.
        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }

    /**
     * جلب إحصائيات سريعة للوحة الإدارة: عدد المستخدمين وعدد السكنات.
     */
    public function getStats()
    {
        // ...
        $userCount = User::count();
        $housingCount = Housing::count();
        $pendingHousingsCount = Housing::where('is_approved', false)->count();
        // $bookingRequestsCount = BookingRequest::count(); // ← احذف أو علّق هذا السطر مؤقتاً

        return response()->json([
            'users_count' => $userCount,
            'housings_count' => $housingCount,
            'pending_housings_count' => $pendingHousingsCount,
            'booking_requests_count' => 0, // ← مؤقتاً حتى تنشئ الجدول
        ]);
    }

    /**
     * جلب إحصائيات لكل مالك سكن: إجمالي السكنات، الطلبات الموافق عليها، المقابلات، التقييمات.
     */
    public function getOwnerStats()
    {
        if (auth()->user()->role->name !== 'Admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $owners = HousingOwner::with('user')->get();

        $stats = $owners->map(function ($owner) {
            $housingIds = Housing::where('housing_owner_id', $owner->id)->pluck('id');

            $totalHousings = $housingIds->count();

            $approvedRequests = BookingRequest::whereIn('housing_id', $housingIds)
                ->where('status', 'approved')
                ->count();

            $interviews = Interview::whereIn('housing_id', $housingIds)->count();

            $ratings = Rating::whereIn('housing_id', $housingIds)->count();

            return [
                'owner_id' => $owner->id,
                'owner_name' => $owner->user->name,
                'total_housings' => $totalHousings,
                'approved_requests' => $approvedRequests,
                'interviews' => $interviews,
                'ratings' => $ratings,
            ];
        });

        return response()->json($stats);
    }

    /**
     * تعديل بروفايل الأدمن: تغيير الاسم وإرفاق صورة ملف شخصي.
     */
    public function updateProfile(AdminUpdateProfileRequest $request)
    {
        if (auth()->user()->role->name !== 'Admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validated();

        $user = auth()->user();

        if ($request->hasFile('image')) {
            $file = $request->file('image');

            // حذف الصورة القديمة إن وجدت
            if ($user->profile_image) {
                Storage::disk('public')->delete($user->profile_image);
            }

            $path = $file->store('admin_avatars', 'public');
            $user->profile_image = $path;
        }

        $user->name = $data['name'];
        $user->save();

        return response()->json(['message' => 'Profile updated successfully', 'user' => $user]);
    }
}
