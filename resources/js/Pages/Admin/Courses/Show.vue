<template>
    <AdminLayout>

        <Head :title="`${course.title} - Kurs`" />

        <PageHeader :title="course.title"
            :breadcrumbs="[{ label: 'Kurslar', href: route('admin.courses.index') }, { label: course.title }]">
            <template #meta>
                <Badge :variant="statusBadge.variant" dot>{{ statusBadge.label }}</Badge>
                <Badge v-if="course.direction?.name" variant="neutral">{{ course.direction.name }}</Badge>
                <span class="text-13 text-fg-subtle tabular-nums">Yaratilgan: {{ formatDate(course.created_at) }}</span>
            </template>
            <template #actions>
                <template v-if="course.status === 'pending'">
                    <Button variant="secondary" :icon-left="XMarkIcon" @click="rejectModal.show = true">Rad etish</Button>
                    <Button :icon-left="CheckIcon" :loading="approving" @click="approveCourse">Tasdiqlash</Button>
                </template>
                <!-- a published course can be taken down (with a reason the teacher sees) -->
                <Button v-else-if="course.status === 'published'" variant="secondary" :icon-left="XMarkIcon" @click="rejectModal.show = true">
                    Nashrdan olish
                </Button>
                <Button v-else-if="course.status === 'rejected'" variant="secondary" :icon-left="CheckIcon" :loading="approving" @click="approveCourse">
                    Nashr qilish
                </Button>
                <Button variant="ghost" :icon-left="TrashIcon" @click="deleteOpen = true">O'chirish</Button>
            </template>
        </PageHeader>

        <div class="space-y-6">
            <Alert v-if="course.status === 'pending'" variant="warning" title="Kurs tekshiruvni kutmoqda">
                Kurs mazmunini ko'rib chiqing va tasdiqlang yoki sababini ko'rsatib rad eting.
            </Alert>
            <Alert v-if="course.rejection_reason" variant="danger" :title="takenDown ? 'Nashrdan olish sababi' : 'Rad etish sababi'">
                <span class="whitespace-pre-line">{{ course.rejection_reason }}</span>
            </Alert>

            <section aria-label="Ko'rsatkichlar" class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <StatCard label="Reyting" :value="Number(course.avg_rating || 0).toFixed(1)" unit="/ 5" :icon="StarIcon"
                    tone="warning" :hint="`${course.reviews_count ?? 0} ta sharh`" />
                <StatCard label="O'quvchilar" :value="Number(course.students_count) || 0" :icon="UsersIcon" tone="brand" />
                <StatCard label="Davomiyligi" :value="Number(course.duration_hours) || 0" unit="soat" :icon="ClockIcon"
                    tone="info" />
                <StatCard label="Modullar" :value="Number(course.modules_count ?? modules.length) || 0"
                    :icon="RectangleStackIcon" tone="math" :hint="`${course.lessons_count ?? 0} ta dars`" />
            </section>

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="min-w-0 space-y-6 lg:col-span-2">
                    <Card title="Kurs haqida">
                        <div v-if="course.description"
                            class="max-w-none text-sm leading-6 text-fg-muted [&_a]:text-brand [&_a]:underline [&_h2]:mb-2 [&_h2]:mt-4 [&_h2]:text-base [&_h2]:font-semibold [&_h2]:text-fg [&_h3]:mb-2 [&_h3]:mt-4 [&_h3]:font-semibold [&_h3]:text-fg [&_li]:mb-1 [&_ol]:mb-3 [&_ol]:list-decimal [&_ol]:pl-5 [&_p]:mb-3 [&_ul]:mb-3 [&_ul]:list-disc [&_ul]:pl-5"
                            v-html="sanitize(course.description)" />
                        <p v-else class="text-sm text-fg-subtle">Tavsif kiritilmagan.</p>
                    </Card>

                    <Card :title="`Modullar (${modules.length})`" description="Kurs tuzilmasi" padding="none">
                        <ol v-if="modules.length" class="divide-y divide-border">
                            <li v-for="(module, index) in modules" :key="module.id"
                                class="flex items-center gap-4 px-5 py-3.5 sm:px-6">
                                <span
                                    class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-surface-2 text-13 font-semibold text-fg-muted tabular-nums">
                                    {{ module.sort_order ?? index + 1 }}
                                </span>
                                <p class="min-w-0 flex-1 truncate text-sm font-medium text-fg">{{ module.title }}</p>
                                <span class="shrink-0 text-13 text-fg-subtle tabular-nums">{{ module.lessons_count }} ta dars</span>
                            </li>
                        </ol>
                        <div v-else class="px-6 py-6">
                            <EmptyState :icon="RectangleStackIcon" title="Modullar yo'q"
                                description="Bu kursda hali modullar qo'shilmagan" size="sm" />
                        </div>
                    </Card>
                </div>

                <aside class="space-y-6">
                    <Card v-if="course.thumbnail && !thumbBroken" padding="none" class="overflow-hidden">
                        <img :src="mediaUrl(course.thumbnail)" :alt="course.title" class="aspect-video w-full object-cover"
                            @error="thumbBroken = true" />
                    </Card>

                    <Card title="Ma'lumot">
                        <dl class="-my-2.5 divide-y divide-border text-sm">
                            <div class="flex justify-between gap-4 py-2.5">
                                <dt class="text-fg-muted">O'qituvchi</dt>
                                <dd class="min-w-0 truncate text-right font-medium">
                                    <Link v-if="course.teacher?.id && canOpenTeacher"
                                        :href="route('admin.teachers.show', course.teacher.id)"
                                        class="focus-ring rounded-sm text-brand hover:underline">
                                        {{ course.teacher.full_name }}
                                    </Link>
                                    <span v-else class="text-fg">{{ course.teacher?.full_name || '—' }}</span>
                                </dd>
                            </div>
                            <div class="flex justify-between gap-4 py-2.5">
                                <dt class="text-fg-muted">Yo'nalish</dt>
                                <dd class="font-medium text-fg">{{ course.direction?.name || '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-4 py-2.5">
                                <dt class="text-fg-muted">Daraja</dt>
                                <dd class="font-medium text-fg">{{ levelLabel(course.level) }}</dd>
                            </div>
                            <div class="flex justify-between gap-4 py-2.5">
                                <dt class="text-fg-muted">Til</dt>
                                <dd class="font-medium text-fg">{{ languageLabel(course.language) }}</dd>
                            </div>
                            <div class="flex justify-between gap-4 py-2.5">
                                <dt class="text-fg-muted">Narx</dt>
                                <dd class="font-medium text-fg tabular-nums">
                                    {{ Number(course.price) > 0 ? formatMoney(course.price) : 'Bepul' }}
                                </dd>
                            </div>
                            <div class="flex justify-between gap-4 py-2.5">
                                <dt class="text-fg-muted">Chegirma narxi</dt>
                                <dd class="font-medium text-fg tabular-nums">
                                    {{ Number(course.discount_price) > 0 ? formatMoney(course.discount_price) : '—' }}
                                </dd>
                            </div>
                        </dl>
                    </Card>
                </aside>
            </div>
        </div>

        <!-- Reject modal -->
        <Modal :show="deleteOpen" title="Kursni o'chirasizmi?" size="sm" @close="deleteOpen = false">
            <p class="text-sm text-fg-muted">«{{ course.title }}» kursi o'chiriladi. O'quvchilar unga kira olmaydi.</p>
            <template #footer>
                <Button variant="secondary" :disabled="deleting" @click="deleteOpen = false">Bekor qilish</Button>
                <Button variant="danger" :loading="deleting" @click="deleteCourse">O'chirish</Button>
            </template>
        </Modal>

        <Modal :show="rejectModal.show" :title="unpublishing ? 'Kursni nashrdan olish' : 'Kursni rad etish'"
            :description="rejectCopy.description" :closeable="!rejectModal.loading" @close="rejectModal.show = false">
            <form id="reject-course-form" @submit.prevent="rejectCourse">
                <Textarea v-model="rejectModal.reason" :label="rejectCopy.label" :rows="4" required autofocus
                    :placeholder="rejectCopy.placeholder" :error="rejectModal.error" />
            </form>

            <template #footer>
                <Button variant="secondary" :disabled="rejectModal.loading" @click="rejectModal.show = false">
                    Bekor qilish
                </Button>
                <Button type="submit" form="reject-course-form" variant="danger" :loading="rejectModal.loading">
                    {{ rejectCopy.button }}
                </Button>
            </template>
        </Modal>
    </AdminLayout>
</template>

<script setup>
import { computed, ref, reactive } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    CheckIcon, ClockIcon, RectangleStackIcon, StarIcon, TrashIcon, UsersIcon, XMarkIcon,
} from '@heroicons/vue/24/outline';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
    Alert, Badge, Button, Card, EmptyState, Modal, PageHeader, StatCard, Textarea,
} from '@/Components/UI';
import { courseStatusMeta, formatDate, formatMoney, mediaUrl } from '@/Components/Admin/format';
import { routeHas } from '@/Utils/nav';
import { useSanitize } from '@/Composables/useSanitize.js';

