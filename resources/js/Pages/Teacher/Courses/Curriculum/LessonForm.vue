<script setup>
import { computed, onBeforeUnmount, onMounted } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    PlayCircleIcon, DocumentTextIcon, ClipboardDocumentCheckIcon, PaperClipIcon, ListBulletIcon, PlusIcon,
} from '@heroicons/vue/24/outline';
import { ChevronRightIcon } from '@heroicons/vue/20/solid';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Card from '@/Components/UI/Card.vue';
import Button from '@/Components/UI/Button.vue';
import Input from '@/Components/UI/Input.vue';
import Textarea from '@/Components/UI/Textarea.vue';
import FormField from '@/Components/UI/FormField.vue';
import Switch from '@/Components/UI/Switch.vue';
import Alert from '@/Components/UI/Alert.vue';
import VideoUploader from '@/Components/Teacher/VideoUploader.vue';
import MarkdownEditor from '@/Components/Teacher/MarkdownEditor.vue';
import { editorShortcut } from '@/Components/Teacher/format';

const props = defineProps({
    course: Object,
    module: Object,
    lesson: Object,
});

const form = useForm({
    title: props.lesson?.title || '',
    description: props.lesson?.description || '',
    type: props.lesson?.type || 'video',
    // stored in lessons.text_content; the request field is still called "content"
    content: props.lesson?.text_content || '',
    is_free: !!props.lesson?.is_free,
    is_preview: !!props.lesson?.is_preview,
});

const submit = () => {
    if (form.processing) return;
    if (props.lesson) {
        form.put(route('teacher.lessons.update', props.lesson.id), {
            preserveScroll: true,
            onSuccess: () => form.defaults(),
        });
    } else {
        form.post(route('teacher.courses.modules.lessons.store', [props.course.id, props.module.id]));
    }
};

// Ctrl+S / Ctrl+Enter — saqlash (browser "save page" dialog is suppressed)
const onKeydown = (e) => {
    if (!editorShortcut(e)) return;
    e.preventDefault();
    submit();
};
onMounted(() => window.addEventListener('keydown', onKeydown));
onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown));

const types = [
    { value: 'video', label: 'Video', description: 'Video yuklanadi', icon: PlayCircleIcon },
    { value: 'text', label: 'Matn', description: 'Konspekt, nazariya', icon: DocumentTextIcon },
    { value: 'quiz', label: 'Test', description: 'Savollar bilan sinov', icon: ClipboardDocumentCheckIcon },
];

const curriculumHref = computed(() => route('teacher.courses.modules.index', props.course.id));
const title = computed(() => (props.lesson ? 'Darsni tahrirlash' : 'Yangi dars'));

const resources = computed(() => {
    if (!props.lesson) return [];
    const list = [
        { label: 'Dars fayllari', description: 'PDF, taqdimot, masalalar to\'plami', href: route('teacher.lessons.attachments.index', props.lesson.id), icon: PaperClipIcon, count: props.lesson.attachments?.length },
    ];
    if (props.lesson.type === 'video' && props.lesson.video) {
        list.push({ label: 'Video bo\'limlari', description: 'Video ichida tezkor navigatsiya', href: route('teacher.lessons.chapters.index', props.lesson.id), icon: ListBulletIcon });
    }
    if (props.lesson.type === 'quiz') {
        list.push({ label: 'Dars testi yaratish', description: 'Shu darsga bog\'langan test', href: route('teacher.courses.tests.create', { course: props.course.id, module_id: props.module?.id, lesson_id: props.lesson.id, type: 'lesson_test' }), icon: PlusIcon });
    }
    return list;
});
</script>

