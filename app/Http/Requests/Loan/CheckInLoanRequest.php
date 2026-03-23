<?php

declare(strict_types=1);

namespace App\Http\Requests\Loan;

use Illuminate\Foundation\Http\FormRequest;

class CheckInLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'room_id' => 'required|exists:rooms,id',
            'desk_id' => 'required|exists:desks,id',
        ];
    }
}
