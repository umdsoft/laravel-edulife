<script setup>
defineProps({
    question: {
        type: Object,
        required: true
    },
    modelValue: {
        type: [String, Number],
        default: null
    }
});

defineEmits(['update:modelValue']);
</script>

<template>
    <div class="space-y-3">
        <div v-for="option in question.options" :key="option.id"
            class="relative flex items-start p-4 cursor-pointer rounded-lg border transition-all duration-200 hover:bg-gray-50 dark:hover:bg-gray-700"
            :class="modelValue === option.id
                ? 'border-purple-500 bg-purple-50 dark:bg-purple-900/20 ring-1 ring-purple-500'
                : 'border-gray-200 dark:border-gray-700'" @click="$emit('update:modelValue', option.id)">
            <div class="flex items-center h-5">
                <input type="radio" :name="'question-' + question.id" :value="option.id"
                    :checked="modelValue === option.id"
                    class="focus:ring-purple-500 h-4 w-4 text-purple-600 border-gray-300" />
            </div>
            <div class="ml-3 text-sm">
                <label class="font-medium text-gray-700 dark:text-gray-200 cursor-pointer">
                    {{ option.content }}
                </label>
            </div>
        </div>
    </div>
</template>
