<template>
    <StudentLayout title="Kunlik topshiriqlar">
        <Head title="Kunlik topshiriqlar" />

        <PageHeader title="Kunlik topshiriqlar" description="Barcha topshiriqlarni bajaring va qo'shimcha mukofot oling.">
            <template #actions>
                <div class="flex items-center gap-2 rounded-md border border-border bg-surface px-3 py-2 shadow-xs">
                    <ClockIcon class="h-5 w-5 text-fg-subtle" aria-hidden="true" />
                    <div class="leading-tight">
                        <p class="text-[11px] uppercase tracking-wide text-fg-subtle">Yangilanishgacha</p>
                        <p class="font-mono text-sm font-semibold tabular-nums text-fg" aria-live="off">
                            {{ reset_time.hours }}:{{ String(reset_time.minutes).padStart(2, '0') }}:{{ String(reset_time.seconds).padStart(2, '0') }}
                        </p>
                    </div>
                </div>
            </template>
        </PageHeader>

        <div class="space-y-6">
            <!-- Progress -->
            <Card padding="sm">
                <div class="flex items-center gap-4">
                    <ProgressRing :value="completed_count || 0" :max="total_count || 1" :size="56" :stroke="5"
                        tone="success" :show-value="false" label="Kunlik progress">
                        <span class="text-13 font-semibold tabular-nums text-fg">{{ completed_count || 0 }}/{{ total_count || 0 }}</span>
                    </ProgressRing>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-fg">Kunlik progress</p>
                        <p class="text-13 text-fg-subtle">
                            <template v-if="total_count && completed_count >= total_count">Barcha topshiriqlar bajarildi</template>
                            <template v-else>{{ (total_count || 0) - (completed_count || 0) }} ta topshiriq qoldi</template>
                        </p>
                        <ProgressBar class="mt-2" :value="completed_count || 0" :max="total_count || 1" tone="success" size="sm" />
                    </div>
                </div>
            </Card>

            <!-- Missions list -->
            <EmptyState v-if="!missions || missions.length === 0" :icon="FlagIcon" bordered title="Bugun topshiriq yo'q"
                description="Yangi topshiriqlar yarim tunda paydo bo'ladi." />

            <Card v-else padding="none">
                <ul class="divide-y divide-border">
                    <li v-for="userMission in missions" :key="userMission.id"
                        class="flex flex-col gap-4 px-5 py-4 sm:flex-row sm:items-center sm:px-6"
                        :class="userMission.is_completed && !userMission.is_claimed ? 'bg-success-50/60' : ''">
                        <div class="flex min-w-0 flex-1 items-start gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md text-lg"
                                :class="userMission.is_completed ? 'bg-success-50 text-success' : 'bg-surface-2 text-fg-muted'">
                                <CheckIcon v-if="userMission.is_claimed" class="h-5 w-5" aria-hidden="true" />
                                <span v-else-if="userMission.mission?.icon" aria-hidden="true">{{ userMission.mission.icon }}</span>
                                <FlagIcon v-else class="h-5 w-5" aria-hidden="true" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <h3 class="text-sm font-semibold text-fg">{{ userMission.mission?.title }}</h3>
                                <p v-if="userMission.mission?.description" class="mt-0.5 text-13 text-fg-muted">{{ userMission.mission.description }}</p>

                                <div class="mt-3 flex items-center gap-3">
                                    <ProgressBar class="flex-1" :value="Number(userMission.current_value) || 0"
                                        :max="Number(userMission.target_value) || 1" size="sm"
                                        :tone="userMission.is_completed ? 'success' : 'brand'"
                                        :label="`${userMission.mission?.title || 'Topshiriq'} progressi`" />
                                    <span class="shrink-0 text-xs font-medium tabular-nums text-fg-muted">
                                        {{ userMission.progress_label || `${userMission.current_value} / ${userMission.target_value}` }}
                                    </span>
                                </div>

                                <div class="mt-3 flex flex-wrap items-center gap-2">
                                    <Badge v-if="userMission.mission?.xp_reward" size="sm" variant="brand" :icon="SparklesIcon">
                                        <span class="tabular-nums">+{{ userMission.mission.xp_reward }} XP</span>
                                    </Badge>
                                    <Badge v-if="userMission.mission?.coin_reward" size="sm" variant="warning" :icon="CircleStackIcon">
                                        <span class="tabular-nums">+{{ userMission.mission.coin_reward }} coin</span>
                                    </Badge>
                                </div>
                            </div>
                        </div>

                        <div class="flex shrink-0 sm:justify-end">
                            <Button v-if="userMission.is_completed && !userMission.is_claimed" variant="primary"
                                :loading="claimingId === userMission.id" :icon-left="GiftIcon" class="w-full sm:w-auto"
                                @click="claimReward(userMission)">
                                Mukofotni olish
                            </Button>
                            <Badge v-else-if="userMission.is_claimed" variant="success" :icon="CheckIcon">Olingan</Badge>
                            <Badge v-else variant="neutral">Jarayonda</Badge>
                        </div>
                    </li>
                </ul>
            </Card>
        </div>
    </StudentLayout>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { ClockIcon, FlagIcon, GiftIcon } from '@heroicons/vue/24/outline';
import { CheckIcon, CircleStackIcon, SparklesIcon } from '@heroicons/vue/20/solid';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Badge, Button, Card, EmptyState, PageHeader, ProgressBar, ProgressRing } from '@/Components/UI';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    missions: Array,
    completed_count: Number,
    total_count: Number,
    reset_time: Object,
});

const toast = useToast();
const claimingId = ref(null);

const claimReward = (userMission) => {
    if (claimingId.value) return;
    claimingId.value = userMission.id;
    axios.post(route('student.missions.claim', userMission.id))
        .then((res) => {
            if (res.data?.success === false) {
                toast.error(res.data?.message || 'Mukofotni olib bo\'lmadi');
            } else {
                toast.success(res.data?.message || 'Mukofot olindi');
            }
            router.reload();
        })
        .catch((e) => {
            toast.error(e.response?.data?.message || 'Mukofotni olib bo\'lmadi');
        })
        .finally(() => {
            claimingId.value = null;
        });
};

// Update timer every second
let timerInterval = null;

onMounted(() => {
    timerInterval = setInterval(() => {
        if (props.reset_time.seconds > 0) {
            props.reset_time.seconds--;
        } else if (props.reset_time.minutes > 0) {
            props.reset_time.minutes--;
            props.reset_time.seconds = 59;
        } else if (props.reset_time.hours > 0) {
            props.reset_time.hours--;
            props.reset_time.minutes = 59;
            props.reset_time.seconds = 59;
        } else {
            router.reload(); // Refresh when timer reaches 0
        }
    }, 1000);
});

onUnmounted(() => {
    if (timerInterval) {
        clearInterval(timerInterval);
    }
});
</script>
