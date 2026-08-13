<!DOCTYPE html>
{{--
    The theme is applied here, inline and before any stylesheet, rather than in
    a mounted component. The prototype set it from localStorage on
    DOMContentLoaded and accepted a flash of the wrong theme; doing it in the
    document head removes that.

    Order of preference: the signed-in user's saved choice, then this browser's
    last choice, then light — which is what the prototype defaulted to, matching
    pilot-telematics.com.
--}}
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      data-theme="{{ auth()->user()?->theme_preference ?? 'light' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title inertia>{{ config('app.name') }}</title>

    <link rel="icon" href="/favicon.ico" sizes="any">

    <script>
        (function () {
            try {
                var serverTheme = @json(auth()->user()?->theme_preference);
                var stored = localStorage.getItem('pilot_theme');
                var theme = serverTheme || stored || 'light';
                document.documentElement.setAttribute('data-theme', theme);
                localStorage.setItem('pilot_theme', theme);
            } catch (e) {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        })();
    </script>

    @routes
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>
<body class="font-sans antialiased">
    @inertia
</body>
</html>
