<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
        <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
        <style>
            body {
                font-family: 'Inter', sans-serif;
            }

            .modulia-splash {
                position: fixed;
                inset: 0;
                z-index: 9999;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                gap: 12px;
                background: linear-gradient(160deg, #f2f2f2 0%, #ffffff 45%, #eef4ff 100%);
                color: #2e2e2e;
                transition: opacity 260ms ease, visibility 260ms ease;
            }

            .modulia-splash.is-hidden {
                opacity: 0;
                visibility: hidden;
                pointer-events: none;
            }

            .modulia-splash__logo {
                width: 84px;
                height: 84px;
                object-fit: contain;
            }

            .modulia-splash__title {
                margin: 0;
                font-family: 'Manrope', sans-serif;
                font-size: 28px;
                font-weight: 800;
                letter-spacing: 0.04em;
            }

            .modulia-splash__tagline {
                margin: 0;
                font-size: 14px;
                color: #4b5563;
            }
        </style>
    </head>
    <body class="font-sans antialiased">
        <div id="modulia-splash" class="modulia-splash" aria-hidden="true">
            <img src="{{ asset('brand/logo_modulia.png') }}" alt="Modulia" class="modulia-splash__logo">
            <p class="modulia-splash__title">MODULIA</p>
            <p class="modulia-splash__tagline">Șantierul devine clar.</p>
        </div>
        @inertia
        <script>
            window.addEventListener('load', function () {
                var splash = document.getElementById('modulia-splash');
                if (!splash) {
                    return;
                }

                splash.classList.add('is-hidden');
                setTimeout(function () {
                    splash.remove();
                }, 320);
            });
        </script>
    </body>
</html>
