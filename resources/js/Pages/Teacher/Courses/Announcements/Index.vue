<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { PlusIcon, MegaphoneIcon } from '@heroicons/vue/24/outline';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Card from '@/Components/UI/Card.vue';
import Button from '@/Components/UI/Button.vue';
import Modal from '@/Components/UI/Modal.vue';
import Input from '@/Components/UI/Input.vue';
import Select from '@/Components/UI/Select.vue';
import Textarea from '@/Components/UI/Textarea.vue';
import Checkbox from '@/Components/UI/Checkbox.vue';
import Switch from '@/Components/UI/Switch.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import CourseNav from '@/Components/Teacher/CourseNav.vue';
import AnnouncementCard from '@/Components/Teacher/AnnouncementCard.vue';
import { ANNOUNCEMENT_TYPES } from '@/Components/Teacher/format';

const props = defineProps({
    course: Object,
    announcements: Object,
});

const showCreateModal = ref(false);
const form = useForm({
    title: '',
    content: '',
    type: 'info',
    is_pinned: false,
    send_notification: true,
    publish_now: true,
    published_at: '',
});

const openCreate = () => {
    form.clearErrors();
    showCreateModal.value = true;
};

const submit = () => {
    form.post(route('teacher.courses.announcements.store', props.course.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            showCreateModal.value = false;
        },
    });
};
</script>

<template>
    <TeacherLayout>
        <Head :title="`${course.title} — E'lonlar`" />

        <PageHeader title="E'lonlar" description="Kurs o'quvchilari uchun yangiliklar va eslatmalar."
            :breadcrumbs="[{ label: 'Kurslarim', href: route('teacher.courses.index') }, { label: course.title, href: route('teacher.courses.edit', course.id) }, { label: 'E\'lonlar' }]">
            <template #actions>
                <Button :icon-left="PlusIcon" @click="openCreate">E'lon yaratish</Button>
            </template>
            <template #tabs>
                <CourseNav :course="course" active="announcements" />
            </template>
        </PageHeader>

        <div class="max-w-3xl space-y-4">
            <AnnouncementCard v-for="announcement in announcements.data" :key="announcement.id"
                :announcement="announcement" :course="course" />

            <Card v-if="announcements.data.length === 0">
                <EmptyState :icon="MegaphoneIcon" title="E'lonlar yo'q"
                    description="Imtihon sanasi, yangi dars yoki uy vazifasi haqida o'quvchilarga xabar bering.">
                    <Button :icon-left="PlusIcon" @click="openCreate">E'lon yaratish</Button>
                </EmptyState>
            </Card>

            <div v-if="announcements.links && announcements.links.length > 3" class="overflow-hidden rounded-lg border border-border">
                <Pagination :data="announcements" />
            </div>
        </div>

        <!-- Create modal -->
        <Modal :show="showCreateModal" title="Yangi e'lon" size="lg" @close="showCreateModal = false">
            <form id="announcement-create-form" class="space-y-5 pb-2" @submit.prevent="submit">
                <Input v-model="form.title" label="Sarlavha" required autofocus maxlength="255" placeholder="E'lon sarlavhasi"
                    :error="form.errors.title" />
                <Select v-model="form.type" label="Turi" :options="ANNOUNCEMENT_TYPES" :error="form.errors.type" />
                <Textarea v-model="form.content" label="Matn" required :rows="5" autoresize maxlength="10000"
                    placeholder="E'lon matni…" hint="Kamida 20 belgi" :error="form.errors.content" />

                <div class="space-y-4 border-t border-border pt-5">
                    <Switch v-model="form.publish_now" label="Hozir e'lon qilish" description="O'chirsangiz, e'lon belgilangan vaqtda chiqadi." />
                    <Input v-if="!form.publish_now" v-model="form.published_at" type="datetime-local" label="E'lon qilish vaqti" required
                        :error="form.errors.published_at" class="sm:max-w-xs" />
                    <Checkbox v-model="form.send_notification" label="O'quvchilarga bildirishnoma yuborish" />
                    <Checkbox v-model="form.is_pinned" label="Yuqoriga qadash" description="Qadalgan e'lon ro'yxat boshida turadi." />
                </div>
            </form>
            <template #footer>
                <Button variant="secondary" @click="showCreateModal = false">Bekor qilish</Button>
                <Button type="submit" form="announcement-create-form" :loading="form.processing">E'lon qilish</Button>
            </template>
        </Modal>
    </TeacherLayout>
</template>
