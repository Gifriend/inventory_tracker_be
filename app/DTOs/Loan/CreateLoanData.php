<?php

declare(strict_types=1);

namespace App\DTOs\Loan;

use Carbon\CarbonImmutable;

readonly class CreateLoanData
{
    public function __construct(
        public int $userId,
        public ?string $documentPath,
        public CarbonImmutable $startTime,
        public CarbonImmutable $endTime,
    ) {
    }
}
