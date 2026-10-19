<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { MagnifyingGlassIcon, RectangleStackIcon } from '@heroicons/vue/24/outline';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { PageHeader, Input, EmptyState, Pagination, Avatar, Spinner } from '@/Components/UI';
import CourseCardHorizontal from '@/Components/Student/CourseCardHorizontal.vue';

const props = defineProps({
    query: String,
    courses: Object,
    teachers: Array,
    directions: Array,
});

const searchQuery = ref(props.query);
const searching = ref(false);

const search = debounce(() => {
    router.get(route('student.search.index'), { q: searchQuery.value }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onStart: () => (searching.value = true),
        onFinish: () => (searching.value = false),
    });
}, 300);

watch(searchQuery, search);
</script>

<template>

    <Head title="Qidiruv" />

    <StudentLayout title="Qidiruv">
        <PageHeader title="Qidiruv" description="Kurslar, o'qituvchilar va yo'nalishlarni toping." />

        <div class="space-y-8">
            <form role="search" class="max-w-2xl" @submit.prevent="search">
                <Input v-model="searchQuery" type="search" size="lg" :leading-icon="MagnifyingGlassIcon"
                    placeholder="Masalan: kinematika, kvadrat tenglama…" aria-label="Qidiruv so'zi" autofocus>
                    <template v-if="searching" #trailing>
                        <Spinner size="mr-2 h-4 w-4 text-fg-subtle" label="Qidirilmoqda" />
                    </template>
                </Input>
            </form>

            <div v-if="query" class="space-y-8" aria-live="polite">
                <p class="text-sm text-fg-muted">
                    <span class="font-medium text-fg">"{{ query }}"</span> bo'yicha natijalar
                </p>

                <!-- Teachers -->
                <section v-if="teachers.length > 0" aria-labelledby="teachers-title">
                    <h2 id="teachers-title" class="mb-3 text-base font-semibold text-fg">O'qituvchilar</h2>
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <div v-for="teacher in teachers" :key="teacher.id"
                            class="flex items-center gap-3 rounded-lg border border-border bg-surface p-4 shadow-xs">
                            <Avatar :src="teacher.avatar_url" :name="teacher.full_name || ''" size="lg" />
                            <div class="min-w-0">
                                <h3 class="truncate text-sm font-semibold text-fg">{{ teacher.full_name }}</h3>
                                <p class="text-13 text-fg-subtle tabular-nums">{{ teacher.courses_count }} ta kurs</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Courses -->
                <section v-if="courses.data.length > 0" aria-labelledby="courses-title" class="space-y-4">
                    <h2 id="courses-title" class="text-base font-semibold text-fg">
                        Kurslar <span v-if="courses.total" class="font-normal text-fg-subtle tabular-nums">· {{ courses.total }}</span>
                    </h2>
                    <div class="space-y-3">
                        <CourseCardHorizontal v-for="course in courses.data" :key="course.id" :course="course" />
                    </div>
                    <Pagination :data="courses" />
                </section>

                <EmptyState v-else-if="teachers.length === 0" bordered size="lg" :icon="MagnifyingGlassIcon"
                    title="Hech narsa topilmadi" description="Boshqa so'z bilan izlab ko'ring yoki yozilishini tekshiring." />
            </div>

            <!-- Default view: directions -->
            <section v-else aria-labelledby="dirs-title">
                <h2 id="dirs-title" class="mb-3 text-base font-semibold text-fg">Ommabop yo'nalishlar</h2>
                <div v-if="directions?.length" class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-4">
                    <Link v-for="direction in directions" :key="direction.id"
                        :href="route('student.courses.category', direction.id)"
                        class="focus-ring group flex flex-col items-start gap-3 rounded-lg border border-border bg-surface p-4 shadow-xs transition-shadow duration-150 hover:border-border-strong hover:shadow-md">
                        <span class="flex h-10 w-10 items-center justify-center rounded-md bg-brand-50 text-brand">
                            <img v-if="direction.icon_url" :src="direction.icon_url" alt="" class="h-6 w-6 object-contain">
                            <RectangleStackIcon v-else class="h-5 w-5" aria-hidden="true" />
                        </span>
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-semibold text-fg">{{ direction.name }}</span>
                            <span class="block text-13 text-fg-subtle tabular-nums">{{ direction.courses_count }} ta kurs</span>
                        </span>
                    </Link>
                </div>
                <EmptyState v-else bordered size="sm" :icon="RectangleStackIcon" title="Yo'nalishlar hali yo'q" />
            </section>
        </div>
    </StudentLayout>
</template>
