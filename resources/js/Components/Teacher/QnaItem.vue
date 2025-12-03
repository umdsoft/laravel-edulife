<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { formatDistanceToNow } from 'date-fns';
import { uz } from 'date-fns/locale';

const props = defineProps({
    question: Object,
    course: Object,
});

const showReplyForm = ref(false);
const replyForm = useForm({
    content: '',
});

const submitReply = () => {
    replyForm.post(route('teacher.courses.qna.reply', [props.course.id, props.question.id]), {
        onSuccess: () => {
            replyForm.reset();
            showReplyForm.value = false;
        },
    });
};

const togglePin = () => {
    router.post(route('teacher.courses.qna.toggle-pin', [props.course.id, props.question.id]));
};

const toggleHighlight = () => {
    router.post(route('teacher.courses.qna.toggle-highlight', [props.course.id, props.question.id]));
};

const deleteQuestion = () => {
    if (confirm('Rostdan ham bu savolni o\'chirmoqchimisiz?')) {
        router.delete(route('teacher.courses.qna.destroy', [props.course.id, props.question.id]));
    }
};
</script>

<template>
    <div class="bg-white rounded-lg border border-gray-200 p-4 space-y-4">
        <!-- Question Header -->
        <div class="flex justify-between items-start">
            <div class="flex items-start space-x-3">
                <img :src="question.user.avatar_url || `https://ui-avatars.com/api/?name=${question.user.name}`"
                    class="h-10 w-10 rounded-full" alt="" />
                <div>
                    <h3 class="text-sm font-medium text-gray-900">{{ question.user.name }}</h3>
                    <p class="text-xs text-gray-500">
                        {{ formatDistanceToNow(new Date(question.created_at), { addSuffix: true, locale: uz }) }}
                        <span v-if="question.lesson" class="ml-2 text-indigo-600">
                            • {{ question.lesson.title }}
                        </span>
                    </p>
                </div>
            </div>

            <!-- Actions Dropdown or Buttons -->
            <div class="flex items-center space-x-2">
                <button @click="togglePin" class="p-1 rounded-full hover:bg-gray-100"
                    :class="{ 'text-indigo-600': question.is_pinned, 'text-gray-400': !question.is_pinned }"
                    title="Qadash">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                    </svg>
                </button>

                <button @click="toggleHighlight" class="p-1 rounded-full hover:bg-gray-100"
                    :class="{ 'text-yellow-500': question.is_highlighted, 'text-gray-400': !question.is_highlighted }"
                    title="Muhim deb belgilash">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                    </svg>
                </button>

                <button @click="deleteQuestion"
                    class="p-1 rounded-full hover:bg-red-50 text-gray-400 hover:text-red-600" title="O'chirish">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Question Content -->
        <div class="text-gray-800 text-sm whitespace-pre-wrap">{{ question.content }}</div>

        <!-- Replies -->
        <div v-if="question.replies && question.replies.length > 0" class="pl-8 space-y-3 border-l-2 border-gray-100">
            <div v-for="reply in question.replies" :key="reply.id" class="bg-gray-50 rounded p-3">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center space-x-2">
                        <img :src="reply.user.avatar_url || `https://ui-avatars.com/api/?name=${reply.user.name}`"
                            class="h-6 w-6 rounded-full" alt="" />
                        <span class="text-xs font-medium text-gray-900">{{ reply.user.name }}</span>
                        <span v-if="reply.user.id === course.teacher_id"
                            class="px-1.5 py-0.5 rounded text-xs bg-indigo-100 text-indigo-800">
                            O'qituvchi
                        </span>
                    </div>
                    <span class="text-xs text-gray-500">
                        {{ formatDistanceToNow(new Date(reply.created_at), { addSuffix: true, locale: uz }) }}
                    </span>
                </div>
                <div class="text-sm text-gray-700 whitespace-pre-wrap">{{ reply.content }}</div>
            </div>
        </div>

        <!-- Reply Form -->
        <div v-if="showReplyForm" class="mt-4">
            <form @submit.prevent="submitReply">
                <textarea v-model="replyForm.content" rows="3"
                    class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                    placeholder="Javobingizni yozing..." required></textarea>
                <div class="flex justify-end space-x-2 mt-2">
                    <button type="button" @click="showReplyForm = false"
                        class="px-3 py-1.5 border border-gray-300 rounded text-xs font-medium text-gray-700 hover:bg-gray-50">
                        Bekor qilish
                    </button>
                    <button type="submit" :disabled="replyForm.processing"
                        class="px-3 py-1.5 border border-transparent rounded text-xs font-medium text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50">
                        Yuborish
                    </button>
                </div>
            </form>
        </div>

        <div v-else class="flex justify-end">
            <button @click="showReplyForm = true" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                Javob yozish
            </button>
        </div>
    </div>
</template>
