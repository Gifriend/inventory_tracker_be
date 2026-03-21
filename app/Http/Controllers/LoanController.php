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
            'pdf_file' => 'nullable|file|mimes:pdf|max:2048',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
        ]);

        $path = $request->hasFile('pdf_file')
            ? $request->file('pdf_file')->store('loans', 'public')
            : null;

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

        $items = $query->get();

        if ($items->isEmpty()) {
            return response()->json([
                'message' => 'Belum ada data',
                'data' => []
            ], 200);
        }

        return response()->json(['data' => $items]);
    }

    // Aslab approves loan
    public function approve(Request $request, $id)
    {
        $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'desk_id' => 'required|exists:desks,id'
        ]);

        $result = DB::transaction(function () use ($id, $request) {
            $loan = Loan::whereKey($id)->lockForUpdate()->firstOrFail();

            if ($loan->status !== 'pending') {
                return [
                    'ok' => false,
                    'message' => 'Permohonan sudah diproses sebelumnya',
                    'code' => 409,
                ];
            }

            $desk = Desk::where('id', $request->desk_id)
                        ->where('room_id', $request->room_id)
                        ->lockForUpdate()
                        ->firstOrFail();

            if ($desk->status !== 'available') {
                return [
                    'ok' => false,
                    'message' => 'Meja sudah terpakai',
                    'code' => 409,
                ];
            }

            $loan->update([
                'status' => 'approved',
                'room_id' => $request->room_id,
                'desk_id' => $request->desk_id,
                'approved_by' => Auth::id(), // Store Aslab user ID
            ]);

            $desk->update(['status' => 'occupied']);

            return [
                'ok' => true,
                'loan' => $loan->fresh(),
            ];
        });

        if (!$result['ok']) {
            return response()->json(['message' => $result['message']], $result['code']);
        }

        return response()->json([
            'message' => 'Permohonan disetujui',
            'data' => $result['loan']
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

    // 5. User Check-In (Scan QR Meja)
    public function checkIn(Request $request)
    {
        // Data ini didapat dari hasil scan QR Code di Flutter
        $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'desk_id' => 'required|exists:desks,id'
        ]);

        $result = DB::transaction(function () use ($request) {
            $loan = Loan::where('user_id', Auth::id())
                        ->where('status', 'approved')
                        ->where('room_id', $request->room_id)
                        ->where('desk_id', $request->desk_id)
                        ->whereNull('check_in_time')
                        ->lockForUpdate()
                        ->first();

            if (!$loan) {
                return [
                    'ok' => false,
                    'message' => 'Tidak ada jadwal peminjaman valid untuk meja ini atau Anda sudah Check-In.',
                    'code' => 404,
                ];
            }

            $desk = Desk::whereKey($request->desk_id)->lockForUpdate()->first();

            if (!$desk || $desk->room_id !== (int) $request->room_id) {
                return [
                    'ok' => false,
                    'message' => 'Meja tidak valid untuk ruangan ini.',
                    'code' => 409,
                ];
            }

            if ($desk->status === 'maintenance') {
                return [
                    'ok' => false,
                    'message' => 'Meja sedang maintenance.',
                    'code' => 409,
                ];
            }

            $loan->update(['check_in_time' => now()]);

            if ($desk->status !== 'occupied') {
                $desk->update(['status' => 'occupied']);
            }

            return [
                'ok' => true,
                'loan' => $loan->fresh(),
            ];
        });

        if (!$result['ok']) {
            return response()->json(['message' => $result['message']], $result['code']);
        }

        return response()->json([
            'message' => 'Berhasil Check-In. Selamat menggunakan fasilitas lab!',
            'data' => $result['loan']
        ]);
    }

    // 6. User Check-Out (Selesai menggunakan meja)
    public function checkOut(Request $request)
    {
        $result = DB::transaction(function () {
            $loan = Loan::where('user_id', Auth::id())
                        ->where('status', 'approved')
                        ->whereNotNull('check_in_time')
                        ->whereNull('check_out_time')
                        ->lockForUpdate()
                        ->first();

            if (!$loan) {
                return [
                    'ok' => false,
                    'message' => 'Anda belum Check-In atau tidak ada sesi aktif.',
                    'code' => 404,
                ];
            }

            $loan->update([
                'check_out_time' => now(),
                'status' => 'completed'
            ]);

            $desk = Desk::whereKey($loan->desk_id)->lockForUpdate()->first();
            if ($desk) {
                $desk->update(['status' => 'available']);
            }

            return [
                'ok' => true,
                'loan' => $loan->fresh(),
            ];
        });

        if (!$result['ok']) {
            return response()->json(['message' => $result['message']], $result['code']);
        }

        return response()->json([
            'message' => 'Berhasil Check-Out. Terima kasih!',
            'data' => $result['loan']
        ]);
    }

    // Loan history (completed or rejected / past loans)
    public function history()
    {
        $user = Auth::user();
        $query = Loan::with(['user:id,name', 'room:id,name', 'desk:id,desk_number']);

        if ($user->role === 'user') {
            $query->where('user_id', $user->id);
        }

        // History: completed, rejected or already checked-out
        $items = $query->where(function ($q) {
            $q->whereIn('status', ['completed', 'rejected'])
              ->orWhereNotNull('check_out_time');
        })->get();

        if ($items->isEmpty()) {
            return response()->json([
                'message' => 'Belum ada data',
                'data' => []
            ], 200);
        }

        return response()->json(['data' => $items]);
    }

    // Return a QR code URL for a desk so client can render or download it
    public function deskQr($id)
    {
        $desk = Desk::findOrFail($id);

        // Build a simple payload the client can encode in a QR (or embed the returned QR URL)
        $payload = json_encode([
            'room_id' => $desk->room_id,
            'desk_id' => $desk->id,
            'type' => 'desk_qr'
        ]);

        // Use Google Chart API as a quick way to provide a QR image URL without extra packages
        $qrUrl = 'https://chart.googleapis.com/chart?cht=qr&chs=300x300&chl=' . urlencode($payload);

        return response()->json([
            'desk' => [
                'id' => $desk->id,
                'desk_number' => $desk->desk_number,
                'room_id' => $desk->room_id
            ],
            'qr_url' => $qrUrl,
            'qr_payload' => $payload
        ]);
    }
}