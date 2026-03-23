<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\DTOs\Auth\LoginUserData;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Exceptions\LoanDomainException;

final class LoginUserAction
{
    public function __invoke(LoginUserData $input): array
    {
        $user = User::where('email', $input->email)->first();

        if (!$user || !Hash::check($input->password, $user->password)) {
            throw new LoanDomainException('Kredensial salah');
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }
}
