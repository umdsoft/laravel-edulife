<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    direction: Object,
});
</script>

<template>
    <Link :href="route('student.courses.category', direction.id)"
        class="group bg-white rounded-2xl p-6 border border-gray-100 hover:shadow-lg hover:border-purple-100 transition-all duration-300 flex flex-col items-center text-center">

    <!-- Icon/Image -->
    <div
        class="w-16 h-16 rounded-2xl bg-purple-50 flex items-center justify-center mb-4 group-hover:scale-110 group-hover:bg-purple-100 transition-all duration-300">
        <img v-if="direction.icon_url" :src="direction.icon_url" :alt="direction.name" class="w-10 h-10 object-contain">
        <span v-else class="text-3xl">📚</span>
    </div>

    <!-- Name -->
    <h3 class="font-bold text-gray-900 mb-1 group-hover:text-purple-600 transition-colors">
        {{ direction.name }}
    </h3>

    <!-- Count -->
    <p class="text-sm text-gray-500">
        {{ direction.courses_count || 0 }} kurslar
    </p>
    </Link>
</template>
