<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingRequestController;
use App\Http\Controllers\HousingController;
use App\Http\Controllers\InterviewController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\SocialAuthController; // ← إضافة جديدة
use Illuminate\Support\Facades\Route;

// ─── Auth Routes (بدون مصادقة) ───────────────────────────────────────────
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// ← تسجيل الدخول بواسطة Google (لا يتطلب توكن مسبق)
Route::post('/auth/google', [SocialAuthController::class, 'googleLogin']);


Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    Route::middleware(['student'])->group(function () {
        Route::get('/student/profile', [AuthController::class, 'studentProfile']);
        Route::patch('/student/profile', [AuthController::class, 'updateStudentProfile']);

        Route::post('/booking-requests', [BookingRequestController::class, 'store']);
        Route::post('/booking-requests/{id}/cancel', [BookingRequestController::class, 'cancel']);
        Route::get('/my-bookings', [BookingRequestController::class, 'myBookings']);
        Route::get('/my-active-bookings', [BookingRequestController::class, 'myActiveBookings']);

        Route::post('/ratings', [RatingController::class, 'store']);
        Route::patch('/ratings/{id}', [RatingController::class, 'update']);
    });

    Route::middleware(['owner'])->group(function () {
        Route::post('/owner/profile', [AuthController::class, 'updateOwnerProfile']);

        Route::get('housing/mine', [HousingController::class, 'mine']);
        Route::delete('housing/{id}/remove-student', [HousingController::class, 'removeStudent']);
        Route::get('housing/{id}/bookings', [HousingController::class, 'bookings']);

        Route::post('/interviews', [InterviewController::class, 'store']);
        Route::patch('/interviews/{id}/result', [InterviewController::class, 'updateResult']);
        Route::patch('/interviews/{id}/date', [InterviewController::class, 'updateDate']);
        Route::get('/owner/interviews', [InterviewController::class, 'ownerInterviews']);

        Route::patch('/booking-requests/{id}/status', [BookingRequestController::class, 'updateStatus']);
    });

    Route::middleware(['admin'])->group(function () {
        Route::get('/admin/pending-owners', [AdminController::class, 'getPendingOwners']);
        Route::post('/admin/approve-owner/{id}', [AdminController::class, 'approveOwner']);
        Route::post('/admin/reject-owner/{id}', [AdminController::class, 'rejectOwner']);
        Route::get('/admin/pending-housings', [AdminController::class, 'getPendingHousings']);
        Route::post('/admin/approve-housing/{id}', [AdminController::class, 'approveHousing']);
        Route::post('/admin/reject-housing/{id}', [AdminController::class, 'rejectHousing']);
        Route::get('/admin/users', [AdminController::class, 'getUsers']);
        Route::delete('/admin/users/{id}', [AdminController::class, 'deleteUser']);
        Route::get('/admin/stats', [AdminController::class, 'getStats']);
        Route::get('/admin/owner-stats', [AdminController::class, 'getOwnerStats']);
        Route::post('/admin/profile', [AdminController::class, 'updateProfile']);
    });

    // Housing Routes
    Route::get('housing', [HousingController::class, 'index']);
    Route::get('housing/{id}', [HousingController::class, 'show']);
    Route::middleware(['owner'])->group(function () {
        Route::post('housing', [HousingController::class, 'store']);
        Route::match(['put', 'patch'], 'housing/{id}', [HousingController::class, 'update']);
        Route::delete('housing/{id}', [HousingController::class, 'destroy']);
    });

    // Booking Requests
    Route::get('/booking-requests', [BookingRequestController::class, 'index']);

    // Interviews
    Route::get('/interviews', [InterviewController::class, 'index']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);

    // Ratings
    Route::get('/ratings', [RatingController::class, 'index']);
    Route::get('/ratings/average', [RatingController::class, 'average']);
    Route::delete('/ratings/{id}', [RatingController::class, 'destroy']);
});
