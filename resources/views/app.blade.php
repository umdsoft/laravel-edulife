<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    {{-- Default Title (overridden by @inertiaHead) --}}
    <title inertia>{{ config('app.name', 'EDULIFE') }}</title>
    
    {{-- Default Meta Description --}}
    <meta name="description" content="EDULIFE - matematika va fizika bo'yicha online ta'lim platformasi: video darslar, testlar, virtual laboratoriya va sertifikatlar.">
    
    {{-- Favicon --}}
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    
    {{-- Theme Color --}}
    <meta name="theme-color" content="#4F46E5">
    <meta name="msapplication-TileColor" content="#4F46E5">

    {{-- Disable browser forced dark mode (Yandex, Opera, etc.) --}}
    <meta name="color-scheme" content="light dark">
    <meta name="darkreader-lock">
    
    {{-- Default Open Graph (overridden by pages) --}}
    <meta property="og:site_name" content="EDULIFE">
    <meta property="og:locale" content="uz_UZ">
    
    {{-- Twitter --}}
    <meta name="twitter:site" content="@edulife_uz">
    
    {{-- Robots --}}
    <meta name="robots" content="index, follow">
    <meta name="googlebot" content="index, follow">
    
    {{-- Preconnect / DNS prefetch (avatar fallbacks used by some pages) --}}
    <link rel="preconnect" href="https://ui-avatars.com">
    <link rel="dns-prefetch" href="//ui-avatars.com">

    {{-- Fonts: Inter Variable is self-hosted via @fontsource-variable/inter (bundled by Vite) --}}

    {{-- Apply saved theme before first paint (no flash). Key shared with useTheme.js --}}
    <script>
        try {
            if (localStorage.getItem('edulife_dark_mode') === 'true') {
                document.documentElement.classList.add('dark');
            }
        } catch (e) {}
    </script>

    {{-- CSRF Token --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    {{-- Inertia Head (dynamic meta from Vue components) --}}
    @inertiaHead
    
    {{-- Named routes for the frontend route() helper (Ziggy) --}}
    @routes

    {{-- Vite Assets --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    {{-- Organization Schema (global) - @ escaped with @@ --}}
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "Organization",
        "name": "EDULIFE",
        "url": "{{ config('app.url', 'https://edulife.uz') }}",
        "logo": "{{ config('app.url', 'https://edulife.uz') }}/images/logo.png",
        "description": "Matematika va fizika bo'yicha online ta'lim platformasi",
        "sameAs": [
            "https://facebook.com/edulife.uz",
            "https://instagram.com/edulife.uz",
            "https://t.me/edulife_uz"
        ],
        "contactPoint": {
            "@@type": "ContactPoint",
            "telephone": "+998-90-123-45-67",
            "contactType": "customer service",
            "availableLanguage": ["uz", "ru"]
        }
    }
    </script>
    
    {{-- WebSite Schema with SearchAction --}}
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "WebSite",
        "name": "EDULIFE",
        "url": "{{ config('app.url', 'https://edulife.uz') }}",
        "potentialAction": {
            "@@type": "SearchAction",
            "target": "{{ config('app.url', 'https://edulife.uz') }}/search?q={search_term_string}",
            "query-input": "required name=search_term_string"
        }
    }
    </script>
</head>
<body class="antialiased font-sans">
    @inertia
    
    {{-- Noscript fallback --}}
    <noscript>
        <div style="padding: 20px; text-align: center; background: #f0f0f0;">
            Bu saytni to'liq ko'rish uchun JavaScript'ni yoqing.
        </div>
    </noscript>
</body>
</html>