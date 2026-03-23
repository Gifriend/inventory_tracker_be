<?php

namespace App\Http\Controllers;

use App\Actions\Auth\LoginUserAction;
use App\Actions\Auth\RegisterUserAction;
use App\DTOs\Auth\LoginUserData;
use App\DTOs\Auth\RegisterUserData;
use App\Exceptions\LoanDomainException;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;

class AuthController extends Controller
{
      public function register(RegisterRequest $request, RegisterUserAction $registerUser)
      {
            $user = $registerUser(new RegisterUserData(
                  name: $request->input('name'),
                  email: $request->input('email'),
                  password: $request->input('password'),
                  role: $request->input('role'),
            ));

            return $this->successResponse($user, 'Registrasi berhasil', 201);
      }

      public function login(LoginRequest $request, LoginUserAction $loginUser)
      {
            try {
                  $result = $loginUser(new LoginUserData(
                        email: $request->input('email'),
                        password: $request->input('password'),
                  ));

                  return $this->successResponse([
                        'token' => $result['token'],
                        'user_data' => $result['user'],
                  ], 'Login berhasil');
            } catch (LoanDomainException $exception) {
                  return $this->errorResponse($exception->getMessage(), 401);
            }
      }
}

