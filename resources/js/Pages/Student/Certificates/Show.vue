<script setup>
import { Head } from '@inertiajs/vue3';
import { ArrowDownTrayIcon, AcademicCapIcon } from '@heroicons/vue/24/outline';
import { CheckBadgeIcon } from '@heroicons/vue/20/solid';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { PageHeader, Button, Badge } from '@/Components/UI';
import { formatDate } from '@/Components/Student/format.js';

defineProps({
    certificate: Object,
});
</script>

<template>

    <Head :title="`Sertifikat - ${certificate.certificate_number}`" />

    <StudentLayout title="Sertifikat">
        <div class="mx-auto max-w-4xl">
            <PageHeader :title="certificate.course?.title" :back="route('student.certificates.index')" back-label="Sertifikatlarim">
                <template #meta>
                    <Badge variant="success" :icon="CheckBadgeIcon">Tasdiqlangan</Badge>
                    <span class="font-mono text-13 text-fg-subtle">{{ certificate.certificate_number }}</span>
                </template>
                <template #actions>
                    <Button external :icon-left="ArrowDownTrayIcon" :href="route('student.certificates.download', certificate.id)">
                        PDF yuklab olish
                    </Button>
                </template>
            </PageHeader>

            <!-- Certificate preview (HTML version of the PDF) -->
            <div class="rounded-xl border border-border bg-surface p-3 shadow-sm sm:p-6">
                <div class="relative overflow-hidden rounded-lg border border-border px-6 py-10 text-center sm:px-14 sm:py-14">
                    <!-- Fine inner frame -->
                    <div class="pointer-events-none absolute inset-2 rounded-md border border-brand-100 sm:inset-3" aria-hidden="true" />

                    <div class="relative">
                        <div class="mx-auto mb-6 flex h-14 w-14 items-center justify-center rounded-full bg-brand-50 text-brand">
                            <AcademicCapIcon class="h-7 w-7" aria-hidden="true" />
                        </div>

                        <p class="text-xs font-semibold uppercase tracking-[0.35em] text-fg-subtle">EDULIFE</p>
                        <h2 class="mt-3 text-3xl font-semibold tracking-[0.12em] text-fg sm:text-4xl">SERTIFIKAT</h2>
                        <p class="mt-2 text-13 uppercase tracking-[0.2em] text-fg-subtle">Muvaffaqiyatli tamomlaganligi uchun</p>

                        <p class="mt-8 text-sm text-fg-muted">Ushbu sertifikat tasdiqlaydiki</p>
                        <p class="mt-2 text-2xl font-semibold text-brand sm:text-3xl">{{ certificate.user?.full_name }}</p>
                        <p class="mt-4 text-sm text-fg-muted">quyidagi kursni muvaffaqiyatli tamomladi:</p>
                        <p class="mx-auto mt-2 max-w-xl text-lg font-semibold text-fg sm:text-xl">{{ certificate.course?.title }}</p>

                        <dl class="mt-12 grid gap-6 border-t border-border pt-6 text-sm sm:grid-cols-3">
                            <div class="flex flex-col-reverse sm:text-left">
                                <dt class="mt-0.5 text-xs uppercase tracking-wider text-fg-subtle">Berilgan sana</dt>
                                <dd class="font-semibold text-fg tabular-nums">{{ formatDate(certificate.issued_at) }}</dd>
                            </div>
                            <div class="flex flex-col-reverse">
                                <dt class="mt-0.5 text-xs uppercase tracking-wider text-fg-subtle">ID raqami</dt>
                                <dd class="font-mono text-13 text-fg">{{ certificate.certificate_number }}</dd>
                            </div>
                            <div class="flex flex-col-reverse sm:text-right">
                                <dt class="mt-0.5 text-xs uppercase tracking-wider text-fg-subtle">O'qituvchi</dt>
                                <dd class="font-semibold text-fg">{{ certificate.course?.teacher?.full_name || '—' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>
