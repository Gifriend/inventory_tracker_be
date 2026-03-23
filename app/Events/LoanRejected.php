<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Loan;

final class LoanRejected
{
    public function __construct(public Loan $loan)
    {
    }
}
