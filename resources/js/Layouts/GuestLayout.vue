<script setup>
/**
 * GuestLayout — split auth layout: form on the left, calm brand panel on the right (≥1024px).
 * Usage unchanged: <GuestLayout> …form… </GuestLayout>
 */
import { Link } from '@inertiajs/vue3';
import { CheckCircleIcon } from '@heroicons/vue/20/solid';
import BrandMark from '@/Components/Layout/BrandMark.vue';
import ToastViewport from '@/Components/UI/ToastViewport.vue';
import { useFlashToasts } from '@/Composables/useFlashToasts';

useFlashToasts();

const year = new Date().getFullYear();
const points = [
    'Har shanba — haftalik imtihon va natijalar tahlili',
    'Video darslar, testlar va virtual fizika laboratoriyasi',
    'Shaxsiy reyting, yutuqlar va sertifikatlar',
];
</script>

<template>
    <div class="flex min-h-screen bg-surface text-fg">
        <!-- Form column -->
        <div class="flex w-full flex-col px-4 py-6 sm:px-8 lg:w-[46%] lg:px-12 xl:px-20">
            <Link href="/" class="focus-ring self-start rounded-md" aria-label="EDULIFE">
                <BrandMark />
            </Link>

            <main class="flex flex-1 items-center justify-center py-10">
                <div class="w-full max-w-sm">
                    <slot />
                </div>
            </main>

            <p class="text-center text-xs text-fg-subtle lg:text-left">
                © {{ year }} EDULIFE. Barcha huquqlar himoyalangan.
            </p>
        </div>

        <!-- Brand panel -->
        <aside class="relative hidden flex-1 overflow-hidden border-l border-border bg-brand-50 lg:block" aria-hidden="true">
            <!-- grid paper -->
            <svg class="absolute inset-0 h-full w-full text-brand-600/[0.07]" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <pattern id="auth-grid" width="32" height="32" patternUnits="userSpaceOnUse">
                        <path d="M32 0H0V32" fill="none" stroke="currentColor" stroke-width="1" />
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#auth-grid)" />
            </svg>

            <div class="relative flex h-full flex-col justify-center px-12 xl:px-20">
                <!-- Illustration: a calm composition of a formula card, a graph and an orbit -->
                <div class="relative mx-auto mb-12 h-72 w-full max-w-md">
                    <svg class="absolute -right-6 -top-6 h-48 w-48 text-brand-600/25" viewBox="0 0 200 200" fill="none">
                        <ellipse cx="100" cy="100" rx="88" ry="34" stroke="currentColor" stroke-width="1.5" transform="rotate(-30 100 100)" />
                        <ellipse cx="100" cy="100" rx="88" ry="34" stroke="currentColor" stroke-width="1.5" transform="rotate(30 100 100)" />
                        <ellipse cx="100" cy="100" rx="88" ry="34" stroke="currentColor" stroke-width="1.5" transform="rotate(90 100 100)" />
                        <circle cx="100" cy="100" r="7" class="fill-brand-600/50" />
                    </svg>

                    <div class="absolute left-0 top-6 w-64 rounded-lg border border-border bg-surface p-5 shadow-md">
                        <p class="text-xs font-medium uppercase tracking-wide text-fg-subtle">Fizika · Mexanika</p>
                        <p class="mt-3 font-mono text-2xl font-medium text-fg">F = m · a</p>
                        <div class="mt-4 flex items-center gap-2">
                            <span class="h-1.5 flex-1 overflow-hidden rounded-full bg-surface-2">
                                <span class="block h-full w-3/4 rounded-full bg-physics-600" />
                            </span>
                            <span class="text-xs font-medium tabular-nums text-fg-muted">75%</span>
                        </div>
                    </div>

                    <div class="absolute bottom-0 right-4 w-60 rounded-lg border border-border bg-surface p-5 shadow-md">
                        <p class="text-xs font-medium uppercase tracking-wide text-fg-subtle">Matematika</p>
                        <svg class="mt-3 h-20 w-full" viewBox="0 0 200 80" fill="none">
                            <path d="M0 70H200M10 0V80" class="stroke-border-strong" stroke-width="1" />
                            <path d="M10 70C50 70 70 10 100 10S150 60 190 30" class="stroke-brand-600" stroke-width="2.5" stroke-linecap="round" />
                            <circle cx="100" cy="10" r="4" class="fill-brand-600" />
                        </svg>
                        <p class="mt-2 font-mono text-sm text-fg-muted">y = sin(x) + x / 4</p>
                    </div>
                </div>

                <div class="mx-auto w-full max-w-md">
                    <h2 class="text-2xl font-semibold tracking-tight text-fg">Matematika va fizikani tizimli o'rganing</h2>
                    <ul class="mt-5 space-y-3">
                        <li v-for="p in points" :key="p" class="flex items-start gap-3 text-sm text-fg-muted">
                            <CheckCircleIcon class="mt-0.5 h-5 w-5 shrink-0 text-brand" />
                            <span>{{ p }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </aside>

        <ToastViewport />
    </div>
</template>
