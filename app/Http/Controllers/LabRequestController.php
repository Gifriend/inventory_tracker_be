<?php
namespace App\Http\Controllers;

use App\Actions\LabRequest\ApproveLabRequestAction;
use App\Actions\LabRequest\CreateLabRequestAction;
use App\Actions\LabRequest\RejectLabRequestAction;
use App\DTOs\LabRequest\ApproveLabRequestData;
use App\DTOs\LabRequest\CreateLabRequestData;
use App\DTOs\LabRequest\RejectLabRequestData;
use App\Exceptions\LoanDomainException;
use App\Http\Requests\LabRequest\ApproveLabRequest;
use App\Http\Requests\LabRequest\RejectLabRequest;
use App\Http\Requests\LabRequest\StoreLabRequest;
use App\Models\LabRequest;
use App\Models\Table;
use Illuminate\Support\Facades\Auth;

class LabRequestController extends Controller
{
    // User uploads PDF and submits request
    public function store(StoreLabRequest $request, CreateLabRequestAction $createLabRequest)
    {
        $path = $request->file('pdf_file')->store('requests', 'public');

        $labRequest = $createLabRequest(new CreateLabRequestData(
            userId: Auth::id(),
            pdfPath: $path,
            message: $request->input('message'),
        ));

        return $this->successResponse($labRequest, 'Permohonan berhasil dikirim', 201);
    }

    // Aslab and User view request list
    public function index()
    {
        $user = Auth::user();
        $query = LabRequest::with(['user:id,name', 'lab:id,name', 'table:id,table_number']);

        if ($user->role === 'user') {
            $query->forUser($user->id);
        }

        $items = $query->get();

        if ($items->isEmpty()) {
            return $this->successResponse([], 'Belum ada data');
        }

        return $this->successResponse($items, 'Berhasil mengambil data permohonan');
    }

    // Aslab approves request
    public function approve(ApproveLabRequest $request, int $id, ApproveLabRequestAction $approveRequest)
    {
        try {
            $labRequest = $approveRequest(new ApproveLabRequestData(
                requestId: $id,
                labId: $request->input('lab_id'),
                tableId: $request->input('table_id'),
            ));

            return $this->successResponse($labRequest, 'Permohonan disetujui');
        } catch (LoanDomainException $exception) {
            return $this->errorResponse($exception->getMessage(), 409);
        }
    }

    // Aslab rejects request
    public function reject(RejectLabRequest $request, int $id, RejectLabRequestAction $rejectRequest)
    {
        try {
            $rejectRequest(new RejectLabRequestData(requestId: $id));

            return $this->successResponse(null, 'Permohonan ditolak');
        } catch (LoanDomainException $exception) {
            return $this->errorResponse($exception->getMessage(), 409);
        }
    }
}