<script setup>
import { computed } from 'vue';

const props = defineProps({
    score: Number,
    level: String,
    nextLevel: Object,
    commissionRate: Number,
});

const levelInfo = computed(() => {
    switch (props.level) {
        case 'top':
            return { name: 'TOP', color: 'text-yellow-600', bg: 'bg-yellow-100', icon: '🏆' };
        case 'featured':
            return { name: 'FEATURED', color: 'text-purple-600', bg: 'bg-purple-100', icon: '⭐' };
        case 'verified':
            return { name: 'VERIFIED', color: 'text-blue-600', bg: 'bg-blue-100', icon: '✓' };
        default:
            return { name: 'NEW', color: 'text-gray-600', bg: 'bg-gray-100', icon: '🆕' };
    }
});

const progressColor = computed(() => {
    if (props.score >= 90) return 'bg-yellow-500';
    if (props.score >= 75) return 'bg-purple-500';
    if (props.score >= 50) return 'bg-blue-500';
    return 'bg-gray-500';
});
</script>

<template>
    <div class="bg-white rounded-lg shadow-lg p-6 border border-gray-200">
        <div class="flex justify-between items-start mb-6">
            <div>
                <div class="flex items-center space-x-2 mb-2">
                    <span class="text-2xl">{{ levelInfo.icon }}</span>
                    <h2 class="text-xl font-bold text-gray-900">{{ levelInfo.name }} TEACHER</h2>
                </div>
                <p class="text-sm text-gray-500">
                    {{
                        level === 'top' ? 'Eng yuqori daraja' :
                            level === 'featured' ? 'Tavsiya etilgan o\'qituvchi' :
                                level === 'verified' ? 'Tasdiqlangan o\'qituvchi' : 'Yangi o\'qituvchi'
                    }}
                </p>
            </div>
            <div class="text-right">
                <div class="text-3xl font-bold text-gray-900">{{ score }}</div>
                <div class="text-xs text-gray-500 uppercase tracking-wide">Jami Ball</div>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="mb-6">
            <div class="flex justify-between text-sm mb-1">
                <span class="font-medium text-gray-700">Reyting</span>
                <span class="font-medium text-gray-900">{{ score }} / 100</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2.5">
                <div class="h-2.5 rounded-full transition-all duration-500" :class="progressColor"
                    :style="{ width: `${score}%` }"></div>
            </div>
        </div>

        <!-- Next Level Info -->
        <div v-if="nextLevel" class="bg-gray-50 rounded-md p-4 mb-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-700">Keyingi daraja:</span>
                <span class="text-sm font-bold text-indigo-600">{{ nextLevel.name }}</span>
            </div>
            <p class="text-sm text-gray-600">
                {{ nextLevel.name }} darajasiga o'tish uchun yana <span class="font-bold">{{ nextLevel.points_needed
                    }}</span> ball kerak.
            </p>
        </div>

        <!-- Commission Rate -->
        <div class="flex items-center justify-between pt-4 border-t border-gray-100">
            <span class="text-sm font-medium text-gray-500">Komissiya stavkasi</span>
            <span class="text-lg font-bold text-green-600">{{ commissionRate }}%</span>
        </div>
    </div>
</template>
