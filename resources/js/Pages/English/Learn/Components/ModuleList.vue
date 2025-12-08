<template>
    <div class="space-y-4">
        <!-- Loading State -->
        <div v-if="loading" class="space-y-4">
            <div v-for="n in 3" :key="n" class="bg-gray-100 dark:bg-gray-800 h-24 rounded-2xl animate-pulse"></div>
        </div>

        <!-- Empty State -->
        <div v-else-if="!modules || modules.length === 0" class="text-center py-12">
            <div class="text-6xl mb-4">📚</div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">No modules found</h3>
            <p class="text-gray-500">Coming soon for this level!</p>
        </div>

        <!-- Module List -->
        <div v-else class="space-y-4">
            <ModuleCard
                v-for="module in modules"
                :key="module.id"
                :module="module"
                @start-lesson="(lesson) => $emit('start-lesson', lesson)"
            />
        </div>
    </div>
</template>

<script setup>
import ModuleCard from './ModuleCard.vue'

defineProps({
    modules: {
        type: Array,
        default: () => []
    },
    loading: {
        type: Boolean,
        default: false
    }
})

defineEmits(['start-lesson'])
</script>
