<?php

declare(strict_types=1);

namespace App\Actions\LabRequest;

use App\DTOs\LabRequest\RejectLabRequestData;
use App\Enums\LabRequestStatus;
use App\Events\LabRequestRejected;
use App\Exceptions\LoanDomainException;
use App\Models\LabRequest;
use Illuminate\Support\Facades\DB;

final class RejectLabRequestAction
{
    public function __invoke(RejectLabRequestData $data): LabRequest
    {
        $labRequest = DB::transaction(function () use ($data): LabRequest {
            $labRequest = LabRequest::whereKey($data->requestId)->lockForUpdate()->firstOrFail();

            if ($labRequest->status !== LabRequestStatus::PENDING->value) {
                throw new LoanDomainException('Permohonan sudah diproses sebelumnya');
            }

            $labRequest->update(['status' => LabRequestStatus::REJECTED->value]);

            return $labRequest->fresh();
        });

        event(new LabRequestRejected($labRequest));

        return $labRequest;
    }
}
