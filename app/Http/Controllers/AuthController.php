<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
      public function register(Request $request)
      {
            $validated = $request->validate([
                  'name' => 'required|string|max:255',
                  'email' => 'required|string|email|unique:users',
                  'password' => 'required|string|min:6',
                  'role' => 'required|in:user,aslab'
            ]);

            $user = User::create([
                  'name' => $validated['name'],
                  'email' => $validated['email'],
                  'password' => Hash::make($validated['password']),
                  'role' => $validated['role'],
            ]);

            return $this->successResponse($user, 'Registrasi berhasil', 201);
      }

      public function login(Request $request)
      {
            $request->validate(['email' => 'required', 'password' => 'required']);

            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                  return $this->errorResponse('Kredensial salah', 401);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return $this->successResponse([
                  'token' => $token,
                  'user_data' => $user
            ], 'Login berhasil');
      }
}
