<template>
    <StudentLayout title="Bildirishnomalar">
        <Head title="Bildirishnomalar" />

        <PageHeader title="Bildirishnomalar"
            :description="unreadCount > 0 ? `${unreadCount} ta o'qilmagan xabar` : 'Barcha xabarlar o\'qilgan'">
            <template #actions>
                <Button v-if="unreadCount > 0" variant="secondary" :icon-left="CheckIcon" :loading="markingAll" @click="markAllRead">
                    Barchasini o'qilgan deb belgilash
                </Button>
            </template>
            <template #tabs>
                <Tabs :tabs="tabs" label="Bildirishnomalar filtri" />
            </template>
        </PageHeader>

        <Card padding="none">
            <EmptyState v-if="!notifications?.data?.length" :icon="BellIcon" size="sm"
                :title="filters?.status === 'unread' ? 'O\'qilmagan xabar yo\'q' : 'Bildirishnomalar yo\'q'"
                description="Yangi xabarlar shu yerda paydo bo'ladi." />

            <ul v-else class="divide-y divide-border">
                <li v-for="notification in notifications.data" :key="notification.id"
                    class="group relative flex items-start gap-3 px-4 py-4 transition-colors duration-150 sm:px-6"
                    :class="isUnread(notification) ? 'bg-brand-50' : 'hover:bg-surface-2'">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md"
                        :class="iconMeta(notification).chip" aria-hidden="true">
                        <component :is="iconMeta(notification).icon" class="h-5 w-5" />
                    </span>

                    <button type="button" class="focus-ring min-w-0 flex-1 rounded-sm text-left"
                        :aria-label="`${titleOf(notification)}${isUnread(notification) ? ' (o\'qilmagan) — o\'qilgan deb belgilash' : ''}`"
                        @click="onOpen(notification)">
                        <span class="flex items-center gap-2">
                            <span v-if="isUnread(notification)" class="h-2 w-2 shrink-0 rounded-full bg-brand-600" aria-hidden="true" />
                            <span class="truncate text-sm" :class="isUnread(notification) ? 'font-semibold text-fg' : 'font-medium text-fg'">
                                {{ titleOf(notification) }}
                            </span>
                        </span>
                        <span v-if="bodyOf(notification)" class="mt-0.5 block text-13 text-fg-muted">{{ bodyOf(notification) }}</span>
                        <time class="mt-1 block text-xs tabular-nums text-fg-subtle" :datetime="notification.created_at"
                            :title="formatDate(notification.created_at)">
                            {{ relativeTime(notification.created_at) }}
                        </time>
                    </button>

                    <div class="flex shrink-0 items-center gap-1">
                        <Button v-if="internalUrl(notification)" size="sm" variant="ghost" :href="internalUrl(notification)">Ochish</Button>
                        <IconButton :icon="TrashIcon" label="O'chirish" size="sm" variant="ghost"
                            @click="deleteNotification(notification.id)" />
                    </div>
                </li>
            </ul>

            <Pagination v-if="notifications?.data?.length && notifications?.links" :data="notifications" />
        </Card>
    </StudentLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    AcademicCapIcon, BellIcon, BoltIcon, ChatBubbleLeftRightIcon, CheckIcon, CreditCardIcon, ExclamationTriangleIcon,
    InformationCircleIcon, MegaphoneIcon, TrashIcon, TrophyIcon,
} from '@heroicons/vue/24/outline';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Button, Card, EmptyState, IconButton, PageHeader, Pagination, Tabs } from '@/Components/UI';

const props = defineProps({
    notifications: Object,
    unreadCount: Number,
    filters: Object,
});

const markingAll = ref(false);

const markRead = (id) => {
    router.post(route('student.notifications.read', id), {}, { preserveScroll: true });
};

const markAllRead = () => {
    markingAll.value = true;
    router.post(route('student.notifications.read-all'), {}, {
        preserveScroll: true,
        onFinish: () => { markingAll.value = false; },
    });
};

const deleteNotification = (id) => {
    router.delete(route('student.notifications.destroy', id), { preserveScroll: true });
};

// Presentation
const tabs = computed(() => [
    { key: 'all', label: 'Barchasi', href: route('student.notifications.index'), active: !props.filters?.status },
    { key: 'unread', label: 'O\'qilmagan', count: props.unreadCount || undefined, href: route('student.notifications.index', { status: 'unread' }), active: props.filters?.status === 'unread' },
    { key: 'read', label: 'O\'qilgan', href: route('student.notifications.index', { status: 'read' }), active: props.filters?.status === 'read' },
]);

const isUnread = (n) => !(n.is_read || n.read_at);
const titleOf = (n) => n.title || n.data?.title || 'Bildirishnoma';
const bodyOf = (n) => n.body || n.data?.message || n.data?.body || '';

const onOpen = (n) => {
    if (isUnread(n)) markRead(n.id);
};

const internalUrl = (n) => {
    const u = n.action_url || n.data?.action_url || n.data?.url;
    if (!u || typeof u !== 'string') return null;
    if (u.startsWith('/') && !u.startsWith('//')) return u;
    try {
        const parsed = new URL(u);
        return parsed.origin === window.location.origin ? parsed.pathname + parsed.search : null;
    } catch {
        return null;
    }
};

const TYPES = [
    { match: /announce|course|lesson/i, icon: MegaphoneIcon, chip: 'bg-info-50 text-info' },
    { match: /question|answer|comment|reply|message/i, icon: ChatBubbleLeftRightIcon, chip: 'bg-brand-50 text-brand' },
    { match: /achiev|reward|level|mission/i, icon: TrophyIcon, chip: 'bg-warning-50 text-warning' },
    { match: /battle|tournament/i, icon: BoltIcon, chip: 'bg-brand-50 text-brand' },
    { match: /payment|coin|order|purchase/i, icon: CreditCardIcon, chip: 'bg-success-50 text-success' },
    { match: /exam|test|certificate|result/i, icon: AcademicCapIcon, chip: 'bg-success-50 text-success' },
    { match: /warning|alert|error/i, icon: ExclamationTriangleIcon, chip: 'bg-danger-50 text-danger' },
    { match: /system|info/i, icon: InformationCircleIcon, chip: 'bg-surface-2 text-fg-muted' },
];
const iconMeta = (n) => {
    const key = `${n.type || ''} ${n.data?.type || ''}`;
    const hit = TYPES.find((t) => t.match.test(key));
    if (n.priority === 'high' && !hit) return { icon: ExclamationTriangleIcon, chip: 'bg-warning-50 text-warning' };
    return hit || { icon: BellIcon, chip: 'bg-surface-2 text-fg-muted' };
};

const pad = (n) => String(n).padStart(2, '0');
const formatDate = (date) => {
    if (!date) return '';
    const d = new Date(date);
    if (Number.isNaN(d.getTime())) return '';
    return `${pad(d.getDate())}.${pad(d.getMonth() + 1)}.${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
};
const relativeTime = (date) => {
    if (!date) return '';
    const d = new Date(date);
    if (Number.isNaN(d.getTime())) return '';
    const diff = Math.max(0, Math.floor((Date.now() - d.getTime()) / 1000));
    if (diff < 60) return 'hozirgina';
    if (diff < 3600) return `${Math.floor(diff / 60)} daqiqa oldin`;
    if (diff < 86400) return `${Math.floor(diff / 3600)} soat oldin`;
    if (diff < 86400 * 7) return `${Math.floor(diff / 86400)} kun oldin`;
    return formatDate(date).slice(0, 10);
};
</script>
