<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInterviewRequest;
use App\Http\Requests\UpdateInterviewDateRequest;
use App\Http\Requests\UpdateInterviewResultRequest;
use App\Models\BookingRequest;
use App\Models\Interview;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InterviewController extends Controller
{
    /**
     * Display a listing of the interviews.
     */
    public function index()
    {
        $user = Auth::user();

        if ($user->role->name === 'Student') {
            $interviews = Interview::where('student_id', $user->student->id)
                ->with('housing', 'bookingRequest')
                ->get();
        } elseif ($user->role->name === 'Housing Owner') {
            $interviews = Interview::where('housing_id', function ($query) use ($user) {
                $query->select('id')->from('housings')->where('housing_owner_id', $user->housingOwner->id);
            })->with('student', 'housing', 'bookingRequest')->get();
        } else {
            $interviews = Interview::with('student', 'housing', 'bookingRequest')->get();
        }

        return response()->json($interviews);
    }

    /**
     * List interviews belonging to the authenticated housing owner.
     * This is mostly a convenience wrapper around index but gives a clearer
     * named endpoint for the front‑end if needed.
     */
    public function ownerInterviews()
    {
        $user = Auth::user();

        if ($user->role->name !== 'Housing Owner') {
            return response()->json(['message' => 'Only housing owners can view these interviews.'], 403);
        }

        // تم تغيير where إلى whereIn لدعم الملاك الذين لديهم أكثر من سكن
        $interviews = Interview::whereIn('housing_id', function ($query) use ($user) {
            $query->select('id')
                ->from('housings')
                ->where('housing_owner_id', $user->housingOwner->id);
        })->with('student', 'housing', 'bookingRequest')->get();

        return response()->json($interviews);
    }

    /**
     * Store a newly created interview in storage.
     */
    public function store(StoreInterviewRequest $request)
    {
        $user = Auth::user();

        if ($user->role->name !== 'Housing Owner') {
            return response()->json(['message' => 'Only housing owners can schedule interviews.'], 403);
        }

        $bookingRequest = BookingRequest::findOrFail($request->request_id);

        // Ensure the owner owns the housing for this request
        if ($bookingRequest->housing->housing_owner_id !== $user->housingOwner->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $interview = Interview::create([
            'request_id' => $bookingRequest->id,
            'student_id' => $bookingRequest->student_id,
            'housing_id' => $bookingRequest->housing_id,
            'interview_date' => $request->interview_date,
            'interview_result' => 'pending',
            'interview_status' => 'scheduled',
            'notes' => $request->notes,
        ]);

        // update request status to show it's now under meeting discussion
        $bookingRequest->update(['status' => 'on_meeting']);

        // Notify the student about the scheduled interview
        Notification::create([
            'user_id' => $bookingRequest->student->user_id,
            'title' => 'تم جدولة مقابلة',
            'message' => 'تم جدولة مقابلة لك في سكن: ' . $bookingRequest->housing->name . ' في تاريخ: ' . $request->interview_date,
            'type' => 'info',
        ]);

        return response()->json([
            'message' => 'Interview scheduled successfully.',
            'interview' => $interview,
        ], 201);
    }

    /**
     * Update the result of an interview.
     */
    public function updateResult(UpdateInterviewResultRequest $request, $id)
    {
        $user = Auth::user();
        $interview = Interview::findOrFail($id);

        if ($user->role->name !== 'Housing Owner' ||
            $user->housingOwner->id !== $interview->housing->housing_owner_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $interview->update([
            'interview_result' => $request->interview_result,
            'interview_status' => 'completed',
            'notes' => $request->notes ?? $interview->notes,
        ]);

        // fetch the related booking request to update its status later
        $bookingRequest = \App\Models\BookingRequest::find($interview->request_id);

        // if the candidate passed the interview, convert the request into a
        // real booking and clean up other entries
        if ($request->interview_result === 'passed') {
            // mark request approved now that interview is successful
            if ($bookingRequest) {
                $bookingRequest->update(['status' => 'approved']);
            }

            // create a booking record
            $booking = \App\Models\Booking::create([
                'student_id' => $interview->student_id,
                'housing_id' => $interview->housing_id,
                'interview_id' => $interview->id,
                'booking_date' => now(),
                // set a default end date one month from now; callers can update later
                'end_date' => now()->addMonth(),
                'status' => 'active',
                'selected_services' => $bookingRequest ? $bookingRequest->selected_services : null,
            ]);

            // decrement capacity of the housing
            $housing = $interview->housing;
            if ($housing->remaining_capacity > 0) {
                $housing->decrement('remaining_capacity');
                if ($housing->remaining_capacity <= 0) {
                    $housing->update(['is_available' => false]);
                }
            }

            // cancel any other requests by the same student that are still
            // pending or approved.  We don't adjust capacity here because only
            // the successful booking decremented it.
            $otherRequests = \App\Models\BookingRequest::where('student_id', $interview->student_id)
                ->where('id', '!=', $interview->request_id)
                ->whereIn('status', ['pending', 'approved', 'on_meeting'])
                ->get();

            foreach ($otherRequests as $other) {
                $other->update(['status' => 'cancelled']);
            }

            // cancel any other interviews by the same student that are not completed
            \App\Models\Interview::where('student_id', $interview->student_id)
                ->where('id', '!=', $interview->id)
                ->where('interview_status', '!=', 'completed')
                ->update(['interview_status' => 'cancelled']);
        } else {
            // failed interview -> mark request rejected
            if ($bookingRequest) {
                $bookingRequest->update(['status' => 'rejected']);
            }
        }

        return response()->json([
            'message' => 'Interview result updated.',
            'interview' => $interview,
        ]);
    }

    /**
     * Update the date of an interview.
     */
    public function updateDate(UpdateInterviewDateRequest $request, $id)
    {
        $user = Auth::user();
        $interview = Interview::findOrFail($id);

        if ($user->role->name !== 'Housing Owner' ||
            $user->housingOwner->id !== $interview->housing->housing_owner_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($interview->interview_status === 'completed') {
            return response()->json(['message' => 'Cannot update date of a completed interview.'], 400);
        }

        $interview->update([
            'interview_date' => $request->interview_date,
        ]);

        // Notify the student about the date change
        Notification::create([
            'user_id' => $interview->student->user_id,
            'title' => 'تم تعديل تاريخ المقابلة',
            'message' => 'تم تعديل تاريخ مقابلتك في سكن: ' . $interview->housing->name . ' إلى: ' . $request->interview_date,
            'type' => 'info',
        ]);

        return response()->json([
            'message' => 'Interview date updated successfully.',
            'interview' => $interview,
        ]);
    }
}
