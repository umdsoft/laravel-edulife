<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import {
    DocumentTextIcon, RectangleStackIcon, TrophyIcon, ClipboardDocumentCheckIcon, ClockIcon as ClockOutline,
} from '@heroicons/vue/24/outline';
import { ClockIcon, QuestionMarkCircleIcon, FlagIcon, ArrowPathIcon, PlayIcon } from '@heroicons/vue/20/solid';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { PageHeader, Card, Button, Badge, EmptyState } from '@/Components/UI';
import { routePath } from '@/Utils/nav';

const props = defineProps({
    course: Object,
    tests: Object,
    enrollment: Object,
});

const getTestStatus = (test) => {
    if (test.is_passed) return { label: "O'tilgan", variant: 'success' };
    if (test.has_active_attempt) return { label: 'Jarayonda', variant: 'info' };
    if (test.can_retake === false) return { label: 'Urinishlar tugadi', variant: 'danger' };
    return { label: 'Mavjud', variant: 'neutral' };
};

const actionLabel = (test) => (test.has_active_attempt ? 'Davom etish' : (test.is_passed ? 'Qayta topshirish' : 'Boshlash'));
const actionVariant = (test) => (test.has_active_attempt || test.can_retake !== false ? 'primary' : 'secondary');

const sections = computed(() => [
    { key: 'lesson', title: 'Dars testlari', icon: DocumentTextIcon, items: props.tests?.lesson_tests || [] },
    { key: 'module', title: 'Modul testlari', icon: RectangleStackIcon, items: props.tests?.module_tests || [] },
].filter((s) => s.items.length));

const isEmpty = computed(() => !sections.value.length && !props.tests?.final_test);
const historyHref = routePath('student.tests.history');
</script>

<template>
    <StudentLayout title="Testlar">

        <Head title="Testlar" />

        <PageHeader title="Testlar" :description="course.title" :back="route('student.learn.course', course.slug)"
            back-label="Kursga qaytish">
            <template v-if="historyHref" #actions>
                <Button variant="secondary" :icon-left="ClockOutline" :href="historyHref">Test tarixi</Button>
            </template>
        </PageHeader>

        <div class="space-y-8">
            <!-- Final exam -->
            <section v-if="tests.final_test" aria-labelledby="final-title">
                <div class="rounded-xl border border-brand-200 bg-brand-50/60 p-5 sm:p-6">
                    <div class="flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
                        <div class="flex items-start gap-4">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-md bg-brand-600 text-white">
                                <TrophyIcon class="h-6 w-6" aria-hidden="true" />
                            </span>
                            <div class="min-w-0">
                                <p class="text-13 font-medium text-brand">Yakuniy imtihon</p>
                                <h2 id="final-title" class="mt-0.5 text-lg font-semibold text-fg">{{ tests.final_test.title }}</h2>
                                <p class="mt-1 text-sm text-fg-muted">Kursni yakunlash va sertifikat olish uchun ushbu imtihondan o'ting.</p>
                                <ul class="mt-3 flex flex-wrap gap-x-5 gap-y-1 text-13 text-fg-muted tabular-nums">
                                    <li class="inline-flex items-center gap-1.5"><ClockIcon class="h-4 w-4 text-fg-subtle" aria-hidden="true" />{{ tests.final_test.time_limit }} daqiqa</li>
                                    <li class="inline-flex items-center gap-1.5"><QuestionMarkCircleIcon class="h-4 w-4 text-fg-subtle" aria-hidden="true" />{{ tests.final_test.questions_count }} savol</li>
                                    <li class="inline-flex items-center gap-1.5"><FlagIcon class="h-4 w-4 text-fg-subtle" aria-hidden="true" />{{ tests.final_test.pass_rate }}% o'tish</li>
                                </ul>
                            </div>
                        </div>
                        <Button size="lg" :icon-left="PlayIcon" :href="route('student.tests.start', tests.final_test.id)" class="w-full md:w-auto">
                            Imtihonni boshlash
                        </Button>
                    </div>
                </div>
            </section>

            <!-- Lesson / module tests -->
            <section v-for="section in sections" :key="section.key" :aria-labelledby="`sec-${section.key}`">
                <h2 :id="`sec-${section.key}`" class="mb-3 flex items-center gap-2 text-base font-semibold text-fg">
                    <component :is="section.icon" class="h-5 w-5 text-fg-subtle" aria-hidden="true" />
                    {{ section.title }}
                    <span class="text-13 font-normal text-fg-subtle tabular-nums">· {{ section.items.length }}</span>
                </h2>

                <Card padding="none">
                    <ul class="divide-y divide-border">
                        <li v-for="test in section.items" :key="test.id"
                            class="flex flex-col gap-4 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-sm font-semibold text-fg">{{ test.title }}</h3>
                                    <Badge v-if="section.key === 'lesson'" :variant="getTestStatus(test).variant" size="sm" dot>
                                        {{ getTestStatus(test).label }}
                                    </Badge>
                                </div>
                                <ul class="mt-1.5 flex flex-wrap gap-x-4 gap-y-1 text-13 text-fg-muted tabular-nums">
                                    <li class="inline-flex items-center gap-1"><ClockIcon class="h-4 w-4 text-fg-subtle" aria-hidden="true" />{{ test.time_limit }} daqiqa</li>
                                    <li class="inline-flex items-center gap-1"><QuestionMarkCircleIcon class="h-4 w-4 text-fg-subtle" aria-hidden="true" />{{ test.questions_count }} savol</li>
                                    <li class="inline-flex items-center gap-1"><FlagIcon class="h-4 w-4 text-fg-subtle" aria-hidden="true" />{{ test.pass_rate }}% o'tish</li>
                                    <li v-if="test.max_attempts" class="inline-flex items-center gap-1">
                                        <ArrowPathIcon class="h-4 w-4 text-fg-subtle" aria-hidden="true" />{{ test.attempts_count || 0 }}/{{ test.max_attempts }} urinish
                                    </li>
                                </ul>
                            </div>

                            <div class="flex items-center justify-between gap-4 sm:justify-end">
                                <div v-if="test.best_score > 0" class="text-left sm:text-right">
                                    <div class="text-base font-semibold tabular-nums" :class="test.is_passed ? 'text-success' : 'text-fg'">
                                        {{ Math.round(test.best_score) }}%
                                    </div>
                                    <div class="text-xs text-fg-subtle">Eng yaxshi natija</div>
                                </div>
                                <Button size="sm" :variant="section.key === 'lesson' ? actionVariant(test) : 'primary'"
                                    :href="route('student.tests.start', test.id)">
                                    {{ section.key === 'lesson' ? actionLabel(test) : 'Boshlash' }}
                                </Button>
                            </div>
                        </li>
                    </ul>
                </Card>
            </section>

            <EmptyState v-if="isEmpty" bordered size="lg" :icon="ClipboardDocumentCheckIcon" title="Bu kursda hali testlar yo'q"
                description="Ustoz testlarni qo'shgach, ular shu yerda paydo bo'ladi." />
        </div>
    </StudentLayout>
</template>
