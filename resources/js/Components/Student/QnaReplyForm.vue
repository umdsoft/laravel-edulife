<script setup>
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    questionId: String,
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
    <form @submit.prevent="submit" class="mt-2">
        <textarea v-model="form.content" rows="3"
            class="w-full border-gray-200 rounded-lg focus:ring-purple-500 focus:border-purple-500 text-sm mb-2"
            placeholder="Javobingizni yozing..."></textarea>
        <div class="flex justify-end gap-2">
            <button type="button" @click="$emit('cancel')"
                class="px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-100 rounded-lg">
                Bekor qilish
            </button>
            <button type="submit" :disabled="form.processing"
                class="px-3 py-1.5 text-sm bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50">
                Yuborish
            </button>
        </div>
    </form>
</template>
