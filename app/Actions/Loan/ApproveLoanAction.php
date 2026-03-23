<?php

declare(strict_types=1);

namespace App\Actions\Loan;

use App\DTOs\Loan\ApproveLoanData;
use App\Enums\LoanStatus;
use App\Events\LoanApproved;
use App\Exceptions\LoanDomainException;
use App\Models\Desk;
use App\Models\Loan;
use Illuminate\Support\Facades\DB;

final class ApproveLoanAction
{
    public function __invoke(ApproveLoanData $data): Loan
    {
        $loan = DB::transaction(function () use ($data): Loan {
            $loan = Loan::whereKey($data->loanId)->lockForUpdate()->firstOrFail();

            if ($loan->status !== LoanStatus::PENDING->value) {
                throw new LoanDomainException('Permohonan sudah diproses sebelumnya');
            }

            $desk = Desk::where('id', $data->deskId)
                ->where('room_id', $data->roomId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($desk->status !== 'available') {
                throw new LoanDomainException('Meja sudah terpakai');
            }

            $loan->update([
                'status' => LoanStatus::APPROVED->value,
                'room_id' => $data->roomId,
                'desk_id' => $data->deskId,
                'approved_by' => $data->approverId,
            ]);

            $desk->update(['status' => 'occupied']);

            return $loan->fresh();
        });

        event(new LoanApproved($loan));

        return $loan;
    }
}
