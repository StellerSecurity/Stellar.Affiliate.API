<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

class AffiliatePasswordResetController extends Controller
{
    public function request()
    {
        return response()->view('admin.forgot-password')->header('Cache-Control', 'no-store');
    }

    public function email(Request $request)
    {
        $this->normalizeEmail($request);
        $data = $request->validate(['email' => ['required', 'string', 'email', 'max:255']]);

        try {
            Password::broker('users')->sendResetLink($data);
        } catch (Throwable $exception) {
            // Never log the request, reset token, email URL, or provider response.
            Log::error('Affiliate password reset email delivery failed.', ['exception' => $exception::class]);
        }

        // Existing, unknown, and throttled accounts receive the same response.
        return redirect()->route('affiliate.password.request')->with('status',
            'If an account matches that email, you will receive a password reset link shortly. Check your spam folder, or try again in a few minutes.');
    }

    public function edit(Request $request, string $token)
    {
        return response()->view('admin.reset-password', [
            'token' => $token,
            'email' => is_string($request->query('email')) ? $request->query('email') : '',
        ])->header('Cache-Control', 'no-store')->header('Referrer-Policy', 'no-referrer');
    }

    public function update(Request $request)
    {
        $this->normalizeEmail($request);
        $validator = Validator::make($request->only('token', 'email', 'password', 'password_confirmation'), [
            'token' => ['required', 'string', 'size:64'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput($request->only('email'));
        }

        $data = $validator->validated();

        $status = Password::broker('users')->reset($data, function (User $user, string $password): void {
            $user->forceFill([
                'password' => Hash::make($password),
                'remember_token' => Str::random(60),
                'api_token' => null,
            ])->save();

            event(new PasswordReset($user));
        });

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors(['email' => 'This password reset link is invalid or has expired. Request a new link below.'])
                ->withInput($request->only('email'));
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('affiliate.login')->with('status', 'Your password has been reset. Log in with your new password.');
    }

    private function normalizeEmail(Request $request): void
    {
        if (is_string($request->input('email'))) {
            $request->merge(['email' => mb_strtolower(trim($request->input('email')))]);
        }
    }
}
