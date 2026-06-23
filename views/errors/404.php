@extends('layouts/main')

@section('title')
404 - Page Not Found
@endsection

@section('content')
<div class="error-container" style="animation: slideIn var(--transition-normal);">
    <div style="margin-bottom: 2rem; position: relative; display: inline-block;">
        <!-- Glowing Space Portal SVG -->
        <svg width="200" height="200" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg" style="filter: drop-shadow(0 0 25px rgba(99, 102, 241, 0.45)); animation: float 6s ease-in-out infinite;">
            <circle cx="100" cy="100" r="80" fill="url(#portalGrad)" fill-opacity="0.15" stroke="url(#portalGrad)" stroke-width="3" stroke-dasharray="10 5"/>
            <circle cx="100" cy="100" r="60" fill="url(#portalInner)" fill-opacity="0.2"/>
            <path d="M70 100C70 83.4315 83.4315 70 100 70C116.569 70 130 83.4315 130 100" stroke="#ec4899" stroke-width="4" stroke-linecap="round"/>
            <circle cx="100" cy="100" r="10" fill="#f1f5f9"/>
            <defs>
                <linearGradient id="portalGrad" x1="20" y1="20" x2="180" y2="180" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#6366f1"/>
                    <stop offset="1" stop-color="#8b5cf6"/>
                </linearGradient>
                <linearGradient id="portalInner" x1="40" y1="40" x2="160" y2="160" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#ec4899"/>
                    <stop offset="1" stop-color="#8b5cf6"/>
                </linearGradient>
            </defs>
        </svg>
    </div>
    
    <div class="error-code" style="margin-bottom: 0.5rem; text-shadow: 0 0 20px rgba(99, 102, 241, 0.3);">404</div>
    <h1 style="font-size: 2.5rem; margin-bottom: 1rem; font-weight: 700; background: var(--secondary-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Lost in Deep Space</h1>
    <p style="margin-bottom: 2.5rem; color: var(--text-secondary); max-width: 500px; margin-left: auto; margin-right: auto; line-height: 1.6;">The page you are looking for has drifted out of orbit or was never created. Let's get you back to safety.</p>
    
    <div style="display: flex; gap: 1rem; justify-content: center;">
        <a href="{{ route('/') }}" class="btn btn-primary" style="padding: 0.8rem 2rem; border-radius: 0.75rem;">Go to Homepage</a>
        <button onclick="window.history.back()" class="btn btn-secondary" style="padding: 0.8rem 2rem; border-radius: 0.75rem; background: rgba(255, 255, 255, 0.05); border: 1px solid var(--border-glass); color: var(--text-primary);">Go Back</button>
    </div>
</div>

<style>
@keyframes float {
    0% { transform: translateY(0px) rotate(0deg); }
    50% { transform: translateY(-15px) rotate(5deg); }
    100% { transform: translateY(0px) rotate(0deg); }
}
</style>
@endsection

