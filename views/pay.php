@extends('layouts/main')

@section('title')
Payments - Mini MVC
@endsection

@section('content')
<?php
/** @var \App\Models\User $user */
?>

<div style="margin-bottom: 2rem;">
    <h1 class="gradient-text">Fund Account</h1>
    <p>Select your preferred gateway to load funds onto your account.</p>
</div>

<div class="profile-grid" style="grid-template-columns: 1fr;">
    <div class="auth-grid" style="margin: 0 auto; max-width: 550px; width: 100%;">
        <div class="card">
            <h2 style="margin-bottom: 0.5rem; font-size: 1.8rem;" class="gradient-text">Make Payment</h2>
            <p style="margin-bottom: 1.5rem; font-size: 0.95rem; color: var(--text-secondary);">Your transaction will be processed securely in real-time.</p>
            
            <form id="payment-form" action="{{ route('pay/initialize') }}" method="POST">
                @csrf

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label class="form-label">Select Payment Gateway</label>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.75rem;">
                        
                        <label class="gateway-option" style="cursor: pointer; border: 1.5px solid var(--border-glass); border-radius: 0.75rem; padding: 1rem; display: flex; flex-direction: column; align-items: center; gap: 0.5rem; transition: var(--transition-fast);">
                            <input type="radio" name="gateway" value="paystack" checked style="display: none;">
                            <span style="font-size: 1.5rem;">🇳🇬</span>
                            <span style="font-weight: 600; font-size: 0.85rem; color: var(--text-primary);">Paystack</span>
                        </label>
                        
                        <label class="gateway-option" style="cursor: pointer; border: 1.5px solid var(--border-glass); border-radius: 0.75rem; padding: 1rem; display: flex; flex-direction: column; align-items: center; gap: 0.5rem; transition: var(--transition-fast);">
                            <input type="radio" name="gateway" value="stripe" style="display: none;">
                            <span style="font-size: 1.5rem;">🇺🇸</span>
                            <span style="font-weight: 600; font-size: 0.85rem; color: var(--text-primary);">Stripe</span>
                        </label>
                        
                        <label class="gateway-option" style="cursor: pointer; border: 1.5px solid var(--border-glass); border-radius: 0.75rem; padding: 1rem; display: flex; flex-direction: column; align-items: center; gap: 0.5rem; transition: var(--transition-fast);">
                            <input type="radio" name="gateway" value="flutterwave" style="display: none;">
                            <span style="font-size: 1.5rem;">🌍</span>
                            <span style="font-weight: 600; font-size: 0.85rem; color: var(--text-primary);">Flutterwave</span>
                        </label>
                        
                    </div>
                </div>
                
                <div class="form-group" style="margin-bottom: 1.25rem;">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" name="email" id="email" class="form-control" 
                           value="{{ $user->email }}" readonly 
                           style="background: rgba(255, 255, 255, 0.02); color: var(--text-muted); cursor: not-allowed;">
                </div>

                <div class="form-group" style="margin-bottom: 2rem;">
                    <label for="amount" class="form-label">Payment Amount</label>
                    <div style="position: relative;">
                        <span id="currency-symbol" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); font-weight: 600; color: var(--text-primary); transition: var(--transition-fast);">₦</span>
                        <input type="number" step="0.01" name="amount" id="amount" class="form-control" 
                               placeholder="0.00" style="padding-left: 2.25rem;" required min="5">
                    </div>
                    <span style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">Minimum transaction: ₦10.00 / $5.00.</span>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.9rem;">
                    <span>💳</span> Proceed to Gateway Checkout
                </button>
            </form>
            
            <div style="margin-top: 2rem; border-top: 1px solid var(--border-glass); padding-top: 1.5rem; display: flex; gap: 1rem; align-items: flex-start;">
                <span style="font-size: 1.5rem; background: rgba(99,102,241,0.1); padding: 0.5rem; border-radius: 0.5rem;">🔒</span>
                <div>
                    <h4 style="font-size: 0.9rem; margin-bottom: 0.25rem; font-weight: 600; color: var(--text-primary);">Secured Encrypted Connection</h4>
                    <p style="font-size: 0.8rem; line-height: 1.4; color: var(--text-secondary); margin-bottom: 0;">We partner with PCI-DSS compliant checkout processors. None of your sensitive payment credentials touch our servers.</p>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('payment-form');
    const currencySymbol = document.getElementById('currency-symbol');
    const options = document.querySelectorAll('.gateway-option');
    const radios = document.getElementsByName('gateway');

    function updateGateway() {
        options.forEach(opt => {
            opt.style.borderColor = 'var(--border-glass)';
            opt.style.background = 'transparent';
        });
        
        const selectedRadio = Array.from(radios).find(r => r.checked);
        if (selectedRadio) {
            const label = selectedRadio.parentElement;
            label.style.borderColor = 'var(--primary)';
            label.style.background = 'rgba(99, 102, 241, 0.08)';
            
            if (selectedRadio.value === 'stripe') {
                form.action = '{{ route("pay/stripe/initialize") }}';
                currencySymbol.textContent = '$';
            } else if (selectedRadio.value === 'flutterwave') {
                form.action = '{{ route("pay/flutterwave/initialize") }}';
                currencySymbol.textContent = '₦';
            } else {
                form.action = '{{ route("pay/initialize") }}';
                currencySymbol.textContent = '₦';
            }
        }
    }

    radios.forEach(radio => {
        radio.addEventListener('change', updateGateway);
    });

    updateGateway();
});
</script>
@endsection

