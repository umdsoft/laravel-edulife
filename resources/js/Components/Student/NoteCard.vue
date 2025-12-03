<script setup>
import { router } from '@inertiajs/vue3';

const props = defineProps({
    note: Object,
});

const emit = defineEmits(['edit', 'seek']);

const deleteNote = () => {
    if (confirm('O\'chirilsinmi?')) {
        router.delete(route('student.learn.notes.destroy', props.note.id), {
            preserveScroll: true,
        });
    }
};

const togglePin = () => {
    router.patch(route('student.learn.notes.toggle-pin', props.note.id), {}, {
        preserveScroll: true,
    });
};

const colorClasses = {
    yellow: 'bg-yellow-50 border-yellow-100',
    blue: 'bg-blue-50 border-blue-100',
    green: 'bg-green-50 border-green-100',
    pink: 'bg-pink-50 border-pink-100',
};
</script>

<template>
    <div class="p-4 rounded-xl border transition-all hover:shadow-sm group"
        :class="colorClasses[note.color] || colorClasses.yellow">
        <div class="flex items-start justify-between mb-2">
            <button @click="$emit('seek', note.video_timestamp)"
                class="text-xs font-medium px-2 py-1 bg-white/50 rounded text-gray-700 hover:bg-white transition-colors">
                ⏱ {{ note.formatted_timestamp }}
            </button>

            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                <button @click="togglePin" class="p-1 hover:bg-white/50 rounded"
                    :class="{ 'text-purple-600 opacity-100': note.is_pinned, 'text-gray-400': !note.is_pinned }">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M16 5l-1.42 1.42-1.59-1.59V16h-1.98V4.83L9.42 6.42 8 5l4-4 4 4zm4 5v11c0 1.1-.9 2-2 2H6c-1.1 0-2-.9-2-2V10c0-1.1.9-2 2-2h3V5H6v16h12V10h-3v-2h3c1.1 0 2 .9 2 2z" />
                    </svg>
                </button>
                <button @click="$emit('edit', note)"
                    class="p-1 text-gray-400 hover:text-blue-600 hover:bg-white/50 rounded">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                </button>
                <button @click="deleteNote" class="p-1 text-gray-400 hover:text-red-600 hover:bg-white/50 rounded">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
            </div>
        </div>

        <p class="text-sm text-gray-800 whitespace-pre-wrap">{{ note.content }}</p>
    </div>
</template>
