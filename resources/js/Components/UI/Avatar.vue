<script setup>
/**
 * Avatar — image with initials fallback (no external avatar service needed).
 *   <Avatar :src="user.avatar" :name="user.full_name" size="sm" />
 * `src` may be a full URL, an absolute path, or a storage path ("avatars/x.jpg" → "/storage/avatars/x.jpg").
 * size: xs (24) | sm (32) | md (40) | lg (48) | xl (64) | 2xl (96)
 */
import { computed, ref, watch } from 'vue';

const props = defineProps({
    src: { type: String, default: null },
    name: { type: String, default: '' },
    alt: { type: String, default: null },
    size: { type: String, default: 'md' },
    shape: { type: String, default: 'circle', validator: (v) => ['circle', 'square'].includes(v) },
    // Optional status dot: online | offline | busy
    status: { type: String, default: null },
});

const failed = ref(false);
watch(() => props.src, () => (failed.value = false));

const resolved = computed(() => {
    const s = props.src;
    if (!s) return null;
    if (/^(https?:|data:|blob:|\/)/i.test(s)) return s;
    return `/storage/${s}`;
});

const initials = computed(() => {
    const parts = String(props.name || '').trim().split(/\s+/).filter(Boolean);
    if (!parts.length) return '?';
    const first = parts[0][0] || '';
    const last = parts.length > 1 ? parts[parts.length - 1][0] : '';
    return (first + last).toUpperCase();
});

// Deterministic calm tint per name
const TINTS = [
    'bg-brand-100 text-brand',
    'bg-physics-100 text-physics',
    'bg-info-100 text-info',
    'bg-success-100 text-success',
    'bg-warning-100 text-warning',
];
const tint = computed(() => {
    let h = 0;
    for (const ch of String(props.name || '')) h = (h * 31 + ch.charCodeAt(0)) >>> 0;
    return TINTS[h % TINTS.length];
});

const sizes = {
    xs: 'h-6 w-6 text-[10px]',
    sm: 'h-8 w-8 text-xs',
    md: 'h-10 w-10 text-sm',
    lg: 'h-12 w-12 text-base',
    xl: 'h-16 w-16 text-lg',
    '2xl': 'h-24 w-24 text-2xl',
};
const statusColor = { online: 'bg-success-600', offline: 'bg-fg-subtle', busy: 'bg-danger-600' };
</script>

<template>
    <span :class="['relative inline-flex shrink-0', sizes[size] || sizes.md]">
        <img v-if="resolved && !failed" :src="resolved" :alt="alt ?? name" loading="lazy" decoding="async"
            :class="['h-full w-full object-cover ring-1 ring-border', shape === 'circle' ? 'rounded-full' : 'rounded-md']"
            @error="failed = true" />
        <span v-else role="img" :aria-label="alt ?? name"
            :class="['inline-flex h-full w-full select-none items-center justify-center font-semibold', tint, shape === 'circle' ? 'rounded-full' : 'rounded-md']">
            {{ initials }}
        </span>
        <span v-if="status" :class="['absolute bottom-0 right-0 h-2.5 w-2.5 rounded-full ring-2 ring-surface', statusColor[status]]"
            aria-hidden="true" />
    </span>
</template>
