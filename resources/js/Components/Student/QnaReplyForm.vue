<script setup>
import { useForm } from '@inertiajs/vue3';
import Textarea from '@/Components/UI/Textarea.vue';
import Button from '@/Components/UI/Button.vue';

const props = defineProps({
    questionId: [String, Number],
});

const emit = defineEmits(['cancel', 'success']);

const form = useForm({
    content: '',
    parent_id: props.questionId,
});

const submit = () => {
    form.post(route('student.learn.qna.store', { course: route().params.course }), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            emit('success');
        },
    });
};
</script>

<template>
    <form class="mt-1 space-y-2" @submit.prevent="submit">
        <Textarea v-model="form.content" :rows="3" autoresize placeholder="Javobingizni yozing…" aria-label="Javob matni"
            :error="form.errors.content" autofocus />
        <div class="flex justify-end gap-2">
            <Button variant="ghost" size="sm" @click="emit('cancel')">Bekor qilish</Button>
            <Button type="submit" size="sm" :loading="form.processing" :disabled="!form.content.trim()">Yuborish</Button>
        </div>
    </form>
</template>
