<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { CloudArrowUpIcon, DocumentIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import Modal from '@/Components/UI/Modal.vue';
import Button from '@/Components/UI/Button.vue';
import Input from '@/Components/UI/Input.vue';
import Checkbox from '@/Components/UI/Checkbox.vue';
import FormField from '@/Components/UI/FormField.vue';
import ProgressBar from '@/Components/UI/ProgressBar.vue';
import { formatSize } from './format';

const props = defineProps({
    lesson: Object,
});

const emit = defineEmits(['close']);

const form = useForm({
    file: null,
    title: '',
    is_downloadable: true,
});

const fileInput = ref(null);
const dragOver = ref(false);

const setFile = (file) => {
    if (!file) return;
    form.file = file;
    form.clearErrors('file');
    if (!form.title) {
        form.title = file.name.split('.').slice(0, -1).join('.') || file.name;
    }
};

const handleFileChange = (e) => setFile(e.target.files[0]);
const onDrop = (e) => {
    dragOver.value = false;
    setFile(e.dataTransfer?.files?.[0]);
};
const clearFile = () => {
    form.file = null;
    if (fileInput.value) fileInput.value.value = '';
};

const submit = () => {
    form.post(route('teacher.lessons.attachments.store', props.lesson.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            emit('close');
        },
    });
};
</script>

<template>
    <Modal :show="true" title="Fayl yuklash" description="PDF, Word, Excel, PowerPoint, rasm, audio yoki arxiv — 100 MB gacha." size="md"
        :closeable="!form.processing" @close="$emit('close')">
        <form id="attachment-form" class="space-y-5 pb-2" @submit.prevent="submit">
            <FormField label="Fayl" required :error="form.errors.file" v-slot="{ id, describedBy }">
                <input :id="id" ref="fileInput" type="file" class="peer sr-only" :aria-describedby="describedBy"
                    accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.rar,.7z,.txt,.md,.jpg,.jpeg,.png,.gif,.mp3,.wav,.mp4,.mov,.json"
                    @change="handleFileChange" />
                <div v-if="form.file" class="flex items-center gap-3 rounded-md border border-border bg-surface-2/60 px-3 py-2.5">
                    <DocumentIcon class="h-5 w-5 shrink-0 text-fg-subtle" aria-hidden="true" />
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-fg">{{ form.file.name }}</p>
                        <p class="text-xs tabular-nums text-fg-subtle">{{ formatSize(form.file.size) }}</p>
                    </div>
                    <button type="button" class="focus-ring inline-flex h-8 w-8 items-center justify-center rounded-sm text-fg-subtle hover:bg-surface-3 hover:text-fg"
                        aria-label="Faylni olib tashlash" :disabled="form.processing" @click="clearFile">
                        <XMarkIcon class="h-4 w-4" aria-hidden="true" />
                    </button>
                </div>
                <label v-else :for="id" :class="[
                    'flex cursor-pointer flex-col items-center gap-2 rounded-lg border-2 border-dashed px-6 py-7 text-center transition-colors duration-150',
                    'peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500 peer-focus-visible:ring-offset-2 peer-focus-visible:ring-offset-surface',
                    dragOver ? 'border-brand-500 bg-brand-50' : 'border-border-strong hover:border-brand-500 hover:bg-surface-2/60',
                ]" @dragover.prevent="dragOver = true" @dragleave.prevent="dragOver = false" @drop.prevent="onDrop">
                    <CloudArrowUpIcon class="h-7 w-7 text-brand" aria-hidden="true" />
                    <span class="text-sm font-medium text-fg">Fayl tanlash yoki shu yerga tashlash</span>
                </label>
            </FormField>

            <Input v-model="form.title" label="Nomi" placeholder="O'quvchilar ko'radigan nom" optional maxlength="255"
                :error="form.errors.title" />

            <Checkbox v-model="form.is_downloadable" label="Yuklab olishga ruxsat berish"
                description="O'chirilsa, fayl faqat platforma ichida ko'riladi." />

            <ProgressBar v-if="form.progress" :value="form.progress.percentage || 0" label="Yuklanmoqda" show-value size="sm" />
        </form>

        <template #footer>
            <Button variant="secondary" :disabled="form.processing" @click="$emit('close')">Bekor qilish</Button>
            <Button type="submit" form="attachment-form" :loading="form.processing" :disabled="!form.file">Yuklash</Button>
        </template>
    </Modal>
</template>
