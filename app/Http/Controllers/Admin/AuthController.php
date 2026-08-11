<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\AffiliateCampaign;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    private function destinationFor(User $user): string
    {
        if ($user->hasAffiliateAdminAccess()) {
            return route('affiliate.admin.dashboard');
        }

        $affiliate = Affiliate::query()
            ->where('external_user_id', $user->id)
            ->first();

        if (! $affiliate) {
            $affiliate = Affiliate::query()
                ->whereNull('external_user_id')
                ->where('email', $user->email)
                ->first();
        }

        if (! $affiliate || ! AffiliateCampaign::where('affiliate_id', $affiliate->id)->exists()) {
            return route('affiliate.onboarding');
        }

        return route('affiliate.dashboard');
    }

    public function showLoginForm()
    {
        if (Auth::guard('web')->check()) {
            /** @var User $user */
            $user = Auth::guard('web')->user();

            return redirect()->to($this->destinationFor($user));
        }

        return view('admin.login');
    }

    public function showRegisterForm()
    {
        if (Auth::guard('web')->check()) {
            /** @var User $user */
            $user = Auth::guard('web')->user();

            return redirect()->to($this->destinationFor($user));
        }

        if (! (bool) config('affiliate.self_register_enabled', true)) {
            abort(404);
        }

        return view('admin.register');
    }

    public function register(Request $request)
    {
        if (! (bool) config('affiliate.self_register_enabled', true)) {
            abort(404);
        }

        $request->merge([
            'email' => mb_strtolower(trim((string) $request->input('email'))),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
        ]);

        $user = User::create([
            'name' => trim($data['name']),
            'email' => mb_strtolower(trim($data['email'])),
            'password' => Hash::make($data['password']),
        ]);

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('affiliate.onboarding')
            ->with('status', 'Account created. Set up your affiliate profile to continue.');
    }

    public function login(Request $request)
    {
        $request->merge([
            'email' => mb_strtolower(trim((string) $request->input('email'))),
        ]);

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        if (Auth::guard('web')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            /** @var User $user */
            $user = $request->user();

            return redirect()->to($this->destinationFor($user));
        }

        return back()
            ->withErrors(['email' => 'Email or password is incorrect.'])
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
