<script setup>
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/UI/Modal.vue';
import { watch } from 'vue';

const props = defineProps({
    lesson: Object,
    chapter: Object, // If editing
    show: Boolean,
});

const emit = defineEmits(['close']);

const form = useForm({
    title: '',
    timestamp: 0,
    description: '',
});

watch(() => props.chapter, (newVal) => {
    if (newVal) {
        form.title = newVal.title;
        form.timestamp = newVal.timestamp;
        form.description = newVal.description;
    } else {
        form.reset();
    }
}, { immediate: true });

const submit = () => {
    if (props.chapter) {
        form.put(route('teacher.lessons.chapters.update', [props.lesson.id, props.chapter.id]), {
            onSuccess: () => emit('close'),
        });
    } else {
        form.post(route('teacher.lessons.chapters.store', props.lesson.id), {
            onSuccess: () => {
                form.reset();
                emit('close');
            },
        });
    }
};

const formatTime = (seconds) => {
    const m = Math.floor(seconds / 60);
    const s = seconds % 60;
    return `${m}:${s.toString().padStart(2, '0')}`;
};
</script>

<template>
    <Modal :show="show" @close="$emit('close')">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">
                {{ chapter ? 'Video bo\'limini tahrirlash' : 'Yangi video bo\'limi' }}
            </h2>

            <form @submit.prevent="submit" class="space-y-4">
                <!-- Title -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nomi</label>
                    <input v-model="form.title" type="text"
                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        required />
                    <div v-if="form.errors.title" class="text-red-500 text-xs mt-1">{{ form.errors.title }}</div>
                </div>

                <!-- Timestamp -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Vaqt (sekund)</label>
                    <div class="flex items-center space-x-2">
                        <input v-model="form.timestamp" type="number" min="0"
                            class="w-32 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <span class="text-sm text-gray-500">
                            {{ formatTime(form.timestamp) }}
                        </span>
                    </div>
                    <div v-if="form.errors.timestamp" class="text-red-500 text-xs mt-1">{{ form.errors.timestamp }}
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tavsif (ixtiyoriy)</label>
                    <textarea v-model="form.description" rows="3"
                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    <div v-if="form.errors.description" class="text-red-500 text-xs mt-1">{{ form.errors.description }}
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" @click="$emit('close')"
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Bekor qilish
                    </button>
                    <button type="submit" :disabled="form.processing"
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50">
                        {{ chapter ? 'Saqlash' : 'Qo\'shish' }}
                    </button>
                </div>
            </form>
        </div>
    </Modal>
</template>
