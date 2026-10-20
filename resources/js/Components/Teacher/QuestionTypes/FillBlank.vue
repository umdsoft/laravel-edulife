<script setup>
import { ref, watch } from 'vue';
import { PlusIcon, TrashIcon } from '@heroicons/vue/24/outline';
import Button from '@/Components/UI/Button.vue';
import IconButton from '@/Components/UI/IconButton.vue';
import { controlBase, controlState, controlSize } from '@/Components/UI/fieldStyles';

const props = defineProps({
    modelValue: Array,
});

const emit = defineEmits(['update:modelValue']);

// Every row is an accepted answer (0.5, 1/2, …); an empty array (new question / type switch) gets one row
const options = ref(props.modelValue?.length ? props.modelValue : [
    { option_text: '', is_correct: true },
]);

const addAnswer = () => {
    options.value.push({ option_text: '', is_correct: true });
};
const removeAnswer = (index) => {
    options.value.splice(index, 1);
};

watch(options, () => {
    emit('update:modelValue', options.value);
}, { deep: true, immediate: true });
</script>

<template>
    <div class="space-y-3">
        <p class="text-13 text-fg-subtle">
            O'quvchi shu javoblardan birini yozsa — to'g'ri. Katta-kichik harf va bo'sh joylar farqlanmaydi,
            o'nli kasrda vergul ham, nuqta ham qabul qilinadi (0,5 = 0.5).
        </p>
        <ol class="space-y-2 sm:max-w-md">
            <li v-for="(option, index) in options" :key="index" class="flex items-center gap-2">
                <input v-model="option.option_text" type="text" required
                    :aria-label="index === 0 ? 'To\'g\'ri javob' : `${index + 1}-qabul qilinadigan javob`"
                    :placeholder="index === 0 ? 'To\'g\'ri javob, masalan: 0.5' : 'Yana bir yozilishi, masalan: 1/2'"
                    :class="[controlBase, controlState(false), controlSize.md, 'min-w-0 flex-1']">
                <IconButton v-if="options.length > 1" :icon="TrashIcon" :label="`${index + 1}-javobni o'chirish`" variant="danger" size="sm"
                    @click="removeAnswer(index)" />
            </li>
        </ol>
        <Button variant="ghost" size="sm" :icon-left="PlusIcon" @click="addAnswer">Boshqa yozilishini qo'shish</Button>
    </div>
</template>
