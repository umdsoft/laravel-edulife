<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    PencilSquareIcon, ChatBubbleLeftRightIcon, MegaphoneIcon, ClipboardDocumentCheckIcon, StarIcon, ArrowRightIcon,
} from '@heroicons/vue/24/outline';
import { PlayIcon, LockClosedIcon, CheckBadgeIcon, BookOpenIcon, ClockIcon } from '@heroicons/vue/20/solid';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { PageHeader, Card, Button, Badge, Avatar, ProgressRing } from '@/Components/UI';
import LearnFrame from '@/Components/Student/LearnFrame.vue';
import LessonSidebar from '@/Components/Student/LessonSidebar.vue';
import StarRating from '@/Components/Student/StarRating.vue';
import { routeHas } from '@/Utils/nav';

const props = defineProps({
    course: Object,
    enrollment: Object,
    lessonProgresses: Object,
    moduleProgress: Object,
    nextLesson: Object,
    announcementsCount: Number,
    qnaCount: Number,
});

const progress = computed(() => Math.round(Number(props.enrollment?.progress) || 0));

const quickLinks = computed(() => [
    {
        key: 'notes', label: 'Mening qaydlarim', text: 'Darslar davomida yozgan qaydlaringiz',
        icon: PencilSquareIcon, tone: 'bg-warning-50 text-warning', route: 'student.learn.notes.index',
    },
    {
        key: 'qna', label: 'Savol-javoblar', text: "Ustoz va o'quvchilar bilan muloqot",
        icon: ChatBubbleLeftRightIcon, tone: 'bg-info-50 text-info', route: 'student.learn.qna.index', count: props.qnaCount,
    },
    {
        key: 'announcements', label: "E'lonlar", text: 'Kurs yangiliklari va eslatmalar',
        icon: MegaphoneIcon, tone: 'bg-brand-50 text-brand', route: 'student.learn.announcements.index', count: props.announcementsCount,
    },
    {
        key: 'tests', label: 'Testlar', text: "Bilimingizni sinab ko'ring",
        icon: ClipboardDocumentCheckIcon, tone: 'bg-success-50 text-success', route: 'student.tests.index',
    },
].filter((l) => routeHas(l.route)));
</script>

