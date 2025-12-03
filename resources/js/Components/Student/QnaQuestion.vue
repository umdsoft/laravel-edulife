<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import QnaReplyForm from './QnaReplyForm.vue';

const props = defineProps({
    question: Object,
});

const showReplyForm = ref(false);

const upvote = () => {
    router.post(route('student.learn.qna.upvote', props.question.id), {}, {
        preserveScroll: true,
    });
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString();
};
</script>

<template>
    <div class="bg-white p-6 rounded-xl border border-gray-100 hover:shadow-sm transition-shadow">
        <div class="flex gap-4">
            <!-- Vote Column -->
            <div class="flex flex-col items-center gap-1">
                <button @click="upvote" class="p-1 text-gray-400 hover:text-purple-600 hover:bg-purple-50 rounded">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                    </svg>
                </button>
                <span class="font-bold text-gray-700">{{ question.upvotes }}</span>
            </div>

            <!-- Content Column -->
            <div class="flex-1">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="font-bold text-gray-900">{{ question.content }}</h3>
                    <span v-if="question.is_resolved"
                        class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full font-medium">
                        Javoblangan
                    </span>
                </div>

                <div class="flex items-center gap-2 text-sm text-gray-500 mb-4">
                    <img :src="question.user.avatar_url || `https://ui-avatars.com/api/?name=${question.user.first_name}+${question.user.last_name}`"
                        class="w-5 h-5 rounded-full">
                    <span>{{ question.user.full_name }}</span>
                    <span>•</span>
                    <span>{{ formatDate(question.created_at) }}</span>
                    <span v-if="question.lesson">• {{ question.lesson.title }}</span>
                </div>

                <!-- Replies -->
                <div v-if="question.replies && question.replies.length > 0"
                    class="space-y-4 mt-4 pl-4 border-l-2 border-gray-100">
                    <div v-for="reply in question.replies" :key="reply.id" class="bg-gray-50 p-4 rounded-lg">
                        <div class="flex items-center gap-2 mb-2">
                            <img :src="reply.user.avatar_url || `https://ui-avatars.com/api/?name=${reply.user.first_name}+${reply.user.last_name}`"
                                class="w-6 h-6 rounded-full">
                            <span class="font-medium text-sm text-gray-900">{{ reply.user.full_name }}</span>
                            <span v-if="reply.user.role === 'teacher'"
                                class="px-1.5 py-0.5 bg-purple-100 text-purple-700 text-xs rounded">O'qituvchi</span>
                            <span class="text-xs text-gray-500">{{ formatDate(reply.created_at) }}</span>
                        </div>
                        <p class="text-gray-700 text-sm">{{ reply.content }}</p>
                    </div>
                </div>

                <!-- Reply Action -->
                <div class="mt-4">
                    <button v-if="!showReplyForm" @click="showReplyForm = true"
                        class="text-sm text-purple-600 hover:text-purple-700 font-medium">
                        Javob yozish
                    </button>
                    <QnaReplyForm v-else :question-id="question.id" @cancel="showReplyForm = false"
                        @success="showReplyForm = false" />
                </div>
            </div>
        </div>
    </div>
</template>
