<script setup>
import { ref, computed } from 'vue'
import { ArrowRightIcon, ArrowLeftIcon, LightBulbIcon, CheckCircleIcon, XCircleIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
    grammarPoints: { type: Array, default: () => [] },
})

const emit = defineEmits(['next', 'back'])

const currentIndex = ref(0)
const currentPoint = computed(() => props.grammarPoints[currentIndex.value])
const isLast = computed(() => currentIndex.value === props.grammarPoints.length - 1)

const next = () => {
    if (isLast.value) {
        emit('next')
    } else {
        currentIndex.value++
        window.scrollTo({ top: 0, behavior: 'smooth' })
    }
}

const previous = () => {
    if (currentIndex.value > 0) {
        currentIndex.value--
        window.scrollTo({ top: 0, behavior: 'smooth' })
    }
}
</script>

<template>
    <div class="max-w-3xl mx-auto px-4 py-8">
        <!-- Progress Header -->
        <div class="text-center mb-8">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Grammar & Explanation</h2>
            <div class="flex items-center justify-center space-x-2">
                <template v-for="(point, index) in grammarPoints" :key="index">
                    <div 
                        class="h-2 rounded-full transition-all duration-300"
                        :class="index === currentIndex ? 'w-8 bg-purple-600' : 'w-2 bg-gray-200 dark:bg-gray-700'"
                    ></div>
                </template>
            </div>
        </div>
        
        <!-- Main Content Card -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden mb-8 transition-all duration-300">
            <!-- Page Title -->
            <div class="bg-purple-50 dark:bg-purple-900/20 p-6 border-b border-purple-100 dark:border-purple-800/30">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-purple-100 dark:bg-purple-800 rounded-full flex items-center justify-center shrink-0">
                        <LightBulbIcon class="w-6 h-6 text-purple-600 dark:text-purple-300" />
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                        {{ currentPoint?.title }}
                    </h3>
                </div>
            </div>

            <div class="p-6 space-y-8">
                <template v-for="(section, sIndex) in currentPoint?.sections" :key="sIndex">
                    
                    <!-- Text Section -->
                    <div v-if="section.type === 'text'" class="prose dark:prose-invert max-w-none">
                        <div class="text-gray-700 dark:text-gray-300 text-lg leading-relaxed" v-html="section.content"></div>
                    </div>

                    <!-- Table Section -->
                    <div v-else-if="section.type === 'table'" class="overflow-hidden bg-gray-50 dark:bg-gray-700/30 rounded-xl border border-gray-200 dark:border-gray-700">
                        <div v-if="section.title" class="bg-gray-100 dark:bg-gray-800/50 px-4 py-3 border-b border-gray-200 dark:border-gray-700 font-semibold text-gray-700 dark:text-gray-200">
                            {{ section.title }}
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr>
                                        <th v-for="(header, hIndex) in section.headers" :key="hIndex" class="p-4 text-sm font-semibold text-gray-600 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                                            {{ header }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="(row, rIndex) in section.rows" :key="rIndex" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                        <td v-for="(cell, cIndex) in row" :key="cIndex" class="p-4 text-gray-800 dark:text-gray-200 text-sm" v-html="cell"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tip Section -->
                    <div v-else-if="section.type === 'tip'" class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400 p-4 rounded-r-xl">
                        <div class="flex items-start space-x-3">
                            <span class="text-2xl animate-pulse">💡</span>
                            <div class="text-yellow-800 dark:text-yellow-100 pt-1 text-base" v-html="section.content"></div>
                        </div>
                    </div>

                    <!-- Examples Section -->
                    <div v-else-if="section.type === 'examples'" class="space-y-4">
                        <h4 v-if="section.title" class="font-bold text-gray-900 dark:text-gray-100 flex items-center space-x-2">
                            <span>📌</span> <span>{{ section.title }}</span>
                        </h4>
                        <div class="grid gap-3">
                            <div v-for="(item, iIndex) in section.items" :key="iIndex" class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-xl border border-blue-100 dark:border-blue-800 hover:shadow-md transition-shadow">
                                <p class="font-medium text-blue-900 dark:text-blue-100 text-lg">{{ item.en }}</p>
                                <p class="text-blue-700 dark:text-blue-300 mt-1">{{ item.uz }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Mistakes Section -->
                    <div v-else-if="section.type === 'mistakes'" class="space-y-4">
                        <h4 v-if="section.title" class="font-bold text-red-600 dark:text-red-400 flex items-center space-x-2">
                            <span>⚠️</span> <span>{{ section.title }}</span>
                        </h4>
                        <div class="grid gap-4 sm:grid-cols-1">
                            <div v-for="(item, mIndex) in section.items" :key="mIndex" class="flex flex-col sm:flex-row gap-4 bg-gray-50 dark:bg-gray-800/50 p-4 rounded-xl border border-gray-200 dark:border-gray-700">
                                <div class="flex-1 flex items-start space-x-3 text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20 p-3 rounded-lg w-full">
                                    <XCircleIcon class="w-6 h-6 shrink-0" />
                                    <span class="font-medium decoration-red-500/50" v-html="item.bad"></span>
                                </div>
                                <div class="flex items-center justify-center text-gray-400">
                                    <ArrowRightIcon class="w-6 h-6 rotate-90 sm:rotate-0" />
                                </div>
                                <div class="flex-1 flex items-start space-x-3 text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/20 p-3 rounded-lg w-full">
                                    <CheckCircleIcon class="w-6 h-6 shrink-0" />
                                    <span class="font-bold" v-html="item.good"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                </template>
            </div>
        </div>
        
        <!-- Navigation -->
        <div class="flex items-center justify-between sticky bottom-6 z-10">
            <button 
                @click="$emit('back')"
                class="flex items-center space-x-2 px-5 py-3 rounded-xl bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 shadow-md hover:bg-gray-50 dark:hover:bg-gray-700 transition-all font-medium"
            >
                <ArrowLeftIcon class="w-5 h-5" />
                <span>Qaytish</span>
            </button>
            
            <div class="flex items-center space-x-4">
                <button
                    v-if="currentIndex > 0"
                    @click="previous"
                    class="p-3 rounded-xl bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 shadow-md hover:bg-gray-50 dark:hover:bg-gray-700 transition-all"
                >
                    <ArrowLeftIcon class="w-5 h-5" />
                </button>
                
                <button
                    @click="next"
                    class="flex items-center space-x-3 px-8 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-xl font-bold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all"
                >
                    <span>{{ isLast ? 'Davom etish' : 'Keyingi' }}</span>
                    <ArrowRightIcon class="w-5 h-5" />
                </button>
            </div>
        </div>
        
        <!-- Extra padding for sticky nav -->
        <div class="h-20"></div>
    </div>
</template>

<style scoped>
/* Custom prose styles if needed */
.prose strong {
    color: inherit;
    font-weight: 700;
}
</style>
