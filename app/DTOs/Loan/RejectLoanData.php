<?php

declare(strict_types=1);

namespace App\DTOs\Loan;

readonly class RejectLoanData
{
    public function __construct(
        public int $loanId,
        public int $approverId,
        public string $adminNotes,
    ) {
    }
}
