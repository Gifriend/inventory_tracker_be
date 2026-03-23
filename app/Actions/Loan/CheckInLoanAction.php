<?php

declare(strict_types=1);

namespace App\Actions\Loan;

use App\DTOs\Loan\CheckInLoanData;
use App\Exceptions\LoanDomainException;
use App\Models\Desk;
use App\Models\Loan;
use Illuminate\Support\Facades\DB;

final class CheckInLoanAction
{
    public function __invoke(CheckInLoanData $data): Loan
    {
        return DB::transaction(function () use ($data): Loan {
            $loan = Loan::where('user_id', $data->userId)
                ->where('status', 'approved')
                ->where('room_id', $data->roomId)
                ->where('desk_id', $data->deskId)
                ->whereNull('check_in_time')
                ->lockForUpdate()
                ->first();

            if (!$loan) {
                throw new LoanDomainException('Tidak ada jadwal peminjaman valid untuk meja ini atau Anda sudah Check-In.');
            }

            $desk = Desk::whereKey($data->deskId)->lockForUpdate()->first();

            if (!$desk || $desk->room_id !== $data->roomId) {
                throw new LoanDomainException('Meja tidak valid untuk ruangan ini.');
            }

            if ($desk->status === 'maintenance') {
                throw new LoanDomainException('Meja sedang maintenance.');
            }

            $loan->update(['check_in_time' => now()]);

            if ($desk->status !== 'occupied') {
                $desk->update(['status' => 'occupied']);
            }

            return $loan->fresh();
        });
    }
}
