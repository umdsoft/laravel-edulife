<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Button, Card, Checkbox, FormField, Input, PageHeader, ProgressBar, Select, Textarea } from '@/Components/UI';
import { CheckIcon, PhotoIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    template: Object,
    directions: Array,
    courses: Array,
});

const previewImage = ref(props.template.thumbnail_path ? `/storage/${props.template.thumbnail_path}` : null);

const form = useForm({
    _method: 'PUT',
    name: props.template.name,
    description: props.template.description,
    direction_id: props.template.direction_id,
    course_id: props.template.course_id,
    pdf_template: null,
    thumbnail: null,
    is_active: Boolean(props.template.is_active),
    is_default: Boolean(props.template.is_default),
    placeholders: props.template.placeholders,
    settings: props.template.settings,
});

const directionOptions = computed(() => (props.directions || []).map((d) => ({ value: d.id, label: d.name })));
const courseOptions = computed(() => (props.courses || [])
    .filter((c) => !form.direction_id || c.direction_id === form.direction_id || c.id === form.course_id)
    .map((c) => ({ value: c.id, label: c.title })));

const alignOptions = [
    { value: 'left', label: 'Chap' },
    { value: 'center', label: 'Markaz' },
    { value: 'right', label: "O'ng" },
];

const PLACEHOLDER_LABELS = {
    student_name: "O'quvchi ismi",
    course_title: 'Kurs nomi',
    completion_date: 'Tugatgan sana',
    certificate_number: 'Sertifikat raqami',
    qr_code: 'QR kod',
};

const handleFileChange = (e) => {
    const file = e.target.files[0];
    form.pdf_template = file;

    if (file && file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = (ev) => (previewImage.value = ev.target.result);
        reader.readAsDataURL(file);
    }
};

