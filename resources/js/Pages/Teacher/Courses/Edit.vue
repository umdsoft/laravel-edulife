<script setup>
import { computed, ref } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import {
    PaperAirplaneIcon, PhotoIcon, FilmIcon, ArrowUpTrayIcon, DocumentDuplicateIcon, TrashIcon,
} from '@heroicons/vue/24/outline';
import { CheckCircleIcon, MinusCircleIcon } from '@heroicons/vue/20/solid';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Button from '@/Components/UI/Button.vue';
import Badge from '@/Components/UI/Badge.vue';
import Card from '@/Components/UI/Card.vue';
import Tabs from '@/Components/UI/Tabs.vue';
import Input from '@/Components/UI/Input.vue';
import Checkbox from '@/Components/UI/Checkbox.vue';
import Alert from '@/Components/UI/Alert.vue';
import ProgressBar from '@/Components/UI/ProgressBar.vue';
import CourseNav from '@/Components/Teacher/CourseNav.vue';
import CourseDetailsFields from '@/Components/Teacher/CourseDetailsFields.vue';
import ConfirmDialog from '@/Components/Teacher/ConfirmDialog.vue';
import { courseStatus, toAmount } from '@/Components/Teacher/format';

const props = defineProps({
    course: Object,
    directions: Array,
    tags: Array,
});

const page = usePage();
const initialTab = (() => {
    const q = String(page.url || '').split('?')[1] || '';
    const tab = new URLSearchParams(q).get('tab');
    return ['basic', 'media', 'settings'].includes(tab) ? tab : 'basic';
})();
const activeTab = ref(initialTab);
const sectionTabs = [
    { key: 'basic', label: 'Asosiy ma\'lumotlar' },
    { key: 'media', label: 'Rasm va video' },
    { key: 'settings', label: 'Sozlamalar' },
];

const status = computed(() => courseStatus(props.course.status));
const canSubmit = computed(() => ['draft', 'rejected'].includes(props.course.status));

const form = useForm({
    title: props.course.title,
    direction_id: props.course.direction_id,
    level: props.course.difficulty,
    short_description: props.course.short_description,
    description: props.course.description,
    // While discounted, courses.price holds the discounted price and original_price the list price
    // decimal columns arrive as "100000.00"; the number input wants a plain number
    price: toAmount(props.course.discount_price != null ? props.course.original_price : props.course.price),
    discount_price: toAmount(props.course.discount_price),
    is_free: !!props.course.is_free,
    requirements: props.course.requirements || [],
    what_will_learn: props.course.what_you_learn || [],
    who_is_for: props.course.target_audience || [],
    tags: props.course.tags.map(t => t.id),
    // Helpers
    new_requirement: '',
    new_learn_item: '',
    new_target_audience: '',
});

const mediaForm = useForm({
    thumbnail: null,
    preview_video: null,
});

const submitBasic = () => {
    form.put(route('teacher.courses.update', props.course.id), {
        preserveScroll: true,
        // the saved values become the new baseline for the "unsaved changes" hint
        onSuccess: () => form.defaults(),
    });
};

// ---- Media -------------------------------------------------------------
const thumbInput = ref(null);
const videoInput = ref(null);
const uploading = ref(null); // 'thumbnail' | 'preview_video'

const updateThumbnail = (e) => {
    const file = e.target.files[0];
    if (!file) return;

    uploading.value = 'thumbnail';
    mediaForm.thumbnail = file;
    mediaForm.post(route('teacher.courses.thumbnail', props.course.id), {
        preserveScroll: true,
        onSuccess: () => mediaForm.reset('thumbnail'),
        onFinish: () => {
            uploading.value = null;
            e.target.value = '';
        },
    });
};

const updatePreviewVideo = (e) => {
    const file = e.target.files[0];
    if (!file) return;

    uploading.value = 'preview_video';
    mediaForm.preview_video = file;
    mediaForm.post(route('teacher.courses.preview-video', props.course.id), {
        preserveScroll: true,
        onSuccess: () => mediaForm.reset('preview_video'),
        onFinish: () => {
            uploading.value = null;
            e.target.value = '';
        },
    });
};

