<?php
namespace App\Http\Controllers;

use App\Models\Loan;
use App\Models\Desk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LoanController extends Controller
{
    // User submits a loan request
    public function store(Request $request)
    {
        $request->validate([
            'pdf_file' => 'required|file|mimes:pdf|max:2048',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
        ]);

        $path = $request->file('pdf_file')->store('loans', 'public');

        $loan = Loan::create([
            'user_id' => Auth::id(),
            'document_path' => $path,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'status' => 'pending'
        ]);

        return response()->json([
            'message' => 'Permohonan peminjaman berhasil dikirim',
            'data' => $loan
        ], 201);
    }

    // View loan list
    public function index()
    {
        $user = Auth::user();
        $query = Loan::with(['user:id,name', 'room:id,name', 'desk:id,desk_number']);

        // If regular user, show only their own data
        if ($user->role === 'user') {
            $query->where('user_id', $user->id);
        }

        return response()->json(['data' => $query->get()]);
    }

    // Aslab approves loan
    public function approve(Request $request, $id)
    {
        $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'desk_id' => 'required|exists:desks,id'
        ]);

        $loan = Loan::findOrFail($id);
        
        $desk = Desk::where('id', $request->desk_id)
                    ->where('room_id', $request->room_id)
                    ->firstOrFail();

        if ($desk->status !== 'available') {
            return response()->json(['message' => 'Meja sudah terpakai'], 400);
        }

        // Use DB transaction for data consistency
        DB::transaction(function () use ($loan, $desk, $request) {
            $loan->update([
                'status' => 'approved',
                'room_id' => $request->room_id,
                'desk_id' => $request->desk_id,
                'approved_by' => Auth::id(), // Store Aslab user ID
            ]);

            $desk->update(['status' => 'maintenance']); // Or use a new 'occupied' status in your DB
        });

        return response()->json([
            'message' => 'Permohonan disetujui',
            'data' => $loan
        ]);
    }

    // Aslab rejects loan
    public function reject(Request $request, $id)
    {
        $request->validate([
            'admin_notes' => 'required|string'
        ]);

        $loan = Loan::findOrFail($id);
        
        $loan->update([
            'status' => 'rejected',
            'admin_notes' => $request->admin_notes,
            'approved_by' => Auth::id(),
        ]);

        return response()->json(['message' => 'Permohonan ditolak']);
    }
}