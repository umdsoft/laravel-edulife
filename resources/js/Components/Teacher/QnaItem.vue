<script setup>
import { computed, nextTick, ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { BookmarkIcon, StarIcon, TrashIcon, ChatBubbleLeftIcon } from '@heroicons/vue/24/outline';
import { BookmarkIcon as BookmarkSolidIcon, StarIcon as StarSolidIcon } from '@heroicons/vue/20/solid';
import Avatar from '@/Components/UI/Avatar.vue';
import Badge from '@/Components/UI/Badge.vue';
import Button from '@/Components/UI/Button.vue';
import IconButton from '@/Components/UI/IconButton.vue';
import Textarea from '@/Components/UI/Textarea.vue';
import ConfirmDialog from './ConfirmDialog.vue';
import { fullName, timeAgo } from './format';

const props = defineProps({
    question: Object,
    course: Object,
});

const showReplyForm = ref(false);
const replyBox = ref(null);
const replyForm = useForm({
    content: '',
});

const replies = computed(() => props.question.replies || []);
const answered = computed(() => replies.value.length > 0);

const openReply = () => {
    showReplyForm.value = true;
    nextTick(() => replyBox.value?.focus());
};

const submitReply = () => {
    replyForm.post(route('teacher.courses.qna.reply', [props.course.id, props.question.id]), {
        preserveScroll: true,
        onSuccess: () => {
            replyForm.reset();
            showReplyForm.value = false;
        },
    });
};

const togglePin = () => {
    router.post(route('teacher.courses.qna.toggle-pin', [props.course.id, props.question.id]), {}, { preserveScroll: true });
};

const toggleHighlight = () => {
    router.post(route('teacher.courses.qna.toggle-highlight', [props.course.id, props.question.id]), {}, { preserveScroll: true });
};

const confirming = ref(false);
const deleting = ref(false);
const deleteQuestion = () => {
    router.delete(route('teacher.courses.qna.destroy', [props.course.id, props.question.id]), {
        preserveScroll: true,
        onStart: () => (deleting.value = true),
        onFinish: () => {
            deleting.value = false;
            confirming.value = false;
        },
    });
};
</script>

<template>
    <article :class="['rounded-lg border bg-surface p-4 shadow-xs sm:p-5', question.is_highlighted ? 'border-warning-100 ring-1 ring-warning-100' : 'border-border']">
        <!-- Header -->
        <header class="flex items-start gap-3">
            <Avatar :src="question.user?.avatar_url" :name="fullName(question.user)" size="md" />
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                    <h3 class="text-sm font-medium text-fg">{{ fullName(question.user) }}</h3>
                    <Badge v-if="question.is_pinned" variant="brand" size="sm" :icon="BookmarkSolidIcon">Qadalgan</Badge>
                    <Badge v-if="question.is_highlighted" variant="warning" size="sm" :icon="StarSolidIcon">Muhim</Badge>
                    <Badge :variant="answered ? 'success' : 'danger'" size="sm" dot>{{ answered ? 'Javob berilgan' : 'Javobsiz' }}</Badge>
                </div>
                <p class="mt-0.5 text-13 text-fg-subtle">
                    <time :datetime="question.created_at">{{ timeAgo(question.created_at) }}</time>
                    <template v-if="question.lesson"> · <span class="text-fg-muted">{{ question.lesson.title }}</span></template>
                </p>
            </div>

            <div class="-mr-1 flex shrink-0 items-center">
                <IconButton :icon="question.is_pinned ? BookmarkSolidIcon : BookmarkIcon" :label="question.is_pinned ? 'Qadashni bekor qilish' : 'Qadash'"
                    size="sm" :variant="question.is_pinned ? 'soft' : 'ghost'" :aria-pressed="!!question.is_pinned" @click="togglePin" />
                <IconButton :icon="question.is_highlighted ? StarSolidIcon : StarIcon" :label="question.is_highlighted ? 'Muhim belgisini olib tashlash' : 'Muhim deb belgilash'"
                    size="sm" :aria-pressed="!!question.is_highlighted" @click="toggleHighlight" />
                <IconButton :icon="TrashIcon" label="O'chirish" size="sm" variant="danger" @click="confirming = true" />
            </div>
        </header>

        <!-- Question -->
        <div class="mt-3 whitespace-pre-wrap text-sm leading-6 text-fg">{{ question.content }}</div>

        <!-- Replies -->
        <ul v-if="replies.length" class="mt-4 space-y-3 border-l-2 border-border pl-4 sm:ml-5">
            <li v-for="reply in replies" :key="reply.id" class="rounded-md bg-surface-2/70 p-3">
                <div class="mb-1.5 flex flex-wrap items-center gap-2">
                    <Avatar :src="reply.user?.avatar_url" :name="fullName(reply.user)" size="xs" />
                    <span class="text-13 font-medium text-fg">{{ fullName(reply.user) }}</span>
                    <Badge v-if="reply.user?.id === course.teacher_id" variant="brand" size="sm">O'qituvchi</Badge>
                    <time :datetime="reply.created_at" class="ml-auto text-xs text-fg-subtle">{{ timeAgo(reply.created_at) }}</time>
                </div>
                <div class="whitespace-pre-wrap text-sm leading-6 text-fg-muted">{{ reply.content }}</div>
            </li>
        </ul>

        <!-- Reply form -->
        <form v-if="showReplyForm" class="mt-4 sm:ml-5" @submit.prevent="submitReply">
            <Textarea ref="replyBox" v-model="replyForm.content" :rows="3" autoresize placeholder="Javobingizni yozing…" required
                aria-label="Javob matni" :error="replyForm.errors.content" @keydown.ctrl.enter.prevent="submitReply"
                @keydown.meta.enter.prevent="submitReply" />
            <div class="mt-2 flex items-center justify-end gap-2">
                <span class="mr-auto hidden text-xs text-fg-subtle sm:inline">Ctrl + Enter — yuborish</span>
                <Button variant="secondary" size="sm" @click="showReplyForm = false">Bekor qilish</Button>
                <Button type="submit" size="sm" :loading="replyForm.processing" :disabled="!replyForm.content.trim()">Yuborish</Button>
            </div>
        </form>

        <div v-else class="mt-3 flex justify-end">
            <Button variant="ghost" size="sm" :icon-left="ChatBubbleLeftIcon" @click="openReply">Javob yozish</Button>
        </div>

        <ConfirmDialog :show="confirming" title="Savolni o'chirasizmi?" description="Savol va unga berilgan javoblar o'chiriladi."
            :loading="deleting" @confirm="deleteQuestion" @close="confirming = false" />
    </article>
</template>
