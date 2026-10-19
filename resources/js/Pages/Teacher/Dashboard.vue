<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    BookOpenIcon, UsersIcon, ArrowTrendingUpIcon, StarIcon, PlusIcon, ChartBarIcon,
    ChatBubbleLeftRightIcon, UserPlusIcon,
} from '@heroicons/vue/24/outline';
import { ChevronRightIcon } from '@heroicons/vue/20/solid';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import StatCard from '@/Components/UI/StatCard.vue';
import Card from '@/Components/UI/Card.vue';
import Button from '@/Components/UI/Button.vue';
import Avatar from '@/Components/UI/Avatar.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import CourseCard from '@/Components/Teacher/CourseCard.vue';
import MonthlyBarChart from '@/Components/Teacher/MonthlyBarChart.vue';
import RatingStars from '@/Components/Teacher/RatingStars.vue';
import { formatDate, formatNumber, fullName } from '@/Components/Teacher/format';

const props = defineProps({
    stats: Object,
    recentEnrollments: Array,
    recentReviews: Array,
    courses: Array,
    enrollmentsChart: Array,
    teacherProfile: Object,
});

const page = usePage();
const firstName = computed(() => page.props.auth?.user?.first_name || '');
const rating = computed(() => Number(props.stats?.avg_rating || 0).toFixed(1));
const chartTotal = computed(() => (props.enrollmentsChart || []).reduce((s, d) => s + (Number(d.value) || 0), 0));

const quickActions = computed(() => [
    { label: 'Yangi kurs yaratish', description: 'Kurs, modul va darslar', href: route('teacher.courses.create'), icon: PlusIcon },
    { label: 'O\'quvchilarim', description: `${formatNumber(props.stats?.total_students || 0)} ta o'quvchi`, href: route('teacher.students.index'), icon: UsersIcon },
    { label: 'Sharhlar', description: `${formatNumber(props.stats?.total_reviews || 0)} ta sharh`, href: route('teacher.reviews.index'), icon: ChatBubbleLeftRightIcon },
    { label: 'Statistika', description: 'Kurslar samaradorligi', href: route('teacher.analytics.index'), icon: ChartBarIcon },
]);
</script>

