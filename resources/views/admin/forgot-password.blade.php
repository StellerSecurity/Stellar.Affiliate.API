@extends('layouts.affiliate-auth')

@section('title', 'Forgot your password?')
@section('description', 'Enter your account email and we will send you a link to reset your password.')

@section('form')
    <form method="POST" action="{{ route('affiliate.password.email') }}" class="stellar-form-grid">
        @csrf
        <div class="stellar-field">
            <label class="stellar-label" for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus maxlength="255" autocomplete="email" class="stellar-input" placeholder="you@example.com">
        </div>
        <button type="submit" class="stellar-btn stellar-btn-primary">Send reset link</button>
    </form>
    <div class="stellar-auth-alt"><a href="{{ route('affiliate.login') }}">Back to log in</a></div>
@endsection
