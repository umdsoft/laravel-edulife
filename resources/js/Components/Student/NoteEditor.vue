<script setup>
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    lessonId: String,
    currentTime: Number,
    note: Object, // If editing
});

const emit = defineEmits(['close', 'saved']);

const colors = ['yellow', 'blue', 'green', 'pink'];

const form = useForm({
    content: props.note?.content || '',
    video_timestamp: props.note?.video_timestamp || Math.floor(props.currentTime || 0),
    color: props.note?.color || 'yellow',
});

const submit = () => {
    if (props.note) {
        form.put(route('student.learn.notes.update', props.note.id), {
            preserveScroll: true,
            onSuccess: () => emit('saved'),
        });
    } else {
        form.post(route('student.learn.notes.store', props.lessonId), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                emit('saved');
            },
        });
    }
};

const formatTime = (seconds) => {
    const m = Math.floor(seconds / 60);
    const s = Math.floor(seconds % 60);
    return `${m}:${s.toString().padStart(2, '0')}`;
};
</script>

<template>
    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200">
        <form @submit.prevent="submit">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <span class="text-xs font-medium px-2 py-1 bg-gray-100 rounded text-gray-600">
                        ⏱ {{ formatTime(form.video_timestamp) }}
                    </span>
                    <div class="flex gap-1">
                        <button v-for="color in colors" :key="color" type="button" @click="form.color = color"
                            class="w-5 h-5 rounded-full border-2 transition-transform hover:scale-110" :class="[
                                `bg-${color}-100`,
                                form.color === color ? `border-${color}-500 scale-110` : 'border-transparent'
                            ]">
                        </button>
                    </div>
                </div>
                <button type="button" @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <textarea v-model="form.content" rows="3"
                class="w-full border-gray-200 rounded-lg focus:ring-purple-500 focus:border-purple-500 text-sm mb-3"
                placeholder="Yozuv qoldiring..."></textarea>

            <div class="flex justify-end gap-2">
                <button type="button" @click="$emit('close')"
                    class="px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-100 rounded-lg">
                    Bekor qilish
                </button>
                <button type="submit" :disabled="form.processing"
                    class="px-3 py-1.5 text-sm bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50">
                    Saqlash
                </button>
            </div>
        </form>
    </div>
</template>