<template>
    <Head title="O'qituvchi paneli" />

    <TeacherLayout>
        <PageHeader :title="firstName ? `Salom, ${firstName}` : 'Dashboard'" description="Kurslaringiz va o'quvchilaringiz bo'yicha bugungi holat." />

        <div class="space-y-6">
            <!-- KPIs -->
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <StatCard label="Jami kurslar" :value="stats.total_courses" :icon="BookOpenIcon" tone="brand"
                    :hint="`${stats.published_courses} ta nashr qilingan`" :href="route('teacher.courses.index')" />
                <StatCard label="O'quvchilar" :value="stats.total_students" :icon="UsersIcon" tone="info"
                    :href="route('teacher.students.index')" />
                <StatCard label="Bu oy yozilishlar" :value="stats.monthly_enrollments" :icon="ArrowTrendingUpIcon" tone="success"
                    hint="Joriy oy" />
                <StatCard label="O'rtacha reyting" :value="rating" unit="/ 5" :icon="StarIcon" tone="warning"
                    :hint="`${stats.total_reviews} ta sharh`" :href="route('teacher.reviews.index')" />
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <Card class="lg:col-span-2" title="Kursga yozilishlar" description="So'nggi 6 oy">
                    <template #actions>
                        <span class="text-13 text-fg-subtle">Jami <span class="font-medium tabular-nums text-fg">{{ formatNumber(chartTotal) }}</span></span>
                    </template>
                    <MonthlyBarChart :data="enrollmentsChart" unit="ta" label="So'nggi 6 oydagi yozilishlar" />
                </Card>

                <Card title="Tezkor amallar" padding="none">
                    <ul class="divide-y divide-border">
                        <li v-for="action in quickActions" :key="action.label">
                            <Link :href="action.href"
                                class="focus-ring group flex items-center gap-3 px-5 py-3.5 transition-colors hover:bg-surface-2/60 sm:px-6">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-brand-50 text-brand">
                                    <component :is="action.icon" class="h-5 w-5" aria-hidden="true" />
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-medium text-fg">{{ action.label }}</span>
                                    <span class="block truncate text-13 text-fg-subtle">{{ action.description }}</span>
                                </span>
                                <ChevronRightIcon class="h-5 w-5 text-fg-subtle transition-transform duration-150 group-hover:translate-x-0.5" aria-hidden="true" />
                            </Link>
                        </li>
                    </ul>
                </Card>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Recent enrollments -->
                <Card title="So'nggi o'quvchilar" padding="none">
                    <template #actions>
                        <Button variant="ghost" size="sm" :href="route('teacher.students.index')">Barchasi</Button>
                    </template>
                    <ul v-if="recentEnrollments?.length" class="divide-y divide-border">
                        <li v-for="enrollment in recentEnrollments" :key="enrollment.id" class="flex items-center gap-3 px-5 py-3 sm:px-6">
                            <Avatar :src="enrollment.user?.avatar_url" :name="fullName(enrollment.user)" size="sm" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-fg">{{ fullName(enrollment.user) }}</p>
                                <p class="truncate text-13 text-fg-subtle">{{ enrollment.course?.title }}</p>
                            </div>
                            <time :datetime="enrollment.created_at" class="shrink-0 text-13 tabular-nums text-fg-subtle">
                                {{ formatDate(enrollment.created_at) }}
                            </time>
                        </li>
                    </ul>
                    <EmptyState v-else :icon="UserPlusIcon" size="sm" title="Hali yozilishlar yo'q"
                        description="O'quvchilar kursga yozilganda shu yerda ko'rinadi." />
                </Card>

                <!-- Recent reviews -->
                <Card title="So'nggi sharhlar" padding="none">
                    <template #actions>
                        <Button variant="ghost" size="sm" :href="route('teacher.reviews.index')">Barchasi</Button>
                    </template>
                    <ul v-if="recentReviews?.length" class="divide-y divide-border">
                        <li v-for="review in recentReviews" :key="review.id" class="flex gap-3 px-5 py-3.5 sm:px-6">
                            <Avatar :src="review.user?.avatar_url" :name="fullName(review.user)" size="sm" />
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5">
                                    <span class="text-sm font-medium text-fg">{{ fullName(review.user) }}</span>
                                    <RatingStars :value="review.rating" size="xs" />
                                </div>
                                <p v-if="review.review_text" class="mt-0.5 line-clamp-2 text-sm text-fg-muted">{{ review.review_text }}</p>
                                <p class="mt-1 truncate text-13 text-fg-subtle">{{ review.course?.title }}</p>
                            </div>
                        </li>
                    </ul>
                    <EmptyState v-else :icon="ChatBubbleLeftRightIcon" size="sm" title="Hali sharhlar yo'q"
                        description="O'quvchilar kursni baholaganda shu yerda ko'rinadi." />
                </Card>
            </div>

            <!-- Courses -->
            <section aria-labelledby="my-courses-title" class="space-y-4">
                <div class="flex items-center justify-between gap-4">
                    <h2 id="my-courses-title" class="text-lg font-semibold text-fg">Kurslarim</h2>
                    <Button variant="ghost" size="sm" :href="route('teacher.courses.index')" :icon-right="ChevronRightIcon">
                        Barcha kurslar
                    </Button>
                </div>

                <div v-if="courses?.length" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <CourseCard v-for="course in courses" :key="course.id" :course="course" />
                    <Link :href="route('teacher.courses.create')"
                        class="focus-ring group flex min-h-[220px] flex-col items-center justify-center gap-3 rounded-lg border border-dashed border-border-strong p-6 text-center transition-colors hover:border-brand-500 hover:bg-brand-50/50">
                        <span class="flex h-11 w-11 items-center justify-center rounded-full bg-brand-50 text-brand">
                            <PlusIcon class="h-5 w-5" aria-hidden="true" />
                        </span>
                        <span class="text-sm font-medium text-fg">Yangi kurs yaratish</span>
                    </Link>
                </div>

                <Card v-else>
                    <EmptyState :icon="BookOpenIcon" title="Hali kurs yo'q" description="Birinchi kursingizni yarating — keyin modul va darslarni qo'shasiz.">
                        <Button :href="route('teacher.courses.create')" :icon-left="PlusIcon">Yangi kurs</Button>
                    </EmptyState>
                </Card>
            </section>
        </div>
    </TeacherLayout>
</template>
