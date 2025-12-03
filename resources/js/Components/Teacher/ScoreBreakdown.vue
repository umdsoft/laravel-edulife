<script setup>
import { computed } from 'vue';

const props = defineProps({
    breakdown: Object,
});

const items = computed(() => [
    {
        label: "O'quvchi baholari",
        score: props.breakdown.rating_score,
        max: 30,
        percent: (props.breakdown.rating_score / 30) * 100,
        detail: `${props.breakdown.raw.avg_rating} / 5 yulduz`,
        icon: '⭐',
        color: 'text-yellow-500',
        bg: 'bg-yellow-500'
    },
    {
        label: "Kurs tugatish darajasi",
        score: props.breakdown.completion_score,
        max: 20,
        percent: (props.breakdown.completion_score / 20) * 100,
        detail: `${props.breakdown.raw.completion_rate}% o'quvchi tugatgan`,
        icon: '📊',
        color: 'text-blue-500',
        bg: 'bg-blue-500'
    },
    {
        label: "O'quvchilar soni",
        score: props.breakdown.students_score,
        max: 15,
        percent: (props.breakdown.students_score / 15) * 100,
        detail: `${props.breakdown.raw.total_students} ta o'quvchi`,
        icon: '👥',
        color: 'text-indigo-500',
        bg: 'bg-indigo-500'
    },
    {
        label: "Javob berish tezligi",
        score: props.breakdown.response_score,
        max: 10,
        percent: (props.breakdown.response_score / 10) * 100,
        detail: props.breakdown.raw.avg_response_hours ? `O'rtacha ${props.breakdown.raw.avg_response_hours} soat` : 'Ma\'lumot yo\'q',
        icon: '💬',
        color: 'text-green-500',
        bg: 'bg-green-500'
    },
    {
        label: "Faollik darajasi",
        score: props.breakdown.activity_score,
        max: 10,
        percent: (props.breakdown.activity_score / 10) * 100,
        detail: `Oxirgi 30 kunda ${props.breakdown.raw.activity_actions} ta harakat`,
        icon: '📈',
        color: 'text-purple-500',
        bg: 'bg-purple-500'
    },
    {
        label: "Test natijalari",
        score: props.breakdown.test_score,
        max: 10,
        percent: (props.breakdown.test_score / 10) * 100,
        detail: `O'rtacha ${props.breakdown.raw.avg_test_score}%`,
        icon: '📝',
        color: 'text-red-500',
        bg: 'bg-red-500'
    },
    {
        label: "Refund darajasi",
        score: props.breakdown.refund_score,
        max: 5,
        percent: (props.breakdown.refund_score / 5) * 100,
        detail: `${props.breakdown.raw.refund_rate}% refund`,
        icon: '💸',
        color: 'text-emerald-500',
        bg: 'bg-emerald-500'
    },
]);
</script>

<template>
    <div class="bg-white rounded-lg shadow-lg p-6 border border-gray-200">
        <h3 class="text-lg font-bold text-gray-900 mb-6">Score Tafsilotlari</h3>

        <div class="space-y-6">
            <div v-for="(item, index) in items" :key="index">
                <div class="flex justify-between items-start mb-1">
                    <div class="flex items-center">
                        <span class="mr-2 text-lg">{{ item.icon }}</span>
                        <div>
                            <div class="text-sm font-medium text-gray-900">{{ item.label }}</div>
                            <div class="text-xs text-gray-500">{{ item.detail }}</div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm font-bold text-gray-900">{{ item.score }} / {{ item.max }}</div>
                        <div class="text-xs text-gray-500">{{ Math.round(item.percent) }}%</div>
                    </div>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-2">
                    <div class="h-2 rounded-full" :class="item.bg" :style="{ width: `${item.percent}%` }"></div>
                </div>
            </div>
        </div>
    </div>
</template>
