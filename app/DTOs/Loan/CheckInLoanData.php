<?php

declare(strict_types=1);

namespace App\DTOs\Loan;

readonly class CheckInLoanData
{
    public function __construct(
        public int $userId,
        public int $roomId,
        public int $deskId,
    ) {
    }
}
