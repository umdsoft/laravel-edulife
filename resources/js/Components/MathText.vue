<script setup>
/**
 * MathText — a question / option / explanation with its LaTeX formulas rendered (KaTeX).
 * No Markdown; line breaks are kept. Shared by the teacher editor preview and the student test pages.
 *   <MathText :text="question.question_text" />
 *   <MathText :text="option.content" tag="span" zoom="badge" />
 * Images ![alt](/storage/questions/…) and ```graph blocks render too (Utils/richText.js, same as RichContent).
 * zoom: 'tap' — tapping an image opens it full screen; 'badge' — only its ⤢ corner does (option cards,
 * where a tap on the picture selects the option).
 */
import { computed } from 'vue';
import 'katex/dist/katex.min.css';
import { useSanitize } from '@/Composables/useSanitize.js';
import { richSegments } from '@/Utils/richText';
import GraphView from '@/Components/Media/GraphView.vue';
import ImageZoom from '@/Components/Media/ImageZoom.vue';
import { useImageZoom } from '@/Components/Media/useImageZoom';

const props = defineProps({
    text: { type: [String, Number], default: '' },
    tag: { type: String, default: 'span' },
    zoom: { type: String, default: 'tap', validator: (v) => ['tap', 'badge'].includes(v) },
});

const { sanitize } = useSanitize();
const segments = computed(() => richSegments(String(props.text ?? ''), { mode: 'plain', sanitize, zoom: props.zoom }));
const { zoomSrc, zoomAlt, onClick, onKeydown } = useImageZoom();
</script>

<template>
    <component :is="tag" class="math-text whitespace-pre-wrap" @click="onClick" @keydown="onKeydown">
        <template v-for="(seg, i) in segments" :key="i">
            <span v-if="seg.type === 'html'" v-html="seg.html" />
            <GraphView v-else :spec="seg.spec" :errors="seg.errors" />
        </template>
        <ImageZoom v-model:src="zoomSrc" :alt="zoomAlt" />
    </component>
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
.math-text :deep(.rt-img-wrap) {
    display: inline-block;
    position: relative;
    max-width: 100%;
    vertical-align: middle;
    margin: 0.25em 0;
}
.math-text :deep(.rt-img-wrap > img) {
    display: block;
    width: 100%;
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    background: #fff;
}
.math-text :deep(.rt-img[data-zoom]) {
    cursor: zoom-in;
}
.math-text :deep(.rt-zoom) {
    position: absolute;
    right: 4px;
    top: 4px;
    display: inline-flex;
    height: 28px;
    width: 28px;
    align-items: center;
    justify-content: center;
    border-radius: 9999px;
    background: rgb(15 23 42 / 0.65);
    color: #fff;
    font-size: 15px;
    line-height: 1;
    cursor: zoom-in;
}
.math-text :deep(.graph-view) {
    white-space: normal;
}
</style>
