<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>{{ __('Secure Payment') }} — SafeDest</title>
    <meta name="description" content="Secure payment powered by HyperPay"/>
    <link rel="preconnect" href="https://fonts.googleapis.com"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet"/>
    <style>
        :root {
            --brand:       #6c63ff;
            --brand-dark:  #4f46e5;
            --brand-glow:  rgba(108,99,255,.35);
            --bg-base:     #0d0f17;
            --bg-card:     #141720;
            --bg-inner:    #1b1f2e;
            --border:      rgba(255,255,255,.08);
            --text-primary:#f0f2ff;
            --text-muted:  #7b82a0;
            --success:     #22c55e;
            --warning:     #f59e0b;
            --radius:      16px;
        }

        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
        html{-webkit-text-size-adjust:100%;}

        body {
            font-family:'Inter','Tajawal',sans-serif;
            background: var(--bg-base);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            background-image:
                radial-gradient(ellipse 80% 50% at 50% -20%, rgba(108,99,255,.18) 0%, transparent 60%),
                radial-gradient(ellipse 40% 30% at 90% 90%, rgba(79,70,229,.12) 0%, transparent 60%);
        }

        /* ─── Header ─────────────────────────────────────────────── */
        .pay-header {
            text-align: center;
            margin-bottom: 28px;
        }
        .logo-wrap {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
        }
        .logo-icon {
            width: 44px; height: 44px;
            background: var(--brand);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 0 24px var(--brand-glow);
        }
        .logo-icon svg { width: 24px; height: 24px; fill: #fff; }
        .logo-name { font-size: 1.3rem; font-weight: 700; letter-spacing: -.5px; }
        .pay-header p { color: var(--text-muted); font-size: .85rem; }

        /* ─── Card ───────────────────────────────────────────────── */
        .pay-card {
            width: 100%;
            max-width: 480px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 32px 80px rgba(0,0,0,.5), 0 0 0 1px rgba(108,99,255,.1);
        }

        /* ─── Amount banner ──────────────────────────────────────── */
        .amount-banner {
            background: linear-gradient(135deg, var(--brand-dark) 0%, var(--brand) 100%);
            padding: 28px 32px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .amount-banner::before {
            content:'';
            position: absolute;
            inset: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        .amount-label { font-size: .8rem; opacity: .8; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 4px; }
        .amount-value { font-size: 2.8rem; font-weight: 800; letter-spacing: -1px; line-height: 1; }
        .amount-currency { font-size: 1rem; font-weight: 500; opacity: .85; margin-top: 4px; }

        /* ─── Security badge ─────────────────────────────────────── */
        .secure-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 12px 24px;
            background: rgba(34,197,94,.06);
            border-bottom: 1px solid var(--border);
            font-size: .78rem;
            color: var(--success);
        }
        .secure-row svg { width: 14px; height: 14px; }

        /* ─── Payment body ───────────────────────────────────────── */
        .pay-body { padding: 28px 32px 32px; }

        .field-label {
            font-size: .78rem;
            font-weight: 600;
            color: var(--text-muted);
            letter-spacing: .5px;
            text-transform: uppercase;
            margin-bottom: 12px;
        }

        /* HyperPay widget override */
        .wpwl-form {
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
            padding: 0 !important;
        }
        .wpwl-group { margin-bottom: 14px !important; }
        .wpwl-label { color: var(--text-muted) !important; font-size: .8rem !important; margin-bottom: 6px !important; }
        .wpwl-control {
            background: var(--bg-inner) !important;
            border: 1px solid var(--border) !important;
            border-radius: 10px !important;
            color: var(--text-primary) !important;
            padding: 0 14px !important;
            height: 48px !important;
            font-size: .95rem !important;
            transition: border-color .2s !important;
        }
        .wpwl-control:focus { border-color: var(--brand) !important; outline: none !important; }
        .wpwl-button-pay {
            background: linear-gradient(135deg, var(--brand-dark), var(--brand)) !important;
            border: none !important;
            border-radius: 12px !important;
            padding: 14px 24px !important;
            font-size: .95rem !important;
            font-weight: 600 !important;
            width: 100% !important;
            letter-spacing: .3px !important;
            box-shadow: 0 8px 24px var(--brand-glow) !important;
            transition: opacity .2s !important;
            cursor: pointer !important;
        }
        .wpwl-button-pay:hover { opacity: .9 !important; }

        /* ─── Bottom trust row ───────────────────────────────────── */
        .trust-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            padding: 18px 24px 0;
            border-top: 1px solid var(--border);
            margin-top: 24px;
        }
        .trust-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: .73rem;
            color: var(--text-muted);
        }
        .trust-item svg { width: 14px; height: 14px; opacity: .7; }

        /* ─── Timer ──────────────────────────────────────────────── */
        .timer-wrap {
            text-align: center;
            margin-top: 20px;
            font-size: .8rem;
            color: var(--text-muted);
        }
        .timer-wrap span { color: var(--warning); font-weight: 600; }

        /* ─── Footer ─────────────────────────────────────────────── */
        .pay-footer {
            text-align: center;
            margin-top: 24px;
            font-size: .75rem;
            color: var(--text-muted);
            opacity: .7;
        }

        @media(max-width:480px){
            .amount-value{font-size:2.2rem;}
            .pay-body{padding:20px;}
        }
    </style>
