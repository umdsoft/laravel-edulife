<script setup>
/**
 * BrandMark — EDULIFE logo (mark + wordmark). `compact` shows the mark only.
 */
defineProps({
    compact: Boolean,
    // small role label next to the wordmark ("Ustoz", "Admin")
    tag: { type: String, default: null },
    size: { type: String, default: 'md', validator: (v) => ['md', 'lg'].includes(v) },
});
</script>

<template>
    <span class="inline-flex min-w-0 items-center gap-2.5">
        <span :class="['inline-flex shrink-0 items-center justify-center rounded-md bg-brand-600 text-white shadow-xs', size === 'lg' ? 'h-10 w-10' : 'h-8 w-8']">
            <svg :class="size === 'lg' ? 'h-6 w-6' : 'h-5 w-5'" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <!-- orbit + nucleus: maths & physics -->
                <ellipse cx="12" cy="12" rx="9" ry="4.2" stroke="currentColor" stroke-width="1.6" transform="rotate(-30 12 12)" opacity=".9" />
                <path d="M8 7.5h7M8 12h5.5M8 16.5h7M8 7.5v9" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" />
            </svg>
        </span>
        <span v-if="!compact" class="flex min-w-0 items-baseline gap-2">
            <span :class="['font-semibold tracking-tight text-fg', size === 'lg' ? 'text-xl' : 'text-base']">EDULIFE</span>
            <span v-if="tag" class="truncate rounded-full bg-surface-2 px-2 py-px text-[11px] font-medium text-fg-muted">{{ tag }}</span>
        </span>
    </span>
</template>
