<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    modelValue: Array,
});

const emit = defineEmits(['update:modelValue']);

const options = ref(props.modelValue || [
    { option_text: '', is_correct: true }
]);

watch(options, () => {
    emit('update:modelValue', options.value);
}, { deep: true });
</script>

<template>
    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">To'g'ri javob</label>
            <input v-model="options[0].option_text" type="text"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                placeholder="To'g'ri javobni kiriting" required>
            <p class="text-xs text-gray-500 mt-1">
                O'quvchi aynan shu so'zni kiritishi kerak (katta-kichik harflar farqlanmaydi).
            </p>
        </div>
    </div>
</template>
