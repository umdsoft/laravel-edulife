<template>
    <StudentLayout title="Yordam">
        <Head title="Yordam" />

        <PageHeader title="Yordam" description="Savolingiz bo'lsa so'rov yuboring — javobni shu yerda kuzatasiz.">
            <template #actions>
                <Button variant="secondary" :icon-left="QuestionMarkCircleIcon" :href="route('student.support.faq')">Ko'p so'raladigan savollar</Button>
                <Button :icon-left="PlusIcon" :href="route('student.support.create')">Yangi so'rov</Button>
            </template>
        </PageHeader>

        <div class="space-y-6">
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <StatCard label="Ochiq so'rovlar" :value="stats?.open ?? 0" :icon="InboxIcon" tone="info" />
                <StatCard label="Hal qilingan" :value="stats?.resolved ?? 0" :icon="CheckCircleIcon" tone="success" />
                <StatCard label="Jami so'rovlar" :value="stats?.total ?? 0" :icon="LifebuoyIcon" tone="neutral" />
            </div>

            <Card title="So'rovlarim" padding="none">
                <EmptyState v-if="!tickets?.data?.length" :icon="LifebuoyIcon" size="sm" title="Hech qanday so'rov yo'q"
                    description="Muammo yoki savol bo'lsa, yangi so'rov yarating.">
                    <Button size="sm" :icon-left="PlusIcon" :href="route('student.support.create')">Yangi so'rov</Button>
                </EmptyState>

                <ul v-else class="divide-y divide-border">
                    <li v-for="ticket in tickets.data" :key="ticket.id">
                        <Link :href="route('student.support.show', ticket.id)"
                            class="focus-ring group flex items-start gap-3 px-5 py-4 transition-colors duration-150 hover:bg-surface-2 sm:px-6">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-mono text-xs text-fg-subtle">{{ ticket.ticket_number }}</span>
                                    <Badge size="sm" :variant="statusVariant(ticket.status)" dot>{{ ticket.status_label || ticket.status }}</Badge>
                                </div>
                                <h3 class="mt-1 truncate text-sm font-semibold text-fg">{{ ticket.subject }}</h3>
                                <p v-if="ticket.description" class="mt-0.5 line-clamp-2 text-13 text-fg-muted">{{ ticket.description }}</p>
                                <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-fg-subtle">
                                    <span v-if="ticket.category_label" class="flex items-center gap-1">
                                        <FolderIcon class="h-4 w-4" aria-hidden="true" />{{ ticket.category_label }}
                                    </span>
                                    <span class="flex items-center gap-1 tabular-nums">
                                        <ChatBubbleLeftRightIcon class="h-4 w-4" aria-hidden="true" />{{ ticket.messages_count || 0 }} xabar
                                    </span>
                                    <span class="tabular-nums">{{ formatDate(ticket.created_at) }}</span>
                                </div>
                            </div>
                            <ChevronRightIcon class="mt-1 h-5 w-5 shrink-0 text-fg-subtle group-hover:text-fg" aria-hidden="true" />
                        </Link>
                    </li>
                </ul>

                <Pagination v-if="tickets?.data?.length && tickets?.links" :data="tickets" />
            </Card>
        </div>
    </StudentLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import {
    ChatBubbleLeftRightIcon, CheckCircleIcon, FolderIcon, InboxIcon, LifebuoyIcon, PlusIcon, QuestionMarkCircleIcon,
} from '@heroicons/vue/24/outline';
import { ChevronRightIcon } from '@heroicons/vue/20/solid';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Badge, Button, Card, EmptyState, PageHeader, Pagination, StatCard } from '@/Components/UI';

const props = defineProps({
    tickets: Object,
    stats: Object,
});

const STATUS = {
    open: 'info',
    in_progress: 'brand',
    waiting_user: 'warning',
    resolved: 'success',
    closed: 'neutral',
};
const statusVariant = (status) => STATUS[status] || 'neutral';

const pad = (n) => String(n).padStart(2, '0');
const formatDate = (date) => {
    if (!date) return '—';
    const d = new Date(date);
    if (Number.isNaN(d.getTime())) return '—';
    return `${pad(d.getDate())}.${pad(d.getMonth() + 1)}.${d.getFullYear()}`;
};
</script>
