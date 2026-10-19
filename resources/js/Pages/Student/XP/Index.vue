<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import {
    AcademicCapIcon, BeakerIcon, BookOpenIcon, ClipboardDocumentCheckIcon, FireIcon, FlagIcon, TrophyIcon,
} from '@heroicons/vue/24/outline';
import { CheckIcon, CircleStackIcon, LockClosedIcon, SparklesIcon } from '@heroicons/vue/20/solid';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Badge, Card, PageHeader, ProgressBar, ProgressRing, StatCard } from '@/Components/UI';

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

const formatNumber = (num) => new Intl.NumberFormat('ru-RU').format(Number(num) || 0).replace(/ /g, ' ');

// Levels data with REALISTIC XP requirements
// O'rtacha o'quvchi kuniga 50-100 XP to'plashi mumkin
// Level 2 ga yetish: 1-2 hafta
// Level 10 ga yetish: 1-2 yil
const levels = [
    { level: 1, xp: 0, title: 'Yangi boshlovchi', description: 'Sayohatingiz boshlanmoqda' },
    { level: 2, xp: 500, title: 'O\'quvchi', description: '1-2 hafta faol o\'qish' },
    { level: 3, xp: 1500, title: 'Izlanuvchi', description: '1 oy muntazam o\'qish' },
    { level: 4, xp: 3500, title: 'Bilimdon', description: '2-3 oy faoliyat' },
    { level: 5, xp: 7000, title: 'Mutaxassis', description: '4-5 oy tajriba' },
    { level: 6, xp: 12000, title: 'Ekspert', description: '6-7 oy o\'qish' },
    { level: 7, xp: 20000, title: 'Mahorat egasi', description: '8-10 oy mehnat' },
    { level: 8, xp: 30000, title: 'Usta', description: '1 yil tajriba' },
    { level: 9, xp: 42000, title: 'Professor', description: '1.5 yil faoliyat' },
    { level: 10, xp: 55000, title: 'Akademik', description: '2 yil va undan ko\'p' },
];

