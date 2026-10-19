<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import draggable from 'vuedraggable';
import { PlusIcon, RectangleStackIcon, InformationCircleIcon } from '@heroicons/vue/24/outline';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Button from '@/Components/UI/Button.vue';
import Card from '@/Components/UI/Card.vue';
import Modal from '@/Components/UI/Modal.vue';
import Input from '@/Components/UI/Input.vue';
import Textarea from '@/Components/UI/Textarea.vue';
import Select from '@/Components/UI/Select.vue';
import Checkbox from '@/Components/UI/Checkbox.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import CourseNav from '@/Components/Teacher/CourseNav.vue';
import ModuleCard from '@/Components/Teacher/ModuleCard.vue';
import ConfirmDialog from '@/Components/Teacher/ConfirmDialog.vue';

const props = defineProps({
    course: Object,
    modules: Array,
});

const modulesList = ref([...(props.modules || [])]);
// Fresh props after every save / delete → refresh the draggable copy
watch(() => props.modules, (modules) => {
    modulesList.value = [...(modules || [])];
});

const lessonsTotal = computed(() => modulesList.value.reduce((s, m) => s + (m.lessons?.length || 0), 0));
const saving = ref(false);

// ---- Module create / edit -------------------------------------------------
const isCreatingModule = ref(false);
const editingModule = ref(null);

const form = useForm({
    title: '',
    description: '',
    is_free: false,
});

const createModule = () => {
    isCreatingModule.value = true;
    editingModule.value = null;
    form.reset();
    form.clearErrors();
};

const editModule = (module) => {
    isCreatingModule.value = true;
    editingModule.value = module;
    form.clearErrors();
    form.title = module.title;
    form.description = module.description;
    form.is_free = !!module.is_free;
};

const submitModule = () => {
    if (editingModule.value) {
        form.put(route('teacher.modules.update', editingModule.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                isCreatingModule.value = false;
                editingModule.value = null;
            },
        });
    } else {
        form.post(route('teacher.courses.modules.store', props.course.id), {
            preserveScroll: true,
            onSuccess: () => {
                isCreatingModule.value = false;
                form.reset();
            },
        });
    }
};

// ---- Module delete ----------------------------------------------------------
const moduleToDelete = ref(null);
const deleting = ref(false);
const deleteModule = (module) => {
    moduleToDelete.value = module;
};
const confirmDeleteModule = () => {
    router.delete(route('teacher.modules.destroy', moduleToDelete.value.id), {
        preserveScroll: true,
        onStart: () => (deleting.value = true),
        onFinish: () => {
            deleting.value = false;
            moduleToDelete.value = null;
        },
    });
};

// ---- Reordering ---------------------------------------------------------------
const onModulesReorder = () => {
    saving.value = true;
    useForm({
        modules: modulesList.value.map((m, index) => ({
            id: m.id,
            sort_order: index + 1,
        })),
    }).post(route('teacher.courses.modules.reorder', props.course.id), {
        preserveScroll: true,
        onFinish: () => (saving.value = false),
    });
};

const moveModule = (from, dir) => {
    const to = from + dir;
    if (to < 0 || to >= modulesList.value.length) return;
    const next = [...modulesList.value];
    const [item] = next.splice(from, 1);
    next.splice(to, 0, item);
    modulesList.value = next;
    onModulesReorder();
};

const onLessonsReorder = (module, lessons) => {
    saving.value = true;
    useForm({
        lessons: lessons.map((l, index) => ({
            id: l.id,
            sort_order: index + 1,
        })),
    }).post(route('teacher.courses.modules.lessons.reorder', [props.course.id, module.id]), {
        preserveScroll: true,
        onFinish: () => (saving.value = false),
    });
};

// ---- Quick "add lesson" (same payload as LessonForm) ------------------------------
const lessonModule = ref(null);
const lessonForm = useForm({
    title: '',
    description: '',
    type: 'video',
    content: '',
    is_free: false,
    is_preview: false,
});
const lessonTypes = [
    { value: 'video', label: 'Video dars' },
    { value: 'text', label: 'Matnli dars' },
    { value: 'quiz', label: 'Test' },
];

const openAddLesson = (module) => {
    lessonForm.reset();
    lessonForm.clearErrors();
    lessonModule.value = module;
};

const submitLesson = () => {
    lessonForm.post(route('teacher.courses.modules.lessons.store', [props.course.id, lessonModule.value.id]), {
        preserveScroll: true,
        onSuccess: () => {
            lessonModule.value = null;
            lessonForm.reset();
        },
    });
};
</script>

