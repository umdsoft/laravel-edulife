<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import AnnouncementCard from '@/Components/Teacher/AnnouncementCard.vue';
import Modal from '@/Components/UI/Modal.vue';
import Pagination from '@/Components/UI/Pagination.vue';

const props = defineProps({
    course: Object,
    announcements: Object,
});

const showCreateModal = ref(false);
const form = useForm({
    title: '',
    content: '',
    type: 'info',
    is_pinned: false,
    send_notification: true,
    publish_now: true,
    published_at: '',
});

const submit = () => {
    form.post(route('teacher.courses.announcements.store', props.course.id), {
        onSuccess: () => {
            form.reset();
            showCreateModal.value = false;
        },
    });
};
</script>

<template>
    <TeacherLayout>

        <Head :title="`${course.title} - E'lonlar`" />

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-6 flex justify-between items-center">
                <div>
                    <div class="flex items-center text-sm text-gray-500 mb-1">
                        <Link :route="route('teacher.courses.index')" class="hover:text-indigo-600">Kurslar</Link>
                        <svg class="h-4 w-4 mx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                        <Link :route="route('teacher.courses.edit', course.id)" class="hover:text-indigo-600">{{
                            course.title }}</Link>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900">E'lonlar</h1>
                </div>

                <button @click="showCreateModal = true"
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    E'lon yaratish
                </button>
            </div>

            <!-- Announcements List -->
            <div class="space-y-6">
                <AnnouncementCard v-for="announcement in announcements.data" :key="announcement.id"
                    :announcement="announcement" :course="course" />

                <div v-if="announcements.data.length === 0" class="text-center py-12 bg-white rounded-lg shadow">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">E'lonlar yo'q</h3>
                    <p class="mt-1 text-sm text-gray-500">Talabalar uchun yangi e'lon yarating.</p>
                    <div class="mt-6">
                        <button @click="showCreateModal = true"
                            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4v16m8-8H4" />
                            </svg>
                            E'lon yaratish
                        </button>
                    </div>
                </div>

                <Pagination :data="announcements" />
            </div>

            <!-- Create Modal -->
            <Modal :show="showCreateModal" @close="showCreateModal = false">
                <div class="p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Yangi e'lon yaratish</h2>

                    <form @submit.prevent="submit" class="space-y-4">
                        <!-- Title -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sarlavha</label>
                            <input v-model="form.title" type="text"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required placeholder="E'lon sarlavhasi" />
                            <div v-if="form.errors.title" class="text-red-500 text-xs mt-1">{{ form.errors.title }}
                            </div>
                        </div>

                        <!-- Type -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Turi</label>
                            <select v-model="form.type"
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
                            <textarea v-model="form.content" rows="5"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                required placeholder="E'lon matni..."></textarea>
                            <div v-if="form.errors.content" class="text-red-500 text-xs mt-1">{{ form.errors.content }}
                            </div>
                        </div>

                        <!-- Options -->
                        <div class="space-y-3">
                            <div class="flex items-center">
                                <input v-model="form.is_pinned" type="checkbox" id="is_pinned"
                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded" />
                                <label for="is_pinned" class="ml-2 block text-sm text-gray-900">
                                    Yuqoriga qadash
                                </label>
                            </div>

                            <div class="flex items-center">
                                <input v-model="form.send_notification" type="checkbox" id="send_notification"
                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded" />
                                <label for="send_notification" class="ml-2 block text-sm text-gray-900">
                                    Talabalarga bildirishnoma yuborish
                                </label>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex justify-end space-x-3 mt-6">
                            <button type="button" @click="showCreateModal = false"
                                class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Bekor qilish
                            </button>
                            <button type="submit" :disabled="form.processing"
                                class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50">
                                Yaratish
                            </button>
                        </div>
                    </form>
                </div>
            </Modal>
        </div>
    </TeacherLayout>
</template>
