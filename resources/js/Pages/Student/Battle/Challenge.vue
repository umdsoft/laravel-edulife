<script setup>
/**
 * Telegram WebView — a friend's battle challenge (t.me/<bot>?startapp=battle_<code> or
 * /student/battles/challenge/<code>): who invites, topic, level; "Qabul qilish" starts the match
 * for both players.
 */
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { BoltIcon, ClockIcon } from '@heroicons/vue/24/outline';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Avatar, Badge, Button, Card, PageHeader } from '@/Components/UI';
import { haptic, useMainButton } from '@/Composables/useTelegram';

const props = defineProps({
    challenge: { type: Object, required: true },
    serverTime: { type: String, default: null },
});

const offset = props.serverTime ? Date.parse(props.serverTime) - Date.now() : 0;
const now = ref(Date.now() + offset);
let timer = null;
onMounted(() => { timer = setInterval(() => { now.value = Date.now() + offset; }, 1000); });
onBeforeUnmount(() => clearInterval(timer));

const secondsLeft = computed(() => (props.challenge.expires_at ? Math.max(0, Math.floor((Date.parse(props.challenge.expires_at) - now.value) / 1000)) : 0));
const open = computed(() => props.challenge.open && secondsLeft.value > 0);
const left = computed(() => {
    const m = Math.floor(secondsLeft.value / 60);
    const s = String(secondsLeft.value % 60).padStart(2, '0');
    return `${m}:${s}`;
});

const accepting = ref(false);
const accept = () => {
    if (!open.value || accepting.value) return;
    accepting.value = true;
    haptic.impact('medium');
    router.post(route('student.battles.challenge.accept', props.challenge.code), {}, { onFinish: () => { accepting.value = false; } });
};

useMainButton(() => (open.value ? { text: 'Qabul qilish', onClick: accept, progress: accepting.value } : null));
</script>

<template>
    <StudentLayout title="Chaqiruv">
        <Head title="Battle'ga chaqiruv" />

        <PageHeader title="Battle'ga chaqiruv" description="Do'stingiz sizni 1 ga 1 bilimlar jangiga chaqirdi."
            :breadcrumbs="[{ label: 'Janglar', href: route('student.battles.index') }, { label: 'Chaqiruv' }]" />

        <Card class="mx-auto max-w-md">
            <div class="flex flex-col items-center gap-4 py-4 text-center">
                <Avatar :src="challenge.challenger.avatar" :name="challenge.challenger.name" size="xl" />
                <div>
                    <p class="text-lg font-semibold text-fg">{{ challenge.challenger.name }}</p>
                    <p v-if="challenge.challenger.elo_rating" class="text-13 tabular-nums text-fg-subtle">ELO {{ challenge.challenger.elo_rating }}</p>
                </div>
                <div class="flex flex-wrap justify-center gap-2">
                    <Badge variant="neutral">{{ challenge.direction || 'Aralash mavzular' }}</Badge>
                    <Badge v-if="challenge.difficulty_label" variant="brand">{{ challenge.difficulty_label }}</Badge>
                    <Badge variant="neutral">{{ challenge.total_rounds }} savol · {{ challenge.time_per_question }} s</Badge>
                </div>

                <template v-if="open">
                    <p class="inline-flex items-center gap-1.5 text-13 tabular-nums text-fg-muted">
                        <ClockIcon class="h-4 w-4" aria-hidden="true" /> Chaqiruv yana {{ left }} amal qiladi
                    </p>
                    <Button size="lg" block :icon-left="BoltIcon" :loading="accepting" @click="accept">Qabul qilish</Button>
                </template>
                <p v-else class="text-sm text-fg-muted" role="status">Bu chaqiruv eskirgan yoki allaqachon qabul qilingan.</p>

                <Button variant="ghost" :href="route('student.battles.index')">Janglar sahifasi</Button>
            </div>
        </Card>
    </StudentLayout>
</template>