<template>
    <Head :title="title" />

    <TeacherLayout>
        <PageHeader :title="lesson ? lesson.title : 'Yangi dars'" :description="module ? `Modul: ${module.title}` : null"
            :breadcrumbs="[
                { label: 'Kurslarim', href: route('teacher.courses.index') },
                { label: course.title, href: route('teacher.courses.edit', course.id) },
                { label: 'Darslar rejasi', href: curriculumHref },
                { label: title },
            ]" />

        <div class="grid gap-6 xl:grid-cols-[minmax(0,40rem)_20rem] xl:gap-8">
            <form novalidate @submit.prevent="submit">
                <div class="space-y-6">
                    <Card title="Dars ma'lumotlari">
                        <div class="space-y-5">
                            <Input v-model="form.title" label="Nomi" required maxlength="255" :error="form.errors.title" />
                            <Textarea v-model="form.description" label="Tavsif" :rows="3" optional autoresize :error="form.errors.description" />

                            <FormField as="fieldset" label="Dars turi" :error="form.errors.type">
                                <div class="grid gap-2 sm:grid-cols-3">
                                    <label v-for="t in types" :key="t.value" :class="[
                                        'relative flex cursor-pointer items-start gap-3 rounded-md border p-3 transition-colors duration-150 focus-within:ring-2 focus-within:ring-brand-500 focus-within:ring-offset-2 focus-within:ring-offset-surface',
                                        form.type === t.value ? 'border-brand-500 bg-brand-50' : 'border-border-strong hover:bg-surface-2',
                                    ]">
                                        <input v-model="form.type" type="radio" name="lesson-type" :value="t.value" class="sr-only" />
                                        <component :is="t.icon" :class="['mt-px h-5 w-5 shrink-0', form.type === t.value ? 'text-brand' : 'text-fg-subtle']" aria-hidden="true" />
                                        <span class="min-w-0">
                                            <span class="block text-sm font-medium text-fg">{{ t.label }}</span>
                                            <span class="block text-xs text-fg-subtle">{{ t.description }}</span>
                                        </span>
                                    </label>
                                </div>
                            </FormField>

                            <div class="space-y-4 border-t border-border pt-5">
                                <Switch v-model="form.is_free" label="Bepul dars" description="Kursni sotib olmaganlar uchun ham ochiq." />
                                <Switch v-model="form.is_preview" label="Ochiq ko'rish (preview)" description="Kurs sahifasida namuna sifatida ko'rsatiladi." />
                            </div>
                        </div>
                    </Card>

                    <Card v-if="form.type === 'video'" title="Video">
                        <VideoUploader v-if="lesson" :lesson="lesson" />
                        <Alert v-else variant="info">Video yuklash uchun avval darsni saqlang.</Alert>
                    </Card>

                    <Card v-if="form.type === 'text'" title="Dars matni">
                        <MarkdownEditor v-model="form.content" label="Matn" :error="form.errors.content" />
                    </Card>
                </div>

                <div class="sticky bottom-0 z-10 -mx-4 mt-6 flex items-center justify-end gap-2 border-t border-border bg-bg/90 px-4 py-3 backdrop-blur sm:mx-0 sm:px-0">
                    <span v-if="lesson && form.isDirty" class="mr-auto text-13 text-fg-subtle">Saqlanmagan o'zgarishlar bor</span>
                    <span v-else class="mr-auto hidden text-xs text-fg-subtle lg:inline">
                        <kbd class="rounded-sm border border-border bg-surface px-1 font-sans">Ctrl</kbd>+<kbd class="rounded-sm border border-border bg-surface px-1 font-sans">S</kbd> — saqlash
                    </span>
                    <Button variant="secondary" :href="curriculumHref">{{ lesson ? 'Darslar rejasiga' : 'Bekor qilish' }}</Button>
                    <Button type="submit" :loading="form.processing">Saqlash</Button>
                </div>
            </form>

            <aside v-if="resources.length" class="space-y-6">
                <Card title="Dars resurslari" padding="none">
                    <ul class="divide-y divide-border">
                        <li v-for="r in resources" :key="r.label">
                            <Link :href="r.href" class="focus-ring group flex items-center gap-3 px-5 py-3.5 hover:bg-surface-2/60 sm:px-6">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-surface-2 text-fg-muted">
                                    <component :is="r.icon" class="h-5 w-5" aria-hidden="true" />
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="flex items-center gap-2 text-sm font-medium text-fg">
                                        {{ r.label }}
                                        <span v-if="r.count" class="rounded-full bg-surface-2 px-1.5 text-[11px] font-semibold tabular-nums text-fg-subtle">{{ r.count }}</span>
                                    </span>
                                    <span class="block truncate text-13 text-fg-subtle">{{ r.description }}</span>
                                </span>
                                <ChevronRightIcon class="h-5 w-5 text-fg-subtle" aria-hidden="true" />
                            </Link>
                        </li>
                    </ul>
                </Card>
            </aside>
        </div>
    </TeacherLayout>
</template>
