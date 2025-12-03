<template>
    <AdminLayout>

        <Head title="Hisobotlar" />

        <!-- Page Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Hisobotlar</h1>
                <p class="text-gray-500">Tizim statistikasi va tahlillar</p>
            </div>

            <select v-model="period" @change="updatePeriod"
                class="bg-white border border-gray-200 rounded-xl px-4 py-2 text-sm focus:ring-2 focus:ring-primary/20">
                <option value="week">So'nggi 7 kun</option>
                <option value="month">So'nggi 30 kun</option>
                <option value="year">Yillik</option>
            </select>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <p class="text-sm text-gray-500 mb-1">Jami Daromad</p>
                <p class="text-2xl font-bold text-gray-900">{{ formatCurrency(stats.total_revenue) }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <p class="text-sm text-gray-500 mb-1">Davr Daromadi</p>
                <p class="text-2xl font-bold text-green-600">+{{ formatCurrency(stats.period_revenue) }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <p class="text-sm text-gray-500 mb-1">Yangi Foydalanuvchilar</p>
                <p class="text-2xl font-bold text-gray-900">{{ formatNumber(stats.new_users) }}</p>
                <p class="text-xs text-gray-500">Jami: {{ formatNumber(stats.total_users) }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <p class="text-sm text-gray-500 mb-1">Faol Obunalar</p>
                <p class="text-2xl font-bold text-primary">{{ formatNumber(stats.active_subscriptions) }}</p>
            </div>
        </div>

        <!-- Charts Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Revenue Chart -->
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Daromad Dinamikasi</h3>
                <div class="h-80">
                    <Line :data="revenueChartData" :options="chartOptions" />
                </div>
            </div>

            <!-- Users Chart -->
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Foydalanuvchilar O'sishi</h3>
                <div class="h-80">
                    <Bar :data="usersChartData" :options="chartOptions" />
                </div>
            </div>
        </div>

        <!-- Courses Chart -->
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Kurslar Kategoriyalar Bo'yicha</h3>
            <div class="h-80 flex justify-center">
                <div class="w-full max-w-md">
                    <Doughnut :data="coursesChartData" :options="doughnutOptions" />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    ArcElement,
    Title,
    Tooltip,
    Legend
} from 'chart.js';
import { Line, Bar, Doughnut } from 'vue-chartjs';

ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    ArcElement,
    Title,
    Tooltip,
    Legend
);

const props = defineProps({
    stats: Object,
    charts: Object,
    filters: Object,
});

const period = ref(props.filters.period);

const updatePeriod = () => {
    router.get('/admin/reports', { period: period.value }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const revenueChartData = computed(() => ({
    labels: props.charts.revenue.labels,
    datasets: [
        {
            label: 'Daromad (UZS)',
            backgroundColor: '#7C3AED',
            borderColor: '#7C3AED',
            data: props.charts.revenue.data,
            tension: 0.4,
        }
    ]
}));

const usersChartData = computed(() => ({
    labels: props.charts.users.labels,
    datasets: [
        {
            label: 'Yangi foydalanuvchilar',
            backgroundColor: '#10B981',
            data: props.charts.users.data,
            borderRadius: 4,
        }
    ]
}));

const coursesChartData = computed(() => ({
    labels: props.charts.courses.labels,
    datasets: [
        {
            backgroundColor: ['#7C3AED', '#10B981', '#F59E0B', '#EF4444', '#3B82F6'],
            data: props.charts.courses.data,
        }
    ]
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false,
        }
    },
    scales: {
        y: {
            beginAtZero: true,
            grid: {
                display: true,
                borderDash: [2, 2],
            }
        },
        x: {
            grid: {
                display: false,
            }
        }
    }
};

const doughnutOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom',
        }
    }
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
