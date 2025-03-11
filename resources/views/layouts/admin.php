<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $this->getTitle() }} | Admin Panel</title>
    <link rel="stylesheet" href="/assets/css/admin.css">
    @yield('styles')
</head>
<body class="admin-panel">
    <div class="admin-wrapper">
        <aside class="sidebar">
            <div class="admin-logo">Admin Panel</div>
            <nav class="admin-nav">
                <ul>
                    <li><a href="/admin/dashboard">Dashboard</a></li>
                    <li><a href="/admin/users">Users</a></li>
                    <li><a href="/admin/settings">Settings</a></li>
                    <li><a href="/logout">Logout</a></li>
                </ul>
            </nav>
        </aside>

        <div class="admin-content">
            <header class="admin-header">
                <h1>{{ $this->getHeader() }}</h1>
                <div class="user-profile">
                    Welcome, {{ $user['name'] ?? 'Admin' }}
                </div>
            </header>

            <main class="admin-main">
                @if(isset($flashMessage))
                    <div class="alert alert-{{ $flashMessage['type'] }}">
                        {{ $flashMessage['message'] }}
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <script src="/assets/js/admin.js"></script>
    @yield('scripts')
</body>
</html>