<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    modelValue: Array,
});

const emit = defineEmits(['update:modelValue']);

const options = ref(props.modelValue || [
    { option_text: '', match_text: '', is_correct: true },
    { option_text: '', match_text: '', is_correct: true },
]);

const addPair = () => {
    options.value.push({ option_text: '', match_text: '', is_correct: true });
    emit('update:modelValue', options.value);
};

const removePair = (index) => {
    options.value.splice(index, 1);
    emit('update:modelValue', options.value);
};

watch(options, () => {
    emit('update:modelValue', options.value);
}, { deep: true });
</script>

<template>
    <div class="space-y-4">
        <div class="grid grid-cols-12 gap-4 mb-2">
            <div class="col-span-5 text-sm font-medium text-gray-700">Savol tomoni</div>
            <div class="col-span-5 text-sm font-medium text-gray-700">Javob tomoni</div>
        </div>

        <div v-for="(option, index) in options" :key="index" class="grid grid-cols-12 gap-4 items-center">
            <div class="col-span-5">
                <input v-model="option.option_text" type="text"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    placeholder="Savol" required>
            </div>
            <div class="col-span-1 flex justify-center text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
            </div>
            <div class="col-span-5">
                <input v-model="option.match_text" type="text"
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    placeholder="Javob" required>
            </div>
            <div class="col-span-1">
                <button @click="removePair(index)" type="button" class="text-red-500 hover:text-red-700"
                    v-if="options.length > 2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
            </div>
        </div>

        <button @click="addPair" type="button"
            class="text-sm text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Juftlik qo'shish
        </button>
    </div>
</template>
