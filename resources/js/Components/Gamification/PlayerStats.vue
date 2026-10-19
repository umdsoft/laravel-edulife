<script setup>
import { computed } from 'vue';
import { SparklesIcon } from '@heroicons/vue/20/solid';
import Avatar from '@/Components/UI/Avatar.vue';

const props = defineProps({
    player: Object,
    score: Number,
    isCurrentUser: Boolean,
});

const name = computed(() => {
    const p = props.player;
    if (!p) return 'Raqib kutilmoqda';
    return p.full_name || [p.first_name, p.last_name].filter(Boolean).join(' ') || 'O\'yinchi';
});
const avatar = computed(() => props.player?.avatar_url || props.player?.avatar || null);
const level = computed(() => props.player?.student_profile?.level || props.player?.studentProfile?.level || 1);
</script>

<template>
    <div class="flex min-w-0 items-center gap-3 rounded-lg border p-3 shadow-xs transition-colors duration-150 sm:p-4"
        :class="isCurrentUser ? 'border-brand-200 bg-brand-50' : 'border-border bg-surface'">
        <Avatar :src="avatar" :name="name" size="md" class="shrink-0" />

        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-semibold text-fg">
                {{ name }}
                <span v-if="isCurrentUser" class="font-normal text-fg-subtle">(siz)</span>
            </p>
            <div class="mt-0.5 flex items-center gap-1 text-xs text-fg-muted">
                <SparklesIcon class="h-3.5 w-3.5 text-brand" aria-hidden="true" />
                <span class="tabular-nums">Daraja {{ level }}</span>
            </div>
        </div>

        <div class="shrink-0 text-right">
            <p class="text-2xl font-semibold leading-none tabular-nums text-fg">{{ score ?? 0 }}</p>
            <p class="mt-1 text-[11px] uppercase tracking-wide text-fg-subtle">ball</p>
        </div>
    </div>
</template>
