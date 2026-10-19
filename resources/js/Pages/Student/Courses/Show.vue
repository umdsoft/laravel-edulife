<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    PlayCircleIcon, DocumentTextIcon, DevicePhoneMobileIcon, TrophyIcon, VideoCameraIcon, UsersIcon, CalendarIcon,
    ChatBubbleLeftEllipsisIcon, BookOpenIcon,
} from '@heroicons/vue/24/outline';
import { HeartIcon as HeartSolid, CheckIcon, LockClosedIcon, PlayIcon } from '@heroicons/vue/20/solid';
import { HeartIcon } from '@heroicons/vue/24/outline';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { PageHeader, Card, Button, Badge, Avatar, EmptyState } from '@/Components/UI';
import CourseTabs from '@/Components/Student/CourseTabs.vue';
import EnrollButton from '@/Components/Student/EnrollButton.vue';
import CourseCard from '@/Components/Student/CourseCard.vue';
import CourseThumbnail from '@/Components/Student/CourseThumbnail.vue';
import StarRating from '@/Components/Student/StarRating.vue';
import RichContent from '@/Components/Student/RichContent.vue';
import { formatDate, formatNumber, formatPrice } from '@/Components/Student/format.js';
import { useCourseSeo } from '@/Composables/useSeo.js';
import { courseSchema, breadcrumbSchema } from '@/Utils/schema.js';

const props = defineProps({
    course: Object,
    enrollment: Object,
    isEnrolled: Boolean,
    isInWishlist: Boolean,
    similarCourses: Array,
    ratingDistribution: Object,
});

// SEO
const { seoMeta } = useCourseSeo(props.course);

// Schema.org JSON-LD
const courseJsonLd = computed(() => courseSchema(props.course));
const breadcrumbJsonLd = computed(() => breadcrumbSchema([
    { name: 'Bosh sahifa', url: 'https://edulife.uz' },
    { name: 'Kurslar', url: 'https://edulife.uz/courses' },
    { name: props.course.direction?.name, url: `https://edulife.uz/courses/category/${props.course.direction?.slug}` },
    { name: props.course.title },
]));

const activeTab = ref('about');

const tabs = computed(() => [
    { id: 'about', name: 'Kurs haqida' },
    { id: 'curriculum', name: 'Dastur', count: props.course.lessons_count },
    { id: 'reviews', name: 'Sharhlar', count: props.course.reviews_count },
]);

const rating = computed(() => Number(props.course.reviews_avg_rating || 0));
const price = computed(() => formatPrice(props.course.price, props.course.is_free));

const breadcrumbs = computed(() => [
    { label: 'Kurslar', href: route('student.courses.index') },
    ...(props.course.direction ? [{ label: props.course.direction.name, href: route('student.courses.category', props.course.direction.id) }] : []),
    { label: props.course.title },
]);

const distribution = computed(() => {
    const total = Number(props.course.reviews_count) || 0;
    return [5, 4, 3, 2, 1].map((star) => {
        const count = Number(props.ratingDistribution?.[star] || 0);
        return { star, count, pct: total ? Math.round((count / total) * 100) : 0 };
    });
});

const totalLessons = (module) => module.lessons?.length || 0;

const wishlistBusy = ref(false);
const toggleWishlist = () => {
    wishlistBusy.value = true;
    router.post(route('student.wishlist.toggle', props.course.id), {}, {
        preserveScroll: true,
        onFinish: () => (wishlistBusy.value = false),
    });
};
</script>

