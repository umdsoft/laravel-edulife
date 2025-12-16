<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    {{-- Default Title (overridden by @inertiaHead) --}}
    <title inertia>{{ config('app.name', 'EDULIFE') }}</title>
    
    {{-- Default Meta Description --}}
    <meta name="description" content="EDULIFE - O'zbekistondagi eng yaxshi online ta'lim platformasi. 1000+ kurs, professional o'qituvchilar, sertifikatlar.">
    
    {{-- Favicon --}}
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    
    {{-- Theme Color --}}
    <meta name="theme-color" content="#7C3AED">
    <meta name="msapplication-TileColor" content="#7C3AED">

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
    
    {{-- Preconnect to external resources --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://ui-avatars.com">
    
    {{-- DNS Prefetch --}}
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="dns-prefetch" href="//ui-avatars.com">
    
    {{-- Fonts with display swap for better performance --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    {{-- CSRF Token --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    {{-- Inertia Head (dynamic meta from Vue components) --}}
    @inertiaHead
    
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
        "description": "O'zbekistondagi eng yaxshi online ta'lim platformasi",
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