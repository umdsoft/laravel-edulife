<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    BookOpenIcon, AcademicCapIcon, ClockIcon, CircleStackIcon, BeakerIcon, CheckBadgeIcon, ArrowRightIcon,
} from '@heroicons/vue/24/outline';
import { CheckCircleIcon, SparklesIcon } from '@heroicons/vue/20/solid';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { PageHeader, Card, StatCard, Button, EmptyState, ProgressBar, Badge } from '@/Components/UI';
import ContinueLearningCard from '@/Components/Student/ContinueLearningCard.vue';
import RecommendationSlider from '@/Components/Student/RecommendationSlider.vue';
import CategoryCard from '@/Components/Student/CategoryCard.vue';
import CourseCard from '@/Components/Student/CourseCard.vue';
import CourseThumbnail from '@/Components/Student/CourseThumbnail.vue';
import { routePath } from '@/Utils/nav';

const props = defineProps({
    profile: Object,
    stats: Object,
    continueLearning: Array,
    recommendations: Array,
    directions: Array,
    popularCourses: Array,
    newCourses: Array,
    labProgress: Object,
    dailyMissions: Object,
});

const page = usePage();
const firstName = computed(() => page.props.auth?.user?.first_name || props.profile?.first_name || '');
const title = computed(() => (firstName.value ? `Salom, ${firstName.value}` : 'Bosh sahifa'));

const labHref = computed(() => routePath('student.lab.index'));
const missionsHref = computed(() => routePath('student.missions.index'));

const continueList = computed(() => props.continueLearning || []);
const recommendationList = computed(() => props.recommendations || []);
const popularList = computed(() => (props.popularCourses || []).slice(0, 4));
const directionList = computed(() => (props.directions || []).slice(0, 4));
const newList = computed(() => (props.newCourses || []).slice(0, 3));
const missions = computed(() => props.dailyMissions?.missions || []);
</script>

