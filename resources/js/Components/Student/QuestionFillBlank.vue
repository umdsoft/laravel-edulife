<script setup>
/**
 * Fill-in-the-blank answer. API unchanged: question, v-model (string).
 */
import { InformationCircleIcon } from '@heroicons/vue/20/solid';
import Input from '@/Components/UI/Input.vue';

defineProps({
    question: {
        type: Object,
        required: true,
    },
    modelValue: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['update:modelValue']);
</script>

<template>
    <div class="space-y-3">
        <Input :model-value="modelValue ?? ''" size="lg" label="Javobingiz" placeholder="Javobni shu yerga yozing…"
            autocomplete="off" spellcheck="false" @update:model-value="emit('update:modelValue', $event)" />
        <p class="flex items-start gap-2 text-13 text-fg-subtle">
            <InformationCircleIcon class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
            Javobni aniq va xatosiz yozing. Katta-kichik harflar farq qilmaydi.
        </p>
    </div>
</template>
