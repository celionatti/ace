<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>
    <link rel="stylesheet" href="{{ route('assets/css/style.css') }}">
</head>
<body>

    <!-- Dynamic Glassmorphic Navbar -->
    <nav class="navbar">
        <a href="{{ route('/') }}" class="nav-logo">
            <span>✨</span> MiniMVC
        </a>
        <ul class="nav-links">
            <li><a href="{{ route('/') }}" class="nav-link">Home</a></li>
            <li><a href="{{ route('upload') }}" class="nav-link">Upload Demo</a></li>
            <li><a href="{{ route('blog') }}" class="nav-link">Blog</a></li>
            @auth
                <li><a href="{{ route('profile') }}" class="nav-link">Profile</a></li>
                <li><a href="{{ route('pay') }}" class="nav-link">Payments</a></li>
                @if(hasRole('admin'))
                    <li><a href="{{ route('admin/dashboard') }}" class="nav-link" style="color: var(--primary-color);">Admin Panel</a></li>
                @endif
            @endauth
        </ul>
        <div class="nav-auth">
            @guest
                <a href="{{ route('login') }}" class="btn btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.875rem;">Login</a>
                <a href="{{ route('register') }}" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.875rem;">Register</a>
            @endguest
            @auth
                <span style="color: var(--text-secondary); font-size: 0.9rem; margin-right: 0.5rem;">
                    Hello, <strong>{{ \Ace\Application::$app->user->name }}</strong>
                </span>
                <a href="{{ route('logout') }}" class="btn btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.875rem;">Logout</a>
            @endauth
        </div>
    </nav>

    <!-- Content Container -->
    <div class="container">
        <!-- Render Flash Messages -->
        @session('success')
            <div class="alert alert-success">
                <span>✅</span> {!! $value !!}
            </div>
        @endsession

        @session('error')
            <div class="alert alert-danger">
                <span>❌</span> {!! $value !!}
            </div>
        @endsession

        @session('warning')
            <div class="alert alert-warning">
                <span>⚠️</span> {!! $value !!}
            </div>
        @endsession

        <!-- Page Content -->
        @yield('content')
    </div>

    <!-- Footer -->
    <footer>
        <p>&copy; <?php echo date('Y'); ?> Mini PHP MVC Framework. Handcrafted by Antigravity.</p>
    </footer>

    <!-- JS Files -->
    <script src="{{ route('assets/js/app.js') }}"></script>
</body>
</html>

