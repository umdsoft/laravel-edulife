<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import Modal from '@/Components/UI/Modal.vue';
import Button from '@/Components/UI/Button.vue';

defineProps({
    reviews: Object,
    stats: Object,
    ratingDistribution: Object,
});

const showReplyModal = ref(false);
const selectedReview = ref(null);
const replyForm = useForm({
    reply: '',
});

const openReplyModal = (review) => {
    selectedReview.value = review;
    replyForm.reply = review.teacher_reply || '';
    showReplyModal.value = true;
};

const submitReply = () => {
    replyForm.post(route('teacher.reviews.reply', selectedReview.value.id), {
        onSuccess: () => showReplyModal.value = false,
    });
};
</script>

<template>

    <Head title="Sharhlar" />

    <TeacherLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-800">Sharhlar</h2>
        </template>

        <div class="space-y-6">
            <!-- Overview -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div
                    class="p-6 bg-white border border-gray-100 rounded-2xl flex flex-col justify-center items-center text-center">
                    <div class="text-5xl font-bold text-gray-900 mb-2">{{ Number(stats.avg_rating).toFixed(1) }}</div>
                    <div class="flex text-yellow-400 text-xl mb-2">★★★★★</div>
                    <p class="text-gray-500">{{ stats.total_reviews }} ta sharh</p>
                </div>

                <div class="p-6 bg-white border border-gray-100 rounded-2xl lg:col-span-2">
                    <h3 class="font-bold text-gray-900 mb-4">Reyting taqsimoti</h3>
                    <div class="space-y-2">
                        <div v-for="star in 5" :key="star" class="flex items-center gap-3">
                            <span class="text-sm font-medium w-3">{{ 6 - star }}</span>
                            <span class="text-yellow-400 text-sm">★</span>
                            <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full bg-yellow-400 rounded-full"
                                    :style="{ width: `${(ratingDistribution[6 - star] / stats.total_reviews) * 100}%` }">
                                </div>
                            </div>
                            <span class="text-xs text-gray-500 w-8 text-right">{{ ratingDistribution[6 - star] }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reviews List -->
            <div class="space-y-4">
                <div v-for="review in reviews.data" :key="review.id"
                    class="p-6 bg-white border border-gray-100 rounded-2xl">
                    <div class="flex items-start gap-4">
                        <div
                            class="w-10 h-10 rounded-full bg-gray-100 flex-shrink-0 flex items-center justify-center font-bold text-gray-500">
                            {{ review.user.first_name[0] }}
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center justify-between mb-1">
                                <h4 class="font-bold text-gray-900">{{ review.user.first_name }} {{
                                    review.user.last_name }}
                                </h4>
                                <span class="text-xs text-gray-400">{{ new Date(review.created_at).toLocaleDateString()
                                    }}</span>
                            </div>
                            <div class="flex items-center gap-2 mb-2">
                                <div class="flex text-yellow-400 text-sm">
                                    <span v-for="i in 5" :key="i">{{ i <= review.rating ? '★' : '☆' }}</span>
                                </div>
                                <span class="text-xs text-gray-500">• {{ review.course.title }}</span>
                            </div>
                            <p class="text-gray-600 text-sm mb-4">{{ review.content }}</p>

                            <!-- Teacher Reply -->
                            <div v-if="review.teacher_reply"
                                class="pl-4 border-l-2 border-emerald-100 bg-emerald-50/50 p-3 rounded-r-lg">
                                <p class="text-xs font-bold text-emerald-700 mb-1">Sizning javobingiz:</p>
                                <p class="text-sm text-gray-700">{{ review.teacher_reply }}</p>
                            </div>

                            <div v-else class="mt-2">
                                <button @click="openReplyModal(review)"
                                    class="text-sm font-medium text-emerald-600 hover:text-emerald-700">
                                    Javob berish
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="reviews.data.length === 0"
                    class="text-center py-12 bg-white rounded-2xl border border-gray-100 text-gray-500">
                    Sharhlar topilmadi
                </div>
            </div>

            <Pagination :data="reviews" />
        </div>

        <!-- Reply Modal -->
        <Modal :show="showReplyModal" @close="showReplyModal = false">
            <div class="p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Sharhga javob berish</h3>
                <div class="mb-4 p-3 bg-gray-50 rounded-xl text-sm text-gray-600 italic">
                    "{{ selectedReview?.content }}"
                </div>
                <form @submit.prevent="submitReply">
                    <textarea v-model="replyForm.reply" rows="4"
                        class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 mb-4"
                        placeholder="Javobingizni yozing..." required></textarea>
                    <div class="flex justify-end gap-3">
                        <button type="button" @click="showReplyModal = false"
                            class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-xl">
                            Bekor qilish
                        </button>
                        <Button type="submit" :loading="replyForm.processing">
                            Yuborish
                        </Button>
                    </div>
                </form>
            </div>
        </Modal>
    </TeacherLayout>
</template>
