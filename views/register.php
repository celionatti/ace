@extends('layouts/main')

@section('title')
Register - Mini MVC
@endsection

@section('content')
<?php
/** @var \App\Models\User $model */
?>

<div class="auth-grid">
    <div class="card">
        <h2 style="margin-bottom: 0.5rem; font-size: 1.8rem;" class="gradient-text-alt">Create Account</h2>
        <p style="margin-bottom: 2rem; font-size: 0.95rem; color: var(--text-secondary);">Join us today and get access to payments and dashboard.</p>
        
        <form action="{{ route('register') }}" method="POST" novalidate>
            @csrf
            
            <div class="form-group">
                <label for="name" class="form-label">Full Name</label>
                <input type="text" name="name" id="name" 
                       class="form-control @if($model->hasError('name')) is-invalid @endif"
                       value="{{ $model->name ?? '' }}" required>
                @if($model->hasError('name'))
                    <span class="invalid-feedback">{{ $model->getFirstError('name') }}</span>
                @endif
            </div>

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

            <div class="form-group">
                <label for="passwordConfirm" class="form-label">Confirm Password</label>
                <input type="password" name="passwordConfirm" id="passwordConfirm" 
                       class="form-control @if($model->hasError('passwordConfirm')) is-invalid @endif" required>
                @if($model->hasError('passwordConfirm'))
                    <span class="invalid-feedback">{{ $model->getFirstError('passwordConfirm') }}</span>
                @endif
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Register</button>
        </form>
        
        <div style="margin-top: 1.5rem; text-align: center; font-size: 0.9rem; color: var(--text-secondary);">
            Already have an account? <a href="{{ route('login') }}" class="gradient-text" style="font-weight: 600; text-decoration: none;">Login here</a>
        </div>
    </div>
</div>
@endsection

