<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowTrendingUpIcon, ChevronRightIcon, ClockIcon, DocumentChartBarIcon, PhoneIcon, UserPlusIcon, XMarkIcon,
} from '@heroicons/vue/24/outline';
import GuardianLayout from '@/Layouts/GuardianLayout.vue';
import { Alert, Avatar, Badge, Button, Card, Checkbox, EmptyState, IconButton, Input, Modal, PageHeader, Select } from '@/Components/UI';
import GrowthChart from '@/Components/Exam/People/GrowthChart.vue';
import TopicMap from '@/Components/Exam/People/TopicMap.vue';
import Recommendations from '@/Components/Exam/People/Recommendations.vue';
import { formatDelta, formatPercent, formatWeek } from '@/Components/Exam/People/mastery';

const props = defineProps({
    children: { type: Array, default: () => [] },
    pending: { type: Array, default: () => [] },
    rejected: { type: Array, default: () => [] },
    reports: { type: Array, default: () => [] },
    relations: { type: Array, default: () => [] },
});

const showLink = ref(props.children.length === 0 && props.pending.length === 0);
const form = useForm({ phone: '+998', relation: 'mother', consent: false });
const submit = () => form.post(route('guardian.links.store'), {
    preserveScroll: true,
    onSuccess: () => { form.reset(); showLink.value = false; },
});
const cancel = (id) => router.delete(route('guardian.links.destroy', id), { preserveScroll: true });

const history = (child) => child.subjects.flatMap((s) => s.history);
const hasHistory = (child) => history(child).length > 0;
const names = (child) => Object.fromEntries(child.subjects.map((s) => [s.code, s.name]));
const fullName = (c) => [c.first_name, c.last_name].filter(Boolean).join(' ');
const unreadReports = computed(() => props.reports.filter((r) => r.unread).length);
</script>

