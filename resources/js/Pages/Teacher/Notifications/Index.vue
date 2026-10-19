<script setup>
import { Head, router } from '@inertiajs/vue3';
import { BellIcon, ChatBubbleLeftRightIcon, MegaphoneIcon, CheckIcon, BellSlashIcon } from '@heroicons/vue/24/outline';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Card from '@/Components/UI/Card.vue';
import Button from '@/Components/UI/Button.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import { formatDateTime, timeAgo } from '@/Components/Teacher/format';

const props = defineProps({
    notifications: Object,
    unreadCount: Number,
});

const markAllAsRead = () => {
    router.post(route('teacher.notifications.mark-all-read'), {}, {
        preserveScroll: true,
    });
};

// Rows come from the app's notifications table (type, title, body, action_url, is_read, data)
const typeOf = (n) => n.type || n.data?.type;
const isUnread = (n) => !(n.is_read || n.read_at);
const titleOf = (n) => n.title || n.data?.title || n.data?.message || 'Bildirishnoma';
const bodyOf = (n) => (n.title ? n.body : (n.data?.title ? n.data?.message : null)) || null;

const getNotificationIcon = (type) => {
    switch (type) {
        case 'new_question':
        case 'question_answered':
            return { icon: ChatBubbleLeftRightIcon, color: 'bg-info-50 text-info' };
        case 'course_announcement':
            return { icon: MegaphoneIcon, color: 'bg-warning-50 text-warning' };
        case 'course_approved':
            return { icon: CheckIcon, color: 'bg-success-50 text-success' };
        case 'course_rejected':
            return { icon: BellSlashIcon, color: 'bg-danger-50 text-danger' };
        default:
            return { icon: BellIcon, color: 'bg-surface-2 text-fg-muted' };
    }
};

// null → the notification has no page to open (rendered as a plain row, never href="#")
const getNotificationLink = (notification) => {
    if (notification.action_url) return notification.action_url;
    const data = notification.data || {};
    const type = typeOf(notification);
    if (!data.course_id) return null;
    if (type === 'new_question' || type === 'question_answered') return route('teacher.courses.qna.index', data.course_id);
    if (type === 'course_announcement') return route('teacher.courses.announcements.index', data.course_id);
    if (type === 'course_approved' || type === 'course_rejected') return route('teacher.courses.edit', data.course_id);
    return null;
};

// Mark as read first, then open the target page (two parallel visits would cancel each other)
const open = (notification, event) => {
    const href = getNotificationLink(notification);
    if (href && (event.metaKey || event.ctrlKey || event.shiftKey || event.button === 1)) return; // new tab: let the browser handle it
    if (href) event.preventDefault();
    if (isUnread(notification)) {
        router.post(route('teacher.notifications.mark-read', notification.id), {}, {
            preserveScroll: true,
            onFinish: () => href && router.visit(href),
        });
    } else if (href) {
        router.visit(href);
    }
};
</script>

<template>
    <TeacherLayout>
        <Head title="Bildirishnomalar" />

        <PageHeader title="Bildirishnomalar"
            :description="unreadCount > 0 ? `${unreadCount} ta o'qilmagan bildirishnoma` : 'Hammasi o\'qilgan'">
            <template #actions>
                <Button v-if="unreadCount > 0" variant="secondary" :icon-left="CheckIcon" @click="markAllAsRead">
                    Barchasini o'qilgan deb belgilash
                </Button>
            </template>
        </PageHeader>

        <Card class="max-w-3xl" padding="none">
            <ul v-if="notifications.data.length" role="list" class="divide-y divide-border">
                <li v-for="notification in notifications.data" :key="notification.id">
                    <component :is="getNotificationLink(notification) ? 'a' : 'button'"
                        v-bind="getNotificationLink(notification) ? { href: getNotificationLink(notification) } : { type: 'button' }"
                        :class="[
                            'focus-ring flex w-full items-start gap-3 px-4 py-4 text-left transition-colors duration-150 hover:bg-surface-2/60 sm:gap-4 sm:px-6',
                            isUnread(notification) ? 'bg-brand-50/40' : '',
                        ]" @click="open(notification, $event)">
                        <span :class="['flex h-9 w-9 shrink-0 items-center justify-center rounded-full', getNotificationIcon(typeOf(notification)).color]">
                            <component :is="getNotificationIcon(typeOf(notification)).icon" class="h-5 w-5" aria-hidden="true" />
                        </span>

                        <span class="min-w-0 flex-1">
                            <span :class="['block text-sm text-fg', isUnread(notification) ? 'font-semibold' : 'font-medium']">
                                {{ titleOf(notification) }}
                            </span>
                            <span v-if="bodyOf(notification)" class="mt-0.5 block line-clamp-2 text-sm text-fg-muted">
                                {{ bodyOf(notification) }}
                            </span>
                            <span v-if="notification.data?.course_title" class="mt-0.5 block truncate text-13 text-fg-subtle">
                                {{ notification.data.course_title }}
                            </span>
                            <time :datetime="notification.created_at" :title="formatDateTime(notification.created_at)" class="mt-1 block text-xs text-fg-subtle">
                                {{ timeAgo(notification.created_at) }}
                            </time>
                        </span>

                        <span v-if="isUnread(notification)" class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-brand-600" aria-hidden="true" />
                        <span v-if="isUnread(notification)" class="sr-only">O'qilmagan</span>
                    </component>
                </li>
            </ul>

            <EmptyState v-else :icon="BellSlashIcon" title="Bildirishnomalar yo'q"
                description="Savollarga javoblar va kurs yangiliklari shu yerda ko'rinadi." />

            <Pagination :data="notifications" />
        </Card>
    </TeacherLayout>
</template>
