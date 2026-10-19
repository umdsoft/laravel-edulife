<template>
    <AdminLayout>

        <Head :title="`${user.full_name} - Foydalanuvchi`" />

        <PageHeader :breadcrumbs="[{ label: 'Foydalanuvchilar', href: route('admin.users.index') }, { label: user.full_name }]">
            <template #title>
                <div class="flex items-center gap-4">
                    <Avatar :name="user.full_name" size="xl" />
                    <div class="min-w-0">
                        <h1 class="truncate text-2xl font-semibold tracking-tight text-fg">{{ user.full_name }}</h1>
                        <div class="mt-1.5 flex flex-wrap items-center gap-2">
                            <Badge :variant="userStatusMeta(user.status).variant" dot>{{ userStatusMeta(user.status).label }}</Badge>
                            <Badge :variant="roleMeta(user.role).variant">{{ roleMeta(user.role).label }}</Badge>
                        </div>
                    </div>
                </div>
            </template>
            <template #actions>
                <Button variant="secondary" :icon-left="PencilSquareIcon" :href="route('admin.users.edit', user.id)">
                    Tahrirlash
                </Button>
                <Button variant="danger" :icon-left="TrashIcon" @click="confirmDelete">O'chirish</Button>
            </template>
        </PageHeader>

        <div class="space-y-6">
            <section aria-label="Ko'rsatkichlar" class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <StatCard label="Daraja" :value="numberOrNull(user.level)" :icon="ArrowTrendingUpIcon" tone="brand" />
                <StatCard label="Jami XP" :value="numberOrNull(user.xp_total)" :icon="SparklesIcon" tone="info" unit="XP" />
                <StatCard label="COIN balansi" :value="numberOrNull(user.coin_balance)" :icon="CircleStackIcon"
                    tone="warning" />
                <StatCard label="Kurslar" :value="numberOrNull(user.enrollments_count)" :icon="BookOpenIcon" tone="math"
                    :hint="`Tugallangan: ${user.completed_courses_count ?? 0}`" />
            </section>

            <div class="grid gap-6 lg:grid-cols-3">
                <Card class="lg:col-span-2" :title="`Kurslar (${enrollments.length})`"
                    description="Foydalanuvchi yozilgan kurslar va progress" padding="none">
                    <ul v-if="enrollments.length" class="divide-y divide-border">
                        <li v-for="enrollment in enrollments" :key="enrollment.id"
                            class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:gap-6 sm:px-6">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-fg">{{ enrollment.course_title }}</p>
                                <p class="mt-0.5 text-13 text-fg-subtle tabular-nums">
                                    Boshlangan: {{ formatDate(enrollment.started_at) }}
                                </p>
                            </div>
                            <div class="flex items-center gap-3 sm:w-56">
                                <ProgressBar :value="Number(enrollment.progress) || 0" size="sm"
                                    :tone="Number(enrollment.progress) >= 100 ? 'success' : 'brand'"
                                    :label="`${enrollment.course_title}: progress`" class="flex-1" />
                                <span class="w-10 text-right text-13 font-medium text-fg tabular-nums">
                                    {{ Math.round(Number(enrollment.progress) || 0) }}%
                                </span>
                            </div>
                        </li>
                    </ul>
                    <div v-else class="px-6 py-6">
                        <EmptyState :icon="BookOpenIcon" title="Kurslar yo'q"
                            description="Foydalanuvchi hali birorta kursga yozilmagan" size="sm" />
                    </div>
                </Card>

                <aside class="space-y-6">
                    <Card title="Ma'lumot">
                        <dl class="-my-2.5 divide-y divide-border text-sm">
                            <div class="flex justify-between gap-4 py-2.5">
                                <dt class="text-fg-muted">Telefon</dt>
                                <dd class="font-medium text-fg tabular-nums">{{ user.phone || '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-4 py-2.5">
                                <dt class="text-fg-muted">Email</dt>
                                <dd class="min-w-0 truncate font-medium text-fg">{{ user.email || 'Kiritilmagan' }}</dd>
                            </div>
                            <div class="flex justify-between gap-4 py-2.5">
                                <dt class="text-fg-muted">Ro'yxatdan o'tgan</dt>
                                <dd class="font-medium text-fg tabular-nums">{{ formatDate(user.created_at) }}</dd>
                            </div>
                            <div class="flex justify-between gap-4 py-2.5">
                                <dt class="text-fg-muted">Yozilgan kurslar</dt>
                                <dd class="font-medium text-fg tabular-nums">{{ user.enrollments_count ?? 0 }} ta</dd>
                            </div>
                            <div class="flex justify-between gap-4 py-2.5">
                                <dt class="text-fg-muted">Tugallangan kurslar</dt>
                                <dd class="font-medium text-fg tabular-nums">{{ user.completed_courses_count ?? 0 }} ta</dd>
                            </div>
                        </dl>
                    </Card>
                </aside>
            </div>
        </div>

        <DeleteConfirm :show="deleteModal.show" :loading="deleteModal.loading" title="Foydalanuvchini o'chirish"
            :message="`${user.full_name} foydalanuvchisi o'chiriladi. Bu amalni bekor qilib bo'lmaydi.`"
            @confirm="deleteUser" @cancel="deleteModal.show = false" />
    </AdminLayout>
</template>

<script setup>
import { reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    ArrowTrendingUpIcon, BookOpenIcon, CircleStackIcon, PencilSquareIcon, SparklesIcon, TrashIcon,
} from '@heroicons/vue/24/outline';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Avatar, Badge, Button, Card, EmptyState, PageHeader, ProgressBar, StatCard } from '@/Components/UI';
import DeleteConfirm from '@/Components/Admin/DeleteConfirm.vue';
import { formatDate, roleMeta, userStatusMeta } from '@/Components/Admin/format';

const props = defineProps({
    user: Object,
    enrollments: Array,
});

const deleteModal = reactive({
    show: false,
    loading: false,
});

const numberOrNull = (v) => {
    const n = Number(v);
    return v === null || v === undefined || !Number.isFinite(n) ? 0 : n;
};

const confirmDelete = () => {
    deleteModal.show = true;
};

const deleteUser = () => {
    deleteModal.loading = true;

    router.delete(route('admin.users.destroy', props.user.id), {
        onSuccess: () => {
            router.visit(route('admin.users.index'));
        },
        onFinish: () => {
            deleteModal.loading = false;
        },
    });
};
</script>
