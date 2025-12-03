<template>
    <AdminLayout>

        <Head :title="`#${payment.transaction_id} - To'lov`" />

        <!-- Page Header -->
        <div class="mb-6">
            <Link :href="route('admin.payments.index')"
                class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 mb-4">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Orqaga
            </Link>

            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">To'lov #{{ payment.transaction_id }}</h1>
                    <p class="text-gray-500">{{ payment.created_at }}</p>
                </div>
                <Badge :variant="getStatusBadgeVariant(payment.status)" size="md">
                    {{ getStatusLabel(payment.status) }}
                </Badge>
            </div>
        </div>

        <!-- Payment Info -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-4">To'lov ma'lumotlari</h3>
                <div class="space-y-3">
                    <div>
                        <p class="text-xs text-gray-500">Summa</p>
                        <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(payment.amount) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Tur</p>
                        <Badge :variant="getTypeBadgeVariant(payment.type)">
                            {{ getTypeLabel(payment.type) }}
                        </Badge>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">To'lov usuli</p>
                        <p class="text-sm font-medium text-gray-900 capitalize">{{ payment.payment_method || 'N/A' }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-4">Foydalanuvchi</h3>
                <div class="space-y-3" v-if="payment.user">
                    <div>
                        <p class="text-xs text-gray-500">Ism</p>
                        <Link :href="route('admin.users.show', payment.user.id)"
                            class="text-sm font-medium text-primary hover:text-primary/80">
                        {{ payment.user.full_name }}
                        </Link>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Telefon</p>
                        <p class="text-sm font-medium text-gray-900">{{ payment.user.phone }}</p>
                    </div>
                    <div v-if="payment.user.email">
                        <p class="text-xs text-gray-500">Email</p>
                        <p class="text-sm font-medium text-gray-900">{{ payment.user.email }}</p>
                    </div>
                </div>
                <p v-else class="text-sm text-gray-500">Foydalanuvchi topilmadi</p>
            </div>

            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wide mb-4">Bog'liq ma'lumot</h3>
                <div class="space-y-3">
                    <!-- Course -->
                    <div v-if="payment.course">
                        <p class="text-xs text-gray-500">Kurs</p>
                        <Link :href="route('admin.courses.show', payment.course.id)"
                            class="text-sm font-medium text-primary hover:text-primary/80">
                        {{ payment.course.title }}
                        </Link>
                    </div>

                    <!-- Subscription Plan -->
                    <div v-if="payment.subscription_plan">
                        <p class="text-xs text-gray-500">Obuna rejasi</p>
                        <p class="text-sm font-medium text-gray-900">{{ payment.subscription_plan.name }}</p>
                    </div>

                    <!-- Promo Code -->
                    <div v-if="payment.promo_code">
                        <p class="text-xs text-gray-500">Promo kod</p>
                        <div class="flex items-center gap-2">
                            <code
                                class="px-2 py-1 bg-gray-100 rounded text-xs font-mono">{{ payment.promo_code.code }}</code>
                            <span class="text-xs text-green-600">-{{ payment.promo_code.discount }}%</span>
                        </div>
                    </div>

                    <p v-if="!payment.course && !payment.subscription_plan" class="text-sm text-gray-500">
                        Ma'lumot yo'q
                    </p>
                </div>
            </div>
        </div>

        <!-- Transaction Details -->
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Tranzaksiya tafsilotlari</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div>
                    <p class="text-xs text-gray-500 mb-1">Tranzaksiya ID</p>
                    <code class="text-sm font-mono text-gray-900">{{ payment.transaction_id }}</code>
                </div>
                <div>
                    <p class="text-xs text-gray-500 mb-1">Status</p>
                    <p class="text-sm font-medium text-gray-900 capitalize">{{ payment.status }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 mb-1">Tur</p>
                    <p class="text-sm font-medium text-gray-900 capitalize">{{ payment.type }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 mb-1">Sana</p>
                    <p class="text-sm font-medium text-gray-900">{{ payment.created_at }}</p>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Badge from '@/Components/Admin/Badge.vue';

defineProps({
    payment: Object,
});

const getTypeLabel = (type) => {
    const labels = {
        subscription: 'Obuna',
        course: 'Kurs',
        coins: 'COIN',
    };
    return labels[type] || type;
};

const getTypeBadgeVariant = (type) => {
    const variants = {
        subscription: 'info',
        course: 'success',
        coins: 'warning',
    };
    return variants[type] || 'gray';
};

const getStatusLabel = (status) => {
    const labels = {
        pending: 'Kutilmoqda',
        completed: 'Tugallangan',
        failed: 'Xato',
        refunded: 'Qaytarilgan',
    };
    return labels[status] || status;
};

const getStatusBadgeVariant = (status) => {
    const variants = {
        pending: 'warning',
        completed: 'success',
        failed: 'danger',
        refunded: 'gray',
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
