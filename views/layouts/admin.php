<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - Admin Panel</title>
    <!-- Outfit Font -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap">
    <!-- Chart.js for interactive dashboards -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --bg-primary: #060913;
            --bg-secondary: #0d1222;
            --bg-card: rgba(18, 26, 47, 0.55);
            --border-glass: rgba(255, 255, 255, 0.05);
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;
            
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --primary-gradient: linear-gradient(135deg, #6366f1, #8b5cf6);
            --secondary-gradient: linear-gradient(135deg, #ec4899, #8b5cf6);
            
            --success: #10b981;
            --success-bg: rgba(16, 185, 129, 0.1);
            --error: #ef4444;
            --error-bg: rgba(239, 68, 68, 0.1);
            --warning: #f59e0b;
            --warning-bg: rgba(245, 158, 11, 0.1);
            
            --transition-fast: 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-normal: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --sidebar-width: 260px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            overflow-x: hidden;
        }

        /* Sidebar Container */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--bg-secondary);
            border-right: 1px solid var(--border-glass);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 100;
            display: flex;
            flex-direction: column;
            padding: 1.5rem;
            transition: var(--transition-normal);
        }

        .sidebar-logo {
            font-size: 1.4rem;
            font-weight: 700;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid var(--border-glass);
            margin-bottom: 2rem;
        }

        .sidebar-nav {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            flex: 1;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.85rem 1rem;
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 500;
            border-radius: 0.75rem;
            transition: var(--transition-fast);
        }

        .sidebar-link:hover {
            color: var(--text-primary);
            background: rgba(255, 255, 255, 0.03);
        }

        .sidebar-link.active {
            color: white;
            background: var(--primary-gradient);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }

        .sidebar-footer {
            border-top: 1px solid var(--border-glass);
            padding-top: 1.5rem;
        }

        /* Main Workspace Content */
        .workspace {
            flex: 1;
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: var(--transition-normal);
        }

        /* Top Header Navigation */
        .header {
            height: 70px;
            background: rgba(6, 9, 19, 0.6);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border-glass);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            position: sticky;
            top: 0;
            z-index: 90;
        }

        .mobile-toggle {
            background: none;
            border: none;
            color: var(--text-primary);
            font-size: 1.5rem;
            cursor: pointer;
            display: none;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--text-primary);
            font-size: 0.95rem;
            font-weight: 500;
        }

        .avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: var(--secondary-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: white;
            font-size: 0.9rem;
            border: 2px solid var(--border-glass);
        }

        .content-body {
            flex: 1;
            padding: 2.5rem;
            max-width: 1300px;
            width: 100%;
            margin: 0 auto;
        }

        /* Buttons & Forms */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.65rem 1.25rem;
            font-size: 0.9rem;
            font-weight: 600;
            border-radius: 0.5rem;
            border: none;
            cursor: pointer;
            transition: var(--transition-fast);
            text-decoration: none;
            gap: 0.5rem;
        }

        .btn-primary {
            background: var(--primary-gradient);
            color: white;
            box-shadow: 0 4px 10px rgba(99, 102, 241, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 14px rgba(99, 102, 241, 0.4);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-primary);
            border: 1px solid var(--border-glass);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.08);
        }

        /* Glassmorphic Cards */
        .card {
            background: var(--bg-card);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--border-glass);
            border-radius: 1rem;
            padding: 1.75rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
            transition: var(--transition-normal);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        /* Table styles */
        .table-responsive {
            overflow-x: auto;
            width: 100%;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .admin-table th {
            padding: 1rem;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border-glass);
            font-weight: 600;
        }

        .admin-table td {
            padding: 1rem;
            font-size: 0.95rem;
            border-bottom: 1px solid var(--border-glass);
            color: var(--text-primary);
        }

        .admin-table tbody tr:hover {
            background: rgba(255, 255, 255, 0.01);
        }

        /* Alerts */
        .alert {
            padding: 1rem 1.25rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.95rem;
            font-weight: 500;
        }

        .alert-success {
            background: var(--success-bg);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .alert-danger {
            background: var(--error-bg);
            color: var(--error);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        /* Responsiveness */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .workspace {
                margin-left: 0;
            }
            .mobile-toggle {
                display: block;
            }
        }
    </style>
</head>
<body>

    <!-- Admin Sidebar -->
    <aside class="sidebar" id="sidebar">
        <a href="{{ route('/') }}" class="sidebar-logo">
            <span>✨</span> MiniMVC Admin
        </a>
        <nav class="sidebar-nav">
            <?php
                $currentPath = $_SERVER['REQUEST_URI'];
                $isDashboard = str_contains($currentPath, '/admin/dashboard') || str_contains($currentPath, '/admin/create') || str_contains($currentPath, '/admin/edit');
                $isUsers = str_contains($currentPath, '/admin/users');
                $isRoles = str_contains($currentPath, '/admin/roles');
            ?>
            <a href="{{ route('admin/dashboard') }}" class="sidebar-link <?= $isDashboard ? 'active' : '' ?>">
                <span>📊</span> Dashboard
            </a>
            <a href="{{ route('admin/users') }}" class="sidebar-link <?= $isUsers ? 'active' : '' ?>">
                <span>👥</span> Manage Users
            </a>
            <a href="{{ route('admin/roles') }}" class="sidebar-link <?= $isRoles ? 'active' : '' ?>">
                <span>🛡️</span> Roles & Permissions
            </a>
        </nav>
        <div class="sidebar-footer">
            <a href="{{ route('/') }}" class="sidebar-link">
                <span>🏠</span> Client Site
            </a>
        </div>
    </aside>

    <!-- Main Workspace -->
    <main class="workspace">
        <header class="header">
            <button class="mobile-toggle" onclick="toggleSidebar()">☰</button>
            <div>
                <h3 style="font-size: 1.1rem; font-weight: 500;">Console Dashboard</h3>
            </div>
            <div class="header-actions">
                <div class="admin-profile">
                    <?php if(\Ace\Application::$app->user): ?>
                        <div class="avatar">
                            <?= strtoupper(substr(\Ace\Application::$app->user->name, 0, 1)) ?>
                        </div>
                        <span><?= \Ace\Application::$app->user->name ?></span>
                    <?php endif; ?>
                </div>
                <a href="{{ route('logout') }}" class="btn btn-secondary" style="padding: 0.45rem 1rem; font-size: 0.85rem;">Logout</a>
            </div>
        </header>

        <section class="content-body">
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

            @yield('content')
        </section>
    </main>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }
    </script>
</body>
</html>

