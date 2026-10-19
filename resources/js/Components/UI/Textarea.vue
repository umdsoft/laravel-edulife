<script setup>
/**
 * Textarea — multi-line control with label / hint / error and optional counter.
 *   <Textarea v-model="form.body" label="Izoh" :rows="4" :maxlength="500" show-count :error="form.errors.body" />
 * `autoresize` grows the field with its content.
 */
import { computed, nextTick, onMounted, ref, useAttrs, useId, watch } from 'vue';
import FormField from './FormField.vue';
import { controlBase, controlState } from './fieldStyles';

defineOptions({ inheritAttrs: false });

const props = defineProps({
    modelValue: { type: [String, Number], default: '' },
    label: String,
    placeholder: String,
    error: [String, Array],
    hint: String,
    disabled: Boolean,
    required: Boolean,
    optional: Boolean,
    id: { type: String, default: null },
    rows: { type: [Number, String], default: 4 },
    maxlength: { type: [Number, String], default: null },
    showCount: Boolean,
    autoresize: Boolean,
});

const emit = defineEmits(['update:modelValue']);
const attrs = useAttrs();
const el = ref(null);
const autoId = useId();
const fieldId = computed(() => props.id || attrs.id || `ta-${autoId}`);
const wrapperAttrs = computed(() => ({ class: attrs.class, style: attrs.style }));
const inputAttrs = computed(() => {
    const { class: _c, style: _s, id: _i, ...rest } = attrs;
    return rest;
});

const length = computed(() => String(props.modelValue ?? '').length);

const resize = () => {
    if (!props.autoresize || !el.value) return;
    el.value.style.height = 'auto';
    el.value.style.height = `${el.value.scrollHeight}px`;
};

const onInput = (e) => {
    emit('update:modelValue', e.target.value);
    resize();
};

watch(() => props.modelValue, () => nextTick(resize));
onMounted(resize);

defineExpose({ focus: () => el.value?.focus(), el });
</script>

<template>
    <FormField v-bind="wrapperAttrs" :id="fieldId" :label="label" :hint="hint" :error="error" :required="required"
        :optional="optional" v-slot="{ id, describedBy, invalid }">
        <textarea ref="el" v-bind="inputAttrs" :id="id" :value="modelValue" :rows="rows" :maxlength="maxlength"
            :placeholder="placeholder" :disabled="disabled" :required="required || undefined"
            :aria-invalid="invalid || undefined" :aria-describedby="describedBy"
            :class="[controlBase, controlState(invalid), 'min-h-[5rem] px-3 py-2.5 text-sm leading-6', autoresize ? 'resize-none overflow-hidden' : 'resize-y']"
            @input="onInput" />
        <p v-if="showCount" class="mt-1 text-right text-xs text-fg-subtle tabular-nums" aria-live="polite">
            {{ length }}<template v-if="maxlength"> / {{ maxlength }}</template>
        </p>
    </FormField>
</template>
