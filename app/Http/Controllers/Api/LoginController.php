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

        // Generate a simple API token for future use
        $token = Str::random(60);

        $user->api_token = hash('sha256', $token);
        $user->save();

        return response()->json([
            // This is the token the frontend should store (NOT the hashed one)
            'token' => $token,
            'user'  => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ],
        ]);
    }
}
