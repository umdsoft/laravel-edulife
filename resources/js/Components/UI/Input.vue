<script setup>
/**
 * Input — text-like control with built-in label / hint / error.
 * Backward compatible: v-model, type, label, placeholder, error, disabled.
 * Native attributes (maxlength, min, step, autocomplete, name, @blur …) go to the <input>;
 * `class` stays on the wrapper.
 *
 * New: id, hint, required, optional, size (sm|md|lg), leadingIcon / trailingIcon (components),
 * #leading / #trailing slots, `revealable` (eye toggle for passwords).
 */
import { computed, ref, useAttrs, useId } from 'vue';
import { EyeIcon, EyeSlashIcon } from '@heroicons/vue/20/solid';
import FormField from './FormField.vue';
import { controlBase, controlState, controlSize } from './fieldStyles';

defineOptions({ inheritAttrs: false });

const props = defineProps({
    modelValue: [String, Number],
    type: { type: String, default: 'text' },
    label: String,
    placeholder: String,
    error: [String, Array],
    disabled: Boolean,
    id: { type: String, default: null },
    hint: { type: String, default: null },
    required: Boolean,
    optional: Boolean,
    size: { type: String, default: 'md', validator: (v) => ['sm', 'md', 'lg'].includes(v) },
    leadingIcon: { type: [Object, Function], default: null },
    trailingIcon: { type: [Object, Function], default: null },
    revealable: Boolean,
});

const emit = defineEmits(['update:modelValue']);
const attrs = useAttrs();
const inputRef = ref(null);
const revealed = ref(false);
const autoId = useId();
const inputId = computed(() => props.id || attrs.id || `in-${autoId}`);

const wrapperAttrs = computed(() => ({ class: attrs.class, style: attrs.style }));
const inputAttrs = computed(() => {
    const { class: _c, style: _s, id: _i, ...rest } = attrs;
    return rest;
});

const actualType = computed(() => (props.revealable && revealed.value ? 'text' : props.type));
const hasLeading = computed(() => !!props.leadingIcon);
const hasTrailing = computed(() => !!props.trailingIcon || props.revealable);

const onInput = (e) => emit('update:modelValue', e.target.value);

defineExpose({ focus: () => inputRef.value?.focus(), el: inputRef });
</script>

<template>
    <FormField v-bind="wrapperAttrs" :id="inputId" :label="label" :hint="hint" :error="error" :required="required"
        :optional="optional" v-slot="{ id, describedBy, invalid }">
        <div class="relative">
            <div v-if="hasLeading || $slots.leading"
                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-fg-subtle">
                <slot name="leading">
                    <component :is="leadingIcon" class="h-[18px] w-[18px]" aria-hidden="true" />
                </slot>
            </div>

            <input ref="inputRef" v-bind="inputAttrs" :id="id" :type="actualType" :value="modelValue"
                :placeholder="placeholder" :disabled="disabled" :required="required || undefined"
                :aria-invalid="invalid || undefined" :aria-describedby="describedBy" :class="[
                    controlBase, controlState(invalid), controlSize[size],
                    (hasLeading || $slots.leading) ? 'pl-10' : '',
                    (hasTrailing || $slots.trailing) ? 'pr-10' : '',
                ]" @input="onInput" />

            <div v-if="hasTrailing || $slots.trailing" class="absolute inset-y-0 right-0 flex items-center pr-1.5">
                <slot name="trailing">
                    <button v-if="revealable" type="button"
                        class="focus-ring inline-flex h-8 w-8 items-center justify-center rounded-sm text-fg-subtle hover:text-fg"
                        :aria-label="revealed ? 'Parolni yashirish' : 'Parolni ko\'rsatish'" :aria-pressed="revealed"
                        @click="revealed = !revealed">
                        <EyeSlashIcon v-if="revealed" class="h-[18px] w-[18px]" aria-hidden="true" />
                        <EyeIcon v-else class="h-[18px] w-[18px]" aria-hidden="true" />
                    </button>
                    <component :is="trailingIcon" v-else class="pointer-events-none mr-1.5 h-[18px] w-[18px] text-fg-subtle"
                        aria-hidden="true" />
                </slot>
            </div>
        </div>
    </FormField>
</template>
