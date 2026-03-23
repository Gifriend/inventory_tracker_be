<?php

declare(strict_types=1);

namespace App\DTOs\LabRequest;

readonly class CreateLabRequestData
{
    public function __construct(
        public int $userId,
        public string $pdfPath,
        public ?string $message,
    ) {
    }
}
