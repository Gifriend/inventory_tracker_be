<?php

declare(strict_types=1);

namespace App\DTOs\LabRequest;

readonly class ApproveLabRequestData
{
    public function __construct(
        public int $requestId,
        public int $labId,
        public int $tableId,
    ) {
    }
}
