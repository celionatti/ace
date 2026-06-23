@extends('layouts/admin')

@section('title')
Admin Dashboard
@endsection

@section('content')
<div style="display: flex; flex-direction: column; gap: 2rem;">

    <!-- Dashboard Stats Cards Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem;">
        <!-- Card 1 -->
        <div class="card" style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <span style="font-size: 0.85rem; text-transform: uppercase; color: var(--text-muted); font-weight: 600;">Total Users</span>
                <h2 style="font-size: 2.2rem; font-weight: 700; margin-top: 0.25rem;">{{ $stats['total_users'] }}</h2>
            </div>
            <div style="font-size: 2.5rem; background: rgba(99, 102, 241, 0.1); width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">👥</div>
        </div>

        <!-- Card 2 -->
        <div class="card" style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <span style="font-size: 0.85rem; text-transform: uppercase; color: var(--text-muted); font-weight: 600;">Blog Posts</span>
                <h2 style="font-size: 2.2rem; font-weight: 700; margin-top: 0.25rem;">{{ $stats['total_posts'] }}</h2>
            </div>
            <div style="font-size: 2.5rem; background: rgba(236, 72, 153, 0.1); width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">📝</div>
        </div>

        <!-- Card 3 -->
        <div class="card" style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <span style="font-size: 0.85rem; text-transform: uppercase; color: var(--text-muted); font-weight: 600;">Revenue</span>
                <h2 style="font-size: 2.2rem; font-weight: 700; margin-top: 0.25rem;">${{ number_format($stats['total_revenue'], 2) }}</h2>
            </div>
            <div style="font-size: 2.5rem; background: rgba(16, 185, 129, 0.1); width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">💳</div>
        </div>
    </div>

    <!-- Chart Block -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Transaction Volumes (Last 7 Days)</h3>
            <span style="color: var(--text-muted); font-size: 0.85rem;">Daily Successful Payments</span>
        </div>
        <div style="position: relative; height: 300px; width: 100%;">
            <canvas id="revenueChart"></canvas>
        </div>
    </div>

    <!-- Main Content Split (Manage Posts & Activity Logs) -->
    <div style="display: grid; grid-template-columns: 1fr; gap: 2rem; align-items: start;">
        
        <!-- Blog Posts Management Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Manage Blog Posts</h3>
                <a href="{{ route('admin/create') }}" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.85rem;">Create New Post</a>
            </div>
            
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Author</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(empty($posts))
                            <tr>
                                <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 2rem;">No blog posts available.</td>
                            </tr>
                        @else
                            @foreach($posts as $post)
                                <tr>
                                    <td>{{ $post->id }}</td>
                                    <td style="font-weight: 500; color: white;">{{ $post->title }}</td>
                                    <td>{{ $post->author()->name ?? 'System' }}</td>
                                    <td>{{ date('M d, Y', strtotime($post->created_at)) }}</td>
                                    <td>
                                        <div style="display: flex; gap: 0.5rem;">
                                            <a href="{{ route('admin/edit/' . $post->id) }}" class="btn btn-secondary" style="padding: 0.35rem 0.75rem; font-size: 0.8rem;">Edit</a>
                                            <form action="{{ route('admin/delete/' . $post->id) }}" method="POST" onsubmit="return confirm('Are you sure?');">
                                                {!! csrf_field() !!}
                                                <button type="submit" class="btn" style="background: rgba(239, 68, 68, 0.15); color: #f87171; padding: 0.35rem 0.75rem; font-size: 0.8rem; border: 1px solid rgba(239, 68, 68, 0.25);">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>

            <!-- Pagination Controls -->
            @if($totalPages > 1)
            <div style="padding: 1.25rem 1.5rem 0.5rem; border-top: 1px solid var(--border-glass);">
                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
                    <!-- Page info -->
                    <span style="color: var(--text-muted); font-size: 0.8rem;">
                        Showing page {{ $page }} of {{ $totalPages }}
                    </span>

                    <div style="display: flex; align-items: center; gap: 0.35rem; flex-wrap: wrap;">
                        <!-- Previous -->
                        @if($page > 1)
                            <a href="?page={{ $page - 1 }}" style="
                                display: inline-flex; align-items: center; justify-content: center;
                                padding: 0.4rem 0.85rem; border-radius: 0.5rem; font-size: 0.8rem; font-weight: 500;
                                background: rgba(255,255,255,0.04); color: var(--text-secondary);
                                border: 1px solid var(--border-glass); text-decoration: none;
                                transition: var(--transition-fast);
                            " onmouseover="this.style.background='rgba(99,102,241,0.15)';this.style.color='var(--text-primary)';this.style.borderColor='rgba(99,102,241,0.4)'"
                               onmouseout="this.style.background='rgba(255,255,255,0.04)';this.style.color='var(--text-secondary)';this.style.borderColor='var(--border-glass)'">&laquo; Prev</a>
                        @else
                            <span style="
                                display: inline-flex; align-items: center; justify-content: center;
                                padding: 0.4rem 0.85rem; border-radius: 0.5rem; font-size: 0.8rem; font-weight: 500;
                                background: rgba(255,255,255,0.02); color: var(--text-muted);
                                border: 1px solid rgba(255,255,255,0.03); opacity: 0.4;
                            ">&laquo; Prev</span>
                        @endif

                        <!-- Page numbers -->
                        <?php
                        $windowSize = 5;
                        $halfWindow = floor($windowSize / 2);
                        $startPage = max(1, $page - $halfWindow);
                        $endPage = min($totalPages, $startPage + $windowSize - 1);
                        if ($endPage - $startPage + 1 < $windowSize) {
                            $startPage = max(1, $endPage - $windowSize + 1);
                        }
                        ?>

                        @if($startPage > 1)
                            <a href="?page=1" style="
                                display: inline-flex; align-items: center; justify-content: center;
                                width: 2.1rem; height: 2.1rem; border-radius: 0.5rem; font-size: 0.8rem; font-weight: 600;
                                background: rgba(255,255,255,0.04); color: var(--text-secondary);
                                border: 1px solid var(--border-glass); text-decoration: none;
                                transition: var(--transition-fast);
                            " onmouseover="this.style.background='rgba(99,102,241,0.15)';this.style.color='var(--text-primary)'"
                               onmouseout="this.style.background='rgba(255,255,255,0.04)';this.style.color='var(--text-secondary)'">1</a>
                            @if($startPage > 2)
                                <span style="color: var(--text-muted); font-size: 0.8rem; padding: 0 0.1rem;">…</span>
                            @endif
                        @endif

                        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                            @if($i === $page)
                                <span style="
                                    display: inline-flex; align-items: center; justify-content: center;
                                    width: 2.1rem; height: 2.1rem; border-radius: 0.5rem; font-size: 0.8rem; font-weight: 700;
                                    background: var(--primary-gradient); color: white;
                                    border: 1px solid transparent;
                                    box-shadow: 0 3px 10px rgba(99, 102, 241, 0.35);
                                ">{{ $i }}</span>
                            @else
                                <a href="?page={{ $i }}" style="
                                    display: inline-flex; align-items: center; justify-content: center;
                                    width: 2.1rem; height: 2.1rem; border-radius: 0.5rem; font-size: 0.8rem; font-weight: 600;
                                    background: rgba(255,255,255,0.04); color: var(--text-secondary);
                                    border: 1px solid var(--border-glass); text-decoration: none;
                                    transition: var(--transition-fast);
                                " onmouseover="this.style.background='rgba(99,102,241,0.15)';this.style.color='var(--text-primary)'"
                                   onmouseout="this.style.background='rgba(255,255,255,0.04)';this.style.color='var(--text-secondary)'">{{ $i }}</a>
                            @endif
                        <?php endfor; ?>

                        @if($endPage < $totalPages)
                            @if($endPage < $totalPages - 1)
                                <span style="color: var(--text-muted); font-size: 0.8rem; padding: 0 0.1rem;">…</span>
                            @endif
                            <a href="?page={{ $totalPages }}" style="
                                display: inline-flex; align-items: center; justify-content: center;
                                width: 2.1rem; height: 2.1rem; border-radius: 0.5rem; font-size: 0.8rem; font-weight: 600;
                                background: rgba(255,255,255,0.04); color: var(--text-secondary);
                                border: 1px solid var(--border-glass); text-decoration: none;
                                transition: var(--transition-fast);
                            " onmouseover="this.style.background='rgba(99,102,241,0.15)';this.style.color='var(--text-primary)'"
                               onmouseout="this.style.background='rgba(255,255,255,0.04)';this.style.color='var(--text-secondary)'">{{ $totalPages }}</a>
                        @endif

                        <!-- Next -->
                        @if($page < $totalPages)
                            <a href="?page={{ $page + 1 }}" style="
                                display: inline-flex; align-items: center; justify-content: center;
                                padding: 0.4rem 0.85rem; border-radius: 0.5rem; font-size: 0.8rem; font-weight: 500;
                                background: rgba(255,255,255,0.04); color: var(--text-secondary);
                                border: 1px solid var(--border-glass); text-decoration: none;
                                transition: var(--transition-fast);
                            " onmouseover="this.style.background='rgba(99,102,241,0.15)';this.style.color='var(--text-primary)';this.style.borderColor='rgba(99,102,241,0.4)'"
                               onmouseout="this.style.background='rgba(255,255,255,0.04)';this.style.color='var(--text-secondary)';this.style.borderColor='var(--border-glass)'">Next &raquo;</a>
                        @else
                            <span style="
                                display: inline-flex; align-items: center; justify-content: center;
                                padding: 0.4rem 0.85rem; border-radius: 0.5rem; font-size: 0.8rem; font-weight: 500;
                                background: rgba(255,255,255,0.02); color: var(--text-muted);
                                border: 1px solid rgba(255,255,255,0.03); opacity: 0.4;
                            ">Next &raquo;</span>
                        @endif
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- split: Users & Transactions -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem;">
            
            <!-- Recent Transactions Table -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Recent Transactions</h3>
                </div>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Ref</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(empty($stats['recent_transactions']))
                                <tr>
                                    <td colspan="3" style="text-align: center; color: var(--text-muted); padding: 1rem;">No recent transactions.</td>
                                </tr>
                            @else
                                @foreach($stats['recent_transactions'] as $tx)
                                    <tr>
                                        <td style="font-size: 0.85rem; font-family: monospace;">{{ substr($tx['reference'], 0, 12) }}...</td>
                                        <td style="font-weight: 600; color: white;">${{ number_format($tx['amount'], 2) }}</td>
                                        <td>
                                            <?php
                                                $status = strtolower($tx['status']);
                                                $color = 'var(--text-muted)';
                                                $bgColor = 'rgba(255, 255, 255, 0.05)';
                                                if (in_array($status, ['success', 'successful', 'succeeded', 'paid', 'completed'])) {
                                                    $color = 'var(--success)';
                                                    $bgColor = 'var(--success-bg)';
                                                } elseif (in_array($status, ['failed', 'declined', 'cancelled'])) {
                                                    $color = 'var(--error)';
                                                    $bgColor = 'var(--error-bg)';
                                                }
                                            ?>
                                            <span style="color: <?= $color ?>; background: <?= $bgColor ?>; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.8rem; font-weight: 500; text-transform: uppercase;">
                                                {{ $tx['status'] }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Users Table -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">New User Registrations</h3>
                </div>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(empty($stats['recent_users']))
                                <tr>
                                    <td colspan="2" style="text-align: center; color: var(--text-muted); padding: 1rem;">No registered users.</td>
                                </tr>
                            @else
                                @foreach($stats['recent_users'] as $user)
                                    <tr>
                                        <td style="font-weight: 500; color: white;">{{ $user['name'] }}</td>
                                        <td style="font-size: 0.85rem; color: var(--text-secondary);">{{ $user['email'] }}</td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
            
        </div>

    </div>

</div>

<!-- Render Chart Script -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const ctx = document.getElementById('revenueChart').getContext('2d');
        const revenueChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($stats['chart_labels']); ?>,
                datasets: [{
                    label: 'Revenue Trend ($)',
                    data: <?php echo json_encode($stats['chart_data']); ?>,
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.05)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#8b5cf6',
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        grid: {
                            color: 'rgba(255, 255, 255, 0.03)'
                        },
                        ticks: {
                            color: '#94a3b8'
                        }
                    },
                    y: {
                        grid: {
                            color: 'rgba(255, 255, 255, 0.03)'
                        },
                        ticks: {
                            color: '#94a3b8'
                        }
                    }
                }
            }
        });
    });
</script>
@endsection

