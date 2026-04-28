<?php

namespace App\Http\Controllers;

use App\Http\Requests\RemoveStudentRequest;
use App\Http\Requests\StoreHousingRequest;
use App\Http\Requests\UpdateHousingRequest;
use App\Models\Booking;
use App\Models\Housing;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * متحكم السكنات (Housing Controller)
 * مسؤول عن إدارة السكنات (عرضها، إضافة جديد، تحديث البيانات، وحذفها).
 */
class HousingController extends Controller
{
    /**
     * عرض قائمة بجميع السكنات المعتمدة.
     */
    public function index()
    {
        $housings = Housing::approved()->with('images', 'services')->get();

        return response()->json($housings);
    }

    /**
     * حفظ سكن جديد في النظام.
     * يتطلب أن يكون المستخدم صاحب سكن ومعتمداً من الإدارة.
     */
    public function store(StoreHousingRequest $request)
    {
        // التأكد من أن المستخدم هو صاحب سكن
        $user = Auth::user();
        if ($user->role->name !== 'Housing Owner') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // التأكد من أن صاحب السكن معتمد من قبل الإدارة لإضافة سكنات
        if (! $user->housingOwner || ! $user->housingOwner->is_approved) {
            return response()->json(['message' => 'Your account is not approved yet.'], 403);
        }

        $validated = $request->validated();

        $housing = Housing::create([
            'name' => $validated['name'],
            'city' => $validated['city'],
            'address' => $validated['address'],
            'housing_owner_id' => $user->housingOwner->id,
            'description' => $validated['description'],
            'conditions' => $validated['conditions'],
            'base_price' => $validated['base_price'],
            'is_available' => true,
            'is_approved' => false, // القيمة الافتراضية "غير معتمد"، يتطلب موافقة المسؤول ليظهر للعامة
            'features' => $validated['features'] ?? null,
            'capacity' => $validated['capacity'],
            'remaining_capacity' => $validated['remaining_capacity'],
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
        ]);

if (! empty($validated['services'])) {
                foreach ($validated['services'] as $serviceData) {
                $housing->services()->create([
                    'name' => $serviceData['name'],
                    'extra_price' => $serviceData['extra_price'],
                    'status' => true,
                ]);
            }
        }

        // handle image uploads (optional, multipart/form-data request)
        if ($request->hasFile('images')) {
            \Log::info('Images found in request', ['count' => count($request->file('images'))]);
            foreach ($request->file('images') as $img) {
                $path = $img->store('housings', 'public');
                \Log::info('Image stored', ['path' => $path]);
                $housing->images()->create(['image_url' => $path]);
            }
        } else {
            \Log::info('No images in request');
        }

        // Notify admins about the new housing awaiting approval
        $admins = User::whereHas('role', function ($query) {
            $query->where('name', 'Admin');
        })->get();

        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'title' => 'سكن جديد بانتظار التوثيق',
                'message' => 'تم إنشاء سكن جديد باسم: ' . $housing->name . '. الرجاء المراجعة والموافقة عليه.',
                'type' => 'info',
            ]);
        }

        return response()->json(['message' => 'Housing created successfully. Waiting for admin approval.', 'housing' => $housing->load('services')], 201);
    }

    /**
     * عرض تفاصيل سكن محدد.
     *
     * @param  int  $id  معرف السكن
     */
    public function show($id)
    {
        $housing = Housing::with('images', 'services')->findOrFail($id);

        // إذا كان السكن غير معتمد، يسمح فقط لصاحبه أو المسؤول برؤيته
        if (! $housing->is_approved) {
            $user = Auth::user();
            if (! $user || ($user->role->name !== 'Admin' && $user->housingOwner->id !== $housing->housing_owner_id)) {
                return response()->json(['message' => 'Unauthorized or Housing not found'], 404);
            }
        }

        return response()->json($housing);
    }

    /**
     * أظهر السكنات الخاصة بالمالك الحالي (معتمدة أو في الانتظار).
     */
    public function mine()
    {
        $user = Auth::user();
        if ($user->role->name !== 'Housing Owner') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $housings = Housing::where('housing_owner_id', $user->housingOwner->id)
            ->with('images', 'services')
            ->get();

        return response()->json($housings);
    }

    /**
     * تحديث بيانات سكن موجود.
     * يسمح فقط لصاحب السكن بتعديله.
     */
    public function update(UpdateHousingRequest $request, $id)
    {
        $user = Auth::user();
        $housing = Housing::findOrFail($id);

        if ($user->role->name !== 'Housing Owner' || $user->housingOwner->id !== $housing->housing_owner_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validated();

        $housing->update(collect($validated)->except(['services', 'images', 'delete_images'])->toArray());

        if (! empty($validated['services'])) {
            // تحديث الخدمات: نقوم بحذف الخدمات الحالية وإعادة إنشائها (طريقة بسيطة للتحديث)
            // Pros: Handles updates (by recreating), deletions (by omission), and additions.
            // Cons: Changes IDs, but for this simple use case it's acceptable.
            $housing->services()->delete();

            foreach ($validated['services'] as $serviceData) {
                $housing->services()->create([
                    'name' => $serviceData['name'],
                    'extra_price' => $serviceData['extra_price'],
                    'status' => true,
                ]);
            }
        }

        if ($request->has('delete_images')) {
            // حذف الصور المحددة من قاعدة البيانات والتخزين
            $imagesToDelete = $housing->images()->whereIn('id', $request->delete_images)->get();
            foreach ($imagesToDelete as $image) {
                // حذف الملف من التخزين
                \Storage::disk('public')->delete($image->image_url);
                // حذف من قاعدة البيانات
                $image->delete();
            }
        }

        if ($request->hasFile('images')) {
            // append new images; old ones are left untouched
            foreach ($request->file('images') as $img) {
                $path = $img->store('housings', 'public');
                $housing->images()->create(['image_url' => $path]);
            }
        }

        return response()->json(['message' => 'Housing updated successfully', 'housing' => $housing->load('services')]);
    }

    /**
     * حذف سكن من النظام.
     * متاح لصاحب السكن وللمسؤول (Admin).
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $housing = Housing::findOrFail($id);

        if ($user->role->name !== 'Housing Owner' || $user->housingOwner->id !== $housing->housing_owner_id) {
            // Check if Admin
            if ($user->role->name !== 'Admin') {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        $housing->delete();

        return response()->json(['message' => 'Housing deleted successfully']);
    }

    /**
     * إزالة طالب من السكن.
     * متاح فقط لصاحب السكن.
     */
    public function removeStudent(RemoveStudentRequest $request, $id)
    {
        $user = Auth::user();
        $housing = Housing::findOrFail($id);

        if ($user->role->name !== 'Housing Owner' || $user->housingOwner->id !== $housing->housing_owner_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validated();

        $booking = Booking::where('housing_id', $housing->id)
            ->where('student_id', $validated['student_id'])
            ->where('status', 'active')
            ->first();

        if (! $booking) {
            return response()->json(['message' => 'No active booking found for this student in this housing.'], 404);
        }

        $booking->update(['status' => 'rejected']);

        // زيادة السعة المتبقية
        $housing->increment('remaining_capacity');
        if ($housing->remaining_capacity > 0) {
            $housing->update(['is_available' => true]);
        }

        return response()->json(['message' => 'Student removed from housing successfully.']);
    }

    /**
     * عرض الحجوزات النشطة للسكن.
     * متاح فقط لصاحب السكن.
     */
    public function bookings($id)
    {
        $user = Auth::user();
        $housing = Housing::findOrFail($id);

        if ($user->role->name !== 'Housing Owner' || $user->housingOwner->id !== $housing->housing_owner_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $bookings = Booking::where('housing_id', $housing->id)
            ->where('status', 'active')
            ->with('student')
            ->get();

        // Load selected services for each booking
        foreach ($bookings as $booking) {
            $booking->selected_services_details = $booking->selectedServices();
        }

        return response()->json($bookings);
    }
}
