<script setup>
import { Head, router } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { ref, onMounted } from 'vue';
import { Alert, Button, Card, PageHeader, Spinner } from '@/Components/UI';
import { PlayIcon, ShieldCheckIcon } from '@heroicons/vue/24/outline';
import { CheckCircleIcon, ExclamationTriangleIcon, XCircleIcon } from '@heroicons/vue/20/solid';

const props = defineProps({
    olympiad: Object,
    canStart: Object,
});

const isLoading = ref(false);
const deviceChecks = ref({
    browser: { status: 'pending', label: 'Brauzer tekshirilmoqda...' },
    screen: { status: 'pending', label: 'Ekran o\'lchami tekshirilmoqda...' },
    connection: { status: 'pending', label: 'Internet tezligi tekshirilmoqda...' },
    fullscreen: { status: 'pending', label: 'Fullscreen qo\'llab-quvvatlash...' },
});
const deviceFingerprint = ref('');
const fingerprintData = ref({});
const allChecksPass = ref(false);
const error = ref('');

const runDeviceChecks = async () => {
    // Browser check
    setTimeout(() => {
        const isChrome = navigator.userAgent.includes('Chrome');
        const isFirefox = navigator.userAgent.includes('Firefox');
        const isEdge = navigator.userAgent.includes('Edge');
        deviceChecks.value.browser = {
            status: (isChrome || isFirefox || isEdge) ? 'passed' : 'warning',
            label: `Brauzer: ${isChrome ? 'Chrome' : isFirefox ? 'Firefox' : isEdge ? 'Edge' : 'Boshqa'}`
        };
    }, 500);

    // Screen check
    setTimeout(() => {
        const width = window.screen.width;
        const height = window.screen.height;
        deviceChecks.value.screen = {
            status: width >= 1024 ? 'passed' : 'warning',
            label: `Ekran: ${width}x${height}`
        };
    }, 1000);

    // Connection check
    setTimeout(() => {
        const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
        deviceChecks.value.connection = {
            status: 'passed',
            label: connection ? `Internet: ${connection.effectiveType}` : 'Internet: Mavjud'
        };
    }, 1500);

    // Fullscreen check
    setTimeout(() => {
        const canFullscreen = document.documentElement.requestFullscreen !== undefined;
        deviceChecks.value.fullscreen = {
            status: canFullscreen ? 'passed' : 'failed',
            label: canFullscreen ? 'Fullscreen: Qo\'llab-quvvatlanadi' : 'Fullscreen: Qo\'llab-quvvatlanmaydi'
        };
        checkAllPassed();
    }, 2000);

    // Generate fingerprint
    await generateFingerprint();
};

const generateFingerprint = async () => {
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    ctx.textBaseline = 'alphabetic';
    ctx.font = '14px Arial';
    ctx.fillText('Fingerprint', 2, 15);
    const canvasFingerprint = canvas.toDataURL();

    fingerprintData.value = {
        canvas_fingerprint: await hashString(canvasFingerprint),
        screen_resolution: `${window.screen.width}x${window.screen.height}`,
        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
        platform: navigator.platform,
        hardware_concurrency: navigator.hardwareConcurrency || 1,
        languages: navigator.languages?.join(',') || navigator.language,
        user_agent: navigator.userAgent,
        os_name: getOS(),
        browser_name: getBrowser(),
    };

    const fingerprintString = Object.values(fingerprintData.value).join('|');
    deviceFingerprint.value = await hashString(fingerprintString);
};

const hashString = async (str) => {
    const encoder = new TextEncoder();
    const data = encoder.encode(str);
    const hashBuffer = await crypto.subtle.digest('SHA-256', data);
    const hashArray = Array.from(new Uint8Array(hashBuffer));
    return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
};

const getOS = () => {
    const platform = navigator.platform.toLowerCase();
    if (platform.includes('win')) return 'Windows';
    if (platform.includes('mac')) return 'macOS';
    if (platform.includes('linux')) return 'Linux';
    return 'Unknown';
};

const getBrowser = () => {
    const ua = navigator.userAgent;
    if (ua.includes('Chrome') && !ua.includes('Edge')) return 'Chrome';
    if (ua.includes('Firefox')) return 'Firefox';
    if (ua.includes('Safari') && !ua.includes('Chrome')) return 'Safari';
    if (ua.includes('Edge')) return 'Edge';
    return 'Unknown';
};

const checkAllPassed = () => {
    const results = Object.values(deviceChecks.value);
    allChecksPass.value = results.every(r => r.status === 'passed' || r.status === 'warning');
};

const startExam = async () => {
    if (!allChecksPass.value || isLoading.value) return;

    isLoading.value = true;
    error.value = '';

    try {
        // Request fullscreen
        if (props.olympiad.anti_cheat_config?.fullscreen_required) {
            await document.documentElement.requestFullscreen();
        }

        const response = await fetch(route('student.olympiads.start', props.olympiad.slug), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                device_fingerprint: deviceFingerprint.value,
                fingerprint_data: fingerprintData.value,
            }),
        });

        const data = await response.json();

        if (data.success) {
            window.location.href = data.redirect;
        } else {
            error.value = data.message || 'Xatolik yuz berdi';
            isLoading.value = false;
        }
    } catch (e) {
        error.value = 'Serverga ulanib bo\'lmadi';
        isLoading.value = false;
    }
};

