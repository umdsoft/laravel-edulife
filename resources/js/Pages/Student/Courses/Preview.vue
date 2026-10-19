<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { EyeIcon, FilmIcon } from '@heroicons/vue/24/outline';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Alert, Badge, Card, EmptyState, PageHeader } from '@/Components/UI';
import VideoPlayer from '@/Components/Student/VideoPlayer.vue';
import RichContent from '@/Components/Student/RichContent.vue';
import EnrollButton from '@/Components/Student/EnrollButton.vue';

const props = defineProps({
    course: Object,
    lesson: Object, // a free / preview lesson, with video and module
});

const hasVideo = computed(() => !!props.lesson.video?.url);
const hasText = computed(() => !!props.lesson.text_content);
</script>

<template>
    <StudentLayout :title="lesson.title">
        <Head :title="`${lesson.title} — ${course.title}`" />

        <PageHeader :title="lesson.title" :description="lesson.module?.title"
            :breadcrumbs="[{ label: 'Kurslar', href: route('student.courses.index') }, { label: course.title, href: route('student.courses.show', course.slug) }, { label: 'Tanishtiruv darsi' }]">
            <template #meta>
                <Badge variant="info" :icon="EyeIcon">Bepul ko'rish</Badge>
            </template>
        </PageHeader>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <VideoPlayer v-if="hasVideo" :src="lesson.video.url" :chapters="lesson.video.chapters || []" />
                <Card v-if="hasText" padding="lg">
                    <article class="mx-auto max-w-[68ch]">
                        <RichContent :html="lesson.text_content" size="lg" />
                    </article>
                </Card>
                <EmptyState v-if="!hasVideo && !hasText" bordered :icon="FilmIcon" title="Bu dars materiali hali yuklanmagan" />
            </div>

            <aside class="space-y-6">
                <Card :title="course.title">
                    <p v-if="course.short_description" class="text-sm text-fg-muted">{{ course.short_description }}</p>
                    <Alert variant="info" class="mt-4">Bu tanishtiruv darsi. Barcha darslar, testlar va sertifikat kursga yozilgach ochiladi.</Alert>
                    <div class="mt-4">
                        <EnrollButton :course="course" />
                    </div>
                </Card>
            </aside>
        </div>
    </StudentLayout>
</template>
