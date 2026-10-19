<script setup>
import { ref, watch } from 'vue';
import Input from '@/Components/UI/Input.vue';

const props = defineProps({
    modelValue: Array,
});

const emit = defineEmits(['update:modelValue']);

// An empty array (new question / type switch) gets the default row
const options = ref(props.modelValue?.length ? props.modelValue : [
    { option_text: '', is_correct: true }
]);

watch(options, () => {
    emit('update:modelValue', options.value);
}, { deep: true, immediate: true });
</script>

<template>
    <div class="sm:max-w-sm">
        <Input v-model="options[0].option_text" label="To'g'ri javob" placeholder="To'g'ri javobni kiriting" required
            hint="O'quvchi aynan shu javobni kiritishi kerak (katta-kichik harf farqlanmaydi)." />
    </div>
</template>
