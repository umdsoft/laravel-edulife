<script setup>
/**
 * SearchInput — debounced search field (UI kit Input + search icon + clear button).
 *   <SearchInput v-model="filters.search" placeholder="Ism yoki telefon" @update:model-value="search" />
 * Props: modelValue, placeholder, debounce (ms, default 300), label (accessible name; defaults to placeholder).
 * Emits update:modelValue after the debounce; clearing emits immediately.
 */
import { onBeforeUnmount, ref, watch } from 'vue';
import { MagnifyingGlassIcon, XMarkIcon } from '@heroicons/vue/20/solid';
import Input from '@/Components/UI/Input.vue';

const props = defineProps({
    modelValue: String,
    placeholder: {
        type: String,
        default: 'Qidirish...',
    },
    debounce: {
        type: Number,
        default: 300,
    },
    label: { type: String, default: null },
});

const emit = defineEmits(['update:modelValue']);

const local = ref(props.modelValue || '');
let timeout = null;

watch(
    () => props.modelValue,
    (v) => {
        if ((v || '') !== local.value) local.value = v || '';
    },
);

const handleInput = (value) => {
    local.value = value;
    if (timeout) clearTimeout(timeout);
    timeout = setTimeout(() => {
        timeout = null;
        emit('update:modelValue', value);
    }, props.debounce);
};

const clear = () => {
    if (timeout) clearTimeout(timeout);
    timeout = null;
    local.value = '';
    emit('update:modelValue', '');
};

onBeforeUnmount(() => {
    if (timeout) clearTimeout(timeout);
});
</script>

<template>
    <Input :model-value="local" type="text" :placeholder="placeholder" :aria-label="label || placeholder"
        :leading-icon="MagnifyingGlassIcon" autocomplete="off" @update:model-value="handleInput"
        @keydown.esc="local && clear()">
        <template v-if="local" #trailing>
            <button type="button"
                class="focus-ring inline-flex h-8 w-8 items-center justify-center rounded-sm text-fg-subtle hover:text-fg"
                aria-label="Qidiruvni tozalash" @click="clear">
                <XMarkIcon class="h-4 w-4" aria-hidden="true" />
            </button>
        </template>
    </Input>
</template>
