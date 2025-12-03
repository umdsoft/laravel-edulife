<template>
    <AdminLayout>

        <Head :title="`${user.full_name} - Foydalanuvchi`" />

        <!-- Page Header -->
        <div class="mb-6">
            <Link :href="route('admin.users.index')"
                class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-4">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Orqaga
            </Link>

            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div
                        class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center text-2xl font-bold text-gray-600">
                        {{ getInitials(user.full_name) }}
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ user.full_name }}</h1>
                        <div class="flex items-center gap-2 mt-1">
                            <Badge :variant="getStatusBadgeVariant(user.status)">
                                {{ getStatusLabel(user.status) }}
                            </Badge>
                            <Badge :variant="getRoleBadgeVariant(user.role)">
                                {{ getRoleLabel(user.role) }}
                            </Badge>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <Link :href="route('admin.users.edit', user.id)"
                        class="inline-flex items-center gap-2 bg-gray-100 text-gray-900 hover:bg-gray-200 font-medium px-4 py-2 rounded-xl transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Tahrirlash
                    </Link>
                    <button @click="confirmDelete"
                        class="inline-flex items-center gap-2 bg-red-500 text-white hover:bg-red-600 font-medium px-4 py-2 rounded-xl transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        O'chirish
                    </button>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <p class="text-sm text-gray-500 mb-1">Level</p>
                <p class="text-2xl font-bold text-gray-900">{{ user.level }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <p class="text-sm text-gray-500 mb-1">XP Total</p>
                <p class="text-2xl font-bold text-gray-900">{{ formatNumber(user.xp_total) }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <p class="text-sm text-gray-500 mb-1">COIN Balance</p>
                <p class="text-2xl font-bold text-gray-900">{{ formatNumber(user.coin_balance) }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <p class="text-sm text-gray-500 mb-1">To'lovlar</p>
                <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(user.total_payments) }}</p>
            </div>
        </div>

        <!-- Info Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-4">Aloqa ma'lumotlari</h3>
                <div class="space-y-3">
                    <div>
                        <p class="text-xs text-gray-500">Telefon</p>
                        <p class="text-sm font-medium text-gray-900">{{ user.phone }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Email</p>
                        <p class="text-sm font-medium text-gray-900">{{ user.email || 'Email yo\'q' }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-4">Statistika</h3>
                <div class="space-y-3">
                    <div>
                        <p class="text-xs text-gray-500">Yozilgan kurslar</p>
                        <p class="text-sm font-medium text-gray-900">{{ user.enrollments_count }} ta</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Tugallangan kurslar</p>
                        <p class="text-sm font-medium text-gray-900">{{ user.completed_courses_count }} ta</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-4">Tizim ma'lumotlari</h3>
                <div class="space-y-3">
                    <div>
                        <p class="text-xs text-gray-500">Ro'yxatdan o'tgan</p>
                        <p class="text-sm font-medium text-gray-900">{{ user.created_at }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="bg-white rounded-2xl shadow-sm">
            <!-- Tab Headers -->
            <div class="border-b border-gray-100">
                <nav class="flex gap-6 px-6">
                    <button @click="activeTab = 'enrollments'" :class="[
                        'py-4 border-b-2 font-medium text-sm transition',
                        activeTab === 'enrollments'
                            ? 'border-primary text-primary'
                            : 'border-transparent text-gray-500 hover:text-gray-700',
                    ]">
                        Kurslar ({{ enrollments.length }})
                    </button>
                    <button @click="activeTab = 'payments'" :class="[
                        'py-4 border-b-2 font-medium text-sm transition',
                        activeTab === 'payments'
                            ? 'border-primary text-primary'
                            : 'border-transparent text-gray-500 hover:text-gray-700',
                    ]">
                        To'lovlar ({{ payments.length }})
                    </button>
                </nav>
            </div>

            <!-- Tab Content -->
            <div class="p-6">
                <!-- Enrollments Tab -->
                <div v-if="activeTab === 'enrollments'" class="space-y-3">
                    <div v-for="enrollment in enrollments" :key="enrollment.id"
                        class="flex items-center justify-between p-4 rounded-lg hover:bg-gray-50 transition">
                        <div class="flex-1">
                            <p class="font-medium text-gray-900">{{ enrollment.course_title }}</p>
                            <p class="text-xs text-gray-500">Boshlangan: {{ enrollment.started_at }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="text-right">
                                <p class="text-sm font-medium text-gray-900">{{ enrollment.progress }}%</p>
                                <div class="w-32 h-2 bg-gray-100 rounded-full mt-1">
                                    <div class="h-full bg-primary rounded-full transition-all"
                                        :style="{ width: enrollment.progress + '%' }" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <EmptyState v-if="enrollments.length === 0" icon="📚" title="Kurslar yo'q"
                        description="Foydalanuvchi hali birorta kursga yozilmagan" />
                </div>

                <!-- Payments Tab -->
                <div v-if="activeTab === 'payments'" class="space-y-3">
                    <div v-for="payment in payments" :key="payment.id"
                        class="flex items-center justify-between p-4 rounded-lg hover:bg-gray-50 transition">
                        <div class="flex-1">
                            <p class="font-medium text-gray-900">{{ formatCurrency(payment.amount) }}</p>
                            <p class="text-xs text-gray-500 capitalize">{{ payment.type }} • {{ payment.created_at }}
                            </p>
                        </div>
                        <Badge :variant="payment.status === 'completed' ? 'success' : 'warning'">
                            {{ payment.status }}
                        </Badge>
                    </div>

                    <EmptyState v-if="payments.length === 0" icon="💳" title="To'lovlar yo'q"
                        description="Foydalanuvchi hali birorta to'lov amalga oshirmagan" />
                </div>
            </div>
        </div>

        <!-- Delete Confirmation -->
        <DeleteConfirm :show="deleteModal.show" :loading="deleteModal.loading"
            :message="`${user.full_name} foydalanuvchisini o'chirishni xohlaysizmi?`" @confirm="deleteUser"
            @cancel="deleteModal.show = false" />
    </AdminLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Badge from '@/Components/Admin/Badge.vue';
import EmptyState from '@/Components/Admin/EmptyState.vue';
import DeleteConfirm from '@/Components/Admin/DeleteConfirm.vue';

const props = defineProps({
    user: Object,
    enrollments: Array,
    payments: Array,
});

const activeTab = ref('enrollments');
const deleteModal = reactive({
    show: false,
    loading: false,
});

const confirmDelete = () => {
    deleteModal.show = true;
};

const deleteUser = () => {
    deleteModal.loading = true;

    router.delete(route('admin.users.destroy', props.user.id), {
        onSuccess: () => {
            router.visit(route('admin.users.index'));
        },
        onFinish: () => {
            deleteModal.loading = false;
        },
    });
};

// Helpers
const getInitials = (name) => {
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};

const getRoleLabel = (role) => {
    const labels = {
        student: 'Student',
        teacher: 'O\'qituvchi',
        admin: 'Admin',
        super_admin: 'Super Admin',
    };
    return labels[role] || role;
};

const getRoleBadgeVariant = (role) => {
    const variants = {
        student: 'info',
        teacher: 'success',
        admin: 'warning',
        super_admin: 'danger',
    };
    return variants[role] || 'gray';
};

const getStatusLabel = (status) => {
    const labels = {
        active: 'Faol',
        inactive: 'Nofaol',
        blocked: 'Bloklangan',
    };
    return labels[status] || status;
};

const getStatusBadgeVariant = (status) => {
    const variants = {
        active: 'success',
        inactive: 'gray',
        blocked: 'danger',
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
