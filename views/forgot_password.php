@extends('layouts/main')

@section('title')
Forgot Password - Mini MVC
@endsection

@section('content')
<div class="auth-grid" style="display: flex; justify-content: center; align-items: center; min-height: 60vh;">
    <div class="card" style="width: 100%; max-width: 450px;">
        <h2 style="margin-bottom: 0.5rem; font-size: 1.8rem;" class="gradient-text">Forgot Password</h2>
        <p style="margin-bottom: 2rem; font-size: 0.95rem; color: var(--text-secondary);">Enter your email address and we'll send you a link to reset your password.</p>
        
        <form action="{{ route('forgot-password') }}" method="POST" novalidate>
            @csrf
            
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label for="email" class="form-label" style="display: block; margin-bottom: 0.5rem; color: var(--text-primary); font-weight: 500;">Email Address</label>
                <input type="email" name="email" id="email" 
                       class="form-control" style="width: 100%; padding: 0.75rem 1rem; border-radius: 0.5rem; background: rgba(255, 255, 255, 0.05); border: 1px solid var(--border-glass); color: var(--text-primary); outline: none; transition: var(--transition-fast);" required>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem; padding: 0.75rem; font-weight: 600; border-radius: 0.5rem; border: none; cursor: pointer; background: var(--primary-gradient); color: #fff; box-shadow: 0 4px 14px rgba(99, 102, 241, 0.4);">Send Reset Link</button>
        </form>
        
        <div style="margin-top: 1.5rem; text-align: center; font-size: 0.9rem; color: var(--text-secondary);">
            Remember your password? <a href="{{ route('login') }}" class="gradient-text-alt" style="font-weight: 600; text-decoration: none;">Login here</a>
        </div>
    </div>
</div>
@endsection