// ---- Readiness checklist (mirrors CourseController@submit rules) -------
const modules = computed(() => props.course.modules || []);
const lessonsCount = computed(() => modules.value.reduce((s, m) => s + (m.lessons?.length || 0), 0));
const checklist = computed(() => [
    { label: 'Kamida bitta modul', done: modules.value.length > 0, meta: `${modules.value.length} ta` },
    { label: 'Kamida bitta dars', done: lessonsCount.value > 0, meta: `${lessonsCount.value} ta` },
    { label: 'Kurs rasmi yuklangan', done: !!(props.course.thumbnail || props.course.thumbnail_url) },
]);
const ready = computed(() => checklist.value.every((c) => c.done));

// ---- Submit / clone / delete ---------------------------------------------
const confirmSubmit = ref(false);
const submitting = ref(false);
const submitCourse = () => {
    router.patch(route('teacher.courses.submit', props.course.id), {}, {
        preserveScroll: true,
        onStart: () => (submitting.value = true),
        onFinish: () => {
            submitting.value = false;
            confirmSubmit.value = false;
        },
    });
};

const cloneForm = useForm({
    title: props.course.title + ' (Copy)',
    clone_curriculum: true,
    clone_tests: true,
    clone_attachments: true,
    clone_media: true,
});
const confirmClone = ref(false);
const cloneCourse = () => {
    cloneForm.post(route('teacher.courses.clone', props.course.id), {
        onFinish: () => (confirmClone.value = false),
    });
};

const confirmDelete = ref(false);
const deleting = ref(false);
const canDelete = computed(() => props.course.status === 'draft');
const deleteCourse = () => {
    router.delete(route('teacher.courses.destroy', props.course.id), {
        onStart: () => (deleting.value = true),
        onFinish: () => {
            deleting.value = false;
            confirmDelete.value = false;
        },
    });
};
</script>

