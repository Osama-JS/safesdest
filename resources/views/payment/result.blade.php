<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>
        @if($status === 'success') {{ __('Payment Successful') }}
        @elseif($status === 'pending') {{ __('Payment Pending') }}
        @elseif($status === 'expired') {{ __('Session Expired') }}
        @else {{ __('Payment Failed') }}
        @endif
        — SafeDest
    </title>
    <link rel="preconnect" href="https://fonts.googleapis.com"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet"/>
    <style>
        :root {
            --bg-base:  #0d0f17;
            --bg-card:  #141720;
            --border:   rgba(255,255,255,.08);
            --text:     #f0f2ff;
            --muted:    #7b82a0;
            --success:  #22c55e;
            --warning:  #f59e0b;
            --danger:   #ef4444;
            --brand:    #6c63ff;
        }
        *{box-sizing:border-box;margin:0;padding:0;}
        body {
            font-family:'Inter','Tajawal',sans-serif;
            background: var(--bg-base);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            background-image:
                radial-gradient(ellipse 70% 50% at 50% -10%,
                    @if($status==='success') rgba(34,197,94,.15)
                    @elseif($status==='pending') rgba(245,158,11,.12)
                    @elseif($status==='expired') rgba(245,158,11,.10)
                    @else rgba(239,68,68,.15) @endif
                0%, transparent 60%);
        }

        .result-card {
            width: 100%; max-width: 440px;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 32px 80px rgba(0,0,0,.5);
            text-align: center;
        }

        /* ── Icon area ── */
        .icon-area {
            padding: 44px 24px 32px;
            position: relative;
        }
        .icon-ring {
            width: 100px; height: 100px;
            border-radius: 50%;
            display: inline-flex; align-items: center; justify-content: center;
            position: relative;
            margin-bottom: 20px;
        }
        .icon-ring::before {
            content:'';
            position: absolute; inset: -6px;
            border-radius: 50%;
            border: 2px solid currentColor;
            opacity: .2;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%,100%{transform:scale(1);opacity:.2;}
            50%{transform:scale(1.06);opacity:.15;}
        }
        @keyframes checkDraw {
            from{stroke-dashoffset:60;}
            to{stroke-dashoffset:0;}
        }
        @keyframes fadeUp {
            from{opacity:0;transform:translateY(16px);}
            to{opacity:1;transform:none;}
        }

        .icon-ring.success { color: var(--success); background: rgba(34,197,94,.1); }
        .icon-ring.pending { color: var(--warning); background: rgba(245,158,11,.1); }
        .icon-ring.failed  { color: var(--danger);  background: rgba(239,68,68,.1);  }
        .icon-ring.expired { color: var(--warning); background: rgba(245,158,11,.1); }

        .check-svg { animation: checkDraw .6s ease forwards; }
        .check-svg path { stroke-dasharray:60; stroke-dashoffset:60; }

        h1 {
            font-size: 1.5rem; font-weight: 800;
            margin-bottom: 8px;
            animation: fadeUp .5s ease both .1s;
        }
        .subtitle {
            color: var(--muted); font-size: .9rem; line-height: 1.5;
            animation: fadeUp .5s ease both .2s;
        }

        /* ── Details ── */
        .details {
            padding: 0 28px 28px;
            animation: fadeUp .5s ease both .3s;
        }
        .detail-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid var(--border);
            font-size: .85rem;
        }
        .detail-row:last-child { border: none; }
        .detail-row .label { color: var(--muted); }
        .detail-row .value { font-weight: 600; }

        /* ── Actions ── */
        .actions {
            padding: 0 28px 32px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            animation: fadeUp .5s ease both .4s;
        }
        .btn {
            display: block; width: 100%;
            padding: 14px;
            border-radius: 12px;
            font-size: .9rem; font-weight: 600;
            text-decoration: none; text-align: center;
            border: none; cursor: pointer;
            transition: opacity .2s, transform .1s;
        }
        .btn:active { transform: scale(.98); }
        .btn-primary {
            background: linear-gradient(135deg,#4f46e5,#6c63ff);
            color: #fff;
            box-shadow: 0 8px 24px rgba(108,99,255,.35);
        }
        .btn-primary:hover { opacity: .9; }
        .btn-ghost {
            background: var(--border);
            color: var(--muted);
        }
        .btn-ghost:hover { color: var(--text); }

        /* ── Spinner for pending ── */
        .spinner {
            width: 40px; height: 40px;
            border: 3px solid rgba(245,158,11,.2);
            border-top-color: var(--warning);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── Logo ── */
        .brand-logo {
            display: flex; align-items: center; justify-content: center;
            gap: 8px; padding: 20px 24px;
            border-top: 1px solid var(--border);
            color: var(--muted); font-size: .78rem;
        }
        .brand-dot {
            width: 8px; height: 8px;
            background: var(--brand); border-radius: 50%;
        }
    </style>
</head>
<body>
<div class="result-card">

    <div class="icon-area">
        @if($status === 'success')
            <div class="icon-ring success">
                <svg class="check-svg" width="44" height="44" viewBox="0 0 44 44" fill="none" stroke="#22c55e" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M8 22l10 10L36 12"/>
                </svg>
            </div>
            <h1>{{ __('Payment Successful!') }}</h1>
            <p class="subtitle">{{ __('Your payment has been processed successfully. Thank you!') }}</p>

        @elseif($status === 'pending')
            <div class="icon-ring pending" style="background:transparent;margin:0 auto 20px;">
                <div class="spinner"></div>
            </div>
            <h1>{{ __('Payment Processing...') }}</h1>
            <p class="subtitle">{{ __('Your payment is being verified. This may take a few moments.') }}</p>

        @elseif($status === 'expired')
            <div class="icon-ring expired">
                <svg width="44" height="44" viewBox="0 0 24 24" fill="#f59e0b"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>
            </div>
            <h1>{{ __('Session Expired') }}</h1>
            <p class="subtitle">{{ __('Your payment session has expired. Please start a new payment.') }}</p>

        @else
            <div class="icon-ring failed">
                <svg width="44" height="44" viewBox="0 0 24 24" fill="#ef4444"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
            </div>
            <h1>{{ __('Payment Failed') }}</h1>
            <p class="subtitle">{{ session('error') ?? __('An error occurred during payment. Please try again.') }}</p>
        @endif
    </div>

    @if($payment)
    <div class="details">
        <div class="detail-row">
            <span class="label">{{ __('Reference') }}</span>
            <span class="value" style="font-size:.8rem;font-family:monospace;">{{ $payment->payment_token }}</span>
        </div>
        <div class="detail-row">
            <span class="label">{{ __('Amount') }}</span>
            <span class="value">{{ number_format($payment->amount, 2) }} {{ $payment->currency ?? 'SAR' }}</span>
        </div>
        <div class="detail-row">
            <span class="label">{{ __('Status') }}</span>
            <span class="value" style="color:
                @if($status==='success') #22c55e
                @elseif($status==='pending') #f59e0b
                @else #ef4444 @endif">
                {{ ucfirst($status) }}
            </span>
        </div>
        @if($payment->completed_at)
        <div class="detail-row">
            <span class="label">{{ __('Date') }}</span>
            <span class="value">{{ $payment->completed_at->format('d M Y, H:i') }}</span>
        </div>
        @endif
    </div>
    @endif

    <div class="actions">
        @if($status === 'success')
            @if(!isset($isApp) || !$isApp)
                <a href="/" class="btn btn-primary">{{ __('Back to Home') }}</a>
            @endif
        @elseif($status === 'pending')
            <button class="btn btn-primary" onclick="location.reload()">{{ __('Refresh Status') }}</button>
            @if(!isset($isApp) || !$isApp)
                <a href="/" class="btn btn-ghost">{{ __('Back to Home') }}</a>
            @endif
        @elseif($status === 'expired' || $status === 'failed')
            <a href="javascript:history.back()" class="btn btn-primary">{{ __('Try Again') }}</a>
            @if(!isset($isApp) || !$isApp)
                <a href="/" class="btn btn-ghost">{{ __('Back to Home') }}</a>
            @endif
        @endif
    </div>

    <div class="brand-logo">
        <div class="brand-dot"></div>
        SafeDest · {{ __('Secured by HyperPay') }}
    </div>
</div>

@if($status === 'pending')
<script>
    // Auto-refresh for pending status
    let attempts = 0;
    const check = setInterval(() => {
        if (++attempts > 10) { clearInterval(check); return; }
        location.reload();
    }, 5000);
</script>
@endif
</body>
</html>
