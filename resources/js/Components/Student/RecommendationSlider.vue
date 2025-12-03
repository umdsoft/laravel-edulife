<script setup>
import { ref } from 'vue';
import CourseCard from './CourseCard.vue';

defineProps({
    courses: Array,
});

const scrollContainer = ref(null);

const scroll = (direction) => {
    if (!scrollContainer.value) return;

    const scrollAmount = 300;
    if (direction === 'left') {
        scrollContainer.value.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
    } else {
        scrollContainer.value.scrollBy({ left: scrollAmount, behavior: 'smooth' });
    }
};
</script>

<template>
    <div class="relative group">
        <!-- Left Button -->
        <button @click="scroll('left')"
            class="absolute left-0 top-1/2 -translate-y-1/2 -translate-x-4 z-10 w-10 h-10 bg-white rounded-full shadow-lg border border-gray-100 flex items-center justify-center text-gray-600 opacity-0 group-hover:opacity-100 transition-opacity hover:bg-purple-50 hover:text-purple-600 disabled:opacity-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </button>

        <!-- Container -->
        <div ref="scrollContainer"
            class="flex gap-6 overflow-x-auto pb-6 -mx-4 px-4 scrollbar-hide snap-x snap-mandatory">
            <div v-for="course in courses" :key="course.id" class="w-72 shrink-0 snap-start h-full">
                <CourseCard :course="course" class="h-full" />
            </div>
        </div>

        <!-- Right Button -->
        <button @click="scroll('right')"
            class="absolute right-0 top-1/2 -translate-y-1/2 translate-x-4 z-10 w-10 h-10 bg-white rounded-full shadow-lg border border-gray-100 flex items-center justify-center text-gray-600 opacity-0 group-hover:opacity-100 transition-opacity hover:bg-purple-50 hover:text-purple-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
        </button>
    </div>
</template>

<style scoped>
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}

.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
