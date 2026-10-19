<script setup>
import { computed, ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ChatBubbleLeftRightIcon, ChatBubbleLeftIcon, PencilSquareIcon, InboxIcon } from '@heroicons/vue/24/outline';
import { StarIcon } from '@heroicons/vue/20/solid';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Card from '@/Components/UI/Card.vue';
import Avatar from '@/Components/UI/Avatar.vue';
import Badge from '@/Components/UI/Badge.vue';
import Button from '@/Components/UI/Button.vue';
import Tabs from '@/Components/UI/Tabs.vue';
import Modal from '@/Components/UI/Modal.vue';
import Textarea from '@/Components/UI/Textarea.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import RatingStars from '@/Components/Teacher/RatingStars.vue';
import { formatDate, formatNumber, fullName } from '@/Components/Teacher/format';
import { useServerFilters } from '@/Composables/useServerFilters';

const props = defineProps({
    reviews: Object,
    stats: Object,
    ratingDistribution: Object,
    filters: { type: Object, default: () => ({}) },
});

const showReplyModal = ref(false);
const selectedReview = ref(null);
const replyForm = useForm({
    reply: '',
});

const openReplyModal = (review) => {
    selectedReview.value = review;
    replyForm.reply = review.teacher_reply || '';
    replyForm.clearErrors();
    showReplyModal.value = true;
};

const submitReply = () => {
    replyForm.post(route('teacher.reviews.reply', selectedReview.value.id), {
        preserveScroll: true,
        onSuccess: () => showReplyModal.value = false,
    });
};

const avg = computed(() => Number(props.stats?.avg_rating || 0));
const total = computed(() => Number(props.stats?.total_reviews || 0));
const distribution = computed(() => [5, 4, 3, 2, 1].map((star) => {
    const count = Number(props.ratingDistribution?.[star] || 0);
    return { star, count, pct: total.value ? (count / total.value) * 100 : 0 };
}));

// Filters are applied by the backend (all pages, not only the loaded one)
const { filters } = useServerFilters(
    { filter: props.filters?.filter || '', rating: props.filters?.rating || '' },
    route('teacher.reviews.index'),
    { only: ['reviews', 'filters'] },
);
const filter = computed({
    get: () => filters.filter || 'all',
    set: (key) => { filters.filter = key === 'all' ? '' : key; },
});
const list = computed(() => props.reviews?.data || []);
const tabs = computed(() => [
    { key: 'all', label: 'Barchasi', count: total.value || undefined },
    { key: 'pending', label: 'Javobsiz', count: Number(props.stats?.pending_replies) || undefined },
]);
const filtered = list;
</script>

