<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { ChevronUpIcon, ChatBubbleLeftIcon, CheckCircleIcon } from '@heroicons/vue/20/solid';
import Avatar from '@/Components/UI/Avatar.vue';
import Badge from '@/Components/UI/Badge.vue';
import Button from '@/Components/UI/Button.vue';
import QnaReplyForm from './QnaReplyForm.vue';
import { formatDate } from './format.js';

const props = defineProps({
    question: Object,
});

const showReplyForm = ref(false);

const upvote = () => {
    router.post(route('student.learn.qna.upvote', props.question.id), {}, {
        preserveScroll: true,
    });
};

const nameOf = (u) => u?.full_name || [u?.first_name, u?.last_name].filter(Boolean).join(' ');
</script>

<template>
    <article class="rounded-lg border border-border bg-surface p-4 shadow-xs sm:p-5">
        <div class="flex gap-3 sm:gap-4">
            <!-- Vote -->
            <div class="flex shrink-0 flex-col items-center">
                <button type="button" @click="upvote"
                    class="focus-ring flex h-10 w-10 flex-col items-center justify-center rounded-md border border-border text-fg-muted transition-colors hover:border-brand-200 hover:bg-brand-50 hover:text-brand"
                    :aria-label="`Foydali deb belgilash, ${question.upvotes || 0} ovoz`">
                    <ChevronUpIcon class="h-5 w-5" aria-hidden="true" />
                </button>
                <span class="mt-1 text-13 font-semibold text-fg tabular-nums" aria-hidden="true">{{ question.upvotes || 0 }}</span>
            </div>

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <h3 class="min-w-0 text-sm font-semibold leading-relaxed text-fg sm:text-[15px]">{{ question.content }}</h3>
                    <Badge v-if="question.is_resolved" variant="success" size="sm" :icon="CheckCircleIcon">Javoblangan</Badge>
                </div>

                <div class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-13 text-fg-subtle">
                    <Avatar :src="question.user?.avatar_url" :name="nameOf(question.user)" size="xs" />
                    <span class="font-medium text-fg-muted">{{ nameOf(question.user) }}</span>
                    <span aria-hidden="true">·</span>
                    <time :datetime="question.created_at" class="tabular-nums">{{ formatDate(question.created_at) }}</time>
                    <template v-if="question.lesson">
                        <span aria-hidden="true">·</span>
                        <span class="truncate">{{ question.lesson.title }}</span>
                    </template>
                </div>

                <!-- Replies -->
                <ul v-if="question.replies && question.replies.length > 0" class="mt-4 space-y-3 border-l-2 border-border pl-4">
                    <li v-for="reply in question.replies" :key="reply.id"
                        :class="['rounded-md p-3', reply.user?.role === 'teacher' ? 'bg-brand-50/60' : 'bg-surface-2/70']">
                        <div class="mb-1.5 flex flex-wrap items-center gap-2">
                            <Avatar :src="reply.user?.avatar_url" :name="nameOf(reply.user)" size="xs" />
                            <span class="text-13 font-medium text-fg">{{ nameOf(reply.user) }}</span>
                            <Badge v-if="reply.user?.role === 'teacher'" variant="brand" size="sm">O'qituvchi</Badge>
                            <time :datetime="reply.created_at" class="text-xs text-fg-subtle tabular-nums">{{ formatDate(reply.created_at) }}</time>
                        </div>
                        <p class="whitespace-pre-wrap text-sm leading-relaxed text-fg-muted">{{ reply.content }}</p>
                    </li>
                </ul>

                <div class="mt-3">
                    <Button v-if="!showReplyForm" variant="ghost" size="sm" :icon-left="ChatBubbleLeftIcon" class="-ml-3"
                        @click="showReplyForm = true">
                        Javob yozish
                    </Button>
                    <QnaReplyForm v-else :question-id="question.id" @cancel="showReplyForm = false"
                        @success="showReplyForm = false" />
                </div>
            </div>
        </div>
    </article>
</template>
