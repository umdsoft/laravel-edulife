<script setup>
/**
 * MarkdownEditor — lesson text with a small toolbar (heading, bold, list, formula) and a
 * "Ko'rinishi" tab that renders it exactly like the student lesson page (Markdown + KaTeX).
 *   <MarkdownEditor v-model="form.content" label="Matn" :error="form.errors.content" />
 * Keyboard: Ctrl+B — qalin, Ctrl+M — formula, Ctrl+Shift+M — alohida qatordagi formula.
 */
import { computed, nextTick, ref, useId } from 'vue';
import { ListBulletIcon, NumberedListIcon } from '@heroicons/vue/24/outline';
import FormField from '@/Components/UI/FormField.vue';
import RichContent from '@/Components/Student/RichContent.vue';
import { controlBase, controlState } from '@/Components/UI/fieldStyles';
import { insertBlockFormula, prefixLines, wrapSelection } from './markdownTools';

const props = defineProps({
    modelValue: { type: String, default: '' },
    label: { type: String, default: 'Matn' },
    error: [String, Array],
    rows: { type: Number, default: 16 },
    placeholder: {
        type: String,
        default: "## Mavzu\n\nNyutonning ikkinchi qonuni: $F = ma$\n\n$$a = \\frac{F}{m}$$\n\n- birinchi fikr\n- ikkinchi fikr",
    },
});
const emit = defineEmits(['update:modelValue']);

const uid = useId();
const textarea = ref(null);
const mode = ref('write'); // write | preview
const isEmpty = computed(() => !String(props.modelValue || '').trim());

const apply = (fn) => {
    const el = textarea.value;
    if (!el) return;
    const { text, start, end } = fn(props.modelValue || '', el.selectionStart, el.selectionEnd);
    emit('update:modelValue', text);
    nextTick(() => {
        el.focus();
        el.setSelectionRange(start, end);
    });
};

const tools = [
    { key: 'h', label: 'Sarlavha', text: 'H', run: () => apply((t, s, e) => prefixLines(t, s, e, '## ')) },
    { key: 'b', label: 'Qalin (Ctrl+B)', text: 'B', bold: true, run: () => apply((t, s, e) => wrapSelection(t, s, e, '**', '**', 'qalin matn')) },
    { key: 'ul', label: "Ro'yxat", icon: ListBulletIcon, run: () => apply((t, s, e) => prefixLines(t, s, e, '- ')) },
    { key: 'ol', label: "Raqamli ro'yxat", icon: NumberedListIcon, run: () => apply((t, s, e) => prefixLines(t, s, e, '1. ')) },
    { key: 'm', label: 'Formula (Ctrl+M)', text: '∑ x', run: () => apply((t, s, e) => wrapSelection(t, s, e, '$', '$', 'x^2')) },
    { key: 'mm', label: 'Alohida qatordagi formula (Ctrl+Shift+M)', text: '$$', run: () => apply((t, s, e) => insertBlockFormula(t, s, e)) },
];

const onKeydown = (e) => {
    if (!(e.ctrlKey || e.metaKey) || e.altKey) return;
    const key = e.key.toLowerCase();
    if (key === 'b') {
        e.preventDefault();
        tools[1].run();
    } else if (key === 'm') {
        e.preventDefault();
        (e.shiftKey ? tools[5] : tools[4]).run();
    }
};

const showWrite = () => {
    mode.value = 'write';
    nextTick(() => textarea.value?.focus());
};
</script>

<template>
    <FormField :label="label" :error="error" :id="`md-${uid}`" v-slot="{ id, describedBy, invalid }">
        <div :class="['overflow-hidden rounded-md border bg-surface shadow-xs', invalid ? 'border-danger-600' : 'border-border-strong']">
            <div class="flex flex-wrap items-center gap-1 border-b border-border bg-surface-2/60 px-1.5 py-1">
                <div class="mr-1 inline-flex rounded-sm bg-surface-2 p-0.5" role="tablist" aria-label="Tahrir rejimi">
                    <button type="button" role="tab" :aria-selected="mode === 'write'" :class="[
                        'focus-ring rounded-sm px-2.5 py-1 text-13 font-medium',
                        mode === 'write' ? 'bg-surface text-fg shadow-xs' : 'text-fg-muted hover:text-fg',
                    ]" @click="showWrite">Yozish</button>
                    <button type="button" role="tab" :aria-selected="mode === 'preview'" :class="[
                        'focus-ring rounded-sm px-2.5 py-1 text-13 font-medium',
                        mode === 'preview' ? 'bg-surface text-fg shadow-xs' : 'text-fg-muted hover:text-fg',
                    ]" @click="mode = 'preview'">Ko'rinishi</button>
                </div>
                <template v-if="mode === 'write'">
                    <button v-for="t in tools" :key="t.key" type="button" :title="t.label" :aria-label="t.label"
                        class="focus-ring inline-flex h-8 min-w-[2rem] items-center justify-center rounded-sm px-2 text-13 text-fg-muted hover:bg-surface hover:text-fg"
                        @click="t.run()">
                        <component :is="t.icon" v-if="t.icon" class="h-4 w-4" aria-hidden="true" />
                        <span v-else :class="['font-mono', t.bold ? 'font-bold' : '']" aria-hidden="true">{{ t.text }}</span>
                    </button>
                </template>
            </div>

            <textarea v-show="mode === 'write'" :id="id" ref="textarea" :value="modelValue" :rows="rows" :placeholder="placeholder"
                :aria-describedby="describedBy" :aria-invalid="invalid || undefined"
                :class="[controlBase, controlState(invalid), 'resize-y rounded-none border-0 px-3 py-2.5 font-mono text-[13px] leading-6 shadow-none focus:ring-0']"
                @input="emit('update:modelValue', $event.target.value)" @keydown="onKeydown" />

            <div v-if="mode === 'preview'" class="min-h-[12rem] px-4 py-3" aria-live="polite">
                <RichContent v-if="!isEmpty" :html="modelValue" size="lg" />
                <p v-else class="text-13 text-fg-subtle">Hali matn yo'q.</p>
            </div>
        </div>
        <p class="mt-1.5 text-13 text-fg-subtle">
            Markdown: <code class="font-mono text-xs text-fg">## sarlavha</code>, <code class="font-mono text-xs text-fg">**qalin**</code>,
            <code class="font-mono text-xs text-fg">- ro'yxat</code>. Formula: <code class="font-mono text-xs text-fg">$x^2$</code>,
            alohida qatorda: <code class="font-mono text-xs text-fg">$$…$$</code>.
        </p>
    </FormField>
</template>