<template>
    <Head title="Sharhlar" />

    <TeacherLayout>
        <PageHeader title="Sharhlar" description="O'quvchilar kurslaringizga qoldirgan baholar va fikrlar." />

        <div class="space-y-6">
            <!-- Overview -->
            <div class="grid gap-6 lg:grid-cols-3">
                <Card>
                    <div class="flex h-full flex-col items-center justify-center py-2 text-center">
                        <p class="text-4xl font-semibold tracking-tight tabular-nums text-fg">{{ avg.toFixed(1) }}</p>
                        <RatingStars :value="avg" size="md" class="mt-2" />
                        <p class="mt-2 text-13 text-fg-subtle"><span class="tabular-nums">{{ formatNumber(total) }}</span> ta sharh</p>
                        <Badge v-if="stats.pending_replies" variant="warning" class="mt-3">{{ stats.pending_replies }} ta javob kutmoqda</Badge>
                    </div>
                </Card>

                <Card class="lg:col-span-2" title="Baholar taqsimoti">
                    <ul class="space-y-2.5">
                        <li v-for="row in distribution" :key="row.star" class="flex items-center gap-3 text-sm">
                            <span class="flex w-8 shrink-0 items-center gap-1 tabular-nums text-fg-muted">
                                {{ row.star }}<StarIcon class="h-3.5 w-3.5 text-warning" aria-hidden="true" />
                            </span>
                            <div class="h-2 flex-1 overflow-hidden rounded-full bg-surface-2" role="img"
                                :aria-label="`${row.star} yulduz: ${row.count} ta`">
                                <div class="h-full rounded-full bg-warning-600 transition-[width] duration-500" :style="{ width: `${row.pct}%` }" />
                            </div>
                            <span class="w-10 shrink-0 text-right text-13 tabular-nums text-fg-subtle">{{ row.count }}</span>
                        </li>
                    </ul>
                </Card>
            </div>

            <!-- List -->
            <div class="max-w-3xl space-y-4">
                <Tabs v-if="total" v-model="filter" :tabs="tabs" variant="pills" label="Sharhlar filtri" />

                <article v-for="review in filtered" :key="review.id" class="rounded-lg border border-border bg-surface p-4 shadow-xs sm:p-5">
                    <div class="flex items-start gap-3">
                        <Avatar :src="review.user?.avatar_url" :name="fullName(review.user)" size="md" />
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1">
                                <h3 class="text-sm font-medium text-fg">{{ fullName(review.user) }}</h3>
                                <time :datetime="review.created_at" class="text-13 tabular-nums text-fg-subtle">{{ formatDate(review.created_at) }}</time>
                            </div>
                            <div class="mt-0.5 flex flex-wrap items-center gap-2">
                                <RatingStars :value="review.rating" size="xs" />
                                <span class="truncate text-13 text-fg-subtle">{{ review.course?.title }}</span>
                            </div>
                            <p v-if="review.content" class="mt-2 whitespace-pre-line text-sm leading-6 text-fg">{{ review.content }}</p>

                            <div v-if="review.teacher_reply" class="mt-3 rounded-md border-l-2 border-brand-500 bg-brand-50/60 px-3 py-2.5">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-xs font-semibold text-brand">Sizning javobingiz</p>
                                    <Button variant="ghost" size="sm" :icon-left="PencilSquareIcon" class="-my-1 -mr-2" @click="openReplyModal(review)">Tahrirlash</Button>
                                </div>
                                <p class="mt-1 whitespace-pre-line text-sm text-fg-muted">{{ review.teacher_reply }}</p>
                            </div>
                            <div v-else class="mt-3">
                                <Button variant="secondary" size="sm" :icon-left="ChatBubbleLeftIcon" @click="openReplyModal(review)">Javob berish</Button>
                            </div>
                        </div>
                    </div>
                </article>

                <Card v-if="!filtered.length">
                    <EmptyState v-if="total" :icon="InboxIcon" title="Javobsiz sharh yo'q" description="Barcha sharhlarga javob berilgan." />
                    <EmptyState v-else :icon="ChatBubbleLeftRightIcon" title="Hali sharhlar yo'q" description="O'quvchilar kursni baholaganda shu yerda ko'rinadi." />
                </Card>

                <div v-if="reviews.links && reviews.links.length > 3" class="overflow-hidden rounded-lg border border-border">
                    <Pagination :data="reviews" />
                </div>
            </div>
        </div>

        <!-- Reply modal -->
        <Modal :show="showReplyModal" title="Sharhga javob" size="lg" @close="showReplyModal = false">
            <figure v-if="selectedReview" class="mb-4 rounded-md bg-surface-2 p-3">
                <div class="flex items-center gap-2">
                    <span class="text-13 font-medium text-fg">{{ fullName(selectedReview.user) }}</span>
                    <RatingStars :value="selectedReview.rating" size="xs" />
                </div>
                <blockquote class="mt-1 text-sm text-fg-muted">{{ selectedReview.content || '—' }}</blockquote>
            </figure>
            <form id="review-reply-form" class="pb-2" @submit.prevent="submitReply">
                <Textarea v-model="replyForm.reply" label="Javobingiz" :rows="4" autoresize maxlength="1000" show-count required autofocus
                    placeholder="Fikr uchun rahmat…" :error="replyForm.errors.reply" />
            </form>
            <template #footer>
                <Button variant="secondary" @click="showReplyModal = false">Bekor qilish</Button>
                <Button type="submit" form="review-reply-form" :loading="replyForm.processing" :disabled="!replyForm.reply.trim()">Yuborish</Button>
            </template>
        </Modal>
    </TeacherLayout>
</template>
