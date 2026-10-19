<script setup>
import { Head } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { PageHeader } from '@/Components/UI';
import PeopleList from '@/Components/Student/PeopleList.vue';

defineProps({
    followers: Object,
});
</script>

<template>
    <StudentLayout title="Kuzatuvchilar">
        <Head title="Kuzatuvchilar" />
        <PageHeader title="Kuzatuvchilar" description="Sizni kuzatayotgan o'quvchilar."
            :breadcrumbs="[{ label: 'Do\'stlar', href: route('student.friends.index') }, { label: 'Kuzatuvchilar' }]" />
        <PeopleList :people="followers" empty-title="Hali kuzatuvchilar yo'q"
            empty-description="Darslarni tugatib, reytingda ko'tarilsangiz, boshqalar sizni topadi." />
    </StudentLayout>
</template>
