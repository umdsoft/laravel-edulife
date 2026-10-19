<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { InformationCircleIcon } from '@heroicons/vue/24/outline';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Button from '@/Components/UI/Button.vue';
import CourseDetailsFields from '@/Components/Teacher/CourseDetailsFields.vue';

defineProps({
    directions: Array,
    tags: Array,
});

const form = useForm({
    title: '',
    direction_id: '',
    level: 'beginner',
    short_description: '',
    description: '',
    price: '',
    discount_price: '',
    is_free: false,
    requirements: [],
    what_will_learn: [],
    who_is_for: [],
    tags: [],
    // Helpers
    new_requirement: '',
    new_learn_item: '',
    new_target_audience: '',
});

const submit = () => {
    form.post(route('teacher.courses.store'));
};
</script>

<template>
    <Head title="Yangi kurs yaratish" />

    <TeacherLayout>
        <PageHeader title="Yangi kurs" description="Avval asosiy ma'lumotlarni kiriting. Saqlangach modul, dars va testlarni qo'shasiz."
            :breadcrumbs="[{ label: 'Kurslarim', href: route('teacher.courses.index') }, { label: 'Yangi kurs' }]" />

        <form class="max-w-[40rem]" novalidate @submit.prevent="submit">
            <CourseDetailsFields :form="form" :directions="directions" :tags="tags" />

            <p class="mt-6 flex items-start gap-2 text-13 text-fg-subtle">
                <InformationCircleIcon class="mt-px h-4 w-4 shrink-0" aria-hidden="true" />
                Kurs qoralama sifatida saqlanadi — tekshiruvga yuborguningizcha o'quvchilarga ko'rinmaydi.
            </p>

            <div class="sticky bottom-0 z-10 -mx-4 mt-6 flex justify-end gap-2 border-t border-border bg-bg/90 px-4 py-3 backdrop-blur sm:mx-0 sm:rounded-b-lg sm:px-0">
                <Button variant="secondary" :href="route('teacher.courses.index')">Bekor qilish</Button>
                <Button type="submit" :loading="form.processing">Kursni yaratish</Button>
            </div>
        </form>
    </TeacherLayout>
</template>
