<script setup>
import { computed } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { PaperAirplaneIcon } from '@heroicons/vue/24/outline';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Avatar, Badge, Button, Card, Checkbox, PageHeader, Select, Textarea } from '@/Components/UI';

const props = defineProps({
    ticket: Object,
    statuses: { type: Object, default: () => ({}) }, // { open: 'Ochiq', ... }
});

const VARIANT = { open: 'info', in_progress: 'brand', waiting_user: 'warning', resolved: 'success', closed: 'neutral' };
const statusOptions = computed(() => Object.entries(props.statuses).map(([value, label]) => ({ value, label })));
const messages = computed(() => props.ticket.messages || []);

const pad = (n) => String(n).padStart(2, '0');
const date = (v) => {
    const d = new Date(v);
    return Number.isNaN(d.getTime()) ? '' : `${pad(d.getDate())}.${pad(d.getMonth() + 1)}.${d.getFullYear()}, ${pad(d.getHours())}:${pad(d.getMinutes())}`;
};
const author = (m) => [m.user?.first_name, m.user?.last_name].filter(Boolean).join(' ') || (m.is_from_support ? 'Yordam xizmati' : "O'quvchi");

const reply = useForm({ content: '', is_internal_note: false });
const send = () => reply.post(route('admin.support.reply', props.ticket.id), { preserveScroll: true, onSuccess: () => reply.reset() });

const setStatus = (status) => router.patch(route('admin.support.status', props.ticket.id), { status }, { preserveScroll: true });
</script>

<template>
    <AdminLayout>
        <Head :title="ticket.subject" />

        <PageHeader :title="ticket.subject"
            :breadcrumbs="[{ label: 'Yordam so\'rovlari', href: route('admin.support.index') }, { label: ticket.ticket_number }]">
            <template #meta>
                <Badge :variant="VARIANT[ticket.status] || 'neutral'" dot>{{ ticket.status_label }}</Badge>
                <Badge variant="neutral">{{ ticket.category_label }}</Badge>
                <Badge variant="neutral">{{ ticket.priority_label }}</Badge>
            </template>
        </PageHeader>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-4 lg:col-span-2">
                <ol class="space-y-4" aria-label="Xabarlar">
                    <li v-for="m in messages" :key="m.id" :class="['flex gap-3', m.is_from_support ? 'flex-row-reverse' : '']">
                        <Avatar :src="m.user?.avatar" :name="author(m)" size="sm" class="mt-1" />
                        <div :class="['max-w-[85%] rounded-lg border px-4 py-3',
                            m.is_internal_note ? 'border-warning-100 bg-warning-50' : m.is_from_support ? 'border-brand-200 bg-brand-50' : 'border-border bg-surface']">
                            <p class="flex items-center gap-2 text-13 font-medium text-fg">
                                {{ author(m) }}
                                <Badge v-if="m.is_internal_note" size="sm" variant="warning">Ichki eslatma</Badge>
                            </p>
                            <p class="mt-1 whitespace-pre-wrap text-sm text-fg">{{ m.content }}</p>
                            <p class="mt-1.5 text-xs tabular-nums text-fg-subtle">{{ date(m.created_at) }}</p>
                        </div>
                    </li>
                </ol>

                <Card title="Javob yozish">
                    <form class="space-y-4" @submit.prevent="send">
                        <Textarea v-model="reply.content" label="Xabar" required :rows="4" autoresize :maxlength="5000" :error="reply.errors.content" />
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <Checkbox v-model="reply.is_internal_note" label="Ichki eslatma" description="O'quvchiga ko'rinmaydi" />
                            <Button type="submit" :icon-left="PaperAirplaneIcon" :loading="reply.processing">
                                {{ reply.is_internal_note ? 'Saqlash' : 'Yuborish' }}
                            </Button>
                        </div>
                    </form>
                </Card>
            </div>

            <aside class="space-y-6">
                <Card title="O'quvchi">
                    <div class="flex items-center gap-3">
                        <Avatar :src="ticket.user?.avatar" :name="ticket.user?.full_name" size="md" />
                        <div class="min-w-0">
                            <p class="truncate font-medium text-fg">{{ ticket.user?.full_name }}</p>
                            <p class="truncate text-13 tabular-nums text-fg-subtle">{{ ticket.user?.phone || ticket.user?.email }}</p>
                        </div>
                    </div>
                    <Button v-if="ticket.user" class="mt-4" size="sm" variant="secondary" block :href="route('admin.users.show', ticket.user.id)">Profilni ochish</Button>
                </Card>
                <Card title="Holat">
                    <Select :model-value="ticket.status" :options="statusOptions" label="So'rov holati" @change="setStatus" />
                    <p v-if="ticket.satisfaction_rating" class="mt-3 text-13 text-fg-muted">O'quvchi bahosi: {{ ticket.satisfaction_rating }}/5</p>
                </Card>
            </aside>
        </div>
    </AdminLayout>
</template>
