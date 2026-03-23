<?php

declare(strict_types=1);

namespace App\Actions\LabRequest;

use App\DTOs\LabRequest\CreateLabRequestData;
use App\Events\LabRequestCreated;
use App\Models\LabRequest;

final class CreateLabRequestAction
{
    public function __invoke(CreateLabRequestData $data): LabRequest
    {
        $request = LabRequest::create([
            'user_id' => $data->userId,
            'pdf_path' => $data->pdfPath,
            'message' => $data->message,
            'status' => 'pending',
        ]);

        event(new LabRequestCreated($request));

        return $request;
    }
}
