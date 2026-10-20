<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';
import { CloudArrowUpIcon, CheckCircleIcon, ExclamationTriangleIcon, TrashIcon, ArrowPathIcon } from '@heroicons/vue/24/outline';
import Button from '@/Components/UI/Button.vue';
import Badge from '@/Components/UI/Badge.vue';
import Alert from '@/Components/UI/Alert.vue';
import ProgressBar from '@/Components/UI/ProgressBar.vue';
import Spinner from '@/Components/UI/Spinner.vue';
import Skeleton from '@/Components/UI/Skeleton.vue';
import ConfirmDialog from './ConfirmDialog.vue';

const props = defineProps({
    lesson: Object,
});

const fileInput = ref(null);
const uploadProgress = ref(0);
const isUploading = ref(false);
const status = ref(null); // pending, processing, completed, failed
const notice = ref(null); // e.g. no ffmpeg on the server / queue worker not running
const transcodingProgress = ref(0);
const qualities = ref([]);
const errorMessage = ref(null);
const loadingStatus = ref(true);
const dragOver = ref(false);
const confirmDelete = ref(false);
const deleting = ref(false);
let statusInterval = null;
// after an upload / processing finishes, refresh the page's lesson (the "Video bo'limlari" link needs lesson.video)
let reloadWhenDone = false;

const CHUNK_SIZE = 5 * 1024 * 1024; // 5MB
const MAX_SIZE = 2 * 1024 * 1024 * 1024; // 2GB

const selectFile = () => {
    fileInput.value?.click();
};

const startUpload = async (file) => {
    if (!file) return;
    errorMessage.value = null;

    if (!String(file.type || '').startsWith('video/')) {
        errorMessage.value = 'Faqat video fayl yuklash mumkin (MP4, MOV, AVI).';
        return;
    }
    if (file.size > MAX_SIZE) {
        errorMessage.value = 'Fayl hajmi 2 GB dan oshmasligi kerak.';
        return;
    }

    isUploading.value = true;
    uploadProgress.value = 0;

    try {
        if (file.size <= CHUNK_SIZE) {
            await uploadStandard(file);
        } else {
            await uploadChunked(file);
        }
    } catch (error) {
        console.error(error);
        errorMessage.value = error?.response?.data?.message || 'Yuklashda xatolik yuz berdi. Qayta urinib ko\'ring.';
        isUploading.value = false;
    } finally {
        if (fileInput.value) fileInput.value.value = '';
    }
};

const handleFileChange = (event) => startUpload(event.target.files[0]);

const onDrop = (event) => {
    dragOver.value = false;
    startUpload(event.dataTransfer?.files?.[0]);
};

const uploadStandard = async (file) => {
    const formData = new FormData();
    formData.append('video', file);

    await axios.post(route('teacher.lessons.video.upload', props.lesson.id), formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
        onUploadProgress: (progressEvent) => {
            uploadProgress.value = Math.round((progressEvent.loaded * 100) / (progressEvent.total || file.size));
        },
    });

    isUploading.value = false;
    startStatusPolling();
};

