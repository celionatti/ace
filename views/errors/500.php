@extends('layouts/main')

@section('title')
{{ $code ?? 500 }} - Server Error
@endsection

@section('content')
<?php
$isDev = ($_ENV['APP_ENV'] ?? 'development') === 'development';
?>

<div class="error-container" style="max-width: 800px; animation: slideIn var(--transition-normal);">
    <div style="margin-bottom: 2rem; position: relative; display: inline-block;">
        <!-- Warning Shield/Bug SVG -->
        <svg width="120" height="120" viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg" style="filter: drop-shadow(0 0 20px rgba(239, 68, 68, 0.4)); animation: pulse 3s infinite;">
            <path d="M60 15L15 35V65C15 89.2 34.2 101.5 60 105C85.8 101.5 105 89.2 105 65V35L60 15Z" fill="url(#shieldGrad)" fill-opacity="0.15" stroke="url(#shieldGrad)" stroke-width="3"/>
            <path d="M60 40V70" stroke="#ef4444" stroke-width="4" stroke-linecap="round"/>
            <circle cx="60" cy="82" r="3" fill="#ef4444"/>
            <defs>
                <linearGradient id="shieldGrad" x1="15" y1="15" x2="105" y2="105" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#ef4444"/>
                    <stop offset="1" stop-color="#f59e0b"/>
                </linearGradient>
            </defs>
        </svg>
    </div>

    <div class="error-code" style="font-size: 6rem; background: linear-gradient(135deg, #ef4444, #f59e0b); -webkit-background-clip: text; -webkit-text-fill-color: transparent; text-shadow: 0 0 30px rgba(239, 68, 68, 0.2); margin-bottom: 0.5rem;">
        {{ $code ?? 500 }}
    </div>
    <h1 style="font-size: 2.2rem; margin-bottom: 1rem; font-weight: 700;">System Overload</h1>
    <p style="margin-bottom: 2.5rem; color: var(--text-secondary); max-width: 550px; margin-left: auto; margin-right: auto; line-height: 1.6;">
        {{ $message ?? 'An unexpected engine failure occurred on our server.' }}
    </p>

    @if($isDev && isset($exception))
        <div class="error-details" style="background: rgba(18, 24, 36, 0.8); border: 1px solid rgba(239, 68, 68, 0.25); border-radius: 1rem; padding: 2rem; text-align: left; box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.4); backdrop-filter: blur(10px);">
            <div style="font-weight: 700; color: #f87171; border-bottom: 1px solid rgba(239, 68, 68, 0.2); padding-bottom: 0.75rem; margin-bottom: 1.25rem; font-size: 1.05rem; display: flex; align-items: center; gap: 0.5rem;">
                <span>🐞</span> Framework Debugger (Development Mode)
            </div>
            
            <div style="margin-bottom: 0.75rem; display: flex; gap: 0.5rem;">
                <span style="color: var(--text-muted); font-weight: 600; min-width: 90px;">Exception:</span>
                <span style="color: #f87171; font-family: monospace; word-break: break-all;"><?php echo get_class($exception); ?></span>
            </div>
            
            <div style="margin-bottom: 0.75rem; display: flex; gap: 0.5rem;">
                <span style="color: var(--text-muted); font-weight: 600; min-width: 90px;">File:</span>
                <span style="color: #fb7185; font-family: monospace; word-break: break-all;">{{ $file }} : Line {{ $line }}</span>
            </div>

            <div style="margin-top: 1.5rem;">
                <div style="font-weight: 600; color: var(--text-primary); margin-bottom: 0.5rem; font-size: 0.95rem;">Stack Trace:</div>
                <pre style="white-space: pre-wrap; font-family: monospace; font-size: 0.8rem; background: rgba(0, 0, 0, 0.4); padding: 1.25rem; border-radius: 0.75rem; color: #a1a1aa; max-height: 250px; overflow-y: auto; border: 1px solid rgba(255, 255, 255, 0.05); line-height: 1.5;">{{ $trace }}</pre>
            </div>
        </div>
    @endif

    <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 3rem;">
        <a href="{{ route('/') }}" class="btn btn-primary" style="padding: 0.8rem 2rem; border-radius: 0.75rem;">Go to Homepage</a>
        <button onclick="location.reload()" class="btn btn-secondary" style="padding: 0.8rem 2rem; border-radius: 0.75rem; background: rgba(255, 255, 255, 0.05); border: 1px solid var(--border-glass); color: var(--text-primary);">Retry Request</button>
    </div>
</div>

<style>
@keyframes pulse {
    0% { transform: scale(1); filter: drop-shadow(0 0 15px rgba(239, 68, 68, 0.3)); }
    50% { transform: scale(1.05); filter: drop-shadow(0 0 25px rgba(239, 68, 68, 0.5)); }
    100% { transform: scale(1); filter: drop-shadow(0 0 15px rgba(239, 68, 68, 0.3)); }
}
</style>
@endsection

