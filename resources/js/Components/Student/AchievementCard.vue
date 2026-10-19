<script setup>
import { computed } from 'vue';
import { TrophyIcon, LockClosedIcon } from '@heroicons/vue/24/outline';
import { CheckCircleIcon, SparklesIcon, CircleStackIcon } from '@heroicons/vue/20/solid';
import Badge from '@/Components/UI/Badge.vue';

const props = defineProps({
    achievement: Object,
    isUnlocked: Boolean,
    isClaimed: Boolean,
});

const emit = defineEmits(['click']);

const rarity = computed(() => {
    const map = {
        common: { variant: 'neutral', label: 'Oddiy' },
        rare: { variant: 'info', label: 'Kamyob' },
        epic: { variant: 'brand', label: 'Epik' },
        legendary: { variant: 'warning', label: 'Afsonaviy' },
    };
    return map[props.achievement?.rarity] || { variant: 'neutral', label: props.achievement?.rarity || 'Oddiy' };
});

const readyToClaim = computed(() => props.isUnlocked && !props.isClaimed);
</script>

<template>
    <button type="button" :class="[
        'focus-ring group flex w-full items-start gap-4 rounded-lg border bg-surface p-5 text-left shadow-xs transition-shadow duration-150 ease-out hover:shadow-md',
        readyToClaim ? 'border-warning-600/40 ring-1 ring-warning-100' : 'border-border hover:border-border-strong',
    ]" @click="emit('click')">
        <span :class="[
            'flex h-12 w-12 shrink-0 items-center justify-center rounded-md text-2xl',
            isUnlocked ? 'bg-warning-50 text-warning' : 'bg-surface-2 text-fg-subtle grayscale',
        ]" aria-hidden="true">
            <template v-if="achievement.icon && typeof achievement.icon === 'string'">{{ achievement.icon }}</template>
            <TrophyIcon v-else class="h-6 w-6" />
        </span>

        <span class="min-w-0 flex-1">
            <span class="flex items-start justify-between gap-3">
                <span class="min-w-0">
                    <span :class="['block font-semibold', isUnlocked ? 'text-fg' : 'text-fg-muted']">{{ achievement.title }}</span>
                    <span class="mt-1 flex flex-wrap items-center gap-2">
                        <Badge :variant="rarity.variant" size="sm">{{ rarity.label }}</Badge>
                        <span v-if="achievement.category" class="text-xs text-fg-subtle">{{ achievement.category }}</span>
                    </span>
                </span>
                <span class="shrink-0">
                    <CheckCircleIcon v-if="isClaimed" class="h-6 w-6 text-success" aria-hidden="true" />
                    <Badge v-else-if="readyToClaim" variant="warning" size="sm" dot>Olish mumkin</Badge>
                    <LockClosedIcon v-else class="h-5 w-5 text-fg-subtle" aria-hidden="true" />
                    <span class="sr-only">{{ isClaimed ? 'Olingan' : (readyToClaim ? 'Mukofotni olish mumkin' : 'Qulflangan') }}</span>
                </span>
            </span>

            <span v-if="achievement.description" class="mt-2 block text-sm text-fg-muted">{{ achievement.description }}</span>

            <span class="mt-3 flex items-center gap-4 text-13 font-medium tabular-nums">
                <span class="inline-flex items-center gap-1 text-brand">
                    <SparklesIcon class="h-4 w-4" aria-hidden="true" />
                    {{ achievement.xp_reward }} XP
                </span>
                <span class="inline-flex items-center gap-1 text-warning">
                    <CircleStackIcon class="h-4 w-4" aria-hidden="true" />
                    {{ achievement.coin_reward }} coin
                </span>
            </span>
        </span>
    </button>
</template>
