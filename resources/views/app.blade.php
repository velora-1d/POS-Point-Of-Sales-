<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Favicon -->
        <link rel="icon" type="image/png" href="/images/pos_logo.png">

        <!-- Open Graph / Link Preview -->
        <meta property="og:title" content="House Of Mentai">
        <meta property="og:description" content="Sistem Point of Sale (POS) terintegrasi untuk House Of Mentai dengan dukungan multi-outlet, real-time kitchen display, dan pemesanan mandiri.">
        <meta property="og:image" content="{{ asset('images/og_bg_mentai.png') }}">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:type" content="website">

        <!-- Twitter Card (Untuk kompatibilitas) -->
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="House Of Mentai">
        <meta name="twitter:description" content="Sistem Point of Sale (POS) terintegrasi untuk House Of Mentai dengan dukungan multi-outlet, real-time kitchen display, dan pemesanan mandiri.">
        <meta name="twitter:image" content="{{ asset('images/og_bg_mentai.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.ts', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