const uploadChunked = async (file) => {
    const totalChunks = Math.ceil(file.size / CHUNK_SIZE);
    const uploadId = Date.now().toString(36) + Math.random().toString(36).substr(2);

    for (let chunkIndex = 0; chunkIndex < totalChunks; chunkIndex++) {
        const start = chunkIndex * CHUNK_SIZE;
        const end = Math.min(start + CHUNK_SIZE, file.size);
        const chunk = file.slice(start, end);

        const formData = new FormData();
        formData.append('chunk', chunk);
        formData.append('chunk_index', chunkIndex);
        formData.append('total_chunks', totalChunks);
        formData.append('upload_id', uploadId);
        formData.append('filename', file.name);

        await axios.post(route('teacher.lessons.video.upload-chunk', props.lesson.id), formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        uploadProgress.value = Math.round(((chunkIndex + 1) / totalChunks) * 100);
    }

    isUploading.value = false;
    startStatusPolling();
};

const checkStatus = async () => {
    try {
        const response = await axios.get(route('teacher.lessons.video.status', props.lesson.id));
        const data = response.data;

        if (data.status === 'no_video') {
            status.value = null;
            stopStatusPolling();
            return;
        }

        status.value = data.status;
        transcodingProgress.value = data.progress;
        qualities.value = data.qualities || [];
        errorMessage.value = data.error;
        notice.value = data.notice || null;

        if (data.status === 'completed' || data.status === 'failed') {
            stopStatusPolling();
            if (reloadWhenDone) {
                reloadWhenDone = false;
                router.reload({ only: ['lesson'], preserveScroll: true });
            }
        } else {
            reloadWhenDone = true;
        }
    } catch (error) {
        console.error(error);
    } finally {
        loadingStatus.value = false;
    }
};

const startStatusPolling = () => {
    stopStatusPolling();
    reloadWhenDone = true;
    checkStatus();
    statusInterval = setInterval(checkStatus, 3000);
};

const stopStatusPolling = () => {
    if (statusInterval) {
        clearInterval(statusInterval);
        statusInterval = null;
    }
};

const deleteVideo = () => {
    // destroy() answers with back() (a redirect, not JSON). Through axios a 302 after DELETE
    // is re-sent as DELETE to the previous page (405), so use an Inertia visit instead.
    router.delete(route('teacher.lessons.video.destroy', props.lesson.id), {
        preserveScroll: true,
        onStart: () => (deleting.value = true),
        onSuccess: () => {
            status.value = null;
            uploadProgress.value = 0;
            transcodingProgress.value = 0;
            qualities.value = [];
            errorMessage.value = null;
            if (fileInput.value) fileInput.value.value = '';
        },
        onError: () => (errorMessage.value = 'O\'chirishda xatolik yuz berdi'),
        onFinish: () => {
            deleting.value = false;
            confirmDelete.value = false;
        },
    });
};

onMounted(async () => {
    // The status request is async — wait for it before deciding whether to keep polling
    await checkStatus();
    if (status.value === 'processing' || status.value === 'pending') {
        startStatusPolling();
    }
});

onUnmounted(() => {
    stopStatusPolling();
});
</script>

<template>
    <div class="space-y-4">
        <input ref="fileInput" type="file" class="sr-only" accept="video/*" tabindex="-1" aria-hidden="true" @change="handleFileChange">

        <Alert v-if="errorMessage && status !== 'failed'" variant="danger" dismissible @dismiss="errorMessage = null">{{ errorMessage }}</Alert>

        <Skeleton v-if="loadingStatus" class="h-32 w-full" />

        <!-- Upload area -->
        <button v-else-if="!status && !isUploading" type="button" :class="[
            'focus-ring flex w-full flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed px-6 py-8 text-center transition-colors duration-150',
            dragOver ? 'border-brand-500 bg-brand-50' : 'border-border-strong hover:border-brand-500 hover:bg-surface-2/60',
        ]" @click="selectFile" @dragover.prevent="dragOver = true" @dragleave.prevent="dragOver = false" @drop.prevent="onDrop">
            <span class="flex h-11 w-11 items-center justify-center rounded-full bg-brand-50 text-brand">
                <CloudArrowUpIcon class="h-6 w-6" aria-hidden="true" />
            </span>
            <span class="text-sm font-medium text-fg">Video tanlash yoki shu yerga tashlash</span>
            <span class="text-13 text-fg-subtle">MP4, MOV, AVI — 2 GB gacha</span>
        </button>

        <!-- Upload progress -->
        <div v-if="isUploading" class="rounded-lg border border-border bg-surface p-4" role="status">
            <ProgressBar :value="uploadProgress" label="Yuklanmoqda…" show-value size="md" />
            <p class="mt-2 text-13 text-fg-subtle">Yuklash tugaguncha sahifani yopmang.</p>
        </div>

        <!-- Processing -->
        <div v-if="status === 'processing' || status === 'pending'" class="rounded-lg border border-info-100 bg-info-50 p-4" role="status">
            <div class="flex items-start gap-3">
                <Spinner size="h-5 w-5" class="mt-0.5 text-info" />
                <div class="min-w-0 flex-1">
                    <h4 class="text-sm font-medium text-fg">Video qayta ishlanmoqda</h4>
                    <p class="text-13 text-fg-muted">Bu biroz vaqt olishi mumkin — sahifadan chiqib ketsangiz ham jarayon davom etadi.</p>
                    <ProgressBar :value="Number(transcodingProgress) || 0" tone="info" size="sm" show-value class="mt-3" />
                    <p v-if="notice" class="mt-2 text-13 text-fg-muted">{{ notice }}</p>
                </div>
            </div>
        </div>

        <!-- Completed -->
        <div v-if="status === 'completed'" class="flex flex-col gap-3 rounded-lg border border-success-100 bg-success-50 p-4 sm:flex-row sm:items-center">
            <CheckCircleIcon class="h-6 w-6 shrink-0 text-success" aria-hidden="true" />
            <div class="min-w-0 flex-1">
                <h4 class="text-sm font-medium text-fg">Video tayyor</h4>
                <p v-if="notice" class="text-13 text-fg-muted">{{ notice }}</p>
                <div v-if="qualities.length" class="mt-1 flex flex-wrap gap-1">
                    <Badge v-for="q in qualities" :key="q" size="sm" variant="neutral">{{ q }}</Badge>
                </div>
            </div>
            <Button variant="secondary" size="sm" :icon-left="TrashIcon" @click="confirmDelete = true">Videoni o'chirish</Button>
        </div>

        <!-- Failed -->
        <div v-if="status === 'failed'" class="flex flex-col gap-3 rounded-lg border border-danger-100 bg-danger-50 p-4 sm:flex-row sm:items-center" role="alert">
            <ExclamationTriangleIcon class="h-6 w-6 shrink-0 text-danger" aria-hidden="true" />
            <div class="min-w-0 flex-1">
                <h4 class="text-sm font-medium text-fg">Videoni qayta ishlashda xatolik</h4>
                <p class="text-13 text-fg-muted">{{ errorMessage || 'Faylni o\'chirib, qayta yuklab ko\'ring.' }}</p>
            </div>
            <Button variant="secondary" size="sm" :icon-left="ArrowPathIcon" :loading="deleting" @click="deleteVideo">Qayta urinish</Button>
        </div>

        <ConfirmDialog :show="confirmDelete" title="Videoni o'chirasizmi?"
            description="Video fayl va uning barcha sifatlari o'chiriladi." :loading="deleting"
            @confirm="deleteVideo" @close="confirmDelete = false" />
    </div>
</template>
