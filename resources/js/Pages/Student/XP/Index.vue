<script setup>
import { computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';

const props = defineProps({
    profile: {
        type: Object,
        required: true
    },
    xp_to_next_level: {
        type: Number,
        default: 100
    },
    level_progress: {
        type: Number,
        default: 0
    }
});

const formatNumber = (num) => new Intl.NumberFormat('uz-UZ').format(num || 0);

// Levels data with REALISTIC XP requirements
// O'rtacha o'quvchi kuniga 50-100 XP to'plashi mumkin
// Level 2 ga yetish: 1-2 hafta
// Level 10 ga yetish: 1-2 yil
const levels = [
    { level: 1, xp: 0, title: 'Yangi boshlovchi', icon: '🌱', description: 'Sayohatingiz boshlanmoqda' },
    { level: 2, xp: 500, title: 'O\'quvchi', icon: '📚', description: '1-2 hafta faol o\'qish' },
    { level: 3, xp: 1500, title: 'Izlanuvchi', icon: '🔍', description: '1 oy muntazam o\'qish' },
    { level: 4, xp: 3500, title: 'Bilimdon', icon: '🧠', description: '2-3 oy faoliyat' },
    { level: 5, xp: 7000, title: 'Mutaxassis', icon: '⭐', description: '4-5 oy tajriba' },
    { level: 6, xp: 12000, title: 'Ekspert', icon: '💎', description: '6-7 oy o\'qish' },
    { level: 7, xp: 20000, title: 'Mahorat egasi', icon: '🏆', description: '8-10 oy mehnat' },
    { level: 8, xp: 30000, title: 'Usta', icon: '🎓', description: '1 yil tajriba' },
    { level: 9, xp: 42000, title: 'Professor', icon: '👨‍🏫', description: '1.5 yil faoliyat' },
    { level: 10, xp: 55000, title: 'Akademik', icon: '🌟', description: '2 yil va undan ko\'p' },
];

// REALISTIC XP earning values
// Har bir faoliyat uchun XP real vaqt va qiyinchilikka asoslangan
const xpActions = [
    {
        action: 'Darsni tugatish',
        xp: '5-15',
        icon: '📚',
        color: 'bg-blue-100 text-blue-700',
        details: 'Video dars: 5 XP, Matnli: 8 XP, Amaliy: 15 XP'
    },
    {
        action: 'Test topshirish',
        xp: '10-40',
        icon: '✅',
        color: 'bg-green-100 text-green-700',
        details: '50-69%: 10 XP, 70-89%: 25 XP, 90-100%: 40 XP'
    },
    {
        action: 'Kurs tugatish',
        xp: '100-300',
        icon: '🏆',
        color: 'bg-purple-100 text-purple-700',
        details: 'Qisqa kurs: 100 XP, O\'rta: 200 XP, Katta: 300 XP'
    },
    {
        action: 'Kunlik streak bonus',
        xp: '5-25',
        icon: '🔥',
        color: 'bg-orange-100 text-orange-700',
        details: '1-7 kun: 5 XP, 8-30 kun: 15 XP, 30+ kun: 25 XP'
    },
    {
        action: 'Turnirda qatnashish',
        xp: '20-100',
        icon: '⚔️',
        color: 'bg-red-100 text-red-700',
        details: 'Qatnashish: 20 XP, Top 10: 50 XP, G\'alaba: 100 XP'
    },
    {
        action: 'Olimpiadada qatnashish',
        xp: '50-200',
        icon: '🥇',
        color: 'bg-yellow-100 text-yellow-700',
        details: 'Qatnashish: 50 XP, Medal: 100-200 XP (fanga bog\'liq)'
    },
    {
        action: 'Lab tajribasi',
        xp: '15-50',
        icon: '🔬',
        color: 'bg-teal-100 text-teal-700',
        details: 'Boshlang\'ich: 15 XP, O\'rta: 30 XP, Murakkab: 50 XP'
    },
    {
        action: 'Haftalik maqsad',
        xp: '30-60',
        icon: '🎯',
        color: 'bg-pink-100 text-pink-700',
        details: 'Asosiy: 30 XP, Bonus: 45 XP, Barcha maqsadlar: 60 XP'
    },
];

const currentLevel = computed(() => {
    return levels.find(l => l.level === props.profile.level) || levels[0];
});

const nextLevel = computed(() => {
    return levels.find(l => l.level === props.profile.level + 1) || null;
});

const progressPercentage = computed(() => {
    return Math.min(100, Math.max(0, props.level_progress || 0));
});
</script>

<template>

    <Head title="XP va Level - EDULIFE" />

    <StudentLayout>
        <div class="space-y-8">

            <!-- Hero Section - Current Level -->
            <div
                class="bg-gradient-to-br from-purple-50 via-indigo-50 to-blue-50 rounded-3xl p-8 border border-purple-100">
                <div class="max-w-4xl mx-auto text-center">
                    <!-- Level Badge -->
                    <div class="inline-flex items-center justify-center w-24 h-24 bg-white rounded-full shadow-lg mb-4">
                        <span class="text-5xl">{{ currentLevel.icon }}</span>
                    </div>

                    <h1 class="text-2xl font-bold text-gray-900 mb-1">
                        Level {{ profile.level }}
                    </h1>
                    <p class="text-lg text-purple-600 font-medium mb-6">{{ currentLevel.title }}</p>

                    <!-- XP Stats -->
                    <div class="flex justify-center items-center gap-8 mb-6">
                        <div class="text-center">
                            <p class="text-3xl font-bold text-gray-900">{{ formatNumber(profile.xp) }}</p>
                            <p class="text-sm text-gray-500">Jami XP</p>
                        </div>
                        <div class="h-12 w-px bg-gray-200"></div>
                        <div class="text-center">
                            <p class="text-3xl font-bold text-purple-600">{{ formatNumber(xp_to_next_level) }}</p>
                            <p class="text-sm text-gray-500">Keyingi levelgacha</p>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="max-w-md mx-auto">
                        <div class="flex justify-between text-sm text-gray-500 mb-1">
                            <span>Level {{ profile.level }}</span>
                            <span>{{ Math.round(progressPercentage) }}%</span>
                            <span>Level {{ profile.level + 1 }}</span>
                        </div>
                        <div class="h-4 bg-gray-200 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-purple-500 to-indigo-500 rounded-full transition-all duration-500"
                                :style="{ width: progressPercentage + '%' }"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl p-4 border border-gray-200 text-center">
                    <span class="text-2xl">🔥</span>
                    <p class="text-xl font-bold text-gray-900 mt-1">{{ profile.streak_days || 0 }}</p>
                    <p class="text-xs text-gray-500">Streak kunlari</p>
                </div>
                <div class="bg-white rounded-xl p-4 border border-gray-200 text-center">
                    <span class="text-2xl">📖</span>
                    <p class="text-xl font-bold text-gray-900 mt-1">{{ profile.completed_lessons || 0 }}</p>
                    <p class="text-xs text-gray-500">Tugatilgan darslar</p>
                </div>
                <div class="bg-white rounded-xl p-4 border border-gray-200 text-center">
                    <span class="text-2xl">🎓</span>
                    <p class="text-xl font-bold text-gray-900 mt-1">{{ profile.completed_courses || 0 }}</p>
                    <p class="text-xs text-gray-500">Tugatilgan kurslar</p>
                </div>
                <div class="bg-white rounded-xl p-4 border border-gray-200 text-center">
                    <span class="text-2xl">🏆</span>
                    <p class="text-xl font-bold text-gray-900 mt-1">{{ profile.achievements_count || 0 }}</p>
                    <p class="text-xs text-gray-500">Yutuqlar</p>
                </div>
            </div>

            <!-- XP Earning Methods with details -->
            <div class="bg-white rounded-2xl border border-gray-200 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <span>⚡</span> XP olish yo'llari
                </h2>
                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div v-for="item in xpActions" :key="item.action"
                        class="p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="text-2xl">{{ item.icon }}</span>
                            <div>
                                <h4 class="font-medium text-gray-900 text-sm">{{ item.action }}</h4>
                                <span :class="['text-xs font-bold px-2 py-0.5 rounded-full', item.color]">
                                    +{{ item.xp }} XP
                                </span>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2 pl-9">{{ item.details }}</p>
                    </div>
                </div>
            </div>

            <!-- All Levels -->
            <div class="bg-white rounded-2xl border border-gray-200 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <span>📊</span> Barcha levellar
                </h2>
                <div class="grid sm:grid-cols-2 lg:grid-cols-5 gap-3">
                    <div v-for="lvl in levels" :key="lvl.level" :class="[
                        'p-4 rounded-xl border-2 text-center transition-all',
                        lvl.level === profile.level
                            ? 'bg-purple-50 border-purple-500 shadow-lg'
                            : lvl.level < profile.level
                                ? 'bg-green-50 border-green-300'
                                : 'bg-gray-50 border-gray-200'
                    ]">
                        <span class="text-3xl block mb-2">{{ lvl.icon }}</span>
                        <p :class="[
                            'font-bold text-lg',
                            lvl.level === profile.level ? 'text-purple-600' : 'text-gray-900'
                        ]">Level {{ lvl.level }}</p>
                        <p class="text-xs text-gray-600 mb-1">{{ lvl.title }}</p>
                        <p class="text-xs text-gray-400">{{ formatNumber(lvl.xp) }} XP</p>

                        <!-- Status badge -->
                        <div class="mt-2">
                            <span v-if="lvl.level === profile.level"
                                class="text-xs bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full">
                                Hozirgi
                            </span>
                            <span v-else-if="lvl.level < profile.level"
                                class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full">
                                ✓ O'tilgan
                            </span>
                            <span v-else class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-full">
                                🔒 Qulflangan
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Next Level Rewards Preview -->
            <div v-if="nextLevel"
                class="bg-gradient-to-r from-amber-50 to-orange-50 rounded-2xl border border-amber-200 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <span>🎁</span> Keyingi level (Level {{ nextLevel.level }}) mukofotlari
                </h2>
                <div class="flex flex-wrap gap-4">
                    <div class="flex items-center gap-3 bg-white rounded-xl p-4 border border-amber-200">
                        <span class="text-2xl">{{ nextLevel.icon }}</span>
                        <div>
                            <p class="font-medium text-gray-900">{{ nextLevel.title }} unvoni</p>
                            <p class="text-xs text-gray-500">Yangi status va badge</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 bg-white rounded-xl p-4 border border-amber-200">
                        <span class="text-2xl">🪙</span>
                        <div>
                            <p class="font-medium text-gray-900">+50 Coin</p>
                            <p class="text-xs text-gray-500">Level bonus</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 bg-white rounded-xl p-4 border border-amber-200">
                        <span class="text-2xl">🔓</span>
                        <div>
                            <p class="font-medium text-gray-900">Yangi imkoniyatlar</p>
                            <p class="text-xs text-gray-500">Maxsus kontentlar ochiladi</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tips Section -->
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <span>💡</span> XP tezroq yig'ish maslahatlari
                </h2>
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="flex items-start gap-3">
                        <span class="text-green-500 mt-0.5">✓</span>
                        <p class="text-sm text-gray-700">Har kuni platformaga kirib <strong>streak</strong>ingizni
                            saqlang</p>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="text-green-500 mt-0.5">✓</span>
                        <p class="text-sm text-gray-700">Testlarni <strong>yuqori ball</strong> bilan topshiring</p>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="text-green-500 mt-0.5">✓</span>
                        <p class="text-sm text-gray-700">Kurslarni <strong>to'liq tugatib</strong> sertifikat oling</p>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="text-green-500 mt-0.5">✓</span>
                        <p class="text-sm text-gray-700"><strong>Turnirlarda</strong> qatnashing va g'olib bo'ling</p>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="text-green-500 mt-0.5">✓</span>
                        <p class="text-sm text-gray-700"><strong>Haftalik maqsadlarga</strong> ering</p>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="text-green-500 mt-0.5">✓</span>
                        <p class="text-sm text-gray-700"><strong>Lab tajribalari</strong>ni bajaring</p>
                    </div>
                </div>
            </div>
        </div>
    </StudentLayout>
</template>
