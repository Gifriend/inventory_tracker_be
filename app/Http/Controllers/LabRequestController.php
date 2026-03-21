<?php
namespace App\Http\Controllers;

use App\Models\LabRequest;
use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LabRequestController extends Controller
{
    // User uploads PDF and submits request
    public function store(Request $request)
    {
        $request->validate([
            'pdf_file' => 'required|file|mimes:pdf|max:2048', // Validate PDF file, max 2MB
            'message' => 'nullable|string'
        ]);

        // Save file to storage/app/public/requests
        $path = $request->file('pdf_file')->store('requests', 'public');

        $labRequest = LabRequest::create([
            'user_id' => Auth::id(),
            'pdf_path' => $path,
            'message' => $request->message,
            'status' => 'pending'
        ]);

        return $this->successResponse($labRequest, 'Permohonan berhasil dikirim', 201);
    }

    // Aslab and User view request list
    public function index()
    {
        $user = Auth::user();
        $query = LabRequest::with(['user:id,name', 'lab:id,name', 'table:id,table_number']);

        // If regular user, show only their own data
        if ($user->role === 'user') {
            $query->where('user_id', $user->id);
        }

        $items = $query->get();

        if ($items->isEmpty()) {
            return $this->successResponse([], 'Belum ada data');
        }

        return $this->successResponse($items, 'Berhasil mengambil data permohonan');
    }

    // Aslab approves request
    public function approve(Request $request, $id)
    {
        $request->validate([
            'lab_id' => 'required|exists:labs,id',
            'table_id' => 'required|exists:tables,id'
        ]);

        $result = DB::transaction(function () use ($id, $request) {
            $labRequest = LabRequest::whereKey($id)->lockForUpdate()->firstOrFail();

            if ($labRequest->status !== 'pending') {
                return [
                    'ok' => false,
                    'message' => 'Permohonan sudah diproses sebelumnya',
                    'code' => 409,
                ];
            }

            $table = Table::where('id', $request->table_id)
                          ->where('lab_id', $request->lab_id)
                          ->lockForUpdate()
                          ->firstOrFail();

            if ($table->status !== 'available') {
                return [
                    'ok' => false,
                    'message' => 'Meja sudah terpakai',
                    'code' => 409,
                ];
            }

            $labRequest->update([
                'status' => 'approved',
                'lab_id' => $request->lab_id,
                'table_id' => $request->table_id
            ]);

            $table->update(['status' => 'occupied']);

            return [
                'ok' => true,
                'request' => $labRequest->fresh(),
            ];
        });

        if (!$result['ok']) {
            return $this->errorResponse($result['message'], $result['code']);
        }

        return $this->successResponse($result['request'], 'Permohonan disetujui');
    }

    // Aslab rejects request
    public function reject($id)
    {
        $result = DB::transaction(function () use ($id) {
            $labRequest = LabRequest::whereKey($id)->lockForUpdate()->firstOrFail();

            if ($labRequest->status !== 'pending') {
                return [
                    'ok' => false,
                    'message' => 'Permohonan sudah diproses sebelumnya',
                    'code' => 409,
                ];
            }

            $labRequest->update([
                'status' => 'rejected'
            ]);

            return ['ok' => true];
        });

        if (!$result['ok']) {
            return $this->errorResponse($result['message'], $result['code']);
        }

        return $this->successResponse(null, 'Permohonan ditolak');
    }
}