<script setup>
import { computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LockClosedIcon, UserCircleIcon } from '@heroicons/vue/24/outline';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Button, Card, PageHeader, Switch, Tabs } from '@/Components/UI';

const props = defineProps({
    settings: { type: Object, default: () => ({}) },
});

const form = useForm({
    is_profile_public: !!props.settings.is_profile_public,
    show_activity: !!props.settings.show_activity,
    show_courses: !!props.settings.show_courses,
    show_achievements: !!props.settings.show_achievements,
    show_stats: !!props.settings.show_stats,
});

const save = () => form.put(route('student.settings.privacy.update'), {
    preserveScroll: true,
    onSuccess: () => form.defaults(),
});

const tabs = computed(() => [
    { key: 'account', label: 'Hisob', icon: UserCircleIcon, href: route('student.settings.account') },
    { key: 'privacy', label: 'Maxfiylik', icon: LockClosedIcon, href: route('student.settings.privacy'), active: true },
]);
</script>

<template>
    <StudentLayout title="Sozlamalar">
        <Head title="Maxfiylik" />

        <PageHeader title="Sozlamalar" description="Boshqa o'quvchilar profilingizda nimani ko'rishini tanlang.">
            <template #tabs>
                <Tabs :tabs="tabs" label="Sozlamalar bo'limlari" />
            </template>
        </PageHeader>

        <form class="max-w-xl space-y-6" @submit.prevent="save">
            <Card title="Profil">
                <Switch v-model="form.is_profile_public" label="Ochiq profil"
                    description="O'chirilsa, boshqalar faqat ismingiz va rasmingizni ko'radi." />
            </Card>

            <Card title="Profilda ko'rinadigan ma'lumotlar" description="Faqat ochiq profil uchun amal qiladi.">
                <div class="space-y-5">
                    <Switch v-model="form.show_stats" :disabled="!form.is_profile_public" label="Statistika"
                        description="Daraja, XP, seriya va kuzatuvchilar soni" />
                    <Switch v-model="form.show_activity" :disabled="!form.is_profile_public" label="Faoliyat"
                        description="Tugatilgan darslar, o'tilgan testlar, sertifikatlar" />
                    <Switch v-model="form.show_courses" :disabled="!form.is_profile_public" label="Kurslar" description="Siz o'qiyotgan kurslar" />
                    <Switch v-model="form.show_achievements" :disabled="!form.is_profile_public" label="Yutuqlar" description="Olingan nishon va yutuqlar" />
                </div>
            </Card>

            <div class="flex justify-end">
                <Button type="submit" :loading="form.processing" :disabled="!form.isDirty">Saqlash</Button>
            </div>
        </form>
    </StudentLayout>
</template>