const { sanitize } = useSanitize();

const props = defineProps({
    course: Object,
    modules: Array,
});

const canOpenTeacher = routeHas('admin.teachers.show');
const thumbBroken = ref(false);
const approving = ref(false);
const deleteOpen = ref(false);
const deleting = ref(false);
const deleteCourse = () => router.delete(route('admin.courses.destroy', props.course.id), {
    onStart: () => (deleting.value = true),
    onFinish: () => (deleting.value = false),
});
const rejectModal = reactive({
    show: false,
    loading: false,
    reason: '',
    error: null,
});

const LEVELS = { beginner: 'Boshlang\'ich', intermediate: 'O\'rta', advanced: 'Yuqori' };
const LANGUAGES = { uz: 'O\'zbek', ru: 'Rus', en: 'Ingliz' };
const levelLabel = (v) => LEVELS[v] || v || '—';
const languageLabel = (v) => LANGUAGES[v] || v || '—';

// a published course is "taken down", not "rejected"; once taken down it reads "Nashrdan olingan"
const unpublishing = computed(() => props.course.status === 'published');
const takenDown = computed(() => props.course.status === 'rejected' && props.course.was_published);
const statusBadge = computed(() => (takenDown.value
    ? { label: 'Nashrdan olingan', variant: 'neutral' }
    : courseStatusMeta(props.course.status)));
