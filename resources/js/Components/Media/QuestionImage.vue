<script setup>
/**
 * QuestionImage — a picture from a legacy image column (question_image, option_image,
 * question_bank_options.image, question_media.image_url): lazy, with alt text, tap to zoom.
 *   <QuestionImage :src="q.image" alt="Savol rasmi" />
 * Images inside the text itself are rendered by RichContent / MathText.
 */
import { ref } from 'vue';
import ImageZoom from './ImageZoom.vue';

defineProps({
    src: { type: String, required: true },
    alt: { type: String, default: 'Rasm' },
});
const zoom = ref('');
</script>

<template>
    <span class="relative inline-block max-w-full align-middle">
        <img :src="src" :alt="alt" loading="lazy" decoding="async" class="block h-auto max-h-72 max-w-full cursor-zoom-in rounded-md border border-border bg-white"
            role="button" tabindex="0" aria-haspopup="dialog"
            @click.prevent.stop="zoom = src" @keydown.enter.prevent.stop="zoom = src" @keydown.space.prevent.stop="zoom = src" />
        <ImageZoom v-model:src="zoom" :alt="alt" />
    </span>
</template>
