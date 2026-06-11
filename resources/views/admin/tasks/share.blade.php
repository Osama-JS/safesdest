<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <!-- WhatsApp Metadata -->
    <meta property="og:title" content="{{ __('New Task #') }}{{ $data['task']->id }}">
    <meta property="og:image" content="{{ asset('images/logo.png') }}">

    <title>SafeDest Driver - Redirecting...</title>

    <style>
        body {
            font-family: system-ui, -apple-system, sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            background-color: #f8f9fa;
            color: #333;
            text-align: center;
        }
        .logo {
            width: 100px;
            margin-bottom: 20px;
        }
        .message {
            font-size: 1.2rem;
            margin-bottom: 30px;
        }
        .btn {
            background-color: #2196F3;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            display: inline-block;
            margin: 5px;
        }
        .btn.store {
            background-color: #34A853;
        }
    </style>
</head>
<body>
    <img src="{{ asset('assets/img/icon.png') }}" alt="Logo" class="logo">

    <div class="message">
        <h3>{{ __('Redirecting to app...') }}</h3>
        <p>{{ __('If the app does not open automatically, click the button below') }}</p>
    </div>

    <a href="{{ $data['app_scheme'] }}" class="btn" id="appBtn">{{ __('Open App') }}</a>
    <a href="https://play.google.com/store/apps/details?id={{ $data['android_package'] }}" class="btn store">{{ __('Download from Store') }}</a>

    <script>
        window.onload = function() {
            var appUrl = "{{ $data['app_scheme'] }}";
            var storeUrl = "https://play.google.com/store/apps/details?id={{ $data['android_package'] }}";
            var userAgent = navigator.userAgent || navigator.vendor || window.opera;

            // Try to open the app automatically
            if (/android/i.test(userAgent)) {
                // Creation of a hidden iframe to try opening the custom scheme
                // This is often smoother than different location hacks
               window.location = appUrl;

               // Fallback mechanism
               setTimeout(function() {
                   // If the user is still here after 1.5 seconds, redirect to store
                   // We check document.hidden to see if the browser tab is hidden (meaning app opened)
                   if (!document.hidden) {
                       window.location = storeUrl;
                   }
               }, 1500);
            }
        };
    </script>
</body>
</html>