<template>

    <Head title="Bosh sahifa" />

    <StudentLayout title="Bosh sahifa">
        <PageHeader :title="title" description="Bugungi o'quv holatingiz va keyingi qadamlar.">
            <template #actions>
                <Button variant="secondary" :icon-left="AcademicCapIcon" :href="route('student.courses.index')">
                    Kurslar katalogi
                </Button>
            </template>
        </PageHeader>

        <div class="space-y-6">
            <!-- Stats -->
            <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                <StatCard label="O'qilayotgan kurslar" :value="stats?.courses_in_progress ?? 0" :icon="BookOpenIcon" tone="brand" />
                <StatCard label="Tugatilgan kurslar" :value="stats?.courses_completed ?? 0" :icon="AcademicCapIcon" tone="success" />
                <StatCard label="Umumiy vaqt" :value="stats?.total_watch_time ?? 0" :icon="ClockIcon" tone="info" />
                <StatCard label="Coinlar" :value="stats?.coins ?? 0" :icon="CircleStackIcon" tone="warning" />
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Main column -->
                <div class="min-w-0 space-y-6 lg:col-span-2">
                    <Card title="O'qishni davom ettirish" description="To'xtagan joyingizdan boshlang">
                        <template v-if="continueList.length" #actions>
                            <Button variant="ghost" size="sm" :href="route('student.my-courses.in-progress')" :icon-right="ArrowRightIcon">
                                Barchasi
                            </Button>
                        </template>
                        <div v-if="continueList.length" class="grid gap-3 md:grid-cols-2">
                            <ContinueLearningCard v-for="enrollment in continueList" :key="enrollment.id" :enrollment="enrollment" />
                        </div>
                        <EmptyState v-else :icon="BookOpenIcon" size="sm" title="Hali faol kurs yo'q"
                            description="Katalogdan kurs tanlab, o'qishni boshlang.">
                            <Button size="sm" :href="route('student.courses.index')">Kurs tanlash</Button>
                        </EmptyState>
                    </Card>

                    <section v-if="recommendationList.length" aria-labelledby="recs-title">
                        <h2 id="recs-title" class="mb-3 text-lg font-semibold text-fg">Siz uchun tavsiyalar</h2>
                        <RecommendationSlider :courses="recommendationList" />
                    </section>

                    <section aria-labelledby="popular-title">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <h2 id="popular-title" class="text-lg font-semibold text-fg">Mashhur kurslar</h2>
                            <Button variant="ghost" size="sm" :href="route('student.courses.index', { sort: 'popular' })"
                                :icon-right="ArrowRightIcon">
                                Barchasi
                            </Button>
                        </div>
                        <div v-if="popularList.length" class="grid gap-4 sm:grid-cols-2">
                            <CourseCard v-for="course in popularList" :key="course.id" :course="course" />
                        </div>
                        <EmptyState v-else bordered size="sm" :icon="AcademicCapIcon" title="Kurslar hali qo'shilmagan" />
                    </section>
                </div>

                <!-- Side column -->
                <aside class="min-w-0 space-y-6">
                    <!-- Daily missions -->
                    <Card title="Kunlik vazifalar">
                        <template #actions>
                            <Badge variant="neutral" size="sm">
                                <span class="tabular-nums">{{ dailyMissions?.hours_remaining || 0 }} soat qoldi</span>
                            </Badge>
                        </template>

                        <ul v-if="missions.length" class="space-y-4">
                            <li v-for="mission in missions" :key="mission.id">
                                <div class="flex items-start gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-surface-2 text-lg" aria-hidden="true">
                                        <template v-if="mission.icon">{{ mission.icon }}</template>
                                        <CheckBadgeIcon v-else class="h-5 w-5 text-fg-subtle" />
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-start justify-between gap-2">
                                            <p class="text-sm font-medium text-fg">{{ mission.title }}</p>
                                            <span class="shrink-0 text-13 font-semibold text-fg tabular-nums">{{ mission.current }}/{{ mission.target }}</span>
                                        </div>
                                        <p class="mt-0.5 flex flex-wrap items-center gap-x-3 text-xs text-fg-subtle tabular-nums">
                                            <span class="inline-flex items-center gap-1"><SparklesIcon class="h-3.5 w-3.5 text-brand" aria-hidden="true" />+{{ mission.xp_reward }} XP</span>
                                            <span v-if="mission.coin_reward" class="inline-flex items-center gap-1">
                                                <CircleStackIcon class="h-3.5 w-3.5 text-warning" aria-hidden="true" />+{{ mission.coin_reward }} coin
                                            </span>
                                            <span v-if="mission.is_completed && !mission.is_claimed" class="inline-flex items-center gap-1 font-medium text-success">
                                                <CheckCircleIcon class="h-3.5 w-3.5" aria-hidden="true" /> Tayyor
                                            </span>
                                        </p>
                                        <ProgressBar :value="Number(mission.progress_percent) || 0" size="xs"
                                            :tone="mission.is_completed ? 'success' : 'brand'" class="mt-2" />
                                    </div>
                                </div>
                            </li>
                        </ul>
                        <EmptyState v-else size="sm" :icon="CheckBadgeIcon" title="Bugun uchun vazifalar yo'q" />

                        <template #footer>
                            <div class="flex items-center justify-between gap-2 text-13">
                                <span class="text-fg-muted tabular-nums">
                                    {{ dailyMissions?.completed_count || 0 }}/{{ dailyMissions?.total_count || 0 }} bajarildi
                                </span>
                                <Link v-if="missionsHref" :href="missionsHref" class="focus-ring rounded-sm font-medium text-brand hover:underline">
                                    Barcha vazifalar
                                </Link>
                            </div>
                        </template>
                    </Card>

                    <!-- Virtual lab -->
                    <Card v-if="labHref" :href="labHref" padding="md">
                        <div class="flex items-start gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-physics-50 text-physics">
                                <BeakerIcon class="h-5 w-5" aria-hidden="true" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <h3 class="text-base font-semibold text-fg">Virtual laboratoriya</h3>
                                <p class="mt-0.5 text-13 text-fg-muted">
                                    {{ labProgress?.started ? 'Tajribalarni davom ettiring' : 'Fizika qonunlarini tajribada kashf eting' }}
                                </p>
                            </div>
                            <ArrowRightIcon class="mt-1 h-4 w-4 shrink-0 text-fg-subtle" aria-hidden="true" />
                        </div>
                        <div v-if="labProgress?.started" class="mt-4">
                            <div class="mb-1.5 flex justify-between text-13">
                                <span class="text-fg-muted">Jarayon</span>
                                <span class="font-medium text-fg tabular-nums">
                                    {{ labProgress.completed_simulations }}/{{ labProgress.total_simulations }}
                                </span>
                            </div>
                            <ProgressBar :value="Number(labProgress.progress_percent) || 0" tone="physics" size="sm" />
                        </div>
                    </Card>

                    <!-- Directions -->
                    <section v-if="directionList.length" aria-labelledby="dirs-title">
                        <div class="mb-3 flex items-center justify-between">
                            <h2 id="dirs-title" class="text-base font-semibold text-fg">Yo'nalishlar</h2>
                            <Link :href="route('student.courses.index')" class="focus-ring rounded-sm text-13 font-medium text-brand hover:underline">
                                Barchasi
                            </Link>
                        </div>
                        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                            <CategoryCard v-for="direction in directionList" :key="direction.id" :direction="direction" />
                        </div>
                    </section>

                    <!-- New courses -->
                    <Card v-if="newList.length" title="Yangi kurslar" padding="sm">
                        <ul class="divide-y divide-border">
                            <li v-for="course in newList" :key="course.id">
                                <Link :href="route('student.courses.show', course.slug)"
                                    class="focus-ring group -mx-1 flex items-center gap-3 rounded-md px-1 py-2.5">
                                    <CourseThumbnail :src="course.thumbnail_url" alt="" icon-class="h-5 w-5" class="h-12 w-16 shrink-0 rounded-md" />
                                    <span class="min-w-0">
                                        <span class="line-clamp-2 text-sm font-medium text-fg group-hover:text-brand">{{ course.title }}</span>
                                        <span class="block truncate text-xs text-fg-subtle">{{ course.teacher?.full_name }}</span>
                                    </span>
                                </Link>
                            </li>
                        </ul>
                    </Card>
                </aside>
            </div>
        </div>
    </StudentLayout>
</template>
