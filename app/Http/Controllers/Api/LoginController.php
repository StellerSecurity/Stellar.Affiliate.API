<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class LoginController extends Controller
{
    private function issueTokenForUser(User $user): string
    {
        $token = Str::random(60);
        $user->api_token = hash('sha256', $token);
        $user->save();

        // This is the token the frontend should store (NOT the hashed one)
        return $token;
    }

    /**
     * Register a new user and issue an API token.
     *
     * Request:
     *  POST /api/v1/auth/register
     *  {
     *    "name": "Jane Doe",
     *    "email": "affiliate@example.com",
     *    "password": "very-long-secret",
     *    "password_confirmation": "very-long-secret"
     *  }
     */
    public function register(Request $request): Response
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:12|confirmed',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => mb_strtolower($data['email']),
            'password' => Hash::make($data['password']),
        ]);

        $token = $this->issueTokenForUser($user);

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ],
        ], 201);
    }

    /**
     * Basic email/password login for affiliates.
     *
     * Request:
     *  POST /api/v1/auth/login
     *  {
     *    "email": "affiliate@example.com",
     *    "password": "secret123"
     *  }
     *
     * Response:
     *  {
     *    "token": "xxxx",
     *    "user": { ... }
     *  }
     */
    public function login(Request $request): Response
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        /** @var \App\Models\User|null $user */
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        $token = $this->issueTokenForUser($user);

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ],
        ]);
    }
}
