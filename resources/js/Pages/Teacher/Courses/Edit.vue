<script setup>
import { ref } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import Input from '@/Components/UI/Input.vue';
import Button from '@/Components/UI/Button.vue';

const props = defineProps({
    course: Object,
    directions: Array,
    tags: Array,
});

const activeTab = ref('basic');

const form = useForm({
    title: props.course.title,
    direction_id: props.course.direction_id,
    level: props.course.level,
    short_description: props.course.short_description,
    description: props.course.description,
    price: props.course.price,
    discount_price: props.course.discount_price,
    is_free: !!props.course.is_free,
    requirements: props.course.requirements || [],
    what_will_learn: props.course.what_will_learn || [],
    who_is_for: props.course.who_is_for || [],
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

const addItem = (field, newItemField) => {
    if (!form[newItemField]) return;
    if (!form[field]) form[field] = [];
    form[field].push(form[newItemField]);
    form[newItemField] = '';
};

const removeItem = (field, index) => {
    form[field].splice(index, 1);
};

const submitBasic = () => {
    form.put(route('teacher.courses.update', props.course.id), {
        preserveScroll: true,
    });
};

const updateThumbnail = (e) => {
    const file = e.target.files[0];
    if (!file) return;

    mediaForm.thumbnail = file;
    mediaForm.post(route('teacher.courses.thumbnail', props.course.id), {
        preserveScroll: true,
        onSuccess: () => mediaForm.reset('thumbnail'),
    });
};

const updatePreviewVideo = (e) => {
    const file = e.target.files[0];
    if (!file) return;

    mediaForm.preview_video = file;
    mediaForm.post(route('teacher.courses.preview-video', props.course.id), {
        preserveScroll: true,
        onSuccess: () => mediaForm.reset('preview_video'),
    });
};

const cloneForm = useForm({
    title: props.course.title + ' (Copy)',
    clone_curriculum: true,
    clone_tests: true,
    clone_attachments: true,
    clone_media: true,
});

const cloneCourse = () => {
    if (confirm('Kursni nusxalamoqchimisiz?')) {
        cloneForm.post(route('teacher.courses.clone', props.course.id));
    }
};
</script>

<template>

    <Head :title="`Tahrirlash: ${course.title}`" />

    <TeacherLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link href="/teacher/courses" class="text-gray-500 hover:text-gray-700">
                    ← Kurslarim
                    </Link>
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800 max-w-md truncate">{{ course.title }}</h2>
                        <div class="flex items-center gap-2 text-sm">
                            <span :class="{
                                'text-gray-500': course.status === 'draft',
                                'text-yellow-500': course.status === 'pending',
                                'text-green-500': course.status === 'published',
                                'text-red-500': course.status === 'rejected',
                            }" class="capitalize font-medium">
                                {{ course.status }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Button v-if="course.status === 'draft'" @click="submitCourse" variant="primary">
                        Tekshiruvga yuborish
                    </Button>
                </div>
            </div>
        </template>

        <div class="max-w-5xl mx-auto">
            <!-- Tabs -->
            <div class="flex border-b border-gray-200 mb-6 bg-white rounded-t-2xl px-6 pt-4">
                <button @click="activeTab = 'basic'"
                    :class="['px-6 py-3 font-medium text-sm border-b-2 transition-colors', activeTab === 'basic' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-gray-500 hover:text-gray-700']">
                    Asosiy ma'lumotlar
                </button>
                <button @click="activeTab = 'media'"
                    :class="['px-6 py-3 font-medium text-sm border-b-2 transition-colors', activeTab === 'media' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-gray-500 hover:text-gray-700']">
                    Media
                </button>
                <button @click="activeTab = 'curriculum'"
                    :class="['px-6 py-3 font-medium text-sm border-b-2 transition-colors', activeTab === 'curriculum' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-gray-500 hover:text-gray-700']">
                    Curriculum
                </button>
                <button @click="activeTab = 'announcements'"
                    :class="['px-6 py-3 font-medium text-sm border-b-2 transition-colors', activeTab === 'announcements' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-gray-500 hover:text-gray-700']">
                    E'lonlar
                </button>
                <button @click="activeTab = 'qna'"
                    :class="['px-6 py-3 font-medium text-sm border-b-2 transition-colors', activeTab === 'qna' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-gray-500 hover:text-gray-700']">
                    Savol-javob
                </button>
                <button @click="activeTab = 'settings'"
                    :class="['px-6 py-3 font-medium text-sm border-b-2 transition-colors', activeTab === 'settings' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-gray-500 hover:text-gray-700']">
                    Sozlamalar
                </button>
            </div>

            <!-- Basic Tab -->
            <div v-if="activeTab === 'basic'" class="space-y-8">
                <form @submit.prevent="submitBasic" class="space-y-8">
                    <!-- Same fields as Create.vue -->
                    <div class="p-6 bg-white border border-gray-100 rounded-2xl space-y-6">
                        <h3 class="text-lg font-bold text-gray-900">Asosiy ma'lumotlar</h3>
                        <Input v-model="form.title" label="Kurs nomi" required />
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Yo'nalish</label>
                                <select v-model="form.direction_id"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                                    required>
                                    <option v-for="dir in directions" :key="dir.id" :value="dir.id">{{ dir.name }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Daraja</label>
                                <select v-model="form.level"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                                    <option value="beginner">Boshlang'ich</option>
                                    <option value="intermediate">O'rta</option>
                                    <option value="advanced">Yuqori</option>
                                </select>
                            </div>
                        </div>
                        <Input v-model="form.short_description" label="Qisqa tavsif" required />
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">To'liq tavsif</label>
                            <textarea v-model="form.description" rows="6"
                                class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                                required></textarea>
                        </div>
                    </div>

                    <div class="p-6 bg-white border border-gray-100 rounded-2xl space-y-6">
                        <h3 class="text-lg font-bold text-gray-900">Narxlash</h3>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" v-model="form.is_free"
                                class="w-5 h-5 text-emerald-600 rounded focus:ring-emerald-500">
                            <span class="text-gray-700 font-medium">Bepul kurs</span>
                        </label>
                        <div v-if="!form.is_free" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <Input v-model="form.price" label="Narxi (UZS)" type="number" required />
                            <Input v-model="form.discount_price" label="Chegirmali narx (ixtiyoriy)" type="number" />
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <Button type="submit" :loading="form.processing">Saqlash</Button>
                    </div>
                </form>
            </div>

            <!-- Media Tab -->
            <div v-if="activeTab === 'media'" class="space-y-6">
                <div class="p-6 bg-white border border-gray-100 rounded-2xl space-y-6">
                    <h3 class="text-lg font-bold text-gray-900">Kurs rasmi (Thumbnail)</h3>
                    <div class="flex flex-col md:flex-row gap-6">
                        <div
                            class="w-full md:w-1/2 aspect-video bg-gray-100 rounded-xl overflow-hidden border border-gray-200">
                            <img v-if="course.thumbnail_url" :src="course.thumbnail_url"
                                class="w-full h-full object-cover" />
                            <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                                1280x720
                            </div>
                        </div>
                        <div class="flex-1 space-y-4">
                            <p class="text-sm text-gray-500">
                                Kursingiz uchun jozibali rasm yuklang. Bu rasm kurs kartochkasida va sahifasida
                                ko'rinadi.<br>
                                Tavsiya etilgan o'lcham: 1280x720 piksel (16:9).<br>
                                Formatlar: JPG, PNG. Max: 5MB.
                            </p>
                            <label
                                class="inline-block px-4 py-2 bg-emerald-600 text-white font-medium rounded-xl cursor-pointer hover:bg-emerald-700 transition-colors">
                                Rasm yuklash
                                <input type="file" class="hidden" accept="image/*" @change="updateThumbnail">
                            </label>
                        </div>
                    </div>
                </div>

                <div class="p-6 bg-white border border-gray-100 rounded-2xl space-y-6">
                    <h3 class="text-lg font-bold text-gray-900">Preview Video</h3>
                    <div class="flex flex-col md:flex-row gap-6">
                        <div
                            class="w-full md:w-1/2 aspect-video bg-gray-100 rounded-xl overflow-hidden border border-gray-200">
                            <video v-if="course.preview_video_url" :src="course.preview_video_url" controls
                                class="w-full h-full object-cover"></video>
                            <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                                Video yo'q
                            </div>
                        </div>
                        <div class="flex-1 space-y-4">
                            <p class="text-sm text-gray-500">
                                Kursingiz haqida qisqacha tanishtiruv videosi.<br>
                                Formatlar: MP4, MOV. Max: 100MB.
                            </p>
                            <label
                                class="inline-block px-4 py-2 bg-emerald-600 text-white font-medium rounded-xl cursor-pointer hover:bg-emerald-700 transition-colors">
                                Video yuklash
                                <input type="file" class="hidden" accept="video/*" @change="updatePreviewVideo">
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Curriculum Tab -->
            <div v-if="activeTab === 'curriculum'"
                class="p-6 bg-white border border-gray-100 rounded-2xl text-center py-12">
                <div class="text-4xl mb-4">📚</div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Curriculum</h3>
                <p class="text-gray-500 mb-6">Darslar va modullarni boshqarish.</p>
                <Link :href="route('teacher.courses.modules.index', course.id)"
                    class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white font-medium rounded-xl hover:bg-emerald-700 transition-colors">
                Curriculumga o'tish
                </Link>
            </div>

            <!-- Announcements Tab -->
            <div v-if="activeTab === 'announcements'"
                class="p-6 bg-white border border-gray-100 rounded-2xl text-center py-12">
                <div class="text-4xl mb-4">📢</div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">E'lonlar</h3>
                <p class="text-gray-500 mb-6">Kurs talabalari uchun yangiliklar va e'lonlar.</p>
                <Link :href="route('teacher.courses.announcements.index', course.id)"
                    class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white font-medium rounded-xl hover:bg-emerald-700 transition-colors">
                E'lonlarni boshqarish
                </Link>
            </div>

            <!-- Q&A Tab -->
            <div v-if="activeTab === 'qna'" class="p-6 bg-white border border-gray-100 rounded-2xl text-center py-12">
                <div class="text-4xl mb-4">💬</div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">Savol-javoblar</h3>
                <p class="text-gray-500 mb-6">Talabalar tomonidan berilgan savollarga javob berish.</p>
                <Link :href="route('teacher.courses.qna.index', course.id)"
                    class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white font-medium rounded-xl hover:bg-emerald-700 transition-colors">
                Savol-javoblarga o'tish
                </Link>
            </div>

            <!-- Settings Tab -->
            <div v-if="activeTab === 'settings'" class="space-y-6">
                <!-- Clone Course -->
                <div class="p-6 bg-white border border-gray-100 rounded-2xl space-y-4">
                    <h3 class="text-lg font-bold text-gray-900">Kursni nusxalash</h3>
                    <p class="text-sm text-gray-500">
                        Ushbu kursning to'liq nusxasini yarating. Video fayllardan tashqari barcha ma'lumotlar (darslar,
                        testlar, fayllar) nusxalanadi.
                    </p>

                    <form @submit.prevent="cloneCourse" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Yangi kurs nomi</label>
                            <input v-model="cloneForm.title" type="text"
                                class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                                required />
                        </div>

                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="checkbox" v-model="cloneForm.clone_curriculum"
                                    class="rounded text-emerald-600 focus:ring-emerald-500">
                                <span class="ml-2 text-sm text-gray-700">Darslar va modullarni nusxalash</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" v-model="cloneForm.clone_tests"
                                    class="rounded text-emerald-600 focus:ring-emerald-500">
                                <span class="ml-2 text-sm text-gray-700">Testlarni nusxalash</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" v-model="cloneForm.clone_attachments"
                                    class="rounded text-emerald-600 focus:ring-emerald-500">
                                <span class="ml-2 text-sm text-gray-700">Fayllarni nusxalash</span>
                            </label>
                        </div>

                        <Button type="submit" :loading="cloneForm.processing" variant="secondary">
                            Nusxalash
                        </Button>
                    </form>
                </div>

                <!-- Danger Zone -->
                <div class="p-6 bg-white border border-gray-100 rounded-2xl space-y-6">
                    <h3 class="text-lg font-bold text-gray-900">Xavfli zona</h3>

                    <div class="p-4 bg-red-50 border border-red-100 rounded-xl flex items-center justify-between">
                        <div>
                            <h4 class="font-bold text-red-700">Kursni o'chirish</h4>
                            <p class="text-sm text-red-600">Bu amalni ortga qaytarib bo'lmaydi. Faqat draft holatidagi
                                kurslarni
                                o'chirish mumkin.</p>
                        </div>
                        <button @click="deleteCourse" :disabled="course.status !== 'draft'"
                            class="px-4 py-2 bg-red-600 text-white font-medium rounded-xl hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed">
                            O'chirish
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </TeacherLayout>
</template>
