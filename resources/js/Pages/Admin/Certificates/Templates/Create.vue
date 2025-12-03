<template>
    <AdminLayout>

        <Head title="Yangi Shablon" />

        <div class="mb-6">
            <Link href="/admin/certificate-templates"
                class="text-gray-500 hover:text-gray-700 flex items-center gap-2 mb-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Orqaga
            </Link>
            <h1 class="text-2xl font-bold text-gray-900">Yangi sertifikat shabloni</h1>
        </div>

        <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column: Form -->
            <div class="lg:col-span-1 space-y-6">
                <div class="bg-white rounded-2xl shadow-sm p-6 space-y-4">
                    <h2 class="text-lg font-semibold text-gray-900">Asosiy ma'lumotlar</h2>

                    <Input v-model="form.name" label="Shablon nomi" :error="form.errors.name" required />

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tavsif</label>
                        <textarea v-model="form.description" rows="3"
                            class="w-full rounded-xl border-gray-300 focus:border-primary focus:ring-primary/20"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Yo'nalish</label>
                            <select v-model="form.direction_id"
                                class="w-full rounded-xl border-gray-300 focus:border-primary focus:ring-primary/20">
                                <option :value="null">Tanlang...</option>
                                <option v-for="dir in directions" :key="dir.id" :value="dir.id">{{ dir.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kurs</label>
                            <select v-model="form.course_id"
                                class="w-full rounded-xl border-gray-300 focus:border-primary focus:ring-primary/20">
                                <option :value="null">Tanlang...</option>
                                <option v-for="course in courses" :key="course.id" :value="course.id">{{ course.title }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">PDF/Rasm Shablon (Max 10MB)</label>
                        <input type="file" @change="handleFileChange" accept=".pdf,.png,.jpg,.jpeg"
                            class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20" />
                        <p v-if="form.errors.pdf_template" class="mt-1 text-sm text-red-600">{{ form.errors.pdf_template
                            }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Thumbnail (Preview)</label>
                        <input type="file" @change="e => form.thumbnail = e.target.files[0]" accept="image/*"
                            class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary/10 file:text-primary hover:file:bg-primary/20" />
                    </div>

                    <div class="flex items-center gap-4 pt-2">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" v-model="form.is_active"
                                class="rounded text-primary focus:ring-primary" />
                            <span class="text-sm text-gray-700">Faol</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="checkbox" v-model="form.is_default"
                                class="rounded text-primary focus:ring-primary" />
                            <span class="text-sm text-gray-700">Default</span>
                        </label>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm p-6 space-y-4">
                    <h2 class="text-lg font-semibold text-gray-900">Placeholder Sozlamalari</h2>

                    <div v-for="(config, key) in form.placeholders" :key="key"
                        class="border-b border-gray-100 pb-4 last:border-0 last:pb-0">
                        <h3 class="font-medium text-gray-900 mb-2 capitalize">{{ formatKey(key) }}</h3>
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <div>
                                <label class="text-xs text-gray-500">X (px)</label>
                                <input type="number" v-model="config.x"
                                    class="w-full rounded-lg border-gray-200 py-1" />
                            </div>
                            <div>
                                <label class="text-xs text-gray-500">Y (px)</label>
                                <input type="number" v-model="config.y"
                                    class="w-full rounded-lg border-gray-200 py-1" />
                            </div>
                            <template v-if="key !== 'qr_code'">
                                <div>
                                    <label class="text-xs text-gray-500">Font Size</label>
                                    <input type="number" v-model="config.font_size"
                                        class="w-full rounded-lg border-gray-200 py-1" />
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500">Align</label>
                                    <select v-model="config.align" class="w-full rounded-lg border-gray-200 py-1">
                                        <option value="left">Left</option>
                                        <option value="center">Center</option>
                                        <option value="right">Right</option>
                                    </select>
                                </div>
                            </template>
                            <template v-else>
                                <div>
                                    <label class="text-xs text-gray-500">Width</label>
                                    <input type="number" v-model="config.width"
                                        class="w-full rounded-lg border-gray-200 py-1" />
                                </div>
                                <div>
                                    <label class="text-xs text-gray-500">Height</label>
                                    <input type="number" v-model="config.height"
                                        class="w-full rounded-lg border-gray-200 py-1" />
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Preview -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-sm p-6 sticky top-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold text-gray-900">Preview</h2>
                        <div class="flex gap-2">
                            <Button type="button" variant="secondary" @click="preview">Yangilash</Button>
                            <Button :loading="form.processing">Saqlash</Button>
                        </div>
                    </div>

                    <div
                        class="relative w-full aspect-[1.414] bg-gray-100 rounded-lg overflow-hidden border border-gray-200">
                        <img v-if="previewImage" :src="previewImage" class="w-full h-full object-contain" />
                        <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                            Fayl yuklang
                        </div>

                        <!-- Visual Placeholders -->
                        <template v-if="previewImage">
                            <div v-for="(config, key) in form.placeholders" :key="key"
                                class="absolute border-2 border-dashed border-primary/50 bg-primary/10 flex items-center justify-center text-xs font-bold text-primary pointer-events-none"
                                :style="getPlaceholderStyle(config, key)">
                                {{ formatKey(key) }}
                            </div>
                        </template>
                    </div>
                    <p class="text-xs text-gray-500 mt-2 text-center">
                        Eslatma: Bu taxminiy ko'rinish. Aniq joylashuv PDF o'lchamiga bog'liq.
                    </p>
                </div>
            </div>
        </form>
    </AdminLayout>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Input from '@/Components/UI/Input.vue';
import Button from '@/Components/UI/Button.vue';

const props = defineProps({
    directions: Array,
    courses: Array,
});

const previewImage = ref(null);

const form = useForm({
    name: '',
    description: '',
    direction_id: null,
    course_id: null,
    pdf_template: null,
    thumbnail: null,
    is_active: true,
    is_default: false,
    placeholders: {
        student_name: { x: 300, y: 400, font_size: 28, align: 'center' },
        course_title: { x: 300, y: 450, font_size: 18, align: 'center' },
        completion_date: { x: 150, y: 550, font_size: 14, align: 'left' },
        certificate_number: { x: 450, y: 550, font_size: 12, align: 'right' },
        qr_code: { x: 480, y: 620, width: 80, height: 80 },
    },
    settings: {
        page_size: 'A4',
        orientation: 'landscape',
    },
});

const handleFileChange = (e) => {
    const file = e.target.files[0];
    form.pdf_template = file;

    if (file && file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = (e) => previewImage.value = e.target.result;
        reader.readAsDataURL(file);
    }
};

const formatKey = (key) => {
    return key.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
};

const getPlaceholderStyle = (config, key) => {
    // Simple scaling for preview (assuming A4 landscape ~842x595 px base for coords)
    // In reality, we might need to know the image dimensions to scale correctly
    // For now, we use direct pixels assuming the user inputs pixels relative to the image size

    return {
        left: `${config.x}px`,
        top: `${config.y}px`,
        fontSize: config.font_size ? `${config.font_size}px` : undefined,
        width: config.width ? `${config.width}px` : 'auto',
        height: config.height ? `${config.height}px` : 'auto',
        transform: 'translate(-50%, -50%)', // Center anchor
    };
};

const submit = () => {
    form.post('/admin/certificate-templates');
};
</script>
