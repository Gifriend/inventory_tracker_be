<?php

declare(strict_types=1);

namespace App\Actions\Loan;

use App\DTOs\Loan\RejectLoanData;
use App\Exceptions\LoanDomainException;
use App\Models\Loan;
use Illuminate\Support\Facades\DB;

final class RejectLoanAction
{
    public function __invoke(RejectLoanData $data): Loan
    {
        return DB::transaction(function () use ($data): Loan {
            $loan = Loan::whereKey($data->loanId)->lockForUpdate()->firstOrFail();

            if ($loan->status !== 'pending') {
                throw new LoanDomainException('Permohonan sudah diproses sebelumnya');
            }

            $loan->update([
                'status' => 'rejected',
                'admin_notes' => $data->adminNotes,
                'approved_by' => $data->approverId,
            ]);

            return $loan->fresh();
        });
    }
}
