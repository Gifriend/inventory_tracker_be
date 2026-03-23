<?php

declare(strict_types=1);

namespace App\DTOs\LabRequest;

readonly class RejectLabRequestData
{
    public function __construct(
        public int $requestId,
    ) {
    }
}
