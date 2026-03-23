<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Loan;

final class LoanCheckedOut
{
    public function __construct(public Loan $loan)
    {
    }
}
