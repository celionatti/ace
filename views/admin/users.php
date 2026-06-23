@extends('layouts/admin')

@section('title')
User Management
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">User Accounts</h3>
        <span style="color: var(--text-muted); font-size: 0.85rem;">Total Users: {{ $totalUsers }}</span>
    </div>

    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Registered At</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @if(empty($users))
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2rem;">No users available.</td>
                    </tr>
                @else
                    @foreach($users as $user)
                        <tr>
                            <td>{{ $user['id'] }}</td>
                            <td style="font-weight: 500; color: white;">{{ $user['name'] }}</td>
                            <td>{{ $user['email'] }}</td>
                            <td>{{ date('M d, Y', strtotime($user['created_at'])) }}</td>
                            <td>
                                @if(empty($user['assigned_roles']))
                                    <span style="background: rgba(239, 68, 68, 0.1); color: #f87171; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.8rem; font-weight: 500; text-transform: uppercase;">None</span>
                                @else
                                    @foreach($user['assigned_roles'] as $role)
                                        <span style="background: var(--success-bg); color: var(--success); padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.8rem; font-weight: 500; text-transform: uppercase; margin-right: 0.25rem;">
                                            {{ $role['name'] }}
                                        </span>
                                    @endforeach
                                @endif
                            </td>
                            <td>
                                <!-- Form to Assign Role -->
                                <form action="{{ route('admin/users/' . $user['id'] . '/role') }}" method="POST" style="display: flex; gap: 0.5rem; align-items: center;">
                                    {!! csrf_field() !!}
                                    <select name="role_id" style="padding: 0.35rem 0.5rem; border-radius: 0.35rem; background: rgba(255, 255, 255, 0.05); border: 1px solid var(--border-glass); color: var(--text-primary); font-size: 0.85rem; outline: none;">
                                        <option value="" style="background: var(--bg-secondary); color: var(--text-secondary);">Select Role...</option>
                                        @foreach($roles as $r)
                                            <?php 
                                                // Check if user currently has this role
                                                $hasThisRole = false;
                                                foreach ($user['assigned_roles'] as $ur) {
                                                    if ($ur['id'] == $r['id']) {
                                                        $hasThisRole = true;
                                                    }
                                                }
                                            ?>
                                            <option value="{{ $r['id'] }}" <?= $hasThisRole ? 'selected' : '' ?> style="background: var(--bg-secondary); color: var(--text-primary);">
                                                {{ $r['name'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn btn-primary" style="padding: 0.35rem 0.75rem; font-size: 0.8rem;">Update</button>
                                </form>
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
@endsection

