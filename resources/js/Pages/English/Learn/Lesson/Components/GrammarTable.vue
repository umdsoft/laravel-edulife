<script setup>
import { computed } from 'vue'

const props = defineProps({
    title: String,
    headers: Array,
    rows: Array,
    highlightColumn: {
        type: Number,
        default: null
    }
})

// Matnni formatlash
const formatCell = (text) => {
    if (!text || typeof text !== 'string') return text
    
    let result = text
    
    // **bold**
    result = result.replace(/\*\*([^*]+)\*\*/g, '<strong class="font-bold text-indigo-600 dark:text-indigo-400">$1</strong>')
    
    // `code`
    result = result.replace(/`([^`]+)`/g, '<code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-700 rounded text-sm font-mono text-pink-600 dark:text-pink-400">$1</code>')
    
    return result
}
</script>

<template>
    <div class="overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
        <!-- Title -->
        <div 
            v-if="title"
            class="px-4 py-3 bg-gradient-to-r from-indigo-500 to-purple-500 text-white font-semibold"
        >
            {{ title }}
        </div>
        
        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <!-- Headers -->
                <thead v-if="headers">
                    <tr class="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700">
                        <th 
                            v-for="(header, index) in headers"
                            :key="index"
                            :class="[
                                'px-4 py-3 text-sm font-bold',
                                index === highlightColumn 
                                    ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50/50 dark:bg-indigo-900/10'
                                    : 'text-gray-600 dark:text-gray-400'
                            ]"
                        >
                            {{ header }}
                        </th>
                    </tr>
                </thead>
                
                <!-- Body -->
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr 
                        v-for="(row, rowIndex) in rows"
                        :key="rowIndex"
                        class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
                    >
                        <td 
                            v-for="(cell, cellIndex) in row"
                            :key="cellIndex"
                            :class="[
                                'px-4 py-3 text-sm',
                                cellIndex === highlightColumn 
                                    ? 'font-bold text-indigo-700 dark:text-indigo-300 bg-indigo-50/30 dark:bg-indigo-900/10'
                                    : 'text-gray-700 dark:text-gray-300'
                            ]"
                            v-html="formatCell(cell)"
                        ></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
