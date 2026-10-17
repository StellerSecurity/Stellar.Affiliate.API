@extends('layouts.affiliate-auth')

@section('title', 'Reset your password')
@section('description', 'Choose a new password for your affiliate account.')

@section('form')
    <form method="POST" action="{{ route('affiliate.password.update') }}" class="stellar-form-grid">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div class="stellar-field">
            <label class="stellar-label" for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required maxlength="255" autocomplete="email" class="stellar-input">
        </div>
        <div class="stellar-field">
            <label class="stellar-label" for="password">New password</label>
            <input id="password" type="password" name="password" required autofocus minlength="8" maxlength="255" autocomplete="new-password" class="stellar-input" aria-describedby="password-help">
            <p id="password-help" class="stellar-field-help">Use at least 8 characters.</p>
        </div>
        <div class="stellar-field">
            <label class="stellar-label" for="password_confirmation">Confirm new password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required minlength="8" maxlength="255" autocomplete="new-password" class="stellar-input">
        </div>
        <button type="submit" class="stellar-btn stellar-btn-primary">Reset password</button>
    </form>
    <div class="stellar-auth-alt"><a href="{{ route('affiliate.password.request') }}">Request a new reset link</a></div>
    <div class="stellar-auth-alt"><a href="{{ route('affiliate.login') }}">Back to log in</a></div>
@endsection
