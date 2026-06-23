@extends('layouts/admin')

@section('title')
Roles & Permissions
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Roles & Access Matrix</h3>
        <span style="color: var(--text-muted); font-size: 0.85rem;">Total Roles: {{ count($roles) }}</span>
    </div>

    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 200px;">Role</th>
                    <th style="width: 250px;">Description</th>
                    <th>Permissions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($roles as $role)
                    <tr>
                        <td>
                            <div style="font-weight: 600; color: white;">{{ $role['name'] }}</div>
                            <code style="font-size: 0.8rem; color: var(--text-secondary); background: rgba(255, 255, 255, 0.05); padding: 0.1rem 0.3rem; border-radius: 0.25rem;">{{ $role['slug'] }}</code>
                        </td>
                        <td style="color: var(--text-secondary); font-size: 0.9rem;">
                            {{ $role['description'] ?? 'No description provided.' }}
                        </td>
                        <td>
                            <form action="{{ route('admin/roles/' . $role['id'] . '/permissions') }}" method="POST">
                                {!! csrf_field() !!}
                                
                                @if($role['slug'] === 'admin')
                                    <div style="background: rgba(245, 158, 11, 0.08); border: 1px solid rgba(245, 158, 11, 0.20); border-radius: 0.5rem; padding: 0.65rem 0.85rem; margin-bottom: 0.75rem; color: #fbbf24; font-size: 0.8rem; display: flex; align-items: flex-start; gap: 0.5rem; line-height: 1.4;">
                                        <span style="font-size: 1.1rem; line-height: 1;">⚠️</span>
                                        <span><strong>Warning:</strong> Modifying permissions for the Administrator role could restrict system access or lock users out of dashboard functionality.</span>
                                    </div>
                                @endif

                                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 0.75rem; margin-bottom: 1rem;">
                                    @foreach($allPermissions as $perm)
                                        <?php
                                            $hasPerm = false;
                                            foreach ($role['permissions'] as $rp) {
                                                if ($rp['slug'] === $perm['slug']) {
                                                    $hasPerm = true;
                                                    break;
                                                }
                                            }
                                        ?>
                                        <label style="display: flex; align-items: flex-start; gap: 0.45rem; color: var(--text-secondary); font-size: 0.85rem; cursor: pointer; user-select: none;">
                                            <input type="checkbox" name="permissions[]" value="{{ $perm['id'] }}" <?= $hasPerm ? 'checked' : '' ?> style="accent-color: #6366f1; width: 1.05rem; height: 1.05rem; cursor: pointer; border-radius: 0.25rem; margin-top: 0.15rem;">
                                            <span style="display: flex; flex-direction: column; line-height: 1.2;">
                                                <span style="color: var(--text-primary); font-weight: 500;">{{ $perm['name'] }}</span>
                                                <span style="color: var(--text-muted); font-size: 0.72rem; font-family: monospace;">{{ $perm['slug'] }}</span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                                <button type="submit" class="btn btn-primary" style="padding: 0.4rem 0.9rem; font-size: 0.8rem; border-radius: 0.35rem; display: inline-flex; align-items: center; gap: 0.35rem;">
                                    💾 Update Permissions
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

