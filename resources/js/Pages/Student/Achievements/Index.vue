<template>
    <StudentLayout title="Achievements">
        <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">

            <!--  Progress Header -->
            <div class="bg-gradient-to-r from-purple-600 to-blue-600 rounded-xl p-8 text-white mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-3xl font-bold mb-2">Your Achievements</h2>
                        <p class="text-purple-100">Unlock rewards by completing challenges</p>
                    </div>
                    <div class="text-center">
                        <div class="text-5xl font-bold">{{ total_unlocked }}</div>
                        <div class="text-sm text-purple-200">of {{ total_achievements }} unlocked</div>
                    </div>
                </div>
            </div>

            <!-- Filter Tabs -->
            <div class="flex gap-2 mb-6 overflow-x-auto">
                <button v-for="cat in categories" :key="cat" @click="activeCategory = cat"
                    class="px-4 py-2 rounded-lg font-semibold whitespace-nowrap"
                    :class="activeCategory === cat ? 'bg-purple-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100'">
                    {{ cat }}
                </button>
            </div>

            <!-- Achievements Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <AchievementCard v-for="achievement in filteredAchievements" :key="achievement.id"
                    :achievement="achievement" :is-unlocked="achievement.is_unlocked"
                    :is-claimed="achievement.is_claimed" @click="handleAchievementClick(achievement)" />
            </div>

        </div>
    </StudentLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import AchievementCard from '@/Components/Student/AchievementCard.vue';

const props = defineProps({
    achievements: Array,
    total_unlocked: Number,
    total_achievements: Number,
});

const activeCategory = ref('all');

const categories = computed(() => {
    return ['all', ...new Set(props.achievements.map(a => a.category))];
});

const filteredAchievements = computed(() => {
    if (activeCategory.value === 'all') return props.achievements;
    return props.achievements.filter(a => a.category === activeCategory.value);
});

const handleAchievementClick = (achievement) => {
    if (achievement.is_unlocked && !achievement.is_claimed) {
        axios.post(route('student.achievements.claim', achievement.id))
            .then(() => {
                router.reload();
            });
    }
};
</script>
