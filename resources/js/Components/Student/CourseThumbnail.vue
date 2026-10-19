<script setup>
/**
 * CourseThumbnail — course cover image with a calm fallback tile
 * (no external placeholder file needed; broken images fall back too).
 *   <CourseThumbnail :src="course.thumbnail_url" :alt="course.title" class="aspect-video" />
 * Size it from the parent with classes (aspect-video, h-16 w-24 …) — the image fills it absolutely.
 */
import { ref, watch } from 'vue';
import { AcademicCapIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    src: { type: String, default: null },
    alt: { type: String, default: '' },
    // lazy-load the image (default) — pass false for above-the-fold covers
    lazy: { type: Boolean, default: true },
    iconClass: { type: String, default: 'h-8 w-8' },
});

const failed = ref(false);
watch(() => props.src, () => (failed.value = false));
</script>

<template>
    <div class="relative overflow-hidden bg-surface-2">
        <img v-if="src && !failed" :src="src" :alt="alt" :loading="lazy ? 'lazy' : 'eager'" decoding="async"
            class="absolute inset-0 h-full w-full object-cover" @error="failed = true">
        <div v-else class="absolute inset-0 flex items-center justify-center bg-brand-50 text-brand" role="img"
            :aria-label="alt || undefined">
            <AcademicCapIcon :class="iconClass" aria-hidden="true" />
        </div>
        <slot />
    </div>
</template>
