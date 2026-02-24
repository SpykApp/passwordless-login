<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ __('passwordless-login::messages.confirmation_title') }}</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f8fafc;
            color: #334155;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 1rem;
        }
        .card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -2px rgba(0,0,0,0.1);
            padding: 2.5rem;
            max-width: 420px;
            width: 100%;
            text-align: center;
        }
        .icon {
            width: 64px;
            height: 64px;
            background: #eff6ff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }
        .icon svg { width: 32px; height: 32px; color: #3b82f6; }
        h1 { font-size: 1.5rem; font-weight: 700; margin-bottom: 0.75rem; color: #1e293b; }
        p { color: #64748b; line-height: 1.6; margin-bottom: 1.5rem; }
        .btn {
            display: inline-block;
            background: #3b82f6;
            color: #fff;
            font-weight: 600;
            font-size: 1rem;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            text-decoration: none;
            transition: background 0.2s;
            border: none;
            cursor: pointer;
        }
        .btn:hover { background: #2563eb; }
        .auto-redirect {
            margin-top: 1.5rem;
            font-size: 0.875rem;
            color: #94a3b8;
        }
        .spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #cbd5e1;
            border-top-color: #3b82f6;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            vertical-align: middle;
            margin-right: 0.5rem;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .hidden { display: none; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
            </svg>
        </div>

        <h1>{{ __('passwordless-login::messages.confirmation_heading') }}</h1>
        <p>{{ __('passwordless-login::messages.confirmation_message') }}</p>

        <a href="{{ $confirmUrl }}" class="btn" id="confirm-btn">
            {{ __('passwordless-login::messages.confirmation_button') }}
        </a>

        <div class="auto-redirect hidden" id="auto-redirect">
            <span class="spinner"></span>
            {{ __('passwordless-login::messages.confirmation_redirecting') }}
        </div>

        <noscript>
            <p style="margin-top: 1rem; font-size: 0.875rem;">
                {{ __('passwordless-login::messages.confirmation_noscript') }}
            </p>
        </noscript>
    </div>

    @if($autoRedirect)
    <script>
        // Auto-redirect after a brief delay to distinguish from bot prefetch
        (function() {
            var redirectEl = document.getElementById('auto-redirect');
            var btnEl = document.getElementById('confirm-btn');

            // Show the auto-redirect message after a moment
            setTimeout(function() {
                redirectEl.classList.remove('hidden');
            }, 500);

            // Redirect after 2 seconds - enough time for the user to see the page
            // but bots won't execute this JavaScript
            setTimeout(function() {
                window.location.href = '{{ $confirmUrl }}';
            }, 2000);
        })();
    </script>
    @endif
</body>
</html>