onMounted(() => {
    runDeviceChecks();
});

// Presentation helpers
const checkRowClass = {
    passed: 'bg-success-50 ring-success-100',
    warning: 'bg-warning-50 ring-warning-100',
    failed: 'bg-danger-50 ring-danger-100',
    pending: 'bg-surface-2 ring-border',
};
const statusText = { passed: 'Tayyor', warning: 'Ogohlantirish', failed: 'Mos emas', pending: 'Tekshirilmoqda' };
</script>

<template>
    <StudentLayout title="Imtihon oldi tekshiruv">
        <Head :title="`${olympiad.title} - Tekshiruv`" />

        <PageHeader title="Imtihon oldi tekshiruv" :description="olympiad.title" :breadcrumbs="[
            { label: 'Olimpiadalar', href: route('student.olympiads.index') },
            { label: olympiad.title, href: route('student.olympiads.show', olympiad.slug) },
            { label: 'Tekshiruv' },
        ]" />

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <!-- Device Checks -->
                <Card title="Qurilma tekshiruvi" description="Imtihonni boshlashdan oldin qurilmangiz tekshiriladi">
                    <ul class="space-y-2" aria-live="polite">
                        <li v-for="(check, key) in deviceChecks" :key="key"
                            :class="['flex items-center gap-3 rounded-md px-4 py-3 ring-1 ring-inset', checkRowClass[check.status] || checkRowClass.pending]">
                            <Spinner v-if="check.status === 'pending'" size="h-5 w-5" class="shrink-0 text-fg-subtle"
                                label="Tekshirilmoqda" />
                            <CheckCircleIcon v-else-if="check.status === 'passed'" class="h-5 w-5 shrink-0 text-success"
                                aria-hidden="true" />
                            <ExclamationTriangleIcon v-else-if="check.status === 'warning'"
                                class="h-5 w-5 shrink-0 text-warning" aria-hidden="true" />
                            <XCircleIcon v-else class="h-5 w-5 shrink-0 text-danger" aria-hidden="true" />
                            <span class="flex-1 text-sm font-medium text-fg">{{ check.label }}</span>
                            <span class="hidden text-13 text-fg-subtle sm:inline">{{ statusText[check.status] }}</span>
                        </li>
                    </ul>
                </Card>

                <!-- Warnings -->
                <Alert variant="warning" title="Muhim eslatmalar">
                    <ul class="list-disc space-y-1 pl-5">
                        <li v-if="olympiad.anti_cheat_config?.fullscreen_required">Imtihon fullscreen rejimda o'tkaziladi</li>
                        <li v-if="olympiad.anti_cheat_config?.device_lock">Qurilmangiz qulflanadi, boshqa qurilmadan kira olmaysiz</li>
                        <li>Tab almashtirishlar qayd etiladi</li>
                        <li>Internet uzilsa, javoblar avtomatik saqlanadi</li>
                    </ul>
                </Alert>

                <!-- Error -->
                <Alert v-if="error" variant="danger" title="Imtihonni boshlab bo'lmadi">{{ error }}</Alert>

                <!-- Actions -->
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <Button variant="secondary" :href="route('student.olympiads.show', olympiad.slug)">
                        Orqaga
                    </Button>
                    <Button size="lg" :icon-left="PlayIcon" :loading="isLoading" :disabled="!allChecksPass"
                        @click="startExam">
                        Imtihonni boshlash
                    </Button>
                </div>
            </div>

            <!-- Aside -->
            <aside class="space-y-6">
                <Card title="Imtihon ma'lumotlari">
                    <dl class="divide-y divide-border text-sm">
                        <div class="flex justify-between gap-3 pb-2.5">
                            <dt class="text-fg-muted">Jami davomiylik</dt>
                            <dd class="font-medium text-fg tabular-nums">{{ olympiad.total_duration }} daqiqa</dd>
                        </div>
                        <div class="flex justify-between gap-3 pt-2.5">
                            <dt class="text-fg-muted">Bo'limlar soni</dt>
                            <dd class="font-medium text-fg tabular-nums">{{ olympiad.sections?.length || 0 }} ta</dd>
                        </div>
                    </dl>
                </Card>

                <Card v-if="olympiad.sections?.length" title="Bo'limlar">
                    <ul class="divide-y divide-border text-sm">
                        <li v-for="section in olympiad.sections" :key="section.id"
                            class="flex justify-between gap-3 py-2.5 first:pt-0 last:pb-0">
                            <span class="text-fg">{{ section.title }}</span>
                            <span class="shrink-0 text-fg-muted tabular-nums">{{ section.duration_minutes }} daqiqa</span>
                        </li>
                    </ul>
                </Card>

                <p class="flex items-start gap-2 text-13 text-fg-subtle">
                    <ShieldCheckIcon class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
                    Qurilma ma'lumotlari faqat imtihon xavfsizligi uchun ishlatiladi.
                </p>
            </aside>
        </div>
    </StudentLayout>
</template>
