<?php

declare(strict_types=1);

namespace App\DTOs\Loan;

readonly class ApproveLoanData
{
    public function __construct(
        public int $loanId,
        public int $roomId,
        public int $deskId,
        public int $approverId,
    ) {
    }
}
