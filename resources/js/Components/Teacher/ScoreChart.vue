<script setup>
import { computed } from 'vue';
import { Line } from 'vue-chartjs';
import { Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, Filler } from 'chart.js';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, Filler);

const props = defineProps({
    history: Array,
});

const chartData = computed(() => {
    // Sort history by date ascending
    const sortedHistory = [...props.history].sort((a, b) => new Date(a.calculated_date) - new Date(b.calculated_date));

    return {
        labels: sortedHistory.map(item => {
            const date = new Date(item.calculated_date);
            return `${date.getDate()}/${date.getMonth() + 1}`;
        }),
        datasets: [
            {
                label: 'Score',
                backgroundColor: 'rgba(79, 70, 229, 0.1)',
                borderColor: '#4F46E5',
                borderWidth: 2,
                pointBackgroundColor: '#4F46E5',
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: '#4F46E5',
                fill: true,
                tension: 0.4,
                data: sortedHistory.map(item => item.score),
            }
        ]
    };
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false
        },
        tooltip: {
            mode: 'index',
            intersect: false,
        }
    },
    scales: {
        y: {
            min: 0,
            max: 100,
            grid: {
                borderDash: [2, 2],
                color: '#f3f4f6'
            }
        },
        x: {
            grid: {
                display: false
            }
        }
    }
};
</script>

<template>
    <div class="bg-white rounded-lg shadow-lg p-6 border border-gray-200">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Score Tarixi (Oxirgi 30 kun)</h3>
        <div class="h-64">
            <Line :data="chartData" :options="chartOptions" />
        </div>
    </div>
</template>
