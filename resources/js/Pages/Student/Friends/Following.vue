<script setup>
import { Head } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { PageHeader } from '@/Components/UI';
import PeopleList from '@/Components/Student/PeopleList.vue';

defineProps({
    following: Object,
});
</script>

<template>
    <StudentLayout title="Kuzatilmoqda">
        <Head title="Kuzatilmoqda" />
        <PageHeader title="Kuzatilmoqda" description="Siz kuzatayotgan o'quvchilar."
            :breadcrumbs="[{ label: 'Do\'stlar', href: route('student.friends.index') }, { label: 'Kuzatilmoqda' }]" />
        <PeopleList :people="following" all-followed empty-title="Hech kimni kuzatmayapsiz"
            empty-description="Reytingdan o'quvchilarni topib kuzating — ularning yutuqlari lentada ko'rinadi." />
    </StudentLayout>
</template>
