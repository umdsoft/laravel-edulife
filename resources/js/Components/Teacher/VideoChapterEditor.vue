<script setup>
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/UI/Modal.vue';
import Button from '@/Components/UI/Button.vue';
import Input from '@/Components/UI/Input.vue';
import Textarea from '@/Components/UI/Textarea.vue';
import FormField from '@/Components/UI/FormField.vue';
import { controlBase, controlState, controlSize } from '@/Components/UI/fieldStyles';
import { formatTimestamp } from './format';

const props = defineProps({
    lesson: Object,
    chapter: Object, // If editing
    show: Boolean,
});

const emit = defineEmits(['close']);

const form = useForm({
    title: '',
    timestamp: 0,
    description: '',
});

// Friendlier time entry: minutes + seconds → form.timestamp (seconds)
const minutes = ref(0);
const seconds = ref(0);
const syncParts = (total) => {
    const t = Math.max(0, Number(total) || 0);
    minutes.value = Math.floor(t / 60);
    seconds.value = t % 60;
};
watch([minutes, seconds], ([m, s]) => {
    form.timestamp = Math.max(0, (Number(m) || 0) * 60 + (Number(s) || 0));
});

watch(() => props.chapter, (newVal) => {
    if (newVal) {
        form.title = newVal.title;
        form.timestamp = newVal.timestamp;
        form.description = newVal.description;
    } else {
        form.reset();
    }
    syncParts(form.timestamp);
}, { immediate: true });

const submit = () => {
    if (props.chapter) {
        form.put(route('teacher.chapters.update', props.chapter.id), {
            preserveScroll: true,
            onSuccess: () => emit('close'),
        });
    } else {
        form.post(route('teacher.lessons.chapters.store', props.lesson.id), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                emit('close');
            },
        });
    }
};
</script>

<template>
    <Modal :show="show" :title="chapter ? 'Bo\'limni tahrirlash' : 'Yangi video bo\'limi'" size="md" @close="$emit('close')">
        <form id="chapter-form" class="space-y-5 pb-2" @submit.prevent="submit">
            <Input v-model="form.title" label="Nomi" required autofocus maxlength="255" placeholder="Masalan: Nyutonning 2-qonuni"
                :error="form.errors.title" />

            <FormField label="Boshlanish vaqti" required :error="form.errors.timestamp"
                :hint="`Video ${formatTimestamp(form.timestamp)} da shu bo'limga o'tadi`" v-slot="{ id, describedBy, invalid }">
                <div class="flex items-center gap-2">
                    <input :id="id" v-model.number="minutes" type="number" min="0" inputmode="numeric" aria-label="Daqiqa"
                        :aria-describedby="describedBy" :aria-invalid="invalid || undefined"
                        :class="[controlBase, controlState(invalid), controlSize.md, 'w-20 text-right tabular-nums']" />
                    <span class="text-13 text-fg-subtle">daq</span>
                    <input v-model.number="seconds" type="number" min="0" max="59" inputmode="numeric" aria-label="Soniya"
                        :class="[controlBase, controlState(invalid), controlSize.md, 'w-20 text-right tabular-nums']" />
                    <span class="text-13 text-fg-subtle">son</span>
                </div>
            </FormField>

            <Textarea v-model="form.description" label="Tavsif" optional :rows="3" maxlength="500" show-count
                :error="form.errors.description" />
        </form>

        <template #footer>
            <Button variant="secondary" @click="$emit('close')">Bekor qilish</Button>
            <Button type="submit" form="chapter-form" :loading="form.processing">{{ chapter ? 'Saqlash' : 'Qo\'shish' }}</Button>
        </template>
    </Modal>
</template>