<template>
    <GuardianLayout title="Farzandlarim">
        <Head title="Farzandlarim" />

        <PageHeader title="Farzandlarim" description="Shanba imtihoni natijalari, o'sish va keyingi haftaga tavsiyalar">
            <template #actions>
                <Button :icon-left="UserPlusIcon" @click="showLink = true">Farzandni bog'lash</Button>
            </template>
        </PageHeader>

        <div class="space-y-6">
            <Alert v-for="r in rejected" :key="r.id" variant="danger" title="So'rov rad etildi">
                {{ r.rejected_at }}<span v-if="r.reason"> — {{ r.reason }}</span>. Raqamni tekshirib, qayta yuboring.
            </Alert>

            <Card v-if="pending.length" title="Tasdiq kutilmoqda" description="Ustoz tasdiqlagach farzandingiz natijalari shu yerda ko'rinadi">
                <ul class="divide-y divide-border">
                    <li v-for="p in pending" :key="p.id" class="flex items-center gap-3 py-2.5 first:pt-0 last:pb-0">
                        <ClockIcon class="h-5 w-5 text-warning" aria-hidden="true" />
                        <span class="flex-1 text-sm text-fg">{{ p.name || 'O\'quvchi' }}</span>
                        <span class="text-xs text-fg-subtle tabular-nums">{{ p.requested_at }}</span>
                        <IconButton :icon="XMarkIcon" label="So'rovni bekor qilish" size="sm" @click="cancel(p.id)" />
                    </li>
                </ul>
            </Card>

            <EmptyState v-if="!children.length && !pending.length" :icon="UserPlusIcon" bordered
                title="Hali farzand bog'lanmagan"
                description="Farzandingiz ro'yxatdan o'tgan telefon raqamini kiriting. Ustoz tasdiqlagandan keyin natijalar ko'rinadi.">
                <Button :icon-left="UserPlusIcon" @click="showLink = true">Farzandni bog'lash</Button>
            </EmptyState>

            <Card v-for="child in children" :key="child.id">
                <template #header>
                    <div class="flex items-center gap-3">
                        <Avatar :name="fullName(child)" size="md" />
                        <div class="min-w-0 flex-1">
                            <h2 class="truncate text-base font-semibold text-fg">{{ fullName(child) }}</h2>
                            <div class="mt-0.5 flex flex-wrap gap-1.5">
                                <Badge v-for="s in child.subjects" :key="s.code" :variant="s.code === 'physics' ? 'physics' : 'math'" size="sm">{{ s.name }}</Badge>
                            </div>
                        </div>
                        <Button variant="ghost" size="sm" :href="route('guardian.children.show', child.id)" :icon-right="ChevronRightIcon">Batafsil</Button>
                    </div>
                </template>

                <div class="grid gap-6 lg:grid-cols-3">
                    <div class="space-y-4 lg:col-span-2">
                        <div class="grid grid-cols-2 gap-3">
                            <div v-for="s in child.subjects" :key="s.code" class="rounded-md bg-surface-2 p-3">
                                <p class="text-xs text-fg-subtle">{{ s.name }} · oxirgi natija</p>
                                <p class="mt-1 text-xl font-semibold text-fg tabular-nums">{{ s.last ? formatPercent(s.last.percent) : '—' }}</p>
                                <p v-if="s.last" class="mt-0.5 text-xs tabular-nums"
                                    :class="s.last.delta > 0 ? 'text-success' : 'text-fg-subtle'">
                                    <ArrowTrendingUpIcon v-if="s.last.delta > 0" class="mr-0.5 inline h-3.5 w-3.5" aria-hidden="true" />
                                    {{ s.last.delta !== null ? `${formatDelta(s.last.delta)} ball` : formatWeek(s.last.week_start) }}
                                </p>
                                <p v-else class="mt-0.5 text-xs text-fg-subtle">Hali imtihon yo'q</p>
                            </div>
                        </div>
                        <GrowthChart v-if="hasHistory(child)" :history="history(child)" :names="names(child)" height="h-52" />
                    </div>
                    <div class="space-y-5">
                        <div>
                            <h3 class="mb-2 text-sm font-semibold text-fg">Keyingi hafta uchun</h3>
                            <Recommendations :items="child.recommendations" />
                        </div>
                    </div>
                </div>

                <div v-if="child.sections.some((m) => m.score !== null)" class="mt-6 grid gap-6 border-t border-border pt-5 md:grid-cols-2">
                    <div v-for="m in child.sections" :key="m.code">
                        <h3 class="mb-3 text-sm font-semibold text-fg">{{ m.name }} — mavzular xaritasi</h3>
                        <TopicMap :sections="m.sections" compact />
                    </div>
                </div>
            </Card>

            <Card v-if="reports.length" padding="none" title="Yakshanba hisobotlari"
                :description="unreadReports ? `${unreadReports} ta o'qilmagan` : 'Har yakshanba ertalab'">
                <template #actions>
                    <Button variant="ghost" size="sm" :href="route('guardian.reports.index')">Barchasi</Button>
                </template>
                <ul class="divide-y divide-border">
                    <li v-for="r in reports" :key="r.id">
                        <Link :href="route('guardian.reports.show', r.id)" class="focus-ring flex items-center gap-3 px-5 py-3 hover:bg-surface-2">
                            <DocumentChartBarIcon class="h-5 w-5 text-fg-subtle" aria-hidden="true" />
                            <span class="flex-1 text-sm text-fg">{{ r.student }} · {{ r.week_label || r.week_start }}</span>
                            <Badge v-if="r.unread" variant="brand" size="sm" dot>Yangi</Badge>
                            <ChevronRightIcon class="h-4 w-4 text-fg-subtle" aria-hidden="true" />
                        </Link>
                    </li>
                </ul>
            </Card>
        </div>

        <Modal :show="showLink" title="Farzandni bog'lash" description="Farzandingiz EDULIFE'da ro'yxatdan o'tgan telefon raqami"
            size="md" @close="showLink = false">
            <form id="link-form" class="space-y-5" novalidate @submit.prevent="submit">
                <Input v-model="form.phone" type="tel" inputmode="tel" label="Farzandning telefon raqami" placeholder="+998901234567"
                    :leading-icon="PhoneIcon" required autofocus :error="form.errors.phone" />
                <Select v-model="form.relation" label="Siz farzandga kimsiz?" :options="relations" :error="form.errors.relation" />
                <Checkbox v-model="form.consent" label="Farzandimning ma'lumotlarini qayta ishlashga roziman"
                    description="Natijalar faqat sizga va ustozga ko'rinadi." :error="form.errors.consent" />
                <p class="text-xs text-fg-subtle">Xavfsizlik uchun bog'lanishni ustoz tasdiqlaydi.</p>
            </form>
            <template #footer>
                <Button variant="secondary" @click="showLink = false">Bekor qilish</Button>
                <Button type="submit" form="link-form" :loading="form.processing">So'rov yuborish</Button>
            </template>
        </Modal>
    </GuardianLayout>
</template>