const rejectCopy = computed(() => (unpublishing.value
    ? {
        description: "Kurs katalogdan olinadi; yozilgan o'quvchilar unga kirishda davom etadi. Sabab o'qituvchiga ko'rsatiladi.",
        label: 'Nashrdan olish sababi',
        placeholder: 'Nima uchun kurs nashrdan olinayotganini tushuntiring...',
        button: 'Nashrdan olish',
        required: 'Nashrdan olish sababini kiriting',
    }
    : {
        description: "Sabab o'qituvchiga ko'rsatiladi, u kursni tuzatib qayta yuborishi mumkin.",
        label: 'Rad etish sababi',
        placeholder: 'Nima uchun kurs rad etilayotganini tushuntiring...',
        button: 'Rad etish',
        required: 'Rad etish sababini kiriting',
    }));

const approveCourse = () => {
    approving.value = true;

    router.patch(route('admin.courses.approve', props.course.id), {}, {
        preserveScroll: true,
        onFinish: () => {
            approving.value = false;
        },
    });
};

const rejectCourse = () => {
    if (!rejectModal.reason.trim()) {
        rejectModal.error = rejectCopy.value.required;
        return;
    }

    rejectModal.error = null;
    rejectModal.loading = true;

    router.patch(route('admin.courses.reject', props.course.id), {
        rejection_reason: rejectModal.reason,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            rejectModal.show = false;
            rejectModal.reason = '';
        },
        onError: (errors) => {
            rejectModal.error = errors.rejection_reason || null;
        },
        onFinish: () => {
            rejectModal.loading = false;
        },
    });
};
</script>
