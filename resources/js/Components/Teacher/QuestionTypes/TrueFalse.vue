<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    modelValue: Array,
});

const emit = defineEmits(['update:modelValue']);

const options = ref(props.modelValue || [
    { option_text: 'Rost', is_correct: true },
    { option_text: 'Yolg\'on', is_correct: false },
]);

const setCorrect = (index) => {
    options.value.forEach((opt, i) => {
        opt.is_correct = i === index;
    });
    emit('update:modelValue', options.value);
};

watch(options, () => {
    emit('update:modelValue', options.value);
}, { deep: true });
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center gap-3 p-3 border border-gray-200 rounded-md bg-gray-50">
            <input type="radio" name="true_false" :checked="options[0].is_correct" @change="setCorrect(0)"
                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300">
            <span class="text-gray-900 font-medium">Rost</span>
        </div>

        <div class="flex items-center gap-3 p-3 border border-gray-200 rounded-md bg-gray-50">
            <input type="radio" name="true_false" :checked="options[1].is_correct" @change="setCorrect(1)"
                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300">
            <span class="text-gray-900 font-medium">Yolg'on</span>
        </div>
    </div>
</template>
