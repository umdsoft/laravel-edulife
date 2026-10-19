<script setup>
import { Head } from '@inertiajs/vue3';
import { AcademicCapIcon, ArrowDownTrayIcon, CheckBadgeIcon, TrophyIcon } from '@heroicons/vue/24/outline';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Badge, Button, Card, EmptyState, PageHeader } from '@/Components/UI';

defineProps({
    certificates: { type: Array, default: () => [] },
});

const TYPE_VARIANT = { winner: 'warning', gold: 'warning', silver: 'neutral', bronze: 'warning', participant: 'info', participation: 'info' };
</script>

<template>
    <StudentLayout title="Olimpiada sertifikatlari">
        <Head title="Olimpiada sertifikatlari" />

        <PageHeader title="Olimpiada sertifikatlari" description="Olimpiadalarda qatnashganingiz va g'olibligingiz uchun berilgan sertifikatlar.">
            <template v-if="route().has('student.olympiad-history')" #actions>
                <Button variant="secondary" :icon-left="TrophyIcon" :href="route('student.olympiad-history')">Olimpiadalar tarixi</Button>
            </template>
        </PageHeader>

        <EmptyState v-if="!certificates.length" bordered size="lg" :icon="AcademicCapIcon" title="Hali sertifikat yo'q"
            description="Olimpiadada qatnashing — natijalar e'lon qilingach sertifikat shu yerda paydo bo'ladi.">
            <Button :href="route('student.olympiads.index')">Olimpiadalarni ko'rish</Button>
        </EmptyState>

        <div v-else class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <Card v-for="c in certificates" :key="c.id" padding="sm">
                <div class="flex h-full flex-col">
                    <div class="flex items-start justify-between gap-3">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-md bg-warning-50 text-warning">
                            <AcademicCapIcon class="h-6 w-6" aria-hidden="true" />
                        </span>
                        <Badge :variant="TYPE_VARIANT[c.certificate_type] || 'neutral'" size="sm">{{ c.certificate_type_label || c.certificate_type }}</Badge>
                    </div>
                    <h2 class="mt-3 text-sm font-semibold text-fg">{{ c.olympiad_title }}</h2>
                    <p class="text-13 text-fg-subtle">{{ [c.olympiad_type, c.stage].filter(Boolean).join(' · ') }}</p>
                    <dl class="mt-3 flex-1 space-y-1 text-13">
                        <div v-if="c.rank" class="flex justify-between"><dt class="text-fg-muted">O'rin</dt><dd class="font-medium tabular-nums">{{ c.rank }}</dd></div>
                        <div v-if="c.score !== null && c.score !== undefined" class="flex justify-between"><dt class="text-fg-muted">Ball</dt><dd class="font-medium tabular-nums">{{ c.score }}</dd></div>
                        <div class="flex justify-between"><dt class="text-fg-muted">Berilgan</dt><dd class="font-medium tabular-nums">{{ c.issued_at }}</dd></div>
                        <div class="flex justify-between"><dt class="text-fg-muted">Raqam</dt><dd class="font-mono text-xs">{{ c.certificate_number }}</dd></div>
                    </dl>
                    <div class="mt-4 flex flex-wrap gap-2 border-t border-border pt-4">
                        <Button size="sm" :icon-left="ArrowDownTrayIcon" :href="c.download_url" external>Yuklab olish</Button>
                        <Button v-if="c.verification_url" size="sm" variant="ghost" :icon-left="CheckBadgeIcon" :href="c.verification_url" external>Tekshirish</Button>
                    </div>
                </div>
            </Card>
        </div>
    </StudentLayout>
</template>
