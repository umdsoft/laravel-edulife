<script setup>
/**
 * MathText — a question / option / explanation with its LaTeX formulas rendered (KaTeX).
 * No Markdown; line breaks are kept. Shared by the teacher editor preview and the student test pages.
 *   <MathText :text="question.question_text" />
 *   <MathText :text="option.content" tag="span" />
 */
import { computed } from 'vue';
import 'katex/dist/katex.min.css';
import { useSanitize } from '@/Composables/useSanitize.js';
import { mathTextHtml } from '@/Utils/mathText';

const props = defineProps({
    text: { type: [String, Number], default: '' },
    tag: { type: String, default: 'span' },
});

const { sanitize } = useSanitize();
const html = computed(() => mathTextHtml(props.text, sanitize));
</script>

<template>
    <component :is="tag" class="math-text whitespace-pre-wrap" v-html="html" />
</template>

<style scoped>
.math-text :deep(.katex) {
    font-size: 1.08em;
}
.math-text :deep(.katex-display) {
    margin: 0.5em 0;
    overflow-x: auto;
    overflow-y: hidden;
}
</style>