<template>
    <Head :title="`Tahrirlash: ${course.title}`" />

    <TeacherLayout>
        <PageHeader :title="course.title"
            :breadcrumbs="[{ label: 'Kurslarim', href: route('teacher.courses.index') }, { label: course.title }]">
            <template #meta>
                <Badge :variant="status.variant" dot>{{ status.label }}</Badge>
            </template>
            <template #actions>
                <Button v-if="canSubmit" :icon-left="PaperAirplaneIcon" @click="confirmSubmit = true">
                    Tekshiruvga yuborish
                </Button>
            </template>
            <template #tabs>
                <CourseNav :course="course" active="details" />
            </template>
        </PageHeader>

        <Alert v-if="course.status === 'rejected'" variant="danger" title="Kurs rad etilgan" class="mb-6">
            {{ course.rejection_reason || 'Kamchiliklarni tuzatib, kursni qayta tekshiruvga yuboring.' }}
        </Alert>
        <Alert v-else-if="course.status === 'pending'" variant="info" class="mb-6">
            Kurs tekshiruvda. Natija haqida bildirishnoma olasiz.
        </Alert>

        <Tabs v-model="activeTab" :tabs="sectionTabs" variant="pills" label="Kurs sozlamalari" class="mb-6" />

        <!-- Basic -->
        <div v-if="activeTab === 'basic'" class="grid gap-6 xl:grid-cols-[minmax(0,40rem)_20rem] xl:gap-8">
            <form novalidate @submit.prevent="submitBasic">
                <CourseDetailsFields :form="form" :directions="directions" :tags="tags" />
                <div class="sticky bottom-0 z-10 -mx-4 mt-6 flex items-center justify-end gap-3 border-t border-border bg-bg/90 px-4 py-3 backdrop-blur sm:mx-0 sm:px-0">
                    <span v-if="form.isDirty" class="mr-auto text-13 text-fg-subtle">Saqlanmagan o'zgarishlar bor</span>
                    <Button type="submit" :loading="form.processing" :disabled="!form.isDirty && !form.processing">Saqlash</Button>
                </div>
            </form>

            <aside class="space-y-6">
                <Card title="Nashrga tayyorlik" :description="ready ? 'Kurs tekshiruvga yuborishga tayyor.' : 'Tekshiruvga yuborish uchun quyidagilar kerak.'">
                    <ul class="space-y-3">
                        <li v-for="item in checklist" :key="item.label" class="flex items-center gap-2.5 text-sm">
                            <CheckCircleIcon v-if="item.done" class="h-5 w-5 shrink-0 text-success" aria-hidden="true" />
                            <MinusCircleIcon v-else class="h-5 w-5 shrink-0 text-fg-subtle" aria-hidden="true" />
                            <span :class="item.done ? 'text-fg' : 'text-fg-muted'">{{ item.label }}</span>
                            <span class="sr-only">{{ item.done ? '— bajarilgan' : '— bajarilmagan' }}</span>
                            <span v-if="item.meta" class="ml-auto text-13 tabular-nums text-fg-subtle">{{ item.meta }}</span>
                        </li>
                    </ul>
                    <template #footer>
                        <div class="flex flex-wrap gap-2">
                            <Button size="sm" variant="secondary" :href="route('teacher.courses.modules.index', course.id)">Darslar rejasi</Button>
                            <Button v-if="!course.thumbnail_url" size="sm" variant="ghost" @click="activeTab = 'media'">Rasm yuklash</Button>
                        </div>
                    </template>
                </Card>
            </aside>
        </div>

        <!-- Media -->
        <div v-else-if="activeTab === 'media'" class="max-w-3xl space-y-6">
            <Card title="Kurs rasmi" description="Kurs kartochkasida va sahifasida ko'rinadi. Tavsiya: 1280×720 (16:9), JPG yoki PNG, 5 MB gacha.">
                <div class="flex flex-col gap-5 sm:flex-row sm:items-start">
                    <div class="aspect-video w-full overflow-hidden rounded-md border border-border bg-surface-2 sm:w-72">
                        <img v-if="course.thumbnail_url" :src="course.thumbnail_url" alt="Kurs rasmi" class="h-full w-full object-cover" />
                        <div v-else class="flex h-full w-full flex-col items-center justify-center gap-1 text-fg-subtle">
                            <PhotoIcon class="h-8 w-8" aria-hidden="true" />
                            <span class="text-xs tabular-nums">1280 × 720</span>
                        </div>
                    </div>
                    <div class="flex-1 space-y-3">
                        <input ref="thumbInput" type="file" class="sr-only" accept="image/*" tabindex="-1" aria-hidden="true" @change="updateThumbnail">
                        <Button variant="secondary" :icon-left="ArrowUpTrayIcon" :loading="uploading === 'thumbnail'"
                            :disabled="!!uploading" @click="thumbInput?.click()">
                            {{ course.thumbnail_url ? 'Rasmni almashtirish' : 'Rasm yuklash' }}
                        </Button>
                        <ProgressBar v-if="uploading === 'thumbnail' && mediaForm.progress" :value="mediaForm.progress.percentage || 0"
                            size="sm" label="Yuklanmoqda" show-value />
                        <p v-if="mediaForm.errors.thumbnail" class="text-13 text-danger" role="alert">{{ mediaForm.errors.thumbnail }}</p>
                    </div>
                </div>
            </Card>

            <Card title="Tanishtiruv videosi" description="Kurs haqida qisqa video. MP4 yoki MOV, 100 MB gacha.">
                <div class="flex flex-col gap-5 sm:flex-row sm:items-start">
                    <div class="aspect-video w-full overflow-hidden rounded-md border border-border bg-surface-2 sm:w-72">
                        <video v-if="course.preview_video_url" :src="course.preview_video_url" controls preload="metadata" class="h-full w-full object-cover" />
                        <div v-else class="flex h-full w-full flex-col items-center justify-center gap-1 text-fg-subtle">
                            <FilmIcon class="h-8 w-8" aria-hidden="true" />
                            <span class="text-xs">Video yo'q</span>
                        </div>
                    </div>
                    <div class="flex-1 space-y-3">
                        <input ref="videoInput" type="file" class="sr-only" accept="video/*" tabindex="-1" aria-hidden="true" @change="updatePreviewVideo">
                        <Button variant="secondary" :icon-left="ArrowUpTrayIcon" :loading="uploading === 'preview_video'"
                            :disabled="!!uploading" @click="videoInput?.click()">
                            {{ course.preview_video_url ? 'Videoni almashtirish' : 'Video yuklash' }}
                        </Button>
                        <ProgressBar v-if="uploading === 'preview_video' && mediaForm.progress" :value="mediaForm.progress.percentage || 0"
                            size="sm" label="Yuklanmoqda" show-value />
                        <p v-if="mediaForm.errors.preview_video" class="text-13 text-danger" role="alert">{{ mediaForm.errors.preview_video }}</p>
                    </div>
                </div>
            </Card>
        </div>

        <!-- Settings -->
        <div v-else class="max-w-[40rem] space-y-6">
            <Card title="Kursni nusxalash" description="Shu kursdan yangi qoralama yaratiladi. Video fayllar nusxalanmaydi.">
                <form class="space-y-5" @submit.prevent="confirmClone = true">
                    <Input v-model="cloneForm.title" label="Yangi kurs nomi" required :error="cloneForm.errors.title" />
                    <fieldset class="space-y-3">
                        <legend class="mb-2 text-sm font-medium text-fg">Nimalar nusxalansin</legend>
                        <Checkbox v-model="cloneForm.clone_curriculum" label="Modul va darslar" />
                        <Checkbox v-model="cloneForm.clone_tests" label="Testlar" />
                        <Checkbox v-model="cloneForm.clone_attachments" label="Dars fayllari" />
                        <Checkbox v-model="cloneForm.clone_media" label="Kurs rasmi va tanishtiruv videosi" />
                    </fieldset>
                    <div class="flex justify-end">
                        <Button type="submit" variant="secondary" :icon-left="DocumentDuplicateIcon" :loading="cloneForm.processing"
                            :disabled="!cloneForm.title">Nusxalash</Button>
                    </div>
                </form>
            </Card>

            <Card title="Xavfli zona">
                <div class="flex flex-col gap-4 rounded-md border border-danger-100 bg-danger-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h4 class="text-sm font-semibold text-danger">Kursni o'chirish</h4>
                        <p class="mt-0.5 text-13 text-fg-muted">
                            Bu amalni qaytarib bo'lmaydi. Faqat qoralama holatidagi va o'quvchisi yo'q kurslarni o'chirish mumkin.
                        </p>
                    </div>
                    <Button variant="danger" :icon-left="TrashIcon" :disabled="!canDelete" @click="confirmDelete = true">O'chirish</Button>
                </div>
            </Card>
        </div>

        <ConfirmDialog :show="confirmSubmit" tone="primary" title="Kursni tekshiruvga yuborasizmi?"
            :description="ready ? 'Tekshiruv davomida kurs o\'quvchilarga ko\'rinmaydi.' : 'Ba\'zi talablar bajarilmagan — server kursni qabul qilmasligi mumkin.'"
            confirm-label="Yuborish" :loading="submitting" @confirm="submitCourse" @close="confirmSubmit = false" />
        <ConfirmDialog :show="confirmClone" tone="primary" title="Kursni nusxalaysizmi?"
            :description="`«${cloneForm.title}» nomli yangi qoralama yaratiladi.`" confirm-label="Nusxalash"
            :loading="cloneForm.processing" @confirm="cloneCourse" @close="confirmClone = false" />
        <ConfirmDialog :show="confirmDelete" title="Kursni o'chirasizmi?"
            :description="`«${course.title}» butunlay o'chiriladi. Bu amalni qaytarib bo'lmaydi.`"
            :loading="deleting" @confirm="deleteCourse" @close="confirmDelete = false" />
    </TeacherLayout>
</template>
