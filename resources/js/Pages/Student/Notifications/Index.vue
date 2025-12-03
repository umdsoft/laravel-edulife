<template>
    <StudentLayout title="Bildirishnomalar">
        <div class="max-w-4xl mx-auto py-6 px-4">

            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="p-6 border-b flex items-center justify-between">
                    <h1 class="text-2xl font-bold">🔔 Bildirishnomalar</h1>
                    <button @click="markAllRead" v-if="unreadCount > 0" class="text-sm text-blue-600 hover:underline">
                        Hammasini o'qilgan qilish
                    </button>
                </div>

                <div v-if="notifications.data.length === 0" class="p-12 text-center text-gray-500">
                    Bildirishnomalar yo'q
                </div>

                <div v-else class="divide-y">
                    <div v-for="notification in notifications.data" :key="notification.id"
                        :class="['p-4 hover:bg-gray-50 cursor-pointer', { 'bg-blue-50': !notification.read_at }]"
                        @click="markRead(notification.id)">
                        <div class="flex items-start gap-4">
                            <div class="text-2xl">🔔</div>
                            <div class="flex-1">
                                <p class="font-semibold">{{ notification.data.title || 'Notification' }}</p>
                                <p class="text-sm text-gray-600">{{ notification.data.message || '' }}</p>
                                <p class="text-xs text-gray-500 mt-1">{{ formatDate(notification.created_at) }}</p>
                            </div>
                            <button @click.stop="deleteNotification(notification.id)"
                                class="text-gray-400 hover:text-red-600">
                                ×
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="notifications.links" class="p-4 border-t flex gap-2">
                    <Link v-for="link in notifications.links" :key="link.label" :href="link.url"
                        :class="['px-3 py-1 rounded', link.active ? 'bg-purple-600 text-white' : 'bg-gray-100']"
                        v-html="link.label">
                    </Link>
                </div>
            </div>

        </div>
    </StudentLayout>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    notifications: Object,
    unreadCount: Number,
    filters: Object,
});

const markRead = (id) => {
    router.post(route('student.notifications.read', id));
};

const markAllRead = () => {
    router.post(route('student.notifications.read-all'));
};

const deleteNotification = (id) => {
    router.delete(route('student.notifications.destroy', id));
};

const formatDate = (date) => {
    return new Date(date).toLocaleString('uz-UZ');
};
</script>
