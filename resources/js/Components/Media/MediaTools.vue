<script setup>
/**
 * MediaTools — "Rasm" and "Grafik" buttons for a text field (MarkdownEditor's toolbar, option fields).
 *   <MediaTools ref="media" v-model="form.text" :target="() => textareaEl" />
 *   <textarea @paste="media.onPaste" @drop="media.onDrop" @dragover="media.onDragOver" />
 * Rasm: file picker, paste (Ctrl+V) or drag-drop → upload (progress, errors) → ![alt](/storage/questions/…).
 * Grafik: builder dialog; when the cursor is inside a ```graph block, that block is edited.
 * `inline` puts images inside the line (options) instead of on their own line.
 */
import { ref } from 'vue';
import { ChartBarIcon, PhotoIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import GraphBuilderDialog from './GraphBuilderDialog.vue';
import { altFromFileName, useImageUpload } from './useImageUpload';
import { graphBlock, graphBlockAt, insertBlock, insertInline } from '@/Components/Teacher/markdownTools';

const props = defineProps({
    modelValue: { type: String, default: '' },
    target: { type: Function, default: null }, // () => HTMLTextAreaElement | null
    inline: Boolean,
    compact: Boolean, // icon-only buttons
});
const emit = defineEmits(['update:modelValue']);

const fileInput = ref(null);
const { uploading, progress, error, upload } = useImageUpload();
const graphOpen = ref(false);
const editing = ref(null); // { start, end, source } of the block being edited

const el = () => props.target?.() || null;
const selection = () => {
    const t = el();
    const len = String(props.modelValue || '').length;
    return t ? [t.selectionStart ?? len, t.selectionEnd ?? len] : [len, len];
};
const apply = ({ text, start, end }) => {
    emit('update:modelValue', text);
    const t = el();
    if (t) {
        requestAnimationFrame(() => {
            t.focus();
            t.setSelectionRange(start, end);
        });
    }
};

const insertImage = (markdown) => {
    const [s, e] = selection();
    const text = props.modelValue || '';
    apply(props.inline ? insertInline(text, s, e, markdown) : insertBlock(text, s, e, markdown));
};

const uploadFiles = async (files) => {
    const images = [...files].filter((f) => f.type?.startsWith('image/')).slice(0, 6);
    if (!images.length) {
        if (files.length) error.value = 'Faqat PNG, JPG, WebP yoki GIF rasm yuklash mumkin (SVG qabul qilinmaydi).';
        return;
    }
    for (const file of images) {
        const res = await upload(file);
        if (!res) break;
        insertImage(`![${altFromFileName(file.name)}](${res.url})`);
    }
};

const pick = () => fileInput.value?.click();
const dismiss = () => { error.value = ''; };
const onFile = (e) => {
    uploadFiles(e.target.files || []);
    e.target.value = '';
};

/** Bind on the textarea: pasted screenshots / pictures are uploaded. */
const onPaste = (e) => {
    const files = [...(e.clipboardData?.files || [])].filter((f) => f.type?.startsWith('image/'));
    if (!files.length) return;
    e.preventDefault();
    uploadFiles(files);
};
const onDragOver = (e) => {
    if ([...(e.dataTransfer?.items || [])].some((i) => i.kind === 'file')) e.preventDefault();
};
const onDrop = (e) => {
    const files = e.dataTransfer?.files;
    if (!files?.length) return;
    e.preventDefault();
    uploadFiles(files);
};

const openGraph = () => {
    const [s] = selection();
    editing.value = graphBlockAt(props.modelValue || '', s);
    graphOpen.value = true;
};
const saveGraph = (json) => {
    const text = props.modelValue || '';
    const block = graphBlock(json);
    if (editing.value) {
        const { start, end } = editing.value;
        const caret = start + block.length;
        apply({ text: text.slice(0, start) + block + text.slice(end), start: caret, end: caret });
    } else {
        const [s, e] = selection();
        apply(insertBlock(text, s, e, block));
    }
    graphOpen.value = false;
    editing.value = null;
};

defineExpose({ onPaste, onDrop, onDragOver, pick, openGraph });

const btn = 'focus-ring inline-flex h-8 items-center justify-center gap-1.5 rounded-sm px-2 text-13 text-fg-muted hover:bg-surface hover:text-fg disabled:opacity-50';
</script>

<template>
    <span class="contents">
        <button type="button" :class="btn" :disabled="uploading" title="Rasm qo'shish (yoki Ctrl+V, sudrab tashlash)" aria-label="Rasm qo'shish" @click="pick">
            <PhotoIcon class="h-4 w-4" aria-hidden="true" />
            <span v-if="!compact">Rasm</span>
        </button>
        <button type="button" :class="btn" title="Grafik chizish (kursor grafik ichida bo'lsa — tahrirlash)" aria-label="Grafik" @click="openGraph">
            <ChartBarIcon class="h-4 w-4" aria-hidden="true" />
            <span v-if="!compact">Grafik</span>
        </button>
        <input ref="fileInput" type="file" class="sr-only" accept="image/png,image/jpeg,image/webp,image/gif" multiple tabindex="-1" aria-hidden="true" @change="onFile" />
        <span v-if="uploading" class="inline-flex items-center gap-2 px-1 text-13 text-fg-muted" role="status">
            <span class="h-1.5 w-16 overflow-hidden rounded-full bg-surface-3"><span class="block h-full bg-brand-600 transition-[width]" :style="{ width: `${progress}%` }" /></span>
            Yuklanmoqda… {{ progress }}%
        </span>
        <span v-else-if="error" class="inline-flex max-w-full items-center gap-1 px-1 text-13 text-danger" role="alert">
            {{ error }}
            <button type="button" class="focus-ring rounded-sm p-0.5 hover:bg-surface" aria-label="Xabarni yopish" @click="dismiss"><XMarkIcon class="h-3.5 w-3.5" aria-hidden="true" /></button>
        </span>
        <GraphBuilderDialog :show="graphOpen" :source="editing?.source ?? null" @save="saveGraph" @close="graphOpen = false; editing = null" />
    </span>
</template>
