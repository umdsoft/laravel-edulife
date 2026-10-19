<script setup>
/**
 * XPProgressBar — level + XP progress panel. API unchanged: level, xp, xpToNextLevel.
 */
import { computed } from 'vue';
import { SparklesIcon } from '@heroicons/vue/24/outline';
import ProgressBar from '@/Components/UI/ProgressBar.vue';

const props = defineProps({
    level: Number,
    xp: Number,
    xpToNextLevel: Number,
});

const progressPercentage = computed(() => {
    if (props.xpToNextLevel === 0) return 100;
    return Math.min(100, Math.round((props.xp / props.xpToNextLevel) * 100));
});
</script>

<template>
    <div class="rounded-lg border border-border bg-surface p-5 shadow-xs sm:p-6">
        <div class="mb-4 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-md bg-brand-50 text-brand">
                    <SparklesIcon class="h-6 w-6" aria-hidden="true" />
                </span>
                <div>
                    <h3 class="text-lg font-semibold text-fg">Daraja <span class="tabular-nums">{{ level }}</span></h3>
                    <p class="text-13 text-fg-subtle tabular-nums">{{ xp }} / {{ xpToNextLevel }} XP</p>
                </div>
            </div>
            <div class="text-2xl font-semibold tracking-tight text-fg tabular-nums">{{ progressPercentage }}%</div>
        </div>

        <ProgressBar :value="progressPercentage" size="md" />
    </div>
</template>
