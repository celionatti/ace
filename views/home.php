@extends('layouts/main')

@section('title')
Welcome to Mini MVC
@endsection

@section('content')
<div class="hero">
    <h1>Supercharge Your Web Apps with <br><span class="gradient-text">Mini PHP MVC</span></h1>
    <p>A lightweight, handcrafted, production-ready PHP MVC framework. Comes with a powerful custom Active Record ORM, secure middleware, dynamic routing, and Paystack payments built-in.</p>
    <div style="display: flex; gap: 1rem; justify-content: center;">
        @guest
            <a href="{{ route('register') }}" class="btn btn-primary">Get Started Free</a>
            <a href="{{ route('login') }}" class="btn btn-secondary">Sign In</a>
        @endguest
        @auth
            <a href="{{ route('profile') }}" class="btn btn-primary">Go to Dashboard</a>
            <a href="{{ route('pay') }}" class="btn btn-outline">Make a Payment</a>
        @endauth
    </div>
</div>

<div class="card" style="margin-top: -2rem;">
    <h2 style="text-align: center; margin-bottom: 2rem; font-size: 1.8rem;" class="gradient-text-alt">Built-in Features</h2>
    
    <div class="features">
        <div class="feature-card">
            <span class="feature-icon">🛡️</span>
            <h3>Dynamic Routing & Middleware</h3>
            <p>Define static and dynamic parameterized routes (like `/users/{id}`) and wrap them with robust route-level or controller-level middlewares.</p>
        </div>
        <div class="feature-card">
            <span class="feature-icon">⚡</span>
            <h3>Active Record ORM</h3>
            <p>Query, save, update, and delete database records elegantly using the Active Record pattern. Zero-config schema mapping.</p>
        </div>
        <div class="feature-card">
            <span class="feature-icon">💳</span>
            <h3>Paystack, Stripe & Flutterwave</h3>
            <p>Seamlessly initialize, verify, and track transaction logs using three integrated native payment gateway clients.</p>
        </div>
        <div class="feature-card">
            <span class="feature-icon">🧪</span>
            <h3>Self-Healing Database</h3>
            <p>Automatic migration triggers on startup to compile and verify necessary database schemas without manual import scripts.</p>
        </div>
        <div class="feature-card">
            <span class="feature-icon">🎛️</span>
            <h3>Blade-Like Template Engine</h3>
            <p>Use directives like @@extends, @@section, @@yield, @@if, @@foreach, @@auth and @@guest with auto-compiled caching.</p>
        </div>
        <div class="feature-card">
            <span class="feature-icon">🎨</span>
            <h3>Premium Glassmorphic UI</h3>
            <p>Stunning, modern aesthetic featuring Outfit typography, CSS custom properties, responsive grids, and clean dark mode styles.</p>
        </div>
    </div>
</div>
@endsection

