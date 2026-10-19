<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick, watch } from 'vue';
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/20/solid';
import IconButton from '@/Components/UI/IconButton.vue';
import CourseCard from './CourseCard.vue';

const props = defineProps({
    courses: Array,
});

const scrollContainer = ref(null);
const canPrev = ref(false);
const canNext = ref(false);

const update = () => {
    const el = scrollContainer.value;
    if (!el) return;
    canPrev.value = el.scrollLeft > 4;
    canNext.value = el.scrollLeft + el.clientWidth < el.scrollWidth - 4;
};

const scroll = (direction) => {
    const el = scrollContainer.value;
    if (!el) return;
    const amount = Math.max(280, el.clientWidth * 0.8);
    el.scrollBy({ left: direction === 'left' ? -amount : amount, behavior: 'smooth' });
};

onMounted(() => {
    nextTick(update);
    window.addEventListener('resize', update, { passive: true });
});
onBeforeUnmount(() => window.removeEventListener('resize', update));
watch(() => props.courses, () => nextTick(update));
</script>

<template>
    <div>
        <div v-if="canPrev || canNext" class="mb-3 flex justify-end gap-1.5">
            <IconButton :icon="ChevronLeftIcon" label="Oldingi kurslar" variant="secondary" size="sm" :disabled="!canPrev"
                @click="scroll('left')" />
            <IconButton :icon="ChevronRightIcon" label="Keyingi kurslar" variant="secondary" size="sm" :disabled="!canNext"
                @click="scroll('right')" />
        </div>

        <div ref="scrollContainer" class="scrollbar-hide -mx-1 flex snap-x snap-mandatory gap-4 overflow-x-auto px-1 pb-2"
            @scroll.passive="update">
            <div v-for="course in courses" :key="course.id" class="w-[272px] shrink-0 snap-start">
                <CourseCard :course="course" class="h-full" />
            </div>
        </div>
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

@media (prefers-reduced-motion: reduce) {
    .scrollbar-hide {
        scroll-behavior: auto;
    }
}
</style>
