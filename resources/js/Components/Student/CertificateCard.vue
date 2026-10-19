<script setup>
import { Link } from '@inertiajs/vue3';
import { ArrowDownTrayIcon, DocumentCheckIcon } from '@heroicons/vue/24/outline';
import { CheckBadgeIcon } from '@heroicons/vue/20/solid';
import Button from '@/Components/UI/Button.vue';
import { formatDate } from './format.js';

defineProps({
    certificate: Object,
});
</script>

<template>
    <article
        class="group relative flex h-full flex-col overflow-hidden rounded-lg border border-border bg-surface shadow-xs transition-shadow duration-150 ease-out hover:border-border-strong hover:shadow-md">
        <!-- Calm certificate preview -->
        <div class="relative flex aspect-[1.414] items-center justify-center border-b border-border bg-surface-2 p-4">
            <div class="flex h-full w-full flex-col items-center justify-center rounded-md border border-border bg-surface text-center shadow-xs">
                <DocumentCheckIcon class="h-8 w-8 text-brand" aria-hidden="true" />
                <p class="mt-2 text-[11px] font-semibold uppercase tracking-[0.2em] text-fg-subtle">Sertifikat</p>
                <p class="mt-1 line-clamp-2 px-4 text-13 font-medium text-fg">{{ certificate.course?.title }}</p>
            </div>
        </div>

        <div class="flex flex-1 flex-col p-4">
            <h3 class="line-clamp-1 text-sm font-semibold text-fg">
                <Link :href="route('student.certificates.show', certificate.id)"
                    class="focus-ring rounded-sm after:absolute after:inset-0 after:content-['']">
                    {{ certificate.course?.title }}
                </Link>
            </h3>
            <p class="mt-0.5 text-13 text-fg-subtle">
                Berilgan: <span class="tabular-nums">{{ formatDate(certificate.issued_at) }}</span>
            </p>

            <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                <span class="rounded-sm bg-surface-2 px-2 py-0.5 font-mono text-xs text-fg-muted">
                    {{ certificate.certificate_number }}
                </span>
                <span class="inline-flex items-center gap-1 text-xs font-medium text-success">
                    <CheckBadgeIcon class="h-4 w-4" aria-hidden="true" />
                    Tasdiqlangan
                </span>
            </div>

            <div class="relative z-10 mt-4 border-t border-border pt-3">
                <Button variant="secondary" size="sm" block external :icon-left="ArrowDownTrayIcon"
                    :href="route('student.certificates.download', certificate.id)">
                    PDF yuklab olish
                </Button>
            </div>
        </div>
    </article>
</template>
