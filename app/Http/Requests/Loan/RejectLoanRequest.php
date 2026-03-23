<?php

declare(strict_types=1);

namespace App\Http\Requests\Loan;

use Illuminate\Foundation\Http\FormRequest;

class RejectLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'admin_notes' => 'required|string',
        ];
    }
}
