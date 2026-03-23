<?php
namespace App\Http\Controllers;

use App\Actions\Loan\ApproveLoanAction;
use App\Actions\Loan\CheckInLoanAction;
use App\Actions\Loan\CheckOutLoanAction;
use App\Actions\Loan\CreateLoanAction;
use App\Actions\Loan\RejectLoanAction;
use App\DTOs\Loan\ApproveLoanData;
use App\DTOs\Loan\CheckInLoanData;
use App\DTOs\Loan\CreateLoanData;
use App\DTOs\Loan\RejectLoanData;
use App\Models\Loan;
use App\Models\Desk;
use App\Http\Requests\Loan\ApproveLoanRequest;
use App\Http\Requests\Loan\CheckInLoanRequest;
use App\Http\Requests\Loan\RejectLoanRequest;
use App\Http\Requests\Loan\StoreLoanRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Exceptions\LoanDomainException;

class LoanController extends Controller
{
    // User submits a loan request
    public function store(StoreLoanRequest $request, CreateLoanAction $createLoan): \Illuminate\Http\JsonResponse
    {
        $path = $request->hasFile('pdf_file')
            ? $request->file('pdf_file')->store('loans', 'public')
            : null;

        $loanData = new CreateLoanData(
            userId: Auth::id(),
            documentPath: $path,
            startTime: \Carbon\CarbonImmutable::parse($request->input('start_time')),
            endTime: \Carbon\CarbonImmutable::parse($request->input('end_time')),
        );

        $loan = $createLoan($loanData);

        return $this->successResponse($loan, 'Permohonan peminjaman berhasil dikirim', 201);
    }

    // View loan list
    public function index()
    {
        $user = Auth::user();
        $query = Loan::with(['user:id,name', 'room:id,name', 'desk:id,desk_number']);

        if ($user->role === 'user') {
            $query->forUser($user->id);
        }

        $items = $query->get();

        if ($items->isEmpty()) {
            return $this->successResponse([], 'Belum ada data');
        }

        return $this->successResponse($items, 'Berhasil mengambil data pinjaman');
    }

    // Aslab approves loan
    public function approve(ApproveLoanRequest $request, int $id, ApproveLoanAction $approveLoan)
    {
        try {
            $loan = $approveLoan(new ApproveLoanData(
                loanId: $id,
                roomId: $request->input('room_id'),
                deskId: $request->input('desk_id'),
                approverId: Auth::id(),
            ));

            return $this->successResponse($loan, 'Permohonan disetujui');
        } catch (\App\Exceptions\LoanDomainException $exception) {
            return $this->errorResponse($exception->getMessage(), 409);
        }
    }

    // Aslab rejects loan
    public function reject(RejectLoanRequest $request, int $id, RejectLoanAction $rejectLoan)
    {
        try {
            $rejectLoan(new RejectLoanData(
                loanId: $id,
                approverId: Auth::id(),
                adminNotes: $request->input('admin_notes'),
            ));

            return $this->successResponse(null, 'Permohonan ditolak');
        } catch (\App\Exceptions\LoanDomainException $exception) {
            return $this->errorResponse($exception->getMessage(), 409);
        }
    }

    // 5. User Check-In (Scan QR Meja)
    public function checkIn(CheckInLoanRequest $request, CheckInLoanAction $checkInLoan)
    {
        try {
            $loan = $checkInLoan(new CheckInLoanData(
                userId: Auth::id(),
                roomId: $request->input('room_id'),
                deskId: $request->input('desk_id'),
            ));

            return $this->successResponse($loan, 'Berhasil Check-In. Selamat menggunakan fasilitas lab!');
        } catch (\App\Exceptions\LoanDomainException $exception) {
            return $this->errorResponse($exception->getMessage(), 409);
        }
    }

    // 6. User Check-Out (Selesai menggunakan meja)
    public function checkOut(CheckOutLoanAction $checkOutLoan)
    {
        try {
            $loan = $checkOutLoan();

            return $this->successResponse($loan, 'Berhasil Check-Out. Terima kasih!');
        } catch (\App\Exceptions\LoanDomainException $exception) {
            return $this->errorResponse($exception->getMessage(), 404);
        }
    }

    // Loan history (completed or rejected / past loans)
    public function history()
    {
        $user = Auth::user();
        $query = Loan::with(['user:id,name', 'room:id,name', 'desk:id,desk_number']);

        if ($user->role === 'user') {
            $query->forUser($user->id);
        }

        $items = $query->history()->get();

        if ($items->isEmpty()) {
            return $this->successResponse([], 'Belum ada data');
        }

        return $this->successResponse($items, 'Berhasil mengambil riwayat pinjaman');
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

        return $this->successResponse([
            'desk' => [
                'id' => $desk->id,
                'desk_number' => $desk->desk_number,
                'room_id' => $desk->room_id
            ],
            'qr_url' => $qrUrl,
            'qr_payload' => $payload
        ], 'Berhasil mengambil QR meja');
    }
}