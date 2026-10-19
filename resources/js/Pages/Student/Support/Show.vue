<script setup>
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { PaperAirplaneIcon, LockClosedIcon, StarIcon as StarOutline } from '@heroicons/vue/24/outline';
import { StarIcon } from '@heroicons/vue/20/solid';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Alert, Avatar, Badge, Button, Card, PageHeader, Textarea } from '@/Components/UI';

const props = defineProps({
    ticket: Object,
});

const STATUS = { open: 'info', in_progress: 'brand', waiting_user: 'warning', resolved: 'success', closed: 'neutral' };
const isClosed = computed(() => ['closed', 'resolved'].includes(props.ticket.status));
const messages = computed(() => props.ticket.messages || []);

const pad = (n) => String(n).padStart(2, '0');
const formatDateTime = (value) => {
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return '';
    return `${pad(d.getDate())}.${pad(d.getMonth() + 1)}.${d.getFullYear()}, ${pad(d.getHours())}:${pad(d.getMinutes())}`;
};
const authorName = (m) => (m.is_from_support ? 'Yordam xizmati' : [m.user?.first_name, m.user?.last_name].filter(Boolean).join(' ') || 'Siz');

const reply = useForm({ content: '' });
const sendReply = () => reply.post(route('student.support.reply', props.ticket.id), {
    preserveScroll: true,
    onSuccess: () => reply.reset(),
});

const closing = ref(false);
const closeTicket = () => router.post(route('student.support.close', props.ticket.id), {}, {
    preserveScroll: true,
    onStart: () => (closing.value = true),
    onFinish: () => (closing.value = false),
});

const rating = useForm({ rating: props.ticket.satisfaction_rating || 0, feedback: props.ticket.feedback || '' });
const rate = () => rating.post(route('student.support.rate', props.ticket.id), { preserveScroll: true });
</script>

<template>
    <StudentLayout :title="ticket.subject">
        <Head :title="ticket.subject" />

        <PageHeader :title="ticket.subject"
            :breadcrumbs="[{ label: 'Yordam', href: route('student.support.index') }, { label: ticket.ticket_number }]">
            <template #meta>
                <Badge :variant="STATUS[ticket.status] || 'neutral'" dot>{{ ticket.status_label }}</Badge>
                <Badge variant="neutral">{{ ticket.category_label }}</Badge>
                <span class="font-mono text-xs text-fg-subtle">{{ ticket.ticket_number }}</span>
            </template>
            <template v-if="!isClosed" #actions>
                <Button variant="secondary" :icon-left="LockClosedIcon" :loading="closing" @click="closeTicket">So'rovni yopish</Button>
            </template>
        </PageHeader>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-4 lg:col-span-2">
                <ol class="space-y-4" aria-label="Xabarlar">
                    <li v-for="m in messages" :key="m.id" :class="['flex gap-3', m.is_from_support ? '' : 'flex-row-reverse']">
                        <Avatar :src="m.is_from_support ? null : m.user?.avatar" :name="authorName(m)" size="sm" class="mt-1" />
                        <div :class="['max-w-[85%] rounded-lg border px-4 py-3', m.is_from_support ? 'border-brand-200 bg-brand-50' : 'border-border bg-surface']">
                            <p class="text-13 font-medium text-fg">{{ authorName(m) }}</p>
                            <p class="mt-1 whitespace-pre-wrap text-sm text-fg">{{ m.content }}</p>
                            <p class="mt-1.5 text-xs tabular-nums text-fg-subtle">{{ formatDateTime(m.created_at) }}</p>
                        </div>
                    </li>
                </ol>

                <Card v-if="!isClosed" title="Javob yozish">
                    <form class="space-y-4" @submit.prevent="sendReply">
                        <Textarea v-model="reply.content" label="Xabar" :rows="4" autoresize :maxlength="2000" show-count required
                            hint="Kamida 10 ta belgi" :error="reply.errors.content" />
                        <div class="flex justify-end">
                            <Button type="submit" :icon-left="PaperAirplaneIcon" :loading="reply.processing">Yuborish</Button>
                        </div>
                    </form>
                </Card>
                <Alert v-else variant="info">Bu so'rov yopilgan. Yangi savol bo'lsa, yangi so'rov yarating.</Alert>
            </div>

            <aside class="space-y-6">
                <Card title="Ma'lumot">
                    <dl class="divide-y divide-border text-sm">
                        <div class="flex justify-between gap-4 py-2.5"><dt class="text-fg-muted">Muhimlik</dt><dd class="font-medium">{{ ticket.priority_label }}</dd></div>
                        <div class="flex justify-between gap-4 py-2.5"><dt class="text-fg-muted">Yaratilgan</dt><dd class="font-medium tabular-nums">{{ formatDateTime(ticket.created_at) }}</dd></div>
                        <div class="flex justify-between gap-4 py-2.5"><dt class="text-fg-muted">Xabarlar</dt><dd class="font-medium tabular-nums">{{ messages.length }}</dd></div>
                    </dl>
                </Card>

                <Card v-if="isClosed" title="Yordamni baholang">
                    <form class="space-y-4" @submit.prevent="rate">
                        <div class="flex gap-1" role="radiogroup" aria-label="Baho">
                            <button v-for="n in 5" :key="n" type="button" role="radio" :aria-checked="rating.rating === n" :aria-label="`${n} yulduz`"
                                class="focus-ring rounded-sm p-0.5" @click="rating.rating = n">
                                <StarIcon v-if="n <= rating.rating" class="h-7 w-7 text-warning" aria-hidden="true" />
                                <StarOutline v-else class="h-7 w-7 text-fg-subtle" aria-hidden="true" />
                            </button>
                        </div>
                        <Textarea v-model="rating.feedback" label="Izoh" optional :rows="2" :maxlength="500" :error="rating.errors.feedback" />
                        <Button type="submit" block :loading="rating.processing" :disabled="!rating.rating">Baholash</Button>
                    </form>
                </Card>
            </aside>
        </div>
    </StudentLayout>
</template>
