<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::guard('web')->check()) {
            return redirect()->route('affiliate.dashboard');
        }

        return view('admin.login');
    }

    public function showRegisterForm()
    {
        if (Auth::guard('web')->check()) {
            return redirect()->route('affiliate.dashboard');
        }

        // Optional safety switch. If set to false, hide registration.
        if (! filter_var(env('AFFILIATE_SELF_REGISTER_ENABLED', true), FILTER_VALIDATE_BOOL)) {
            abort(404);
        }

        return view('admin.register');
    }

    public function register(Request $request)
    {
        // Optional safety switch. If set to false, disable registration.
        if (! filter_var(env('AFFILIATE_SELF_REGISTER_ENABLED', true), FILTER_VALIDATE_BOOL)) {
            abort(404);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => mb_strtolower($data['email']),
            'password' => Hash::make($data['password']),
        ]);

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('affiliate.dashboard')
            ->with('status', 'Account created.');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::guard('web')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended(route('affiliate.dashboard'));
        }

        return back()
            ->withErrors(['email' => 'Invalid login credentials.'])
            ->withInput($request->only('email'));
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('affiliate.login');
    }
}
