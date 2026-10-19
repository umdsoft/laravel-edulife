<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { HeartIcon, TrashIcon } from '@heroicons/vue/24/outline';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { PageHeader, EmptyState, Pagination, Button, IconButton, Modal } from '@/Components/UI';
import CourseCardHorizontal from '@/Components/Student/CourseCardHorizontal.vue';

defineProps({
    wishlistItems: Object,
});

// Confirm in a modal instead of window.confirm()
const pending = ref(null);
const removing = ref(false);

const removeFromWishlist = (course) => {
    pending.value = course;
};

const confirmRemove = () => {
    if (!pending.value) return;
    removing.value = true;
    router.post(route('student.wishlist.toggle', pending.value.id), {}, {
        preserveScroll: true,
        onFinish: () => {
            removing.value = false;
            pending.value = null;
        },
    });
};
</script>

<template>

    <Head title="Istaklar ro'yxati" />

    <StudentLayout title="Istaklar">
        <PageHeader title="Istaklar ro'yxati" description="Sizga yoqqan va keyinroq o'qimoqchi bo'lgan kurslar." />

        <div class="space-y-6">
            <div v-if="wishlistItems.data.length > 0" class="space-y-3">
                <CourseCardHorizontal v-for="item in wishlistItems.data" :key="item.id" :course="item.course">
                    <template #actions>
                        <IconButton :icon="TrashIcon" variant="secondary" size="sm" label="Istaklardan olib tashlash"
                            @click="removeFromWishlist(item.course)" />
                    </template>
                </CourseCardHorizontal>
            </div>

            <EmptyState v-else bordered size="lg" :icon="HeartIcon" title="Istaklar ro'yxati bo'sh"
                description="Kurs sahifasidagi «Istaklar ro'yxatiga qo'shish» tugmasi orqali kurslarni saqlang.">
                <Button :href="route('student.courses.index')">Kurslarni ko'rish</Button>
            </EmptyState>

            <Pagination :data="wishlistItems" />
        </div>

        <Modal :show="!!pending" size="sm" title="Istaklardan olib tashlash"
            :description="pending ? `«${pending.title}» kursi ro'yxatdan olib tashlanadi.` : null" @close="pending = null">
            <template #footer>
                <Button variant="secondary" @click="pending = null">Bekor qilish</Button>
                <Button variant="danger" :loading="removing" @click="confirmRemove">Olib tashlash</Button>
            </template>
        </Modal>
    </StudentLayout>
</template>