</head>
<body>

    <!-- Header -->
    <header class="pay-header">
        <div class="logo-wrap">
            <div class="logo-icon">
                <svg viewBox="0 0 24 24"><path d="M20 4H4a2 2 0 00-2 2v12a2 2 0 002 2h16a2 2 0 002-2V6a2 2 0 00-2-2zm-1 12H5a1 1 0 010-2h14a1 1 0 010 2zm0-4H5a1 1 0 010-2h14a1 1 0 010 2zm0-4H5a1 1 0 010-2h14a1 1 0 010 2z"/></svg>
            </div>
            <span class="logo-name">SafeDest Pay</span>
        </div>
        <p>{{ __('Complete your payment securely') }}</p>
    </header>

    <!-- Card -->
    <div class="pay-card">

        <!-- Amount Banner -->
        <div class="amount-banner">
            <div class="amount-label">{{ __('Amount Due') }}</div>
            <div class="amount-value">{{ number_format($payment->amount, 2) }}</div>
            <div class="amount-currency">{{ $payment->currency ?? 'SAR' }}</div>
        </div>

        <!-- Secure row -->
        <div class="secure-row">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
            {{ __('256-bit SSL Encrypted · Powered by HyperPay') }}
        </div>

        <!-- Payment form -->
        <div class="pay-body">
            <div class="field-label">{{ __('Card Information') }}</div>
            <form action="{{ $callbackUrl }}" class="paymentWidgets" data-brands="{{ $brandsCss }}"></form>

            <!-- Trust badges -->
            <div class="trust-row">
                <div class="trust-item">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
                    {{ __('Secure') }}
                </div>
                <div class="trust-item">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/></svg>
                    {{ __('PCI DSS') }}
                </div>
                <div class="trust-item">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M11.5 2C6.81 2 3 5.81 3 10.5S6.81 19 11.5 19h.5v3c4.86-2.34 8-7 8-11.5C20 5.81 16.19 2 11.5 2zm1 14.5h-2v-2h2v2zm0-4h-2c0-3.25 3-3 3-5 0-1.1-.9-2-2-2s-2 .9-2 2h-2c0-2.21 1.79-4 4-4s4 1.79 4 4c0 2.5-3 2.75-3 5z"/></svg>
                    {{ __('3D Secure') }}
                </div>
            </div>
        </div>
    </div>

    <!-- Timer -->
    @if($payment->expires_at)
    <div class="timer-wrap">
        {{ __('Session expires in') }} <span id="countdown">--:--</span>
    </div>
    @endif

    <!-- Footer -->
    <p class="pay-footer">
        &copy; {{ date('Y') }} SafeDest · {{ __('All Rights Reserved') }}
    </p>

    <!-- HyperPay widget -->
    <script src="{{ $scriptUrl }}"></script>

    @if($payment->expires_at)
    <script>
        const expiry = new Date("{{ $payment->expires_at->toIso8601String() }}");
        function tick() {
            const diff = Math.max(0, Math.floor((expiry - Date.now()) / 1000));
            const m = String(Math.floor(diff / 60)).padStart(2,'0');
            const s = String(diff % 60).padStart(2,'0');
            document.getElementById('countdown').textContent = m + ':' + s;
            if (diff > 0) setTimeout(tick, 1000);
            else location.href = "{{ route('payment.result', ['status'=>'expired','token'=>$token]) }}";
        }
        tick();
    </script>
    @endif
</body>
</html>
