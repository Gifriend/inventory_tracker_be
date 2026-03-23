<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\LoanApproved;
use App\Events\LoanCheckedIn;
use App\Events\LoanCheckedOut;
use App\Events\LoanCreated;
use App\Events\LoanRejected;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Log\LogManager;

final class LoanEventLogger implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(private LogManager $logger)
    {
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(LoanCreated::class, [self::class, 'onLoanCreated']);
        $events->listen(LoanApproved::class, [self::class, 'onLoanApproved']);
        $events->listen(LoanRejected::class, [self::class, 'onLoanRejected']);
        $events->listen(LoanCheckedIn::class, [self::class, 'onLoanCheckedIn']);
        $events->listen(LoanCheckedOut::class, [self::class, 'onLoanCheckedOut']);
    }

    public function onLoanCreated(LoanCreated $event): void
    {
        $this->logger->info('Loan created', ['loan_id' => $event->loan->id]);
    }

    public function onLoanApproved(LoanApproved $event): void
    {
        $this->logger->info('Loan approved', ['loan_id' => $event->loan->id]);
    }

    public function onLoanRejected(LoanRejected $event): void
    {
        $this->logger->info('Loan rejected', ['loan_id' => $event->loan->id]);
    }

    public function onLoanCheckedIn(LoanCheckedIn $event): void
    {
        $this->logger->info('Loan checked in', ['loan_id' => $event->loan->id]);
    }

    public function onLoanCheckedOut(LoanCheckedOut $event): void
    {
        $this->logger->info('Loan checked out', ['loan_id' => $event->loan->id]);
    }
}
