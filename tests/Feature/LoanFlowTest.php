<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Room;
use App\Models\Desk;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LoanFlowTest extends TestCase
{
    use RefreshDatabase; // Reset database on each test

    public function test_user_can_submit_loan_request_without_pdf()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/loans', [
            'start_time' => now()->addDay()->toDateTimeString(),
            'end_time' => now()->addDays(2)->toDateTimeString(),
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Permohonan peminjaman berhasil dikirim');

        $loanId = $response->json('data.id');

        $this->assertDatabaseHas('loans', [
            'id' => $loanId,
            'status' => 'pending',
            'user_id' => $user->id,
            'document_path' => null,
        ]);
    }

    public function test_full_loan_approval_flow()
    {
        Storage::fake('public'); // Mock storage to prevent disk usage during testing

        // Setup data
       /** @var \App\Models\User $user */
        $user = User::factory()->create(['role' => 'user']);
        
        /** @var \App\Models\User $aslab */
        $aslab = User::factory()->create(['role' => 'aslab']);

        $room = Room::create(['name' => 'Lab Cyber']);
        $desk = Desk::create([
            'room_id' => $room->id,
            'desk_number' => 'Meja 1',
            'status' => 'available'
        ]);

        // User submits loan request
        $pdf = UploadedFile::fake()->create('surat_izin.pdf', 100, 'application/pdf');

        $responseUser = $this->actingAs($user, 'sanctum')->postJson('/api/loans', [
            'pdf_file' => $pdf,
            'start_time' => now()->addDay()->toDateTimeString(),
            'end_time' => now()->addDays(2)->toDateTimeString(),
        ]);

        // Assert endpoint responds with standard format
        $responseUser->assertStatus(201)
            // ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Permohonan peminjaman berhasil dikirim');

        // Assert file is saved in virtual storage and database
        $loanId = $responseUser->json('data.id');
        $this->assertDatabaseHas('loans', [
            'id' => $loanId,
            'status' => 'pending',
            'user_id' => $user->id
        ]);

        // Aslab approves loan and assigns room and desk
        $responseAslab = $this->actingAs($aslab, 'sanctum')->patchJson("/api/loans/{$loanId}/approve", [
            'room_id' => $room->id,
            'desk_id' => $desk->id,
        ]);

        $responseAslab->assertStatus(200)
            // ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Permohonan disetujui');

        // Verify all database changes match expectations

        // Check loans table status changed and has desk relation
        $this->assertDatabaseHas('loans', [
            'id' => $loanId,
            'status' => 'approved',
            'room_id' => $room->id,
            'desk_id' => $desk->id,
            'approved_by' => $aslab->id
        ]);

        // Check desks table status changed to occupied
        $this->assertDatabaseHas('desks', [
            'id' => $desk->id,
            'status' => 'occupied'
        ]);

        // User checks in using scanned QR payload (room_id + desk_id)
        $responseCheckIn = $this->actingAs($user, 'sanctum')->postJson('/api/loans/check-in', [
            'room_id' => $room->id,
            'desk_id' => $desk->id,
        ]);

        $responseCheckIn->assertStatus(200)
            ->assertJsonPath('message', 'Berhasil Check-In. Selamat menggunakan fasilitas lab!');

        // User checks out and desk becomes available again
        $responseCheckOut = $this->actingAs($user, 'sanctum')->postJson('/api/loans/check-out');

        $responseCheckOut->assertStatus(200)
            ->assertJsonPath('message', 'Berhasil Check-Out. Terima kasih!');

        $this->assertDatabaseHas('loans', [
            'id' => $loanId,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('desks', [
            'id' => $desk->id,
            'status' => 'available',
        ]);
    }
}
