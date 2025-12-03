<template>
    <AdminLayout>

        <Head title="Admin Dashboard" />

        <!-- Page Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
            <p class="text-gray-500">Platformaning umumiy statistikasi</p>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <StatsCard title="Foydalanuvchilar" :value="stats.users.total" :trend="stats.users.trend" color="blue" />

            <StatsCard title="O'qituvchilar" :value="stats.teachers.total" :trend="stats.teachers.trend"
                color="green" />

            <StatsCard title="Kurslar" :value="stats.courses.total" :trend="stats.courses.trend" color="purple" />

            <StatsCard title="Daromad" :value="stats.revenue.total" :trend="stats.revenue.trend" color="yellow"
                format-as-currency />
        </div>

        <!-- Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Users -->
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">So'nggi foydalanuvchilar</h2>

                <div class="space-y-3">
                    <div v-for="user in recentUsers" :key="user.id"
                        class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 transition">
                        <div
                            class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center font-medium text-gray-600">
                            {{ getUserInitials(user.name) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ user.name }}</p>
                            <p class="text-xs text-gray-500">{{ user.phone }}</p>
                        </div>
                        <div class="text-right">
                            <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-600 capitalize">
                                {{ user.role }}
                            </span>
                            <p class="text-xs text-gray-400 mt-1">{{ user.created_at }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Payments -->
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">So'nggi to'lovlar</h2>

                <div class="space-y-3">
                    <div v-for="payment in recentPayments" :key="payment.id"
                        class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 transition">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ payment.user_name }}</p>
                            <p class="text-xs text-gray-500 capitalize">{{ payment.type }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium text-gray-900">
                                {{ formatCurrency(payment.amount) }}
                            </p>
                            <span :class="[
                                'inline-block mt-1 px-2 py-0.5 text-xs rounded-full',
                                payment.status === 'completed' ? 'bg-green-100 text-green-600' : 'bg-yellow-100 text-yellow-600'
                            ]">
                                {{ payment.status }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import StatsCard from '@/Components/Admin/StatsCard.vue';

defineProps({
    stats: Object,
    recentUsers: Array,
    recentPayments: Array,
});

// Helpers
const getUserInitials = (name) => {
    const parts = name.split(' ');
    return parts.map(p => p[0]).join('').toUpperCase().slice(0, 2);
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('uz-UZ', {
        style: 'currency',
        currency: 'UZS',
        maximumFractionDigits: 0,
    }).format(amount);
};
</script>
