@extends('layouts/main')

@section('title')
Dashboard - Mini MVC
@endsection

@section('content')
<?php
/** @var \App\Models\User $user */
$transactions = \App\Models\Transaction::find(['user_id' => $user->id]);

$totalPayments = 0;
$successfulPaymentsCount = 0;
$pendingPaymentsCount = 0;

foreach ($transactions as $tx) {
    if ($tx->status === 'success') {
        $totalPayments += (float)$tx->amount;
        $successfulPaymentsCount++;
    } elseif ($tx->status === 'pending') {
        $pendingPaymentsCount++;
    }
}
?>

<div style="margin-bottom: 2rem;">
    <h1 class="gradient-text">User Dashboard</h1>
    <p>Welcome back, {{ $user->name }}. Manage your account and review payments.</p>
</div>

<div class="profile-grid">
    <!-- User Info Card -->
    <div class="card" style="height: fit-content; padding: 2rem;">
        <div style="text-align: center; margin-bottom: 1.5rem;">
            <div style="width: 80px; height: 80px; border-radius: 50%; background: var(--primary-gradient); display: inline-flex; align-items: center; justify-content: center; font-size: 2.25rem; font-weight: 700; color: white; margin-bottom: 1rem; box-shadow: 0 4px 14px 0 rgba(99,102,241,0.4);">
                <?php echo strtoupper(substr($user->name, 0, 1)); ?>
            </div>
            <h3>{{ $user->name }}</h3>
            <p style="font-size: 0.875rem; margin-bottom: 0;">{{ $user->email }}</p>
        </div>
        
        <hr style="border: 0; border-top: 1px solid var(--border-glass); margin: 1.5rem 0;">
        
        <div style="font-size: 0.9rem; color: var(--text-secondary); display: flex; flex-direction: column; gap: 0.75rem;">
            <div style="display: flex; justify-content: space-between;">
                <span>Account ID:</span>
                <strong style="color: var(--text-primary);">#{{ $user->id }}</strong>
            </div>
            <div style="display: flex; justify-content: space-between;">
                <span>Joined On:</span>
                <strong style="color: var(--text-primary);"><?php echo date('M d, Y', strtotime($user->created_at)); ?></strong>
            </div>
        </div>
        
        <a href="{{ route('logout') }}" class="btn btn-secondary" style="width: 100%; margin-top: 2rem; padding: 0.6rem;">Log Out</a>
    </div>

    <!-- Statistics and Transaction History -->
    <div style="display: flex; flex-direction: column; gap: 2rem;">
        
        <!-- Stats Widgets -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
            <div class="card" style="padding: 1.5rem; display: flex; flex-direction: column; gap: 0.5rem; border-left: 4px solid var(--primary);">
                <span style="font-size: 0.9rem; color: var(--text-secondary); font-weight: 500;">Total Paid</span>
                <h2 style="margin: 0; font-size: 1.8rem;" class="gradient-text">₦<?php echo number_format($totalPayments, 2); ?></h2>
                <span style="font-size: 0.75rem; color: var(--text-muted);"><?php echo $successfulPaymentsCount; ?> successful charges</span>
            </div>
            
            <div class="card" style="padding: 1.5rem; display: flex; flex-direction: column; gap: 0.5rem; border-left: 4px solid var(--warning);">
                <span style="font-size: 0.9rem; color: var(--text-secondary); font-weight: 500;">Pending Invoices</span>
                <h2 style="margin: 0; font-size: 1.8rem; color: var(--warning);"><?php echo $pendingPaymentsCount; ?></h2>
                <span style="font-size: 0.75rem; color: var(--text-muted);">Awaiting gateway response</span>
            </div>

            <div class="card" style="padding: 1.5rem; display: flex; flex-direction: column; gap: 0.5rem; border-left: 4px solid var(--success);">
                <span style="font-size: 0.9rem; color: var(--text-secondary); font-weight: 500;">Overall Transactions</span>
                <h2 style="margin: 0; font-size: 1.8rem; color: var(--success);"><?php echo count($transactions); ?></h2>
                <span style="font-size: 0.75rem; color: var(--text-muted);">Total payment requests</span>
            </div>
        </div>

        <!-- Recent Transactions Panel -->
        <div class="card" style="padding: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h3 style="font-size: 1.25rem;">Payment Logs</h3>
                <a href="{{ route('pay') }}" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.85rem;">New Payment</a>
            </div>

            @if(empty($transactions))
                <div style="text-align: center; padding: 3rem 1rem; color: var(--text-secondary);">
                    <span style="font-size: 3rem; display: block; margin-bottom: 1rem;">🎫</span>
                    <p style="margin-bottom: 0;">No payment logs found on your account.</p>
                </div>
            @else
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            usort($transactions, fn($a, $b) => strcmp($b->created_at, $a->created_at));
                            ?>
                            @foreach($transactions as $tx)
                                <tr>
                                    <td style="font-family: monospace; font-size: 0.875rem;">{{ $tx->reference }}</td>
                                    <td><strong>₦<?php echo number_format((float)$tx->amount, 2); ?></strong></td>
                                    <td style="color: var(--text-secondary); font-size: 0.875rem;"><?php echo date('Y-m-d H:i', strtotime($tx->created_at)); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo strtolower($tx->status); ?>">
                                            {{ $tx->status }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

    </div>
</div>
@endsection

