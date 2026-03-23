<?php

namespace Tests\Feature;

use App\Models\LabRequest;
use App\Models\Lab;
use App\Models\Table;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LabRequestFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_submit_lab_request_with_pdf()
    {
        Storage::fake('public');

        $user = User::factory()->create(['role' => 'user']);

        $file = UploadedFile::fake()->create('request.pdf', 1024, 'application/pdf');

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/lab-requests', [
            'pdf_file' => $file,
            'message' => 'Permintaan ruang lab',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Permohonan berhasil dikirim');

        $this->assertDatabaseHas('lab_requests', [
            'user_id' => $user->id,
            'status' => 'pending',
        ]);
    }

    public function test_aslab_can_approve_and_reject_lab_request()
    {
        $user = User::factory()->create(['role' => 'user']);
        $aslab = User::factory()->create(['role' => 'aslab']);

        $lab = Lab::create(['name' => 'Lab Test']);
        $table = Table::create(['lab_id' => $lab->id, 'table_number' => 'T1', 'status' => 'available']);

        $labRequest = LabRequest::create([
            'user_id' => $user->id,
            'pdf_path' => 'requests/test.pdf',
            'message' => 'Need this',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($aslab, 'sanctum')->patchJson("/api/lab-requests/{$labRequest->id}/approve", [
            'lab_id' => $lab->id,
            'table_id' => $table->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Permohonan disetujui');

        $this->assertDatabaseHas('lab_requests', [
            'id' => $labRequest->id,
            'status' => 'approved',
            'lab_id' => $lab->id,
            'table_id' => $table->id,
        ]);

        $this->assertDatabaseHas('tables', [
            'id' => $table->id,
            'status' => 'occupied',
        ]);

        $labRequest2 = LabRequest::create([
            'user_id' => $user->id,
            'pdf_path' => 'requests/test2.pdf',
            'message' => 'Need other',
            'status' => 'pending',
        ]);

        $responseReject = $this->actingAs($aslab, 'sanctum')->patchJson("/api/lab-requests/{$labRequest2->id}/reject", []);
        $responseReject->assertStatus(200)
            ->assertJsonPath('message', 'Permohonan ditolak');

        $this->assertDatabaseHas('lab_requests', [
            'id' => $labRequest2->id,
            'status' => 'rejected',
        ]);
    }
}
