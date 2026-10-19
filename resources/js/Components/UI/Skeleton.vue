<script setup>
/**
 * Skeleton — loading placeholder.
 *   <Skeleton class="h-5 w-40" />                 (any box via classes)
 *   <Skeleton variant="text" :lines="3" />         (paragraph)
 *   <Skeleton variant="circle" class="h-10 w-10" />
 *   <Skeleton variant="card" />                    (card-sized block)
 */
defineProps({
    variant: { type: String, default: 'rect', validator: (v) => ['rect', 'text', 'circle', 'card'].includes(v) },
    lines: { type: Number, default: 3 },
});
</script>

<template>
    <div v-if="variant === 'text'" class="space-y-2" aria-hidden="true">
        <div v-for="n in lines" :key="n" class="h-3.5 animate-pulse rounded bg-surface-2 motion-reduce:animate-none"
            :class="n === lines && lines > 1 ? 'w-2/3' : 'w-full'" />
    </div>
    <div v-else-if="variant === 'card'" aria-hidden="true" class="rounded-lg border border-border bg-surface p-5">
        <div class="h-4 w-1/3 animate-pulse rounded bg-surface-2 motion-reduce:animate-none" />
        <div class="mt-4 space-y-2">
            <div class="h-3.5 w-full animate-pulse rounded bg-surface-2 motion-reduce:animate-none" />
            <div class="h-3.5 w-5/6 animate-pulse rounded bg-surface-2 motion-reduce:animate-none" />
            <div class="h-3.5 w-2/3 animate-pulse rounded bg-surface-2 motion-reduce:animate-none" />
        </div>
    </div>
    <div v-else aria-hidden="true"
        :class="['animate-pulse bg-surface-2 motion-reduce:animate-none', variant === 'circle' ? 'rounded-full' : 'rounded-md']" />
</template>
