<script setup>
import { Head } from '@inertiajs/vue3';
import { ShieldCheckIcon, ShieldExclamationIcon, ArrowDownTrayIcon, AcademicCapIcon } from '@heroicons/vue/24/outline';
import { Button } from '@/Components/UI';

defineProps({
    found: Boolean,
    certificate: Object,
});

const year = new Date().getFullYear();
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center bg-bg px-4 py-10 text-fg">

        <Head title="Sertifikatni tekshirish" />

        <main class="w-full max-w-md">
            <!-- Brand -->
            <div class="mb-8 flex flex-col items-center text-center">
                <a href="/" class="focus-ring inline-flex items-center gap-2 rounded-md">
                    <span class="flex h-9 w-9 items-center justify-center rounded-md bg-brand-600 text-white">
                        <AcademicCapIcon class="h-5 w-5" aria-hidden="true" />
                    </span>
                    <span class="text-lg font-semibold tracking-tight text-fg">EDULIFE</span>
                </a>
                <p class="mt-2 text-sm text-fg-muted">Sertifikat haqiqiyligini tekshirish</p>
            </div>

            <section class="overflow-hidden rounded-xl border border-border bg-surface shadow-sm" aria-live="polite">
                <!-- Status -->
                <div :class="['flex flex-col items-center px-6 py-8 text-center', found ? 'bg-success-50' : 'bg-danger-50']">
                    <span :class="[
                        'mb-3 flex h-14 w-14 items-center justify-center rounded-full',
                        found ? 'bg-success-100 text-success' : 'bg-danger-100 text-danger',
                    ]">
                        <ShieldCheckIcon v-if="found" class="h-7 w-7" aria-hidden="true" />
                        <ShieldExclamationIcon v-else class="h-7 w-7" aria-hidden="true" />
                    </span>
                    <h1 :class="['text-lg font-semibold', found ? 'text-success' : 'text-danger']">
                        {{ found ? 'Sertifikat tasdiqlandi' : 'Sertifikat topilmadi' }}
                    </h1>
                    <p class="mt-1 text-sm text-fg-muted">
                        {{ found ? "Ushbu sertifikat EDULIFE tomonidan berilgan va haqiqiy." : "Kiritilgan kod bo'yicha sertifikat mavjud emas." }}
                    </p>
                </div>

                <!-- Details -->
                <div v-if="found" class="p-6">
                    <dl class="divide-y divide-border text-sm">
                        <div class="flex justify-between gap-4 py-2.5 first:pt-0">
                            <dt class="shrink-0 text-fg-muted">Sertifikat raqami</dt>
                            <dd class="text-right font-mono text-13 font-medium text-fg">{{ certificate.number || certificate.code }}</dd>
                        </div>
                        <div v-if="certificate.number" class="flex justify-between gap-4 py-2.5">
                            <dt class="shrink-0 text-fg-muted">Tekshirish kodi</dt>
                            <dd class="text-right font-mono text-13 font-medium text-fg">{{ certificate.code }}</dd>
                        </div>
                        <div class="flex justify-between gap-4 py-2.5">
                            <dt class="shrink-0 text-fg-muted">O'quvchi</dt>
                            <dd class="text-right font-medium text-fg">{{ certificate.student_name }}</dd>
                        </div>
                        <div class="flex justify-between gap-4 py-2.5">
                            <dt class="shrink-0 text-fg-muted">{{ certificate.subject_label || 'Kurs' }}</dt>
                            <dd class="text-right font-medium text-fg">{{ certificate.course_title }}</dd>
                        </div>
                        <div v-if="certificate.course_direction" class="flex justify-between gap-4 py-2.5">
                            <dt class="shrink-0 text-fg-muted">Yo'nalish</dt>
                            <dd class="text-right font-medium text-fg">{{ certificate.course_direction }}</dd>
                        </div>
                        <div class="flex justify-between gap-4 py-2.5">
                            <dt class="shrink-0 text-fg-muted">Berilgan sana</dt>
                            <dd class="text-right font-medium text-fg tabular-nums">{{ certificate.issued_at }}</dd>
                        </div>
                        <div v-if="certificate.verification_count !== null" class="flex justify-between gap-4 py-2.5 last:pb-0">
                            <dt class="shrink-0 text-fg-muted">Tekshirilgan</dt>
                            <dd class="text-right font-medium text-fg tabular-nums">{{ certificate.verification_count }} marta</dd>
                        </div>
                    </dl>

                    <Button v-if="certificate.pdf_url" external block class="mt-6" :icon-left="ArrowDownTrayIcon" :href="certificate.pdf_url">
                        PDF yuklab olish
                    </Button>
                </div>

                <!-- Not found -->
                <div v-else class="space-y-4 p-6 text-center text-sm">
                    <p class="text-fg-muted">Kodni qaytadan tekshirib ko'ring: harflar va raqamlar to'liq mos bo'lishi kerak.</p>
                    <p class="text-fg-subtle">
                        Muammo davom etsa,
                        <a href="mailto:support@edulife.uz" class="focus-ring rounded-sm font-medium text-brand hover:underline">support@edulife.uz</a>
                        manziliga yozing.
                    </p>
                    <a href="/"
                        class="focus-ring inline-flex h-10 items-center justify-center rounded-md border border-border-strong bg-surface px-4 text-sm font-medium text-fg shadow-xs transition-colors duration-150 hover:bg-surface-2">
                        Bosh sahifaga qaytish
                    </a>
                </div>
            </section>

            <p class="mt-8 text-center text-xs text-fg-subtle">&copy; {{ year }} EDULIFE. Barcha huquqlar himoyalangan.</p>
        </main>
    </div>
</template>
