<script setup>
const props = defineProps({
    question: {
        type: Object,
        required: true
    },
    modelValue: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['update:modelValue']);

const toggleOption = (optionId) => {
    const newValue = [...props.modelValue];
    const index = newValue.indexOf(optionId);

    if (index === -1) {
        newValue.push(optionId);
    } else {
        newValue.splice(index, 1);
    }

    emit('update:modelValue', newValue);
};
</script>

<template>
    <div class="space-y-3">
        <div v-for="option in question.options" :key="option.id"
            class="relative flex items-start p-4 cursor-pointer rounded-lg border transition-all duration-200 hover:bg-gray-50 dark:hover:bg-gray-700"
            :class="modelValue.includes(option.id)
                ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20 ring-1 ring-purple-500'
                : 'border-gray-200 dark:border-gray-700'" @click="toggleOption(option.id)">
            <div class="flex items-center h-5">
                <input type="checkbox" :value="option.id" :checked="modelValue.includes(option.id)"
                    class="focus:ring-purple-500 h-4 w-4 text-purple-600 border-gray-300 rounded" />
            </div>
            <div class="ml-3 text-sm">
                <label class="font-medium text-gray-700 dark:text-gray-200 cursor-pointer">
                    {{ option.content }}
                </label>
            </div>
        </div>
    </div>
</template>
