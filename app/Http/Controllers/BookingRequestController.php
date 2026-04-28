<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\UpdateBookingStatusRequest;
use App\Models\BookingRequest;
use App\Models\Housing;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookingRequestController extends Controller
{
    /**
     * Display a listing of the booking requests.
     */
    public function index()
    {
        $user = Auth::user();

        if ($user->role->name === 'Student') {
            $requests = BookingRequest::where('student_id', $user->student->id)
                ->with('housing')
                ->get();
        } elseif ($user->role->name === 'Housing Owner') {
            $requests = BookingRequest::whereHas('housing', function ($query) use ($user) {
                $query->where('housing_owner_id', $user->housingOwner->id);
            })->with('student', 'housing')->get();
        } else {
            $requests = BookingRequest::with('student', 'housing')->get();
        }

        return response()->json($requests);
    }

    /**
     * Display all bookings for the authenticated student.
     */
    public function myBookings()
    {
        $user = Auth::user();

        if ($user->role->name !== 'Student') {
            return response()->json(['message' => 'Only students can view their bookings.'], 403);
        }

        if (! $user->student) {
            return response()->json(['message' => 'Student profile not found.'], 404);
        }

        $bookings = BookingRequest::where('student_id', $user->student->id)
            ->with('housing')
            ->get();

        return response()->json($bookings);
    }

    /**
     * Store a newly created booking request in storage.
     */
    public function store(StoreBookingRequest $request)
    {
        $user = Auth::user();

        if ($user->role->name !== 'Student') {
            return response()->json(['message' => 'Only students can make booking requests.'], 403);
        }

        // Check if the student has already requested to book this housing
        $existingRequest = BookingRequest::where('student_id', $user->student->id)
            ->where('housing_id', $request->housing_id)
            ->first();

        if ($existingRequest) {
            if ($existingRequest->status === 'cancelled') {
                // Reactivate the cancelled request
                $updateData = ['status' => 'pending'];
                if ($request->has('selected_services')) {
                    $updateData['selected_services'] = $request->selected_services;
                }
                $existingRequest->update($updateData);

                return response()->json([
                    'message' => 'Booking request reactivated successfully.',
                    'booking_request' => $existingRequest,
                ], 200);
            } elseif ($existingRequest->status !== 'cancelled') {
                return response()->json(['message' => 'You have already requested to book this housing.'], 409);
            }
        }

        $bookingRequest = BookingRequest::create([
            'student_id' => $user->student->id,
            'housing_id' => $request->housing_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => 'pending',
            'selected_services' => $request->selected_services,
        ]);

        // Send notification to the housing owner
        Notification::create([
            'user_id' => $bookingRequest->housing->owner->user_id,
            'title' => 'طلب حجز جديد',
            'message' => 'قام طالب بحجز جديد في سكن: '.$bookingRequest->housing->name,
            'type' => 'info',
        ]);

        return response()->json([
            'message' => 'Booking request submitted successfully.',
            'booking_request' => $bookingRequest,
        ], 201);
    }

    /**
     * Update the status of a booking request.
     */
    public function updateStatus(UpdateBookingStatusRequest $request, $id)
    {
        $user = Auth::user();
        $bookingRequest = BookingRequest::findOrFail($id);

        if ($user->role->name !== 'Housing Owner' ||
            $user->housingOwner->id !== $bookingRequest->housing->housing_owner_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // simply update the request; capacity changes and final booking are
        // handled after the interview succeeds.
        $bookingRequest->update(['status' => $request->status]);

        // Send notification to the student
        if ($request->status === 'approved') {
            Notification::create([
                'user_id' => $bookingRequest->student->user_id,
                'title' => 'تم قبول طلبك',
                'message' => 'لقد تمت الموافقة على طلب الحجز الخاص بك لـ '.$bookingRequest->housing->name,
                'type' => 'success',
            ]);
        }

        return response()->json([
            'message' => 'Booking request status updated.',
            'booking_request' => $bookingRequest,
        ]);
    }

    /**
     * Cancel a booking request.
     */
    public function cancel($id)
    {
        $user = Auth::user();
        $bookingRequest = BookingRequest::findOrFail($id);

        if ($user->role->name !== 'Student' || $user->student->id !== $bookingRequest->student_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($bookingRequest->status !== 'pending') {
            return response()->json(['message' => 'Only pending requests can be cancelled.'], 400);
        }

        $bookingRequest->update(['status' => 'cancelled']);

        return response()->json(['message' => 'Booking request cancelled successfully.']);
    }

    /**
     * Display active bookings for the authenticated student.
     */
    public function myActiveBookings()
    {
        $user = Auth::user();

        if ($user->role->name !== 'Student') {
            return response()->json(['message' => 'Only students can view their bookings.'], 403);
        }

        if (! $user->student) {
            return response()->json(['message' => 'Student profile not found.'], 404);
        }

        $bookings = \App\Models\Booking::where('student_id', $user->student->id)
            ->where('status', 'active')
            ->with('housing')
            ->get();

        // Load selected services for each booking
        foreach ($bookings as $booking) {
            $booking->selected_services_details = $booking->selectedServices();
        }

        return response()->json($bookings);
    }
}
