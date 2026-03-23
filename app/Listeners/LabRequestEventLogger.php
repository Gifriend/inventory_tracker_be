<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\LabRequestApproved;
use App\Events\LabRequestCreated;
use App\Events\LabRequestRejected;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Log\LogManager;

final class LabRequestEventLogger
{
    public function __construct(private LogManager $logger)
    {
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(LabRequestCreated::class, [self::class, 'onLabRequestCreated']);
        $events->listen(LabRequestApproved::class, [self::class, 'onLabRequestApproved']);
        $events->listen(LabRequestRejected::class, [self::class, 'onLabRequestRejected']);
    }

    public function onLabRequestCreated(LabRequestCreated $event): void
    {
        $this->logger->info('Lab request created', ['request_id' => $event->request->id]);
    }

    public function onLabRequestApproved(LabRequestApproved $event): void
    {
        $this->logger->info('Lab request approved', ['request_id' => $event->request->id]);
    }

    public function onLabRequestRejected(LabRequestRejected $event): void
    {
        $this->logger->info('Lab request rejected', ['request_id' => $event->request->id]);
    }
}
