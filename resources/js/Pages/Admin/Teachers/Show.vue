<template>
    <AdminLayout>

        <Head :title="`${teacher.full_name} - O'qituvchi`" />

        <PageHeader :breadcrumbs="[{ label: 'O\'qituvchilar', href: route('admin.teachers.index') }, { label: teacher.full_name }]">
            <template #title>
                <div class="flex items-center gap-4">
                    <Avatar :name="teacher.full_name" size="xl" />
                    <div class="min-w-0">
                        <h1 class="truncate text-2xl font-semibold tracking-tight text-fg">{{ teacher.full_name }}</h1>
                        <div class="mt-1.5 flex flex-wrap items-center gap-2">
                            <Badge v-if="teacher.is_verified" variant="success" :icon="CheckBadgeIcon">Tasdiqlangan</Badge>
                            <Badge v-else variant="warning" dot>Tasdiqlanmagan</Badge>
                            <span v-if="teacher.phone" class="text-13 text-fg-subtle tabular-nums">{{ teacher.phone }}</span>
                        </div>
                    </div>
                </div>
            </template>
            <template #actions>
                <Button v-if="!teacher.is_verified" :icon-left="CheckBadgeIcon" :loading="verifying" @click="verify">
                    Tasdiqlash
                </Button>
                <Button v-else variant="secondary" :icon-left="XCircleIcon" :loading="verifying" @click="unverify">
                    Tasdiqni bekor qilish
                </Button>
            </template>
        </PageHeader>

        <div class="space-y-6">
            <section aria-label="Ko'rsatkichlar" class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <StatCard label="Kurslar" :value="Number(teacher.total_courses) || 0" unit="ta" :icon="BookOpenIcon"
                    tone="math" />
                <StatCard label="O'quvchilar" :value="Number(teacher.total_students) || 0" :icon="UsersIcon" tone="brand" />
                <StatCard label="O'rtacha reyting" :value="Number(teacher.avg_rating || 0).toFixed(1)" unit="/ 5"
                    :icon="StarIcon" tone="warning" />
            </section>

            <div class="grid gap-6 lg:grid-cols-3">
            <Card class="min-w-0 lg:col-span-2" :title="`Kurslar (${courses.length})`"
                description="O'qituvchi yaratgan kurslar" padding="none">
                <Table :columns="columns" :rows="courses" :bordered="false" :clickable="canOpenCourse"
                    caption="O'qituvchi kurslari" @row-click="openCourse">
                    <template #cell-title="{ row }">
                        <Link v-if="canOpenCourse" :href="route('admin.courses.show', row.id)"
                            class="focus-ring block max-w-xs truncate rounded-sm font-medium text-fg hover:text-brand sm:max-w-md">
                            {{ row.title }}
                        </Link>
                        <span v-else class="block max-w-xs truncate font-medium sm:max-w-md">{{ row.title }}</span>
                    </template>
                    <template #cell-status="{ value }">
                        <Badge :variant="courseStatusMeta(value).variant" dot>{{ courseStatusMeta(value).label }}</Badge>
                    </template>
                    <template #cell-students_count="{ value }">{{ formatNumber(value) }}</template>
                    <template #cell-avg_rating="{ value }">{{ Number(value || 0).toFixed(1) }}</template>
                    <template #cell-price="{ value }">
                        <span class="whitespace-nowrap">{{ formatMoney(value) }}</span>
                    </template>
                    <template #empty>
                        <EmptyState :icon="BookOpenIcon" title="Kurslar yo'q"
                            description="O'qituvchi hali birorta kurs yaratmagan" size="sm" />
                    </template>
                </Table>
            </Card>

            <aside class="space-y-6">
                <Card title="Ma'lumot">
                    <dl class="-my-2.5 divide-y divide-border text-sm">
                        <div class="flex justify-between gap-4 py-2.5">
                            <dt class="text-fg-muted">Telefon</dt>
                            <dd class="font-medium text-fg tabular-nums">{{ teacher.phone || '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4 py-2.5">
                            <dt class="text-fg-muted">Email</dt>
                            <dd class="min-w-0 truncate font-medium text-fg">{{ teacher.email || 'Kiritilmagan' }}</dd>
                        </div>
                        <div v-if="teacher.expertise" class="flex justify-between gap-4 py-2.5">
                            <dt class="text-fg-muted">Mutaxassislik</dt>
                            <dd class="min-w-0 text-right font-medium text-fg">{{ teacher.expertise }}</dd>
                        </div>
                        <div class="flex justify-between gap-4 py-2.5">
                            <dt class="text-fg-muted">Tasdiqlangan</dt>
                            <dd class="font-medium text-fg tabular-nums">
                                {{ teacher.verified_at ? formatDate(teacher.verified_at) : '—' }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4 py-2.5">
                            <dt class="text-fg-muted">Ro'yxatdan o'tgan</dt>
                            <dd class="font-medium text-fg tabular-nums">{{ formatDate(teacher.created_at) }}</dd>
                        </div>
                    </dl>
                </Card>

                <Card v-if="teacher.bio" title="Bio">
                    <p class="whitespace-pre-line text-sm leading-6 text-fg-muted">{{ teacher.bio }}</p>
                </Card>
            </aside>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { BookOpenIcon, CheckBadgeIcon, StarIcon, UsersIcon, XCircleIcon } from '@heroicons/vue/24/outline';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Avatar, Badge, Button, Card, EmptyState, PageHeader, StatCard, Table } from '@/Components/UI';
import { courseStatusMeta, formatDate, formatMoney, formatNumber } from '@/Components/Admin/format';
import { routeHas } from '@/Utils/nav';

const props = defineProps({
    teacher: Object,
    courses: Array,
});

const verifying = ref(false);
const canOpenCourse = routeHas('admin.courses.show');

const columns = [
    { key: 'title', label: 'Kurs' },
    { key: 'status', label: 'Holat' },
    { key: 'students_count', label: 'O\'quvchilar', numeric: true, hideBelow: 'sm' },
    { key: 'avg_rating', label: 'Reyting', numeric: true, hideBelow: 'md' },
    { key: 'price', label: 'Narx', numeric: true },
];

const openCourse = (course) => router.visit(route('admin.courses.show', course.id));

const verify = () => {
    verifying.value = true;
    router.patch(route('admin.teachers.verify', props.teacher.id), {}, {
        preserveScroll: true,
        onFinish: () => {
            verifying.value = false;
        },
    });
};

const unverify = () => {
    verifying.value = true;
    router.patch(route('admin.teachers.unverify', props.teacher.id), {}, {
        preserveScroll: true,
        onFinish: () => {
            verifying.value = false;
        },
    });
};
</script>