<template>
    <Head :title="`${course.title} — Darslar rejasi`" />

    <TeacherLayout>
        <PageHeader title="Darslar rejasi"
            :breadcrumbs="[{ label: 'Kurslarim', href: route('teacher.courses.index') }, { label: course.title, href: route('teacher.courses.edit', course.id) }, { label: 'Darslar rejasi' }]">
            <template #meta>
                <span class="text-13 tabular-nums text-fg-muted">{{ modulesList.length }} modul · {{ lessonsTotal }} dars</span>
                <span v-if="saving" class="text-13 text-fg-subtle" role="status">Tartib saqlanmoqda…</span>
            </template>
            <template #actions>
                <Button :icon-left="PlusIcon" @click="createModule">Yangi modul</Button>
            </template>
            <template #tabs>
                <CourseNav :course="course" active="curriculum" />
            </template>
        </PageHeader>

        <div v-if="modulesList.length" class="space-y-4">
            <p class="flex items-center gap-2 text-13 text-fg-subtle">
                <InformationCircleIcon class="h-4 w-4 shrink-0" aria-hidden="true" />
                Modul va darslar tartibini chapdagi tutqich bilan sudrab o'zgartiring — tartib avtomatik saqlanadi.
            </p>

            <draggable v-model="modulesList" item-key="id" handle=".module-handle" :animation="150" ghost-class="opacity-40"
                class="space-y-4" @end="onModulesReorder">
                <template #item="{ element: module, index }">
                    <ModuleCard :module="module" :course="course" :index="index" :total="modulesList.length"
                        @edit="editModule(module)" @delete="deleteModule(module)" @add-lesson="openAddLesson(module)"
                        @move="(dir) => moveModule(index, dir)"
                        @reorder-lessons="(lessons) => onLessonsReorder(module, lessons)" />
                </template>
            </draggable>

            <button type="button"
                class="focus-ring flex h-12 w-full items-center justify-center gap-2 rounded-lg border border-dashed border-border-strong text-sm font-medium text-fg-muted transition-colors hover:border-brand-500 hover:bg-surface hover:text-brand"
                @click="createModule">
                <PlusIcon class="h-5 w-5" aria-hidden="true" />
                Modul qo'shish
            </button>
        </div>

        <Card v-else>
            <EmptyState :icon="RectangleStackIcon" size="lg" title="Hali modul yo'q"
                description="Kursni modullarga bo'ling (masalan: «Kinematika», «Dinamika»), so'ng har biriga dars qo'shing.">
                <Button :icon-left="PlusIcon" @click="createModule">Birinchi modulni qo'shish</Button>
            </EmptyState>
        </Card>

        <!-- Module modal -->
        <Modal :show="isCreatingModule" :title="editingModule ? 'Modulni tahrirlash' : 'Yangi modul'" size="md"
            @close="isCreatingModule = false">
            <form id="module-form" class="space-y-5 pb-2" @submit.prevent="submitModule">
                <Input v-model="form.title" label="Nomi" required autofocus maxlength="255" placeholder="Masalan: Kinematika"
                    :error="form.errors.title" />
                <Textarea v-model="form.description" label="Tavsif" :rows="3" optional :error="form.errors.description" />
                <Checkbox v-model="form.is_free" label="Bepul modul" description="Moduldagi barcha darslar to'lovsiz ochiq bo'ladi." />
            </form>
            <template #footer>
                <Button variant="secondary" @click="isCreatingModule = false">Bekor qilish</Button>
                <Button type="submit" form="module-form" :loading="form.processing">
                    {{ editingModule ? 'Saqlash' : 'Qo\'shish' }}
                </Button>
            </template>
        </Modal>

        <!-- Quick add lesson -->
        <Modal :show="!!lessonModule" title="Yangi dars" :description="lessonModule ? `Modul: ${lessonModule.title}` : null" size="md"
            @close="lessonModule = null">
            <form id="lesson-quick-form" class="space-y-5 pb-2" @submit.prevent="submitLesson">
                <Input v-model="lessonForm.title" label="Dars nomi" required autofocus maxlength="255" :error="lessonForm.errors.title" />
                <Select v-model="lessonForm.type" label="Turi" :options="lessonTypes" :error="lessonForm.errors.type" />
                <Textarea v-model="lessonForm.description" label="Qisqa tavsif" :rows="2" optional :error="lessonForm.errors.description" />
                <div class="space-y-3">
                    <Checkbox v-model="lessonForm.is_free" label="Bepul dars" />
                    <Checkbox v-model="lessonForm.is_preview" label="Ochiq ko'rish" description="Kursni sotib olmaganlar ham ko'ra oladi." />
                </div>
                <p v-if="lessonForm.type !== 'quiz'" class="text-13 text-fg-subtle">
                    Video, matn va fayllarni dars yaratilgach «Tahrirlash» orqali qo'shasiz.
                </p>
            </form>
            <template #footer>
                <Button variant="secondary" @click="lessonModule = null">Bekor qilish</Button>
                <Button type="submit" form="lesson-quick-form" :loading="lessonForm.processing">Qo'shish</Button>
            </template>
        </Modal>

        <ConfirmDialog :show="!!moduleToDelete" title="Modulni o'chirasizmi?"
            :description="moduleToDelete ? `«${moduleToDelete.title}» va undagi barcha darslar o'chiriladi.` : null"
            :loading="deleting" @confirm="confirmDeleteModule" @close="moduleToDelete = null" />
    </TeacherLayout>
</template>