// REALISTIC XP earning values
// Har bir faoliyat uchun XP real vaqt va qiyinchilikka asoslangan
const xpActions = [
    { action: 'Darsni tugatish', xp: '5–15', icon: BookOpenIcon, details: 'Video dars: 5 XP, matnli: 8 XP, amaliy: 15 XP' },
    { action: 'Test topshirish', xp: '10–40', icon: ClipboardDocumentCheckIcon, details: '50–69%: 10 XP, 70–89%: 25 XP, 90–100%: 40 XP' },
    { action: 'Kurs tugatish', xp: '100–300', icon: AcademicCapIcon, details: 'Qisqa kurs: 100 XP, o\'rta: 200 XP, katta: 300 XP' },
    { action: 'Kunlik seriya bonusi', xp: '5–25', icon: FireIcon, details: '1–7 kun: 5 XP, 8–30 kun: 15 XP, 30+ kun: 25 XP' },
    { action: 'Turnirda qatnashish', xp: '20–100', icon: TrophyIcon, details: 'Qatnashish: 20 XP, Top 10: 50 XP, g\'alaba: 100 XP' },
    { action: 'Olimpiadada qatnashish', xp: '50–200', icon: SparklesIcon, details: 'Qatnashish: 50 XP, medal: 100–200 XP (fanga bog\'liq)' },
    { action: 'Laboratoriya tajribasi', xp: '15–50', icon: BeakerIcon, details: 'Boshlang\'ich: 15 XP, o\'rta: 30 XP, murakkab: 50 XP' },
    { action: 'Haftalik maqsad', xp: '30–60', icon: FlagIcon, details: 'Asosiy: 30 XP, bonus: 45 XP, barcha maqsadlar: 60 XP' },
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

const lessonsDone = computed(() => props.profile.lessons_completed ?? props.profile.completed_lessons ?? 0);
const coursesDone = computed(() => props.profile.courses_completed ?? props.profile.completed_courses ?? 0);
</script>

<template>

    <Head title="XP va daraja" />

    <StudentLayout title="XP va daraja">
        <PageHeader title="XP va daraja" description="Tajriba ballari qanday to'planishi va keyingi darajaga qancha qolgani." />

        <div class="space-y-6">
            <!-- Current level -->
            <Card>
                <div class="flex flex-col items-center gap-6 sm:flex-row sm:items-center">
                    <ProgressRing :value="progressPercentage" :size="112" :stroke="8" tone="brand" :show-value="false"
                        :label="`Daraja ${profile.level} progressi`">
                        <div class="text-center leading-tight">
                            <p class="text-[11px] uppercase tracking-wide text-fg-subtle">Daraja</p>
                            <p class="text-3xl font-semibold tabular-nums text-fg">{{ profile.level }}</p>
                        </div>
                    </ProgressRing>

                    <div class="w-full min-w-0 flex-1 text-center sm:text-left">
                        <div class="flex flex-wrap items-center justify-center gap-2 sm:justify-start">
                            <h2 class="text-lg font-semibold text-fg">{{ currentLevel.title }}</h2>
                            <Badge variant="brand" size="sm" :icon="SparklesIcon">
                                <span class="tabular-nums">{{ formatNumber(profile.xp) }} XP</span>
                            </Badge>
                        </div>
                        <p class="mt-1 text-sm text-fg-muted">{{ currentLevel.description }}</p>

                        <div class="mt-4 space-y-1.5">
                            <div class="flex justify-between text-xs tabular-nums text-fg-subtle">
                                <span>Daraja {{ profile.level }}</span>
                                <span class="font-medium text-fg">{{ Math.round(progressPercentage) }}%</span>
                                <span>Daraja {{ profile.level + 1 }}</span>
                            </div>
                            <ProgressBar :value="progressPercentage" tone="brand" size="md" :label="`Daraja ${profile.level + 1} gacha`" />
                            <p class="text-13 text-fg-muted">
                                Keyingi darajagacha <span class="font-medium tabular-nums text-fg">{{ formatNumber(xp_to_next_level) }} XP</span>
                            </p>
                        </div>
                    </div>
                </div>
            </Card>

            <!-- Quick stats -->
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <StatCard label="Seriya" :value="profile.streak_days || 0" unit="kun" :icon="FireIcon" tone="warning" />
                <StatCard label="Tugatilgan darslar" :value="lessonsDone" :icon="BookOpenIcon" tone="brand" />
                <StatCard label="Tugatilgan kurslar" :value="coursesDone" :icon="AcademicCapIcon" tone="success" />
                <StatCard label="Coinlar" :value="profile.coins || 0" :icon="CircleStackIcon" tone="warning" />
            </div>

            <!-- XP earning methods -->
            <Card title="XP olish yo'llari" description="Har bir faoliyat uchun beriladigan taxminiy XP">
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div v-for="item in xpActions" :key="item.action" class="rounded-md border border-border p-4">
                        <div class="flex items-start gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-surface-2 text-fg-muted">
                                <component :is="item.icon" class="h-5 w-5" aria-hidden="true" />
                            </span>
                            <div class="min-w-0">
                                <h4 class="text-sm font-medium text-fg">{{ item.action }}</h4>
                                <p class="text-xs font-medium tabular-nums text-brand">+{{ item.xp }} XP</p>
                            </div>
                        </div>
                        <p class="mt-3 text-xs text-fg-subtle">{{ item.details }}</p>
                    </div>
                </div>
            </Card>

            <!-- All levels -->
            <Card title="Barcha darajalar">
                <ol class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                    <li v-for="lvl in levels" :key="lvl.level"
                        :aria-current="lvl.level === profile.level ? 'step' : undefined"
                        :class="[
                            'rounded-md border p-3',
                            lvl.level === profile.level ? 'border-brand-200 bg-brand-50' : 'border-border',
                        ]">
                        <div class="flex items-center justify-between gap-2">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full text-13 font-semibold tabular-nums"
                                :class="lvl.level < profile.level ? 'bg-success-50 text-success'
                                    : lvl.level === profile.level ? 'bg-brand-600 text-white' : 'bg-surface-2 text-fg-subtle'">
                                {{ lvl.level }}
                            </span>
                            <CheckIcon v-if="lvl.level < profile.level" class="h-4 w-4 text-success" aria-label="O'tilgan" />
                            <Badge v-else-if="lvl.level === profile.level" size="sm" variant="brand">Hozirgi</Badge>
                            <LockClosedIcon v-else class="h-4 w-4 text-fg-subtle" aria-label="Qulflangan" />
                        </div>
                        <p class="mt-2 text-sm font-medium" :class="lvl.level > profile.level ? 'text-fg-muted' : 'text-fg'">{{ lvl.title }}</p>
                        <p class="text-xs tabular-nums text-fg-subtle">{{ formatNumber(lvl.xp) }} XP</p>
                    </li>
                </ol>
            </Card>

            <!-- Next level -->
            <Card v-if="nextLevel" :title="`Keyingi daraja: ${nextLevel.level}`">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-brand-50 text-brand">
                        <SparklesIcon class="h-5 w-5" aria-hidden="true" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-fg">«{{ nextLevel.title }}» unvoni</p>
                        <p class="text-13 text-fg-subtle">
                            <span class="tabular-nums">{{ formatNumber(nextLevel.xp) }} XP</span> da ochiladi · {{ nextLevel.description }}
                        </p>
                    </div>
                </div>
            </Card>
        </div>
    </StudentLayout>
</template>