<template>
    <!-- Full SEO Meta Tags -->
    <Head>
        <title>{{ seoMeta.title }}</title>
        <meta name="description" :content="seoMeta.description" />
        <link rel="canonical" :href="seoMeta.ogUrl" />

        <!-- Open Graph -->
        <meta property="og:title" :content="seoMeta.ogTitle" />
        <meta property="og:description" :content="seoMeta.ogDescription" />
        <meta property="og:image" :content="seoMeta.ogImage" />
        <meta property="og:url" :content="seoMeta.ogUrl" />
        <meta property="og:type" content="article" />

        <!-- Twitter -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" :content="seoMeta.twitterTitle" />
        <meta name="twitter:description" :content="seoMeta.twitterDescription" />
        <meta name="twitter:image" :content="seoMeta.twitterImage" />

        <!-- Course + breadcrumb schema (a plain <script> in a template is dropped by Vue) -->
        <component :is="'script'" type="application/ld+json">{{ JSON.stringify(courseJsonLd) }}</component>
        <component :is="'script'" type="application/ld+json">{{ JSON.stringify(breadcrumbJsonLd) }}</component>
    </Head>

    <StudentLayout :title="course.title">
        <PageHeader :title="course.title" :breadcrumbs="breadcrumbs"
            :description="course.short_description || course.description?.replace(/<[^>]*>/g, '').substring(0, 200)">
            <template #meta>
                <Badge v-if="course.direction?.name" variant="brand">{{ course.direction.name }}</Badge>
                <StarRating :value="rating" show-value :count="course.reviews_count || 0" />
                <span class="inline-flex items-center gap-1.5 text-13 text-fg-muted tabular-nums">
                    <UsersIcon class="h-4 w-4 text-fg-subtle" aria-hidden="true" />
                    {{ formatNumber(course.enrollments_count || 0) }} o'quvchi
                </span>
                <span v-if="course.updated_at" class="inline-flex items-center gap-1.5 text-13 text-fg-muted">
                    <CalendarIcon class="h-4 w-4 text-fg-subtle" aria-hidden="true" />
                    Yangilangan: <span class="tabular-nums">{{ formatDate(course.updated_at) }}</span>
                </span>
            </template>
        </PageHeader>

        <div class="grid gap-6 lg:grid-cols-3 lg:items-start">
            <!-- Purchase / progress card (first on mobile, right rail on desktop) -->
            <aside class="lg:sticky lg:top-20 lg:order-last">
                <Card padding="none" class="overflow-hidden">
                    <CourseThumbnail :src="course.thumbnail_url" :alt="`${course.title} kursi muqovasi`" :lazy="false"
                        class="aspect-video" icon-class="h-10 w-10" />

                    <div class="space-y-4 p-5">
                        <div class="flex items-baseline justify-between gap-3">
                            <span class="text-2xl font-semibold tracking-tight tabular-nums" :class="course.is_free ? 'text-success' : 'text-fg'">
                                {{ price }}
                            </span>
                            <Badge v-if="isEnrolled" variant="success" dot>Yozilgansiz</Badge>
                        </div>

                        <Button v-if="isEnrolled" size="lg" block :icon-left="PlayIcon" :href="route('student.learn.course', course.slug)">
                            O'qishni davom ettirish
                        </Button>
                        <EnrollButton v-else :course="course" :is-enrolled="isEnrolled" />

                        <Button variant="secondary" block :loading="wishlistBusy" :aria-pressed="isInWishlist" @click="toggleWishlist">
                            <template #leading>
                                <HeartSolid v-if="isInWishlist" class="h-[18px] w-[18px] text-danger" />
                                <HeartIcon v-else class="h-[18px] w-[18px]" />
                            </template>
                            {{ isInWishlist ? "Istaklardan olib tashlash" : "Istaklar ro'yxatiga qo'shish" }}
                        </Button>

                        <div class="border-t border-border pt-4">
                            <h2 class="mb-3 text-sm font-semibold text-fg">Bu kursda</h2>
                            <ul class="space-y-2.5 text-sm text-fg-muted">
                                <li class="flex items-center gap-3">
                                    <VideoCameraIcon class="h-5 w-5 shrink-0 text-fg-subtle" aria-hidden="true" />
                                    <span><span class="tabular-nums">{{ course.total_duration || 0 }}</span> soat video darslar</span>
                                </li>
                                <li class="flex items-center gap-3">
                                    <BookOpenIcon class="h-5 w-5 shrink-0 text-fg-subtle" aria-hidden="true" />
                                    <span><span class="tabular-nums">{{ course.lessons_count || 0 }}</span> ta dars</span>
                                </li>
                                <li class="flex items-center gap-3">
                                    <DevicePhoneMobileIcon class="h-5 w-5 shrink-0 text-fg-subtle" aria-hidden="true" />
                                    <span>Telefonda ham qulay</span>
                                </li>
                                <li class="flex items-center gap-3">
                                    <TrophyIcon class="h-5 w-5 shrink-0 text-fg-subtle" aria-hidden="true" />
                                    <span>Bitiruv sertifikati</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </Card>
            </aside>

            <!-- Main column -->
            <div class="min-w-0 space-y-6 lg:col-span-2">
                <Card padding="none">
                    <div class="px-5 pt-2 sm:px-6">
                        <CourseTabs :tabs="tabs" v-model:activeTab="activeTab" />
                    </div>

                    <div class="p-5 sm:p-6">
                        <!-- About -->
                        <section v-if="activeTab === 'about'" class="space-y-6" aria-labelledby="about-heading">
                            <h2 id="about-heading" class="sr-only">Kurs haqida</h2>
                            <RichContent v-if="course.description" :html="course.description" />
                            <p v-else class="text-sm text-fg-subtle">Kurs tavsifi hali qo'shilmagan.</p>

                            <div v-if="course.what_you_learn?.length" class="rounded-lg border border-border bg-surface-2/60 p-5">
                                <h3 class="mb-4 text-base font-semibold text-fg">Nimalarni o'rganasiz</h3>
                                <ul class="grid gap-3 md:grid-cols-2">
                                    <li v-for="(item, index) in course.what_you_learn" :key="index" class="flex items-start gap-2.5 text-sm text-fg">
                                        <CheckIcon class="mt-0.5 h-4 w-4 shrink-0 text-success" aria-hidden="true" />
                                        <span>{{ item }}</span>
                                    </li>
                                </ul>
                            </div>
                        </section>

                        <!-- Curriculum -->
                        <section v-else-if="activeTab === 'curriculum'" class="space-y-3" aria-labelledby="curriculum-heading">
                            <h2 id="curriculum-heading" class="sr-only">O'quv dasturi</h2>
                            <div v-for="(module, mi) in course.modules" :key="module.id"
                                class="overflow-hidden rounded-lg border border-border">
                                <div class="flex items-center justify-between gap-3 bg-surface-2/60 px-4 py-3">
                                    <h3 class="flex min-w-0 items-center gap-2.5 text-sm font-semibold text-fg">
                                        <span class="text-xs text-fg-subtle tabular-nums">{{ String(mi + 1).padStart(2, '0') }}</span>
                                        <span class="truncate">{{ module.title }}</span>
                                    </h3>
                                    <span class="shrink-0 text-13 text-fg-subtle tabular-nums">{{ totalLessons(module) }} ta dars</span>
                                </div>
                                <ul class="divide-y divide-border">
                                    <li v-for="lesson in module.lessons" :key="lesson.id"
                                        class="flex items-center justify-between gap-3 px-4 py-3">
                                        <div class="flex min-w-0 items-center gap-3">
                                            <PlayCircleIcon v-if="lesson.type === 'video'" class="h-5 w-5 shrink-0 text-fg-subtle" aria-hidden="true" />
                                            <DocumentTextIcon v-else class="h-5 w-5 shrink-0 text-fg-subtle" aria-hidden="true" />
                                            <span class="truncate text-sm text-fg">{{ lesson.title }}</span>
                                        </div>
                                        <div class="flex shrink-0 items-center gap-3">
                                            <span v-if="lesson.duration_minutes" class="hidden text-13 text-fg-subtle tabular-nums sm:inline">
                                                {{ lesson.duration_minutes }} daq
                                            </span>
                                            <Button v-if="lesson.is_preview || isEnrolled" variant="soft" size="sm"
                                                :href="route('student.courses.preview', [course.slug, lesson.id])">
                                                {{ isEnrolled ? "Ko'rish" : 'Namuna' }}
                                            </Button>
                                            <span v-else class="flex h-8 w-8 items-center justify-center text-fg-subtle">
                                                <LockClosedIcon class="h-4 w-4" aria-hidden="true" />
                                                <span class="sr-only">Yopiq dars</span>
                                            </span>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                            <EmptyState v-if="!course.modules?.length" size="sm" :icon="BookOpenIcon" title="Dastur hali e'lon qilinmagan" />
                        </section>

                        <!-- Reviews -->
                        <section v-else-if="activeTab === 'reviews'" class="space-y-6" aria-labelledby="reviews-heading">
                            <h2 id="reviews-heading" class="sr-only">Sharhlar</h2>

                            <div class="flex flex-col gap-6 rounded-lg border border-border bg-surface-2/60 p-5 sm:flex-row sm:items-center">
                                <div class="text-center sm:w-36">
                                    <div class="text-4xl font-semibold tracking-tight text-fg tabular-nums">{{ rating.toFixed(1) }}</div>
                                    <StarRating :value="rating" size="sm" class="mt-1 justify-center" />
                                    <div class="mt-1 text-13 text-fg-subtle tabular-nums">{{ course.reviews_count || 0 }} ta sharh</div>
                                </div>
                                <ul class="flex-1 space-y-2">
                                    <li v-for="row in distribution" :key="row.star" class="flex items-center gap-3 text-13">
                                        <span class="w-16 shrink-0 text-fg-muted tabular-nums">{{ row.star }} yulduz</span>
                                        <span class="h-2 flex-1 overflow-hidden rounded-full bg-surface-3">
                                            <span class="block h-full rounded-full bg-warning-600" :style="{ width: `${row.pct}%` }" />
                                        </span>
                                        <span class="w-10 shrink-0 text-right text-fg-subtle tabular-nums">{{ row.pct }}%</span>
                                    </li>
                                </ul>
                            </div>

                            <ul v-if="course.reviews?.length" class="divide-y divide-border">
                                <li v-for="review in course.reviews" :key="review.id" class="py-5 first:pt-0 last:pb-0">
                                    <article class="flex items-start gap-3">
                                        <Avatar :src="review.user?.avatar_url" :name="review.user?.full_name || ''" size="md" />
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1">
                                                <h3 class="text-sm font-semibold text-fg">{{ review.user?.full_name }}</h3>
                                                <time :datetime="review.created_at" class="text-13 text-fg-subtle tabular-nums">{{ formatDate(review.created_at) }}</time>
                                            </div>
                                            <StarRating :value="review.rating" size="xs" class="mt-1" />
                                            <p v-if="review.comment" class="mt-2 whitespace-pre-wrap text-sm leading-relaxed text-fg-muted">{{ review.comment }}</p>
                                        </div>
                                    </article>
                                </li>
                            </ul>
                            <EmptyState v-else size="sm" :icon="ChatBubbleLeftEllipsisIcon" title="Hali sharhlar yo'q"
                                description="Kursni tugatgach, birinchi bo'lib fikr bildiring." />
                        </section>
                    </div>
                </Card>
            </div>
        </div>

        <!-- Similar courses -->
        <section v-if="similarCourses?.length > 0" class="mt-10" aria-labelledby="similar-courses-heading">
            <h2 id="similar-courses-heading" class="mb-4 text-lg font-semibold text-fg">O'xshash kurslar</h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <CourseCard v-for="item in similarCourses" :key="item.id" :course="item" />
            </div>
        </section>
    </StudentLayout>
</template>
