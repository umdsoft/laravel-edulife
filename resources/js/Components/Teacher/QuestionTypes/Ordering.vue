<script setup>
import { ref, watch } from 'vue';
import draggable from 'vuedraggable';

const props = defineProps({
    modelValue: Array,
});

const emit = defineEmits(['update:modelValue']);

const options = ref(props.modelValue || [
    { option_text: '', correct_position: 1, is_correct: true },
    { option_text: '', correct_position: 2, is_correct: true },
    { option_text: '', correct_position: 3, is_correct: true },
]);

const addOption = () => {
    options.value.push({
        option_text: '',
        correct_position: options.value.length + 1,
        is_correct: true
    });
    emit('update:modelValue', options.value);
};

const removeOption = (index) => {
    options.value.splice(index, 1);
    updatePositions();
};

const updatePositions = () => {
    options.value.forEach((opt, index) => {
        opt.correct_position = index + 1;
    });
    emit('update:modelValue', options.value);
};

watch(options, () => {
    emit('update:modelValue', options.value);
}, { deep: true });
</script>

<template>
    <div class="space-y-4">
        <p class="text-sm text-gray-600 mb-2">Elementlarni to'g'ri tartibda joylashtiring:</p>

        <draggable v-model="options" item-key="correct_position" handle=".drag-handle" @end="updatePositions"
            class="space-y-2">
            <template #item="{ element: option, index }">
                <div class="flex items-center gap-3 bg-gray-50 p-2 rounded-md border border-gray-200">
                    <div class="drag-handle cursor-move text-gray-400 hover:text-gray-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                        </svg>
                    </div>
                    <span class="text-gray-500 font-medium w-6">{{ index + 1 }}.</span>
                    <input v-model="option.option_text" type="text"
                        class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        placeholder="Element matni" required>
                    <button @click="removeOption(index)" type="button" class="text-red-500 hover:text-red-700"
                        v-if="options.length > 2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>
            </template>
        </draggable>

        <button @click="addOption" type="button"
            class="text-sm text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Element qo'shish
        </button>
    </div>
</template>
