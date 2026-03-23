<?php

declare(strict_types=1);

namespace App\DTOs\Auth;

readonly class LoginUserData
{
    public function __construct(
        public string $email,
        public string $password,
    ) {
    }
}
