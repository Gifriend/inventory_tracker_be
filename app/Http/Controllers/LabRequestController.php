<?php
namespace App\Http\Controllers;

use App\Models\LabRequest;
use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        return response()->json([
            'message' => 'Permohonan berhasil dikirim',
            'data' => $labRequest
        ], 201);
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

        return response()->json(['data' => $query->get()]);
    }

    // Aslab approves request
    public function approve(Request $request, $id)
    {
        $request->validate([
            'lab_id' => 'required|exists:labs,id',
            'table_id' => 'required|exists:tables,id'
        ]);

        $labRequest = LabRequest::findOrFail($id);
        
        // Ensure table is available
        $table = Table::where('id', $request->table_id)
                      ->where('lab_id', $request->lab_id)
                      ->firstOrFail();

        if ($table->status !== 'available') {
            return response()->json(['message' => 'Meja sudah terpakai'], 400);
        }

        // Update request status
        $labRequest->update([
            'status' => 'approved',
            'lab_id' => $request->lab_id,
            'table_id' => $request->table_id
        ]);

        // Update table status to occupied
        $table->update(['status' => 'occupied']);

        return response()->json([
            'message' => 'Permohonan disetujui',
            'data' => $labRequest
        ]);
    }

    // Aslab rejects request
    public function reject($id)
    {
        $labRequest = LabRequest::findOrFail($id);
        
        $labRequest->update([
            'status' => 'rejected'
        ]);

        return response()->json(['message' => 'Permohonan ditolak']);
    }
}