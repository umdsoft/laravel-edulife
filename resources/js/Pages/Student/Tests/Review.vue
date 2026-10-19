<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { ArrowLeftIcon } from '@heroicons/vue/20/solid';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { PageHeader, Button, Badge, EmptyState } from '@/Components/UI';
import QuestionReview from '@/Components/Student/QuestionReview.vue';

const props = defineProps({
    attempt: Object,
    test: Object,
    course: Object,
    answers: Array
});

const correct = computed(() => (props.answers || []).filter((a) => a.is_correct).length);
</script>

<template>
    <StudentLayout title="Javoblar tahlili">

        <Head :title="test.title + ' - Tahlil'" />

        <div class="mx-auto max-w-3xl">
            <PageHeader title="Javoblar tahlili" :description="test.title" :back="route('student.tests.result', attempt.id)"
                back-label="Natijaga qaytish">
                <template #actions>
                    <div class="text-right">
                        <div class="text-2xl font-semibold tracking-tight tabular-nums" :class="attempt.is_passed ? 'text-success' : 'text-danger'">
                            {{ Math.round(attempt.score) }}%
                        </div>
                        <div class="text-13 text-fg-subtle">Umumiy natija</div>
                    </div>
                </template>
                <template #meta>
                    <Badge variant="success" dot><span class="tabular-nums">{{ correct }}</span> to'g'ri</Badge>
                    <Badge variant="danger" dot><span class="tabular-nums">{{ (answers?.length || 0) - correct }}</span> xato</Badge>
                </template>
            </PageHeader>

            <div v-if="answers?.length" class="space-y-4">
                <QuestionReview v-for="(answer, index) in answers" :key="answer.id" :answer="answer" :index="index" />
            </div>
            <EmptyState v-else bordered title="Tahlil uchun javoblar yo'q" />

            <div class="mt-8 flex justify-center">
                <Button variant="secondary" :icon-left="ArrowLeftIcon" :href="route('student.tests.index', course.id)">
                    Testlar ro'yxatiga qaytish
                </Button>
            </div>
        </div>
    </StudentLayout>
</template>
