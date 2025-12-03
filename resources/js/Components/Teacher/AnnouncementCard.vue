<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { format } from 'date-fns';
import { uz } from 'date-fns/locale';
import Modal from '@/Components/UI/Modal.vue';

const props = defineProps({
    announcement: Object,
    course: Object,
});

const showEditModal = ref(false);
const editForm = useForm({
    title: props.announcement.title,
    content: props.announcement.content,
    type: props.announcement.type,
    is_pinned: props.announcement.is_pinned,
});

const togglePin = () => {
    router.post(route('teacher.courses.announcements.toggle-pin', [props.course.id, props.announcement.id]));
};

const deleteAnnouncement = () => {
    if (confirm('Rostdan ham bu e\'lonni o\'chirmoqchimisiz?')) {
        router.delete(route('teacher.courses.announcements.destroy', [props.course.id, props.announcement.id]));
    }
};

const updateAnnouncement = () => {
    editForm.put(route('teacher.courses.announcements.update', [props.course.id, props.announcement.id]), {
        onSuccess: () => {
            showEditModal.value = false;
        },
    });
};

const getTypeColor = (type) => {
    const colors = {
        info: 'bg-blue-100 text-blue-800',
        update: 'bg-green-100 text-green-800',
        important: 'bg-red-100 text-red-800',
        promotion: 'bg-purple-100 text-purple-800',
    };
    return colors[type] || colors.info;
};

const getTypeLabel = (type) => {
    const labels = {
        info: 'Ma\'lumot',
        update: 'Yangilanish',
        important: 'Muhim',
        promotion: 'Aksiya',
    };
    return labels[type] || type;
};
</script>

<template>
    <div class="bg-white rounded-lg border border-gray-200 p-6 relative group">
        <!-- Actions -->
        <div
            class="absolute top-4 right-4 flex items-center space-x-2 opacity-0 group-hover:opacity-100 transition-opacity">
            <button @click="togglePin" class="p-1 rounded-full hover:bg-gray-100"
                :class="{ 'text-indigo-600': announcement.is_pinned, 'text-gray-400': !announcement.is_pinned }"
                title="Qadash">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                </svg>
            </button>

            <button @click="showEditModal = true"
                class="p-1 rounded-full hover:bg-gray-100 text-gray-400 hover:text-indigo-600" title="Tahrirlash">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
            </button>

            <button @click="deleteAnnouncement"
                class="p-1 rounded-full hover:bg-red-50 text-gray-400 hover:text-red-600" title="O'chirish">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </button>
        </div>

        <!-- Header -->
        <div class="flex items-center space-x-3 mb-4">
            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium" :class="getTypeColor(announcement.type)">
                {{ getTypeLabel(announcement.type) }}
            </span>
            <span class="text-sm text-gray-500">
                {{ format(new Date(announcement.created_at), 'dd MMM, yyyy HH:mm', { locale: uz }) }}
            </span>
            <span v-if="announcement.is_pinned" class="text-indigo-600">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z" />
                </svg>
            </span>
        </div>

        <!-- Content -->
        <h3 class="text-lg font-medium text-gray-900 mb-2">{{ announcement.title }}</h3>
        <div class="text-gray-600 whitespace-pre-wrap">{{ announcement.content }}</div>

        <!-- Edit Modal -->
        <Modal :show="showEditModal" @close="showEditModal = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">E'lonni tahrirlash</h2>

                <form @submit.prevent="updateAnnouncement" class="space-y-4">
                    <!-- Title -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sarlavha</label>
                        <input v-model="editForm.title" type="text"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required />
                    </div>

                    <!-- Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Turi</label>
                        <select v-model="editForm.type"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="info">Ma'lumot</option>
                            <option value="update">Yangilanish</option>
                            <option value="important">Muhim</option>
                            <option value="promotion">Aksiya</option>
                        </select>
                    </div>

                    <!-- Content -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Matn</label>
                        <textarea v-model="editForm.content" rows="5"
                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required></textarea>
                    </div>

                    <!-- Is Pinned -->
                    <div class="flex items-center">
                        <input v-model="editForm.is_pinned" type="checkbox" id="edit_is_pinned"
                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded" />
                        <label for="edit_is_pinned" class="ml-2 block text-sm text-gray-900">
                            Yuqoriga qadash
                        </label>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" @click="showEditModal = false"
                            class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Bekor qilish
                        </button>
                        <button type="submit" :disabled="editForm.processing"
                            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50">
                            Saqlash
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </div>
</template>
