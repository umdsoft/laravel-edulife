<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    
    <title inertia><?php echo e(config('app.name', 'EDULIFE')); ?></title>
    
    
    <meta name="description" content="EDULIFE - O'zbekistondagi eng yaxshi online ta'lim platformasi. 1000+ kurs, professional o'qituvchilar, sertifikatlar.">
    
    
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    
    
    <meta name="theme-color" content="#7C3AED">
    <meta name="msapplication-TileColor" content="#7C3AED">
    
    
    <meta property="og:site_name" content="EDULIFE">
    <meta property="og:locale" content="uz_UZ">
    
    
    <meta name="twitter:site" content="@edulife_uz">
    
    
    <meta name="robots" content="index, follow">
    <meta name="googlebot" content="index, follow">
    
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://ui-avatars.com">
    
    
    <link rel="dns-prefetch" href="//fonts.googleapis.com">
    <link rel="dns-prefetch" href="//ui-avatars.com">
    
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    
    
    <?php if (!isset($__inertiaSsrDispatched)) { $__inertiaSsrDispatched = true; $__inertiaSsrResponse = app(\Inertia\Ssr\Gateway::class)->dispatch($page); }  if ($__inertiaSsrResponse) { echo $__inertiaSsrResponse->head; } ?>
    
    
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    
    
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "EDULIFE",
        "url": "<?php echo e(config('app.url', 'https://edulife.uz')); ?>",
        "logo": "<?php echo e(config('app.url', 'https://edulife.uz')); ?>/images/logo.png",
        "description": "O'zbekistondagi eng yaxshi online ta'lim platformasi",
        "sameAs": [
            "https://facebook.com/edulife.uz",
            "https://instagram.com/edulife.uz",
            "https://t.me/edulife_uz"
        ],
        "contactPoint": {
            "@type": "ContactPoint",
            "telephone": "+998-90-123-45-67",
            "contactType": "customer service",
            "availableLanguage": ["uz", "ru"]
        }
    }
    </script>
    
    
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "EDULIFE",
        "url": "<?php echo e(config('app.url', 'https://edulife.uz')); ?>",
        "potentialAction": {
            "@type": "SearchAction",
            "target": "<?php echo e(config('app.url', 'https://edulife.uz')); ?>/search?q={search_term_string}",
            "query-input": "required name=search_term_string"
        }
    }
    </script>
</head>
<body class="antialiased font-sans">
    <?php if (!isset($__inertiaSsrDispatched)) { $__inertiaSsrDispatched = true; $__inertiaSsrResponse = app(\Inertia\Ssr\Gateway::class)->dispatch($page); }  if ($__inertiaSsrResponse) { echo $__inertiaSsrResponse->body; } else { ?><div id="app" data-page="<?php echo e(json_encode($page)); ?>"></div><?php } ?>
    
    
    <noscript>
        <div style="padding: 20px; text-align: center; background: #f0f0f0;">
            Bu saytni to'liq ko'rish uchun JavaScript'ni yoqing.
        </div>
    </noscript>
</body>
</html><?php /**PATH /Users/abc/Desktop/laravel-edulife/resources/views/app.blade.php ENDPATH**/ ?>