const formatKey = (key) => PLACEHOLDER_LABELS[key]
    || key.split('_').map((word) => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');

const getPlaceholderStyle = (config) => ({
    // Coordinates are entered in pixels relative to the template image
    left: `${config.x}px`,
    top: `${config.y}px`,
    fontSize: config.font_size ? `${config.font_size}px` : undefined,
    width: config.width ? `${config.width}px` : 'auto',
    height: config.height ? `${config.height}px` : 'auto',
    transform: 'translate(-50%, -50%)',
});

const fileClass = 'block w-full text-sm text-fg-muted file:mr-3 file:cursor-pointer file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-brand hover:file:bg-brand-100 focus-ring rounded-md';

const submit = () => {
    form.post(route('admin.certificate-templates.update', props.template.id), { preserveScroll: true });
};
</script>

<template>
    <AdminLayout>
        <Head :title="`${template.name} — tahrirlash`" />

        <PageHeader title="Shablonni tahrirlash" :description="form.name || template.name"
            :breadcrumbs="[
                { label: 'Sertifikatlar', href: route('admin.certificates.index') },
                { label: 'Shablonlar', href: route('admin.certificate-templates.index') },
                { label: template.name, href: route('admin.certificate-templates.show', template.id) },
                { label: 'Tahrirlash' },
            ]" />

        <form class="grid gap-6 lg:grid-cols-5" @submit.prevent="submit">
            <div class="space-y-6 lg:col-span-2">
                <Card title="Asosiy ma'lumotlar">
                    <div class="space-y-5">
                        <Input v-model="form.name" label="Shablon nomi" required :error="form.errors.name" />
                        <Textarea v-model="form.description" label="Tavsif" optional :rows="3" :error="form.errors.description" />
                        <Select v-model="form.direction_id" label="Yo'nalish" optional placeholder="Barcha yo'nalishlar"
                            :options="directionOptions" :error="form.errors.direction_id" />
                        <Select v-model="form.course_id" label="Kurs" optional placeholder="Barcha kurslar"
                            :options="courseOptions" :error="form.errors.course_id" />

                        <FormField label="Yangi shablon fayli" optional hint="Almashtirish uchun tanlang — PDF yoki rasm, 10 MB gacha"
                            :error="form.errors.pdf_template" v-slot="{ id, describedBy }">
                            <input :id="id" type="file" accept=".pdf,.png,.jpg,.jpeg" :aria-describedby="describedBy"
                                :class="fileClass" @change="handleFileChange" />
                        </FormField>

                        <FormField label="Yangi thumbnail" optional hint="Bo'sh qoldirilsa joriy rasm saqlanadi" :error="form.errors.thumbnail"
                            v-slot="{ id, describedBy }">
                            <input :id="id" type="file" accept="image/*" :aria-describedby="describedBy" :class="fileClass"
                                @change="(e) => (form.thumbnail = e.target.files[0])" />
                        </FormField>

                        <ProgressBar v-if="form.progress" :value="form.progress.percentage || 0" label="Yuklanmoqda" show-value size="sm" />

                        <div class="space-y-3 border-t border-border pt-4">
                            <Checkbox v-model="form.is_active" label="Faol" description="Yangi sertifikatlar uchun tanlanishi mumkin" />
                            <Checkbox v-model="form.is_default" label="Asosiy shablon" description="Boshqa shablon mos kelmasa shu ishlatiladi" />
                        </div>
                    </div>
                </Card>

                <Card title="Matn joylashuvi" description="Koordinatalar shablon rasmiga nisbatan pikselda">
                    <div class="space-y-5">
                        <fieldset v-for="(config, key) in form.placeholders" :key="key"
                            class="border-b border-border pb-5 last:border-0 last:pb-0">
                            <legend class="mb-3 text-sm font-medium text-fg">{{ formatKey(key) }}</legend>
                            <div class="grid grid-cols-2 gap-3">
                                <Input v-model="config.x" type="number" size="sm" label="X" />
                                <Input v-model="config.y" type="number" size="sm" label="Y" />
                                <template v-if="key !== 'qr_code'">
                                    <Input v-model="config.font_size" type="number" size="sm" label="Shrift o'lchami" />
                                    <Select v-model="config.align" size="sm" label="Tekislash" :options="alignOptions" />
                                </template>
                                <template v-else>
                                    <Input v-model="config.width" type="number" size="sm" label="Kenglik" />
                                    <Input v-model="config.height" type="number" size="sm" label="Balandlik" />
                                </template>
                            </div>
                        </fieldset>
                    </div>
                </Card>
            </div>

            <div class="lg:col-span-3">
                <div class="space-y-4 lg:sticky lg:top-24">
                    <Card title="Ko'rinish" description="Taxminiy ko'rinish — aniq joylashuv PDF o'lchamiga bog'liq">
                        <div class="relative aspect-[1.414] w-full overflow-hidden rounded-md border border-border bg-surface-2">
                            <img v-if="previewImage" :src="previewImage" alt="Shablon ko'rinishi" class="h-full w-full object-contain" />
                            <div v-else class="flex h-full w-full flex-col items-center justify-center gap-2 text-fg-subtle">
                                <PhotoIcon class="h-10 w-10" aria-hidden="true" />
                                <span class="text-13">Ko'rinish uchun rasm faylini tanlang</span>
                            </div>

                            <template v-if="previewImage">
                                <div v-for="(config, key) in form.placeholders" :key="key"
                                    class="pointer-events-none absolute flex items-center justify-center whitespace-nowrap rounded-sm border border-dashed border-brand-500 bg-brand-500/10 px-1 text-xs font-semibold text-brand"
                                    :style="getPlaceholderStyle(config)">
                                    {{ formatKey(key) }}
                                </div>
                            </template>
                        </div>
                    </Card>

                    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <Button variant="secondary" :href="route('admin.certificate-templates.show', template.id)">Bekor qilish</Button>
                        <Button type="submit" :icon-left="CheckIcon" :loading="form.processing">Saqlash</Button>
                    </div>
                </div>
            </div>
        </form>
    </AdminLayout>
</template>
