<script setup>
import { Head, router } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { ref, onMounted } from 'vue';

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
</script>

<template>
    <StudentLayout>
        <Head :title="`${olympiad.title} - Tekshiruv`" />
        
        <div class="min-h-screen bg-gradient-to-br from-gray-900 via-purple-900 to-indigo-900 flex items-center justify-center p-4">
            <div class="max-w-2xl w-full">
                <!-- Header -->
                <div class="text-center mb-8">
                    <h1 class="text-3xl font-bold text-white mb-2">🔐 Imtihon oldi tekshiruv</h1>
                    <p class="text-purple-200">{{ olympiad.title }}</p>
                </div>

                <!-- Main Card -->
                <div class="bg-white/10 backdrop-blur-lg rounded-3xl p-8 border border-white/20">
                    <!-- Device Checks -->
                    <div class="space-y-4 mb-8">
                        <div 
                            v-for="(check, key) in deviceChecks" 
                            :key="key"
                            class="flex items-center gap-4 p-4 rounded-xl"
                            :class="{
                                'bg-green-500/20': check.status === 'passed',
                                'bg-yellow-500/20': check.status === 'warning',
                                'bg-red-500/20': check.status === 'failed',
                                'bg-white/5': check.status === 'pending',
                            }">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-lg"
                                :class="{
                                    'bg-green-500 text-white': check.status === 'passed',
                                    'bg-yellow-500 text-white': check.status === 'warning',
                                    'bg-red-500 text-white': check.status === 'failed',
                                    'bg-white/20 animate-pulse': check.status === 'pending',
                                }">
                                <span v-if="check.status === 'passed'">✓</span>
                                <span v-else-if="check.status === 'warning'">!</span>
                                <span v-else-if="check.status === 'failed'">✗</span>
                                <span v-else class="w-4 h-4 border-2 border-white/50 border-t-transparent rounded-full animate-spin"></span>
                            </div>
                            <span class="text-white font-medium">{{ check.label }}</span>
                        </div>
                    </div>

                    <!-- Olympiad Info -->
                    <div class="bg-white/5 rounded-xl p-4 mb-8">
                        <h3 class="text-white font-semibold mb-3">📋 Imtihon ma'lumotlari</h3>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div class="text-purple-200">Jami davomiylik:</div>
                            <div class="text-white font-medium">{{ olympiad.total_duration }} daqiqa</div>
                            <div class="text-purple-200">Bo'limlar soni:</div>
                            <div class="text-white font-medium">{{ olympiad.sections?.length || 0 }} ta</div>
                        </div>
                    </div>

                    <!-- Sections Preview -->
                    <div class="bg-white/5 rounded-xl p-4 mb-8">
                        <h3 class="text-white font-semibold mb-3">🧩 Bo'limlar</h3>
                        <div class="space-y-2">
                            <div 
                                v-for="section in olympiad.sections" 
                                :key="section.id"
                                class="flex items-center justify-between text-sm">
                                <span class="text-purple-200">{{ section.title }}</span>
                                <span class="text-white">{{ section.duration_minutes }} daqiqa</span>
                            </div>
                        </div>
                    </div>

                    <!-- Warnings -->
                    <div class="bg-amber-500/20 border border-amber-500/30 rounded-xl p-4 mb-8">
                        <h3 class="text-amber-300 font-semibold mb-2">⚠️ Muhim eslatmalar</h3>
                        <ul class="text-sm text-amber-200 space-y-1">
                            <li v-if="olympiad.anti_cheat_config?.fullscreen_required">• Imtihon fullscreen rejimda o'tkaziladi</li>
                            <li v-if="olympiad.anti_cheat_config?.device_lock">• Qurilmangiz qulflanadi, boshqa qurilmadan kira olmaysiz</li>
                            <li>• Tab almashtirishlar qayd etiladi</li>
                            <li>• Internet uzilsa, avtomatik saqlanadi</li>
                        </ul>
                    </div>

                    <!-- Error -->
                    <div v-if="error" class="bg-red-500/20 border border-red-500/30 rounded-xl p-4 mb-6">
                        <p class="text-red-300 text-center">{{ error }}</p>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-col sm:flex-row gap-4">
                        <button 
                            @click="$inertia.visit(route('student.olympiads.show', olympiad.slug))"
                            class="flex-1 py-3 bg-white/10 text-white rounded-xl font-medium hover:bg-white/20 transition-colors">
                            ← Orqaga
                        </button>
                        <button 
                            @click="startExam"
                            :disabled="!allChecksPass || isLoading"
                            class="flex-1 py-3 bg-gradient-to-r from-green-500 to-emerald-500 text-white rounded-xl font-bold hover:from-green-600 hover:to-emerald-600 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                            {{ isLoading ? 'Yuklanmoqda...' : '🚀 Imtihonni boshlash' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>
