<script setup>
/**
 * SeoHead Component
 * 
 * Renders all SEO meta tags using Inertia's Head component.
 * Use with useSeo composable for best results.
 * 
 * @example
 * <SeoHead :seo="seoMeta" />
 */
import { Head } from '@inertiajs/vue3';

defineProps({
    seo: {
        type: Object,
        required: true,
    },
    // Optional JSON-LD structured data
    jsonLd: {
        type: Object,
        default: null,
    },
});
</script>

<template>
    <Head>
        <!-- Basic Meta -->
        <title>{{ seo.title }}</title>
        <meta name="description" :content="seo.description" />
        
        <!-- Canonical URL -->
        <link rel="canonical" :href="seo.canonical" />
        
        <!-- Open Graph -->
        <meta property="og:title" :content="seo.ogTitle" />
        <meta property="og:description" :content="seo.ogDescription" />
        <meta property="og:image" :content="seo.ogImage" />
        <meta property="og:url" :content="seo.ogUrl" />
        <meta property="og:type" :content="seo.ogType" />
        <meta property="og:site_name" :content="seo.ogSiteName" />
        <meta property="og:locale" :content="seo.ogLocale" />
        
        <!-- Twitter Card -->
        <meta name="twitter:card" :content="seo.twitterCard" />
        <meta name="twitter:title" :content="seo.twitterTitle" />
        <meta name="twitter:description" :content="seo.twitterDescription" />
        <meta name="twitter:image" :content="seo.twitterImage" />
        <meta name="twitter:site" :content="seo.twitterSite" />
        
        <!-- JSON-LD Structured Data -->
        <script v-if="jsonLd" type="application/ld+json">
            {{ JSON.stringify(jsonLd) }}
        </script>
    </Head>
</template>
