<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import ModuleCard from '@/Components/Teacher/ModuleCard.vue';
import Modal from '@/Components/UI/Modal.vue';
import draggable from 'vuedraggable';

const props = defineProps({
    course: Object,
    modules: Array,
});

const isCreatingModule = ref(false);
const editingModule = ref(null);
const modulesList = ref(props.modules);

const form = useForm({
    title: '',
    description: '',
    is_free: false,
});

const createModule = () => {
    isCreatingModule.value = true;
    editingModule.value = null;
    form.reset();
};

const editModule = (module) => {
    isCreatingModule.value = true;
    editingModule.value = module;
    form.title = module.title;
    form.description = module.description;
    form.is_free = !!module.is_free;
};

const submitModule = () => {
    if (editingModule.value) {
        form.put(route('teacher.courses.modules.update', editingModule.value.id), {
            onSuccess: () => {
                isCreatingModule.value = false;
                editingModule.value = null;
            },
        });
    } else {
        form.post(route('teacher.courses.modules.store', props.course.id), {
            onSuccess: () => {
                isCreatingModule.value = false;
            },
        });
    }
};

const deleteModule = (module) => {
    if (confirm('Modulni o\'chirishni tasdiqlaysizmi? Barcha darslar ham o\'chiriladi.')) {
        useForm({}).delete(route('teacher.courses.modules.destroy', module.id));
    }
};

const onModulesReorder = () => {
    useForm({
        modules: modulesList.value.map((m, index) => ({
            id: m.id,
            sort_order: index + 1,
        })),
    }).post(route('teacher.courses.modules.reorder', props.course.id), {
        preserveScroll: true,
    });
};

const onLessonsReorder = (module, lessons) => {
    useForm({
        lessons: lessons.map((l, index) => ({
            id: l.id,
            sort_order: index + 1,
        })),
    }).post(route('teacher.courses.modules.lessons.reorder', [props.course.id, module.id]), {
        preserveScroll: true,
    });
};
</script>

<template>

    <Head title="Darslar rejasi" />

    <TeacherLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ course.title }} - Darslar rejasi
                </h2>
                <div class="flex gap-3">
                    <Link :href="route('teacher.courses.index')" class="btn-secondary">
                    Ortga
                    </Link>
                    <button @click="createModule" class="btn-primary">
                        Yangi modul
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div v-if="modules.length === 0"
                    class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 text-center text-gray-500">
                    Hozircha modullar yo'q. Yangi modul qo'shing.
                </div>

                <draggable v-model="modulesList" item-key="id" handle=".module-handle" @end="onModulesReorder"
                    class="space-y-6">
                    <template #item="{ element: module }">
                        <ModuleCard :module="module" :course="course" @edit="editModule(module)"
                            @delete="deleteModule(module)"
                            @reorder-lessons="(lessons) => onLessonsReorder(module, lessons)" />
                    </template>
                </draggable>
            </div>
        </div>

        <!-- Module Modal -->
        <Modal :show="isCreatingModule" @close="isCreatingModule = false">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">
                    {{ editingModule ? 'Modulni tahrirlash' : 'Yangi modul' }}
                </h2>

                <form @submit.prevent="submitModule" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nomi</label>
                        <input v-model="form.title" type="text"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required>
                        <div v-if="form.errors.title" class="text-red-500 text-sm mt-1">{{ form.errors.title }}</div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tavsif</label>
                        <textarea v-model="form.description" rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    </div>

                    <div class="flex items-center">
                        <input v-model="form.is_free" type="checkbox"
                            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                        <label class="ml-2 block text-sm text-gray-900">Bepul modul (barcha darslar ochiq
                            bo'ladi)</label>
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button" @click="isCreatingModule = false" class="btn-secondary">Bekor
                            qilish</button>
                        <button type="submit" class="btn-primary" :disabled="form.processing">
                            {{ editingModule ? 'Saqlash' : 'Qo\'shish' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </TeacherLayout>
</template>
