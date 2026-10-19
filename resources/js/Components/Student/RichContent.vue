<script setup>
/**
 * RichContent — renders teacher-authored HTML (sanitized) with calm, readable typography.
 * The app has no @tailwindcss/typography plugin, so `prose` classes were no-ops; this replaces them.
 *   <RichContent :html="course.description" />
 *   <RichContent :html="announcement.content" size="sm" />
 * Formulas stay as text for the future <MathContent> (KaTeX) renderer.
 */
import { computed } from 'vue';
import { useSanitize } from '@/Composables/useSanitize.js';

const props = defineProps({
    html: { type: String, default: '' },
    size: { type: String, default: 'md', validator: (v) => ['sm', 'md', 'lg'].includes(v) },
});

const { sanitize } = useSanitize();
const safe = computed(() => sanitize(props.html || ''));
const sizes = { sm: 'text-sm', md: 'text-[15px]', lg: 'text-base' };
</script>

<template>
    <div :class="['rich-content text-fg-muted', sizes[size]]" v-html="safe" />
</template>

<style scoped>
.rich-content {
    line-height: 1.7;
    overflow-wrap: anywhere;
}
.rich-content :deep(> * + *) {
    margin-top: 0.85em;
}
.rich-content :deep(h1),
.rich-content :deep(h2),
.rich-content :deep(h3),
.rich-content :deep(h4) {
    color: rgb(var(--fg));
    font-weight: 600;
    letter-spacing: -0.01em;
    line-height: 1.35;
    margin-top: 1.6em;
}
.rich-content :deep(h1) { font-size: 1.5em; }
.rich-content :deep(h2) { font-size: 1.3em; }
.rich-content :deep(h3) { font-size: 1.12em; }
.rich-content :deep(h4) { font-size: 1em; }
.rich-content :deep(> :first-child) { margin-top: 0; }
.rich-content :deep(strong),
.rich-content :deep(b) {
    color: rgb(var(--fg));
    font-weight: 600;
}
.rich-content :deep(a) {
    color: rgb(var(--brand-fg));
    text-decoration: underline;
    text-underline-offset: 3px;
}
.rich-content :deep(ul),
.rich-content :deep(ol) {
    padding-left: 1.4em;
}
.rich-content :deep(ul) { list-style: disc; }
.rich-content :deep(ol) { list-style: decimal; }
.rich-content :deep(li + li) { margin-top: 0.35em; }
.rich-content :deep(li::marker) { color: rgb(var(--fg-subtle)); }
.rich-content :deep(blockquote) {
    border-left: 3px solid rgb(var(--brand-200));
    padding-left: 1em;
    color: rgb(var(--fg-muted));
    font-style: italic;
}
.rich-content :deep(code) {
    font-family: 'JetBrains Mono', ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    font-size: 0.9em;
    background: rgb(var(--surface-2));
    border-radius: 6px;
    padding: 0.1em 0.35em;
    color: rgb(var(--fg));
}
.rich-content :deep(pre) {
    background: rgb(var(--surface-2));
    border: 1px solid rgb(var(--border));
    border-radius: 10px;
    padding: 0.9em 1em;
    overflow-x: auto;
}
.rich-content :deep(pre code) {
    background: none;
    padding: 0;
}
.rich-content :deep(img) {
    max-width: 100%;
    height: auto;
    border-radius: 10px;
}
.rich-content :deep(figcaption) {
    margin-top: 0.4em;
    font-size: 0.85em;
    color: rgb(var(--fg-subtle));
}
.rich-content :deep(hr) {
    border-color: rgb(var(--border));
    margin: 1.6em 0;
}
.rich-content :deep(table) {
    display: block;
    width: 100%;
    overflow-x: auto;
    border-collapse: collapse;
    font-size: 0.92em;
}
.rich-content :deep(th),
.rich-content :deep(td) {
    border: 1px solid rgb(var(--border));
    padding: 0.5em 0.75em;
    text-align: left;
}
.rich-content :deep(th) {
    background: rgb(var(--surface-2));
    color: rgb(var(--fg));
    font-weight: 600;
}
</style>
