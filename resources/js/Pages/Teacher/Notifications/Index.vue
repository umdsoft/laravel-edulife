<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import { formatDistanceToNow } from 'date-fns';
import { uz } from 'date-fns/locale';

const props = defineProps({
    notifications: Object,
    unreadCount: Number,
});

const markAsRead = (notification) => {
    if (!notification.read_at) {
        router.post(route('teacher.notifications.mark-read', notification.id), {}, {
            preserveScroll: true,
        });
    }
};

const markAllAsRead = () => {
    router.post(route('teacher.notifications.mark-all-read'), {}, {
        preserveScroll: true,
    });
};

const getNotificationIcon = (type) => {
    switch (type) {
        case 'question_answered':
            return {
                icon: 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z',
                color: 'text-blue-500 bg-blue-100',
            };
        case 'course_announcement':
            return {
                icon: 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z',
                color: 'text-yellow-500 bg-yellow-100',
            };
        default:
            return {
                icon: 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
                color: 'text-gray-500 bg-gray-100',
            };
    }
};

const getNotificationLink = (notification) => {
    if (notification.data.type === 'question_answered') {
        return route('teacher.courses.qna.index', notification.data.course_id);
    }
    if (notification.data.type === 'course_announcement') {
        return route('teacher.courses.announcements.index', notification.data.course_id);
    }
    return '#';
};
</script>

<template>
    <TeacherLayout>

        <Head title="Bildirishnomalar" />

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Bildirishnomalar</h1>

                <button v-if="unreadCount > 0" @click="markAllAsRead"
                    class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                    Barchasini o'qilgan deb belgilash
                </button>
            </div>

            <div class="bg-white shadow overflow-hidden sm:rounded-md">
                <ul role="list" class="divide-y divide-gray-200">
                    <li v-for="notification in notifications.data" :key="notification.id">
                        <Link :href="getNotificationLink(notification)" @click="markAsRead(notification)"
                            class="block hover:bg-gray-50 transition duration-150 ease-in-out"
                            :class="{ 'bg-blue-50': !notification.read_at }">
                        <div class="px-4 py-4 sm:px-6 flex items-start space-x-4">
                            <div class="flex-shrink-0 h-10 w-10 rounded-full flex items-center justify-center"
                                :class="getNotificationIcon(notification.data.type).color">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        :d="getNotificationIcon(notification.data.type).icon" />
                                </svg>
                            </div>

                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">
                                    {{ notification.data.title || notification.data.message }}
                                </p>
                                <p class="text-sm text-gray-500">
                                    {{ notification.data.course_title }}
                                </p>
                                <p class="mt-1 text-xs text-gray-400">
                                    {{ formatDistanceToNow(new Date(notification.created_at), {
                                        addSuffix: true, locale:
                                            uz
                                    }) }}
                                </p>
                            </div>

                            <div v-if="!notification.read_at" class="flex-shrink-0">
                                <span class="inline-block h-2 w-2 rounded-full bg-blue-600"></span>
                            </div>
                        </div>
                        </Link>
                    </li>

                    <li v-if="notifications.data.length === 0" class="px-4 py-12 text-center text-gray-500">
                        Bildirishnomalar yo'q
                    </li>
                </ul>
            </div>

            <div class="mt-6">
                <Pagination :data="notifications" />
            </div>
        </div>
    </TeacherLayout>
</template>
