<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { PlusIcon, BookOpenIcon, MagnifyingGlassIcon, FunnelIcon } from '@heroicons/vue/24/outline';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Button from '@/Components/UI/Button.vue';
import Input from '@/Components/UI/Input.vue';
import Tabs from '@/Components/UI/Tabs.vue';
import Card from '@/Components/UI/Card.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import CourseCard from '@/Components/Teacher/CourseCard.vue';
import { useServerFilters } from '@/Composables/useServerFilters';

const props = defineProps({
    courses: Object,
    filters: { type: Object, default: () => ({}) },
    // { status: count } over all of the teacher's courses
    statusCounts: { type: Object, default: () => ({}) },
});

const { filters, reset: resetFilters } = useServerFilters(
    { search: props.filters.search || '', status: props.filters.status || '' },
    route('teacher.courses.index'),
    { only: ['courses', 'filters', 'statusCounts'] },
);

// Tabs need a key for "all"; the backend expects no status
const status = computed({
    get: () => filters.status || 'all',
    set: (key) => { filters.status = key === 'all' ? '' : key; },
});

const list = computed(() => props.courses?.data || []);
const totalCourses = computed(() => Object.values(props.statusCounts || {}).reduce((sum, n) => sum + Number(n || 0), 0));
const countOf = (s) => Number(props.statusCounts?.[s] || 0);
const hasFilters = computed(() => !!(filters.search || filters.status));

const tabs = computed(() => [
    { key: 'all', label: 'Barchasi', count: totalCourses.value },
    { key: 'published', label: 'Nashr qilingan', count: countOf('published') },
    { key: 'draft', label: 'Qoralama', count: countOf('draft') },
    { key: 'pending', label: 'Tekshiruvda', count: countOf('pending') },
    { key: 'approved', label: 'Tasdiqlangan', count: countOf('approved') },
    { key: 'rejected', label: 'Rad etilgan', count: countOf('rejected') },
].filter((t) => t.key === 'all' || t.key === status.value || t.count > 0));
</script>

<template>
    <Head title="Kurslarim" />

    <TeacherLayout>
        <PageHeader title="Kurslarim" description="Kurslaringizni yarating, tahrirlang va tekshiruvga yuboring.">
            <template #actions>
                <Button :href="route('teacher.courses.create')" :icon-left="PlusIcon">Yangi kurs</Button>
            </template>
        </PageHeader>

        <div v-if="totalCourses || hasFilters" class="space-y-5">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <Tabs v-model="status" :tabs="tabs" variant="pills" label="Holat bo'yicha" />
                <Input v-model="filters.search" type="search" placeholder="Kurs nomi bo'yicha qidirish" :leading-icon="MagnifyingGlassIcon"
                    aria-label="Kurs qidirish" class="md:w-72" />
            </div>

            <div v-if="list.length" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <CourseCard v-for="course in list" :key="course.id" :course="course" />
            </div>
            <Card v-else>
                <EmptyState :icon="FunnelIcon" title="Mos kurs topilmadi" description="Filtr yoki qidiruv so'zini o'zgartirib ko'ring.">
                    <Button variant="secondary" @click="resetFilters">Filtrni tozalash</Button>
                </EmptyState>
            </Card>

            <div v-if="courses.links && courses.links.length > 3" class="overflow-hidden rounded-lg border border-border">
                <Pagination :data="courses" />
            </div>
        </div>

        <Card v-else>
            <EmptyState :icon="BookOpenIcon" size="lg" title="Hali kurs yo'q"
                description="Birinchi kursingizni yarating. Keyin modul, dars va testlarni qo'shasiz.">
                <Button :href="route('teacher.courses.create')" :icon-left="PlusIcon">Birinchi kursni yaratish</Button>
            </EmptyState>
        </Card>
    </TeacherLayout>
</template>
