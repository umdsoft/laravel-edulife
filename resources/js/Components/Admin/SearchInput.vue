<template>
    <div class="relative">
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>

            <input :value="modelValue" @input="handleInput" type="text" :placeholder="placeholder"
                class="w-full bg-gray-50 border-0 rounded-xl pl-10 pr-10 py-2.5 text-sm placeholder-gray-400 focus:ring-2 focus:ring-primary/20 focus:bg-white transition" />

            <button v-if="modelValue" @click="clear"
                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';

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
});

const emit = defineEmits(['update:modelValue']);

let timeout = null;

const handleInput = (event) => {
    const value = event.target.value;

    if (timeout) clearTimeout(timeout);

    timeout = setTimeout(() => {
        emit('update:modelValue', value);
    }, props.debounce);
};

const clear = () => {
    emit('update:modelValue', '');
};
</script>
