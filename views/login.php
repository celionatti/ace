@extends('layouts/main')

@section('title')
Login - Mini MVC
@endsection

@section('content')
<?php
/** @var \App\Models\User $model */
?>

<div class="auth-grid">
    <div class="card">
        <h2 style="margin-bottom: 0.5rem; font-size: 1.8rem;" class="gradient-text">Welcome Back</h2>
        <p style="margin-bottom: 2rem; font-size: 0.95rem; color: var(--text-secondary);">Log in to access your profile and payment logs.</p>
        
        <form action="{{ route('login') }}" method="POST" novalidate>
            @csrf
            
            <div class="form-group">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" name="email" id="email" 
                       class="form-control @if($model->hasError('email')) is-invalid @endif"
                       value="{{ $model->email ?? '' }}" required>
                @if($model->hasError('email'))
                    <span class="invalid-feedback">{{ $model->getFirstError('email') }}</span>
                @endif
            </div>

            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" name="password" id="password" 
                       class="form-control @if($model->hasError('password')) is-invalid @endif" required>
                @if($model->hasError('password'))
                    <span class="invalid-feedback">{{ $model->getFirstError('password') }}</span>
                @endif
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; font-size: 0.9rem;">
                <label style="display: flex; align-items: center; cursor: pointer; color: var(--text-secondary);">
                    <input type="checkbox" name="remember" value="1" style="margin-right: 0.5rem; accent-color: var(--primary-color);">
                    Remember Me
                </label>
                <a href="{{ route('forgot-password') }}" class="gradient-text-alt" style="text-decoration: none; font-weight: 500;">Forgot Password?</a>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;">Sign In</button>
        </form>
        
        <div style="margin-top: 1.5rem; text-align: center; font-size: 0.9rem; color: var(--text-secondary);">
            Don't have an account? <a href="{{ route('register') }}" class="gradient-text-alt" style="font-weight: 600; text-decoration: none;">Register here</a>
        </div>
    </div>
</div>
@endsection

