<?php

namespace Tests\Feature;

use App\Models\BookingRequest;
use App\Models\Housing;
use App\Models\HousingOwner;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A student should be able to receive approvals for multiple bookings.
     * After approving one request we expect any other pending requests to stay
     * pending and be approvable later.
     */
    public function test_student_can_have_multiple_approvals()
    {
        // prepare roles
        $studentRole = Role::create(['name' => 'Student']);
        $ownerRole = Role::create(['name' => 'Housing Owner']);

        // create a student user and profile
        $studentUser = User::factory()->create(['role_id' => $studentRole->id]);
        $student = Student::create([
            'user_id' => $studentUser->id,
            'full_name' => 'John Doe',
            'university_name' => 'Test University',
            'major' => 'Computer Science',
            'academic_level' => 'Bachelor',
            'phone_number' => '1234567890',
            'address' => '123 Main St',
            'nationality' => 'Testland',
        ]);

        // create an owner user and profile
        $ownerUser = User::factory()->create(['role_id' => $ownerRole->id]);
        $owner = HousingOwner::create([
            'user_id' => $ownerUser->id,
            'is_approved' => true,
        ]);

        // create two housings for the owner
        $housing1 = Housing::create([
            'name' => 'House A',
            'housing_owner_id' => $owner->id,
            'description' => 'First housing',
            'base_price' => 100,
            'is_available' => true,
            'latitude' => 0,
            'longitude' => 0,
            'capacity' => 2,
            'remaining_capacity' => 2,
        ]);

        $housing2 = Housing::create([
            'name' => 'House B',
            'housing_owner_id' => $owner->id,
            'description' => 'Second housing',
            'base_price' => 200,
            'is_available' => true,
            'latitude' => 0,
            'longitude' => 0,
            'capacity' => 1,
            'remaining_capacity' => 1,
        ]);

        // the student makes two booking requests
        $request1 = BookingRequest::create([
            'student_id' => $student->id,
            'housing_id' => $housing1->id,
            'status' => 'pending',
        ]);

        $request2 = BookingRequest::create([
            'student_id' => $student->id,
            'housing_id' => $housing2->id,
            'status' => 'pending',
        ]);

        // owner approves the first request via API endpoint
        // owner marks the first request as ready for meeting
        $response = $this->actingAs($ownerUser, 'sanctum')
            ->patchJson("/api/booking-requests/{$request1->id}/status", [
                'status' => 'on_meeting',
            ]);

        $response->assertStatus(200);

        // refresh to see updated statuses
        $request1->refresh();
        $request2->refresh();

        $this->assertEquals('on_meeting', $request1->status);
        $this->assertEquals('pending', $request2->status,
            'Second request should remain pending after the first is marked on_meeting.');

        // schedule an interview for the first request via API which should
        // also set the booking request status to 'on_meeting'
        $respSchedule = $this->actingAs($ownerUser, 'sanctum')
            ->postJson('/api/interviews', [
                'request_id' => $request1->id,
                'interview_date' => now()->addDay()->toDateTimeString(),
            ]);
        $respSchedule->assertStatus(201);
        $interview = $respSchedule->json('interview');

        // reload request to see status change by controller
        $request1->refresh();
        $this->assertEquals('on_meeting', $request1->status);

        // owner marks interview as passed via API
        $resp2 = $this->actingAs($ownerUser, 'sanctum')
            ->patchJson("/api/interviews/{$interview['id']}/result", [
                'interview_result' => 'passed',
            ]);
        $resp2->assertStatus(200);

        // refresh all models
        $request1->refresh();
        $request2->refresh();
        $housing1->refresh();
        $housing2->refresh();

        // request1 should be marked booked, request2 cancelled
        $this->assertEquals('approved', $request1->status,
            'Request should remain approved after interview pass.');
        $this->assertEquals('cancelled', $request2->status,
            'Other request must be cancelled.');

        // booking record should exist
        $this->assertDatabaseHas('bookings', [
            'student_id' => $student->id,
            'housing_id' => $housing1->id,
            'interview_id' => $interview['id'],
        ]);

        // capacity of housing1 decreased by one (initial was 2)
        $this->assertEquals(1, $housing1->remaining_capacity);
        // housing2 capacity unchanged
        $this->assertEquals(1, $housing2->remaining_capacity);
    }

    /**
     * If the interview is failed, the original booking request should be
     * marked rejected and no bookings created; other requests also cancel.
     */
    public function test_interview_failure_rejects_request()
    {
        // reuse setup logic from previous test
        $studentRole = Role::create(['name' => 'Student']);
        $ownerRole = Role::create(['name' => 'Housing Owner']);

        $studentUser = User::factory()->create(['role_id' => $studentRole->id]);
        $student = Student::create([
            'user_id' => $studentUser->id,
            'full_name' => 'John Doe',
            'university_name' => 'Test University',
            'major' => 'Computer Science',
            'academic_level' => 'Bachelor',
            'phone_number' => '1234567890',
            'address' => '123 Main St',
            'nationality' => 'Testland',
        ]);

        $ownerUser = User::factory()->create(['role_id' => $ownerRole->id]);
        $owner = HousingOwner::create([
            'user_id' => $ownerUser->id,
            'is_approved' => true,
        ]);

        $housing = Housing::create([
            'name' => 'House C',
            'housing_owner_id' => $owner->id,
            'description' => 'Third housing',
            'base_price' => 300,
            'is_available' => true,
            'latitude' => 0,
            'longitude' => 0,
            'capacity' => 1,
            'remaining_capacity' => 1,
        ]);

        $req = BookingRequest::create([
            'student_id' => $student->id,
            'housing_id' => $housing->id,
            'status' => 'pending',
        ]);

        // owner marks on_meeting
        $this->actingAs($ownerUser, 'sanctum')
            ->patchJson("/api/booking-requests/{$req->id}/status", ['status' => 'on_meeting'])
            ->assertStatus(200);
        $req->refresh();
        $this->assertEquals('on_meeting', $req->status);

        // schedule and then fail interview
        $respSchedule = $this->actingAs($ownerUser, 'sanctum')
            ->postJson('/api/interviews', [
                'request_id' => $req->id,
                'interview_date' => now()->addDay()->toDateTimeString(),
            ]);
        $respSchedule->assertStatus(201);
        $interview = $respSchedule->json('interview');

        $respFail = $this->actingAs($ownerUser, 'sanctum')
            ->patchJson("/api/interviews/{$interview['id']}/result", [
                'interview_result' => 'failed',
            ]);
        $respFail->assertStatus(200);

        $req->refresh();
        $this->assertEquals('rejected', $req->status);
        $this->assertDatabaseMissing('bookings', ['student_id' => $student->id]);
    }
}
