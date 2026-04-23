<?php

namespace App\Http\Controllers;

use App\Http\Requests\AverageRatingRequest;
use App\Http\Requests\IndexRatingRequest;
use App\Http\Requests\StoreRatingRequest;
use App\Http\Requests\UpdateRatingRequest;
use App\Models\Housing;
use App\Models\Rating;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RatingController extends Controller
{
    /**
     * Display a listing of the ratings for a specific housing.
     */
    public function index(IndexRatingRequest $request)
    {
        $validated = $request->validated();

        $ratings = Rating::where('housing_id', $validated['housing_id'])
            ->with('student')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($ratings);
    }

    /**
     * Store a newly created rating in storage.
     */
    public function store(StoreRatingRequest $request)
    {
        $user = Auth::user();

        if ($user->role->name !== 'Student') {
            return response()->json(['message' => 'Only students can rate housings.'], 403);
        }

        $validated = $request->validated();

        // Check if the student has already rated this housing
        $existingRating = Rating::where('student_id', $user->student->id)
            ->where('housing_id', $validated['housing_id'])
            ->first();

        if ($existingRating) {
            return response()->json(['message' => 'You have already rated this housing.'], 409);
        }

        $rating = Rating::create([
            'student_id' => $user->student->id,
            'housing_id' => $validated['housing_id'],
            'rate' => $validated['rate'],
            'comment' => $validated['comment'] ?? null,
        ]);

        return response()->json(['message' => 'Rating submitted successfully.', 'rating' => $rating], 201);
    }

    /**
     * Return the average rating for a specific housing.
     *
     * Requires `housing_id` as a query parameter.
     */
    public function average(AverageRatingRequest $request)
    {
        $validated = $request->validated();

        $avg = Rating::where('housing_id', $validated['housing_id'])
            ->avg('rate');

        return response()->json(['average' => round($avg, 2)]);
    }

    /**
     * Update an existing rating.
     *
     * Only the student who created the rating may edit it.
     */
    public function update(UpdateRatingRequest $request, $id)
    {
        $user = Auth::user();
        $rating = Rating::findOrFail($id);

        if ($user->role->name !== 'Student' || $user->student->id !== $rating->student_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validated();

        $rating->fill($validated);
        $rating->save();

        return response()->json(['message' => 'Rating updated successfully', 'rating' => $rating]);
    }

    /**
     * Delete a rating.
     *
     * Only the authoring student or an admin may remove it.
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $rating = Rating::findOrFail($id);

        if ($user->role->name !== 'Student' || $user->student->id !== $rating->student_id) {
            if ($user->role->name !== 'Admin') {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        $rating->delete();

        return response()->json(['message' => 'Rating deleted successfully']);
    }
}