<template>

    <Head :title="course.title" />

    <StudentLayout :title="course.title">
        <LearnFrame :course="course" :progress="lessonProgresses" :module-progress="moduleProgress"
            :counts="{ qna: qnaCount, announcements: announcementsCount }">
            <template #header>
                <PageHeader :title="course.title"
                    :breadcrumbs="[{ label: 'Kurslarim', href: route('student.my-courses.index') }, { label: course.title }]">
                    <template #meta>
                        <Badge variant="brand">{{ course.category?.name || 'Kurs' }}</Badge>
                        <StarRating v-if="course.avg_rating" :value="course.avg_rating" compact size="xs" />
                        <span class="inline-flex items-center gap-1.5 text-13 text-fg-muted">
                            <Avatar :src="course.teacher?.avatar_url" :name="course.teacher?.full_name || ''" size="xs" />
                            {{ course.teacher?.full_name }}
                        </span>
                        <span class="inline-flex items-center gap-1 text-13 text-fg-muted tabular-nums">
                            <BookOpenIcon class="h-4 w-4 text-fg-subtle" aria-hidden="true" />{{ course.lessons_count }} ta dars
                        </span>
                        <span v-if="course.total_duration_human" class="inline-flex items-center gap-1 text-13 text-fg-muted">
                            <ClockIcon class="h-4 w-4 text-fg-subtle" aria-hidden="true" />{{ course.total_duration_human }}
                        </span>
                    </template>
                </PageHeader>
            </template>

            <!-- Progress + next step -->
            <Card padding="lg">
                <div class="flex flex-col gap-6 sm:flex-row sm:items-center">
                    <ProgressRing :value="progress" :size="96" :stroke="8" :tone="enrollment?.is_completed ? 'success' : 'brand'"
                        label="Kurs progressi" class="mx-auto sm:mx-0">
                        <span class="text-xl font-semibold text-fg tabular-nums">{{ progress }}%</span>
                    </ProgressRing>

                    <div class="min-w-0 flex-1">
                        <p class="text-13 font-medium text-fg-subtle">{{ enrollment?.is_completed ? 'Kurs yakunlangan' : 'Keyingi qadam' }}</p>
                        <h2 class="mt-0.5 text-lg font-semibold text-fg">
                            {{ nextLesson ? nextLesson.title : (enrollment?.is_completed ? 'Tabriklaymiz!' : 'Barcha darslar ko\'rib chiqildi') }}
                        </h2>
                        <dl class="mt-3 flex flex-wrap gap-x-6 gap-y-1 text-13">
                            <div class="flex gap-1.5">
                                <dt class="text-fg-muted">Tugatilgan darslar:</dt>
                                <dd class="font-medium text-fg tabular-nums">{{ enrollment?.completed_lessons }}/{{ enrollment?.total_lessons }}</dd>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <dt class="text-fg-muted">Sertifikat:</dt>
                                <dd v-if="enrollment?.is_completed" class="inline-flex items-center gap-1 font-medium text-success">
                                    <CheckBadgeIcon class="h-4 w-4" aria-hidden="true" /> Mavjud
                                </dd>
                                <dd v-else class="inline-flex items-center gap-1 text-fg-subtle">
                                    <LockClosedIcon class="h-3.5 w-3.5" aria-hidden="true" /> Kurs yakunida
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <div class="flex flex-col gap-2 sm:items-end">
                        <Button v-if="nextLesson" size="lg" :icon-left="PlayIcon"
                            :href="route('student.learn.lesson', { course: course.slug, lesson: nextLesson.id })">
                            {{ enrollment?.progress > 0 ? 'Davom ettirish' : 'Boshlash' }}
                        </Button>
                        <Button variant="ghost" size="sm" :icon-left="StarIcon" :href="route('student.reviews.create', course.id)">
                            Sharh qoldirish
                        </Button>
                    </div>
                </div>
            </Card>

            <!-- Course tools -->
            <section aria-labelledby="tools-title">
                <h2 id="tools-title" class="sr-only">Kurs bo'limlari</h2>
                <div class="grid gap-3 sm:grid-cols-2">
                    <Link v-for="item in quickLinks" :key="item.key" :href="route(item.route, course.id)"
                        class="focus-ring group flex items-start gap-3 rounded-lg border border-border bg-surface p-4 shadow-xs transition-shadow duration-150 hover:border-border-strong hover:shadow-md">
                        <span :class="['flex h-10 w-10 shrink-0 items-center justify-center rounded-md', item.tone]">
                            <component :is="item.icon" class="h-5 w-5" aria-hidden="true" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="flex items-center gap-2">
                                <span class="text-sm font-semibold text-fg">{{ item.label }}</span>
                                <Badge v-if="item.count > 0" variant="danger" size="sm">{{ item.count }}</Badge>
                            </span>
                            <span class="mt-0.5 block text-13 text-fg-muted">{{ item.text }}</span>
                        </span>
                        <ArrowRightIcon class="mt-0.5 h-4 w-4 shrink-0 text-fg-subtle transition-transform duration-150 group-hover:translate-x-0.5"
                            aria-hidden="true" />
                    </Link>
                </div>
            </section>

            <!-- Curriculum inline on small screens (desktop shows it in the side rail) -->
            <div class="overflow-hidden rounded-lg border border-border bg-surface shadow-xs lg:hidden">
                <LessonSidebar :course="course" :progress="lessonProgresses" :module-progress="moduleProgress" />
            </div>
        </LearnFrame>
    </StudentLayout>
</template>
