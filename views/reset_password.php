@extends('layouts/main')

@section('title')
Reset Password - Mini MVC
@endsection

@section('content')
<?php
/** @var \App\Models\User $model */
?>

<div class="auth-grid" style="display: flex; justify-content: center; align-items: center; min-height: 60vh;">
    <div class="card" style="width: 100%; max-width: 450px;">
        <h2 style="margin-bottom: 0.5rem; font-size: 1.8rem;" class="gradient-text">Reset Password</h2>
        <p style="margin-bottom: 2rem; font-size: 0.95rem; color: var(--text-secondary);">Create a new password for your account.</p>
        
        <form action="{{ route('reset-password') }}" method="POST" novalidate>
            @csrf
            
            <input type="hidden" name="token" value="{{ $token }}">
            <input type="hidden" name="email" value="{{ $email }}">

            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label for="password" class="form-label" style="display: block; margin-bottom: 0.5rem; color: var(--text-primary); font-weight: 500;">New Password</label>
                <input type="password" name="password" id="password" 
                       class="form-control @if($model->hasError('password')) is-invalid @endif" 
                       style="width: 100%; padding: 0.75rem 1rem; border-radius: 0.5rem; background: rgba(255, 255, 255, 0.05); border: 1px solid var(--border-glass); color: var(--text-primary); outline: none;" required>
                @if($model->hasError('password'))
                    <span class="invalid-feedback" style="display: block; font-size: 0.8rem; color: var(--error); margin-top: 0.25rem;">{{ $model->getFirstError('password') }}</span>
                @endif
            </div>

            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label for="passwordConfirm" class="form-label" style="display: block; margin-bottom: 0.5rem; color: var(--text-primary); font-weight: 500;">Confirm New Password</label>
                <input type="password" name="passwordConfirm" id="passwordConfirm" 
                       class="form-control @if($model->hasError('passwordConfirm')) is-invalid @endif" 
                       style="width: 100%; padding: 0.75rem 1rem; border-radius: 0.5rem; background: rgba(255, 255, 255, 0.05); border: 1px solid var(--border-glass); color: var(--text-primary); outline: none;" required>
                @if($model->hasError('passwordConfirm'))
                    <span class="invalid-feedback" style="display: block; font-size: 0.8rem; color: var(--error); margin-top: 0.25rem;">{{ $model->getFirstError('passwordConfirm') }}</span>
                @endif
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem; padding: 0.75rem; font-weight: 600; border-radius: 0.5rem; border: none; cursor: pointer; background: var(--primary-gradient); color: #fff; box-shadow: 0 4px 14px rgba(99, 102, 241, 0.4);">Reset Password</button>
        </form>
    </div>
</div>
@endsection

