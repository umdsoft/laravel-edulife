<script setup>
import { Head } from '@inertiajs/vue3';
import { LockClosedIcon } from '@heroicons/vue/24/outline';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Avatar, Button, Card } from '@/Components/UI';

defineProps({
    // only id, first_name and avatar are shared for a private profile
    user: Object,
});
</script>

<template>
    <StudentLayout title="Yopiq profil">
        <Head title="Yopiq profil" />

        <Card class="mx-auto max-w-md">
            <div class="flex flex-col items-center py-6 text-center">
                <Avatar :src="user.avatar" :name="user.first_name" size="2xl" />
                <h1 class="mt-4 text-xl font-semibold text-fg">{{ user.first_name }}</h1>
                <p class="mt-3 flex items-center gap-2 text-sm text-fg-muted">
                    <LockClosedIcon class="h-4 w-4" aria-hidden="true" />
                    Bu o'quvchi profilini yopiq qilgan.
                </p>
                <Button class="mt-6" variant="secondary" :href="route('student.friends.index')">Do'stlarga qaytish</Button>
            </div>
        </Card>
    </StudentLayout>
</template>
