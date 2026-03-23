<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\LabRequest;

final class LabRequestRejected
{
    public function __construct(public LabRequest $request)
    {
    }
}
