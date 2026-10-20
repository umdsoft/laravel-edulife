<script setup>
/**
 * MediaTextarea — the UI Textarea plus small "Rasm" / "Grafik" buttons (paste and drag-drop
 * work too) and a preview once the text has a picture or a graph. For short fields: test
 * questions, answer options, explanations.
 *   <MediaTextarea v-model="option.text" aria-label="A variant" :error="err" />
 *   <MediaTextarea v-model="form.question_text" label="Savol matni" required :rows="3" />
 * mode: 'plain' previews like MathText (test questions), 'markdown' like RichContent (exam bank).
 */
import { computed, ref } from 'vue';
import Textarea from '@/Components/UI/Textarea.vue';
import MathText from '@/Components/MathText.vue';
import RichContent from '@/Components/Student/RichContent.vue';
import MediaTools from './MediaTools.vue';
import { hasMedia } from '@/Utils/richText';

defineOptions({ inheritAttrs: false });

const props = defineProps({
    modelValue: { type: [String, Number], default: '' },
    mode: { type: String, default: 'plain', validator: (v) => ['plain', 'markdown'].includes(v) },
    inline: { type: Boolean, default: true },
    preview: { type: Boolean, default: true }, // false when the page has its own preview
});
const emit = defineEmits(['update:modelValue']);

const area = ref(null);
const media = ref(null);
const text = computed(() => String(props.modelValue ?? ''));
const showPreview = computed(() => props.preview && hasMedia(text.value));
</script>

<template>
    <div :class="['min-w-0', $attrs.class]">
        <Textarea ref="area" v-bind="{ ...$attrs, class: undefined }" :model-value="modelValue" autoresize
            @update:model-value="emit('update:modelValue', $event)"
            @paste="media?.onPaste($event)" @dragover="media?.onDragOver($event)" @drop="media?.onDrop($event)" />
        <div class="mt-1 flex flex-wrap items-center gap-1">
            <MediaTools ref="media" :compact="inline" :inline="inline" :model-value="text" :target="() => area?.el"
                @update:model-value="emit('update:modelValue', $event)" />
        </div>
        <div v-if="showPreview" class="mt-1 rounded-sm bg-surface-2/60 px-2.5 py-1.5">
            <RichContent v-if="mode === 'markdown'" :html="text" size="sm" />
            <MathText v-else :text="text" tag="div" class="text-sm text-fg" />
        </div>
    </div>
</template>
