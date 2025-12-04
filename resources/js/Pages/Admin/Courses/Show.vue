<template>
    <AdminLayout>

        <Head :title="`${course.title} - Kurs`" />

        <!-- Page Header -->
        <div class="mb-6">
            <Link :href="route('admin.courses.index')"
                class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-4">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Orqaga
            </Link>
        </div>

        <!-- Course Header -->
        <div class="bg-white rounded-2xl shadow-sm p-6 mb-6">
            <div class="flex items-start gap-6">
                <img v-if="course.thumbnail" :src="course.thumbnail" :alt="course.title"
                    class="w-48 h-32 object-cover rounded-xl" />
                <div class="flex-1">
                    <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ course.title }}</h1>
                    <div class="flex items-center gap-4 text-sm text-gray-600 mb-4">
                        <span class="flex items-center gap-1">
                            ⭐ {{ course.avg_rating }} ({{ course.reviews_count }} sharh)
                        </span>
                        <span>👥 {{ course.students_count }} talaba</span>
                        <span>⏱️ {{ course.duration_hours }} soat</span>
                    </div>
                    <div class="flex items-center gap-3 mb-4">
                        <Badge :variant="getStatusBadgeVariant(course.status)" size="md">
                            {{ getStatusLabel(course.status) }}
                        </Badge>
                        <span class="text-sm text-gray-600">👨‍🏫 {{ course.teacher.full_name }}</span>
                        <span class="text-sm text-gray-600">💻 {{ course.direction.name }}</span>
                    </div>

                    <!-- Action Buttons -->
                    <div v-if="course.status === 'pending'" class="flex items-center gap-3">
                        <Button variant="primary" @click="approveCourse" :loading="approving">
                            ✅ Tasdiqlash
                        </Button>
                        <Button variant="danger" @click="rejectModal.show = true">
                            ❌ Rad etish
                        </Button>
                    </div>

                    <!-- Rejection Reason -->
                    <div v-if="course.rejection_reason" class="mt-4 p-4 bg-red-50 rounded-lg">
                        <p class="text-sm font-medium text-red-900 mb-1">Rad etish sababi:</p>
                        <p class="text-sm text-red-700">{{ course.rejection_reason }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Course Details -->
        <div class="bg-white rounded-2xl shadow-sm p-6 mb-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Kurs haqida</h2>
            <div class="prose max-w-none text-gray-700" v-html="sanitize(course.description)"></div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6 pt-6 border-t border-gray-100">
                <div>
                    <p class="text-xs text-gray-500">Daraja</p>
                    <p class="text-sm font-medium text-gray-900 capitalize">{{ course.level }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Til</p>
                    <p class="text-sm font-medium text-gray-900 capitalize">{{ course.language }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Narx</p>
                    <p class="text-sm font-medium text-gray-900">{{ formatCurrency(course.price) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Chegirma narxi</p>
                    <p class="text-sm font-medium text-gray-900">{{ formatCurrency(course.discount_price || 0) }}</p>
                </div>
            </div>
        </div>

        <!-- Modules -->
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Modullar ({{ modules.length }})</h2>
            <div class="space-y-3">
                <div v-for="module in modules" :key="module.id"
                    class="flex items-center justify-between p-4 rounded-lg hover:bg-gray-50 transition">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">📁</span>
                        <div>
                            <p class="font-medium text-gray-900">{{ module.title }}</p>
                            <p class="text-xs text-gray-500">{{ module.lessons_count }} ta dars</p>
                        </div>
                    </div>
                    <span class="text-sm text-gray-500">#{{ module.sort_order }}</span>
                </div>

                <EmptyState v-if="modules.length === 0" icon="📂" title="Modullar yo'q"
                    description="Bu kursda hali modullar qo'shilmagan" />
            </div>
        </div>

        <!-- Reject Modal -->
        <Modal :show="rejectModal.show" title="Kursni rad etish" @close="rejectModal.show = false">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Rad etish sababi
                    </label>
                    <textarea v-model="rejectModal.reason" rows="4"
                        placeholder="Nima uchun kurs rad etilayotganini tushuntiring..."
                        class="w-full bg-gray-50 border-0 rounded-xl px-4 py-3 text-gray-900 placeholder-gray-400 focus:ring-2 focus:ring-primary/20"
                        :class="{ 'ring-2 ring-red-500/50': rejectModal.error }"></textarea>
                    <p v-if="rejectModal.error" class="mt-2 text-sm text-red-600">
                        {{ rejectModal.error }}
                    </p>
                </div>
            </div>

            <template #footer>
                <div class="flex items-center justify-end gap-3">
                    <Button variant="secondary" @click="rejectModal.show = false" :disabled="rejectModal.loading">
                        Bekor qilish
                    </Button>
                    <Button variant="danger" @click="rejectCourse" :loading="rejectModal.loading">
                        Rad etish
                    </Button>
                </div>
            </template>
        </Modal>
    </AdminLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Badge from '@/Components/Admin/Badge.vue';
import Button from '@/Components/UI/Button.vue';
import Modal from '@/Components/Admin/Modal.vue';
import EmptyState from '@/Components/Admin/EmptyState.vue';
import { useSanitize } from '@/Composables/useSanitize.js';

const { sanitize } = useSanitize();

const props = defineProps({
    course: Object,
    modules: Array,
});

const approving = ref(false);
const rejectModal = reactive({
    show: false,
    loading: false,
    reason: '',
    error: null,
});

const approveCourse = () => {
    approving.value = true;

    router.patch(route('admin.courses.approve', props.course.id), {}, {
        onFinish: () => {
            approving.value = false;
        },
    });
};

const rejectCourse = () => {
    if (!rejectModal.reason.trim()) {
        rejectModal.error = 'Rad etish sababini kiriting';
        return;
    }

    rejectModal.error = null;
    rejectModal.loading = true;

    router.patch(route('admin.courses.reject', props.course.id), {
        rejection_reason: rejectModal.reason,
    }, {
        onSuccess: () => {
            rejectModal.show = false;
            rejectModal.reason = '';
        },
        onFinish: () => {
            rejectModal.loading = false;
        },
    });
};

const getStatusLabel = (status) => {
    const labels = {
        draft: 'Qoralama',
        pending: 'Kutilmoqda',
        published: 'Nashr qilingan',
        rejected: 'Rad etilgan',
    };
    return labels[status] || status;
};

const getStatusBadgeVariant = (status) => {
    const variants = {
        draft: 'gray',
        pending: 'warning',
        published: 'success',
        rejected: 'danger',
    };
    return variants[status] || 'gray';
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('uz-UZ', {
        style: 'currency',
        currency: 'UZS',
        maximumFractionDigits: 0,
    }).format(amount);
};
</script>
