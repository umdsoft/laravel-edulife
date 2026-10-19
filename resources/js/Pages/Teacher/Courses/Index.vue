<script setup>
import { computed, ref } from 'vue';
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

const props = defineProps({
    courses: Object,
});

const status = ref('all');
const search = ref('');

const list = computed(() => props.courses?.data || []);
const countOf = (s) => list.value.filter((c) => c.status === s).length;

const tabs = computed(() => [
    { key: 'all', label: 'Barchasi', count: list.value.length },
    { key: 'published', label: 'Nashr qilingan', count: countOf('published') },
    { key: 'draft', label: 'Qoralama', count: countOf('draft') },
    { key: 'pending', label: 'Tekshiruvda', count: countOf('pending') },
    { key: 'rejected', label: 'Rad etilgan', count: countOf('rejected') },
].filter((t) => t.key === 'all' || t.key === status.value || t.count > 0));

const filtered = computed(() => {
    const q = search.value.trim().toLowerCase();
    return list.value.filter((c) =>
        (status.value === 'all' || c.status === status.value) && (!q || String(c.title || '').toLowerCase().includes(q)));
});

const resetFilters = () => {
    status.value = 'all';
    search.value = '';
};
</script>

<template>
    <Head title="Kurslarim" />

    <TeacherLayout>
        <PageHeader title="Kurslarim" description="Kurslaringizni yarating, tahrirlang va tekshiruvga yuboring.">
            <template #actions>
                <Button :href="route('teacher.courses.create')" :icon-left="PlusIcon">Yangi kurs</Button>
            </template>
        </PageHeader>

        <div v-if="list.length || courses.total" class="space-y-5">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <Tabs v-model="status" :tabs="tabs" variant="pills" label="Holat bo'yicha" />
                <Input v-model="search" type="search" placeholder="Kurs nomi bo'yicha qidirish" :leading-icon="MagnifyingGlassIcon"
                    aria-label="Kurs qidirish" class="md:w-72" />
            </div>

            <div v-if="filtered.length" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <CourseCard v-for="course in filtered" :key="course.id" :course="course" />
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
