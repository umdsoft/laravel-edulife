<script setup>
defineProps({
    chapters: {
        type: Array,
        required: true
    },
    currentTime: {
        type: Number,
        default: 0
    }
});

const emit = defineEmits(['seek']);

const formatTime = (seconds) => {
    const m = Math.floor(seconds / 60);
    const s = Math.floor(seconds % 60);
    return `${m}:${s.toString().padStart(2, '0')}`;
};
</script>

<template>
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="p-4 border-b border-gray-100">
            <h3 class="font-bold text-gray-900">Video bo'limlari</h3>
        </div>
        <div class="divide-y divide-gray-100">
            <button v-for="chapter in chapters" :key="chapter.id" @click="$emit('seek', chapter.timestamp)"
                class="w-full flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-colors text-left group">
                <div
                    class="w-12 text-xs font-mono bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded text-center group-hover:bg-purple-100 group-hover:text-purple-600 transition-colors">
                    {{ formatTime(chapter.timestamp) }}
                </div>
                <span class="text-sm text-gray-700 group-hover:text-gray-900 font-medium line-clamp-1">
                    {{ chapter.title }}
                </span>
            </button>
        </div>
    </div>
</template>
