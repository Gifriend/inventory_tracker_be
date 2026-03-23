<?php

declare(strict_types=1);

namespace App\Actions\Loan;

use App\DTOs\Loan\CreateLoanData;
use App\Models\Loan;

final class CreateLoanAction
{
    public function __invoke(CreateLoanData $data): Loan
    {
        return Loan::create([
            'user_id' => $data->userId,
            'document_path' => $data->documentPath,
            'start_time' => $data->startTime,
            'end_time' => $data->endTime,
            'status' => 'pending',
        ]);
    }
}
