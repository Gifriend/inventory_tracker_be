<?php

declare(strict_types=1);

namespace App\Actions\LabRequest;

use App\DTOs\LabRequest\ApproveLabRequestData;
use App\Enums\LabRequestStatus;
use App\Events\LabRequestApproved;
use App\Exceptions\LoanDomainException;
use App\Models\LabRequest;
use App\Models\Table;
use Illuminate\Support\Facades\DB;

final class ApproveLabRequestAction
{
    public function __invoke(ApproveLabRequestData $data): LabRequest
    {
        $labRequest = DB::transaction(function () use ($data): LabRequest {
            $labRequest = LabRequest::whereKey($data->requestId)->lockForUpdate()->firstOrFail();

            if ($labRequest->status !== LabRequestStatus::PENDING->value) {
                throw new LoanDomainException('Permohonan sudah diproses sebelumnya');
            }

            $table = Table::where('id', $data->tableId)
                ->where('lab_id', $data->labId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($table->status !== 'available') {
                throw new LoanDomainException('Meja sudah terpakai');
            }

            $labRequest->update([
                'status' => LabRequestStatus::APPROVED->value,
                'lab_id' => $data->labId,
                'table_id' => $data->tableId,
            ]);

            $table->update(['status' => 'occupied']);

            return $labRequest->fresh();
        });

        event(new LabRequestApproved($labRequest));

        return $labRequest;
    }
}
