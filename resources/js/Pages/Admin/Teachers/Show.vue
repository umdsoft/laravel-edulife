<template>
    <AdminLayout>

        <Head :title="`${teacher.full_name} - O'qituvchi`" />

        <!-- Page Header -->
        <div class="mb-6">
            <Link href="/admin/teachers" class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-4">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Orqaga
            </Link>

            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div
                        class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center text-2xl font-bold text-gray-600">
                        {{ getInitials(teacher.full_name) }}
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ teacher.full_name }}</h1>
                        <div class="flex items-center gap-2 mt-1">
                            <Badge :variant="getLevelBadgeVariant(teacher.level)">
                                {{ getLevelLabel(teacher.level) }}
                            </Badge>
                            <span v-if="teacher.is_verified" class="text-green-500 text-sm">✅ Tasdiqlangan</span>
                            <span class="text-sm text-gray-500">• Commission: {{ teacher.commission_rate }}%</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <select v-model="selectedLevel" @change="updateLevel"
                        class="bg-gray-100 border-0 rounded-xl px-4 py-2 text-sm font-medium focus:ring-2 focus:ring-primary/20">
                        <option value="new">Yangi</option>
                        <option value="verified">Tasdiqlangan</option>
                        <option value="featured">Featured</option>
                        <option value="top">Top</option>
                    </select>

                    <button v-if="!teacher.is_verified" @click="verify" :disabled="verifying"
                        class="inline-flex items-center gap-2 bg-green-500 text-white hover:bg-green-600 font-medium px-4 py-2 rounded-xl transition disabled:opacity-50">
                        ✅ Tasdiqlash
                    </button>
                    <button v-else @click="unverify" :disabled="verifying"
                        class="inline-flex items-center gap-2 bg-gray-500 text-white hover:bg-gray-600 font-medium px-4 py-2 rounded-xl transition disabled:opacity-50">
                        ❌ Bekor qilish
                    </button>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <p class="text-sm text-gray-500 mb-1">Kurslar</p>
                <p class="text-2xl font-bold text-gray-900">{{ teacher.total_courses }} ta</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <p class="text-sm text-gray-500 mb-1">Talabalar</p>
                <p class="text-2xl font-bold text-gray-900">{{ formatNumber(teacher.total_students) }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <p class="text-sm text-gray-500 mb-1">Rating</p>
                <div class="flex items-center gap-2">
                    <span class="text-yellow-500 text-2xl">⭐</span>
                    <p class="text-2xl font-bold text-gray-900">{{ teacher.avg_rating }}</p>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <p class="text-sm text-gray-500 mb-1">Daromad</p>
                <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(teacher.total_earnings) }}</p>
            </div>
        </div>

        <!-- Courses -->
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Kurslar ({{ courses.length }})</h2>
            <div class="space-y-3">
                <div v-for="course in courses" :key="course.id"
                    class="flex items-center justify-between p-4 rounded-lg hover:bg-gray-50 transition">
                    <div class="flex-1">
                        <p class="font-medium text-gray-900">{{ course.title }}</p>
                        <div class="flex items-center gap-4 mt-1">
                            <span class="text-xs text-gray-500">👥 {{ course.students_count }} talaba</span>
                            <span class="text-xs text-gray-500">⭐ {{ course.avg_rating }}</span>
                            <Badge :variant="getStatusBadgeVariant(course.status)" size="sm">
                                {{ getStatusLabel(course.status) }}
                            </Badge>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-900">{{ formatCurrency(course.price) }}</p>
                    </div>
                </div>

                <EmptyState v-if="courses.length === 0" icon="📚" title="Kurslar yo'q"
                    description="O'qituvchi hali birorta kurs yaratmagan" />
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Badge from '@/Components/Admin/Badge.vue';
import EmptyState from '@/Components/Admin/EmptyState.vue';

const props = defineProps({
    teacher: Object,
    courses: Array,
});

const selectedLevel = ref(props.teacher.level);
const verifying = ref(false);

const verify = () => {
    verifying.value = true;
    router.patch(`/admin/teachers/${props.teacher.id}/verify`, {}, {
        onFinish: () => {
            verifying.value = false;
        },
    });
};

const unverify = () => {
    verifying.value = true;
    router.patch(`/admin/teachers/${props.teacher.id}/unverify`, {}, {
        onFinish: () => {
            verifying.value = false;
        },
    });
};

const updateLevel = () => {
    router.patch(`/admin/teachers/${props.teacher.id}/update-level`, {
        level: selectedLevel.value,
    }, {
        preserveScroll: true,
    });
};

const getInitials = (name) => {
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};

const getLevelLabel = (level) => {
    const labels = {
        new: 'Yangi',
        verified: 'Tasdiqlangan',
        featured: 'Featured',
        top: 'Top',
    };
    return labels[level] || level;
};

const getLevelBadgeVariant = (level) => {
    const variants = {
        new: 'gray',
        verified: 'info',
        featured: 'warning',
        top: 'success',
    };
    return variants[level] || 'gray';
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

const formatNumber = (num) => {
    return new Intl.NumberFormat('uz-UZ').format(num);
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('uz-UZ', {
        style: 'currency',
        currency: 'UZS',
        maximumFractionDigits: 0,
    }).format(amount);
};
</script>
