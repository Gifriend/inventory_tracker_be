<?php

declare(strict_types=1);

namespace App\Actions\Loan;

use App\Enums\LoanStatus;
use App\Events\LoanCheckedOut;
use App\Exceptions\LoanDomainException;
use App\Models\Desk;
use App\Models\Loan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final class CheckOutLoanAction
{
    public function __invoke(): Loan
    {
        $loan = DB::transaction(function (): Loan {
            $loan = Loan::where('user_id', Auth::id())
                ->where('status', LoanStatus::APPROVED->value)
                ->whereNotNull('check_in_time')
                ->whereNull('check_out_time')
                ->lockForUpdate()
                ->first();

            if (!$loan) {
                throw new LoanDomainException('Anda belum Check-In atau tidak ada sesi aktif.');
            }

            $loan->update([
                'check_out_time' => now(),
                'status' => 'completed',
            ]);

            $desk = Desk::whereKey($loan->desk_id)->lockForUpdate()->first();
            if ($desk) {
                $desk->update(['status' => 'available']);
            }

            return $loan->fresh();
        });

        event(new LoanCheckedOut($loan));

        return $loan;
    }
}
