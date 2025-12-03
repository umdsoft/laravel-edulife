<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    lesson: Object,
});

const fileInput = ref(null);
const uploadProgress = ref(0);
const isUploading = ref(false);
const status = ref(null); // pending, processing, completed, failed
const transcodingProgress = ref(0);
const qualities = ref([]);
const errorMessage = ref(null);
let statusInterval = null;

const CHUNK_SIZE = 5 * 1024 * 1024; // 5MB

const selectFile = () => {
    fileInput.value.click();
};

const handleFileChange = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    if (file.size > 2 * 1024 * 1024 * 1024) { // 2GB limit
        alert('Fayl hajmi 2GB dan oshmasligi kerak');
        return;
    }

    isUploading.value = true;
    uploadProgress.value = 0;
    errorMessage.value = null;

    try {
        if (file.size <= CHUNK_SIZE) {
            await uploadStandard(file);
        } else {
            await uploadChunked(file);
        }
    } catch (error) {
        console.error(error);
        errorMessage.value = 'Yuklashda xatolik yuz berdi';
        isUploading.value = false;
    }
};

const uploadStandard = async (file) => {
    const formData = new FormData();
    formData.append('video', file);

    await axios.post(route('teacher.lessons.video.upload', props.lesson.id), formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
        onUploadProgress: (progressEvent) => {
            uploadProgress.value = Math.round((progressEvent.loaded * 100) / progressEvent.total);
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
            return;
        }

        status.value = data.status;
        transcodingProgress.value = data.progress;
        qualities.value = data.qualities || [];
        errorMessage.value = data.error;

        if (data.status === 'completed' || data.status === 'failed') {
            stopStatusPolling();
        }
    } catch (error) {
        console.error(error);
    }
};

const startStatusPolling = () => {
    checkStatus();
    statusInterval = setInterval(checkStatus, 3000);
};

const stopStatusPolling = () => {
    if (statusInterval) {
        clearInterval(statusInterval);
        statusInterval = null;
    }
};

const deleteVideo = async () => {
    if (!confirm('Videoni o\'chirishni tasdiqlaysizmi?')) return;

    try {
        await axios.delete(route('teacher.lessons.video.destroy', props.lesson.id));
        status.value = null;
        uploadProgress.value = 0;
        transcodingProgress.value = 0;
        qualities.value = [];
        fileInput.value.value = '';
    } catch (error) {
        console.error(error);
        alert('O\'chirishda xatolik yuz berdi');
    }
};

onMounted(() => {
    checkStatus();
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
        <!-- Upload Area -->
        <div v-if="!status && !isUploading" @click="selectFile"
            class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-indigo-500 cursor-pointer transition-colors">
            <input ref="fileInput" type="file" class="hidden" accept="video/*" @change="handleFileChange">
            <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-gray-400" fill="none"
                viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
            </svg>
            <p class="mt-2 text-sm text-gray-600">Video yuklash uchun bosing yoki shu yerga tashlang</p>
            <p class="text-xs text-gray-500">MP4, MOV, AVI (max 2GB)</p>
        </div>

        <!-- Upload Progress -->
        <div v-if="isUploading" class="bg-white p-4 rounded-lg border border-gray-200">
            <div class="flex justify-between mb-1">
                <span class="text-sm font-medium text-gray-700">Yuklanmoqda...</span>
                <span class="text-sm font-medium text-gray-700">{{ uploadProgress }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2.5">
                <div class="bg-indigo-600 h-2.5 rounded-full transition-all duration-300"
                    :style="{ width: uploadProgress + '%' }"></div>
            </div>
        </div>

        <!-- Processing Status -->
        <div v-if="status === 'processing' || status === 'pending'"
            class="bg-blue-50 p-4 rounded-lg border border-blue-200">
            <div class="flex items-center gap-3">
                <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-blue-600"></div>
                <div>
                    <h4 class="text-sm font-medium text-blue-900">Video qayta ishlanmoqda</h4>
                    <p class="text-xs text-blue-700">Bu jarayon biroz vaqt olishi mumkin. Sahifadan chiqib ketishingiz
                        mumkin.</p>
                </div>
            </div>
            <div class="mt-3">
                <div class="flex justify-between mb-1">
                    <span class="text-xs text-blue-700">Jarayon</span>
                    <span class="text-xs text-blue-700">{{ transcodingProgress }}%</span>
                </div>
                <div class="w-full bg-blue-200 rounded-full h-1.5">
                    <div class="bg-blue-600 h-1.5 rounded-full transition-all duration-300"
                        :style="{ width: transcodingProgress + '%' }"></div>
                </div>
            </div>
        </div>

        <!-- Completed Status -->
        <div v-if="status === 'completed'" class="bg-green-50 p-4 rounded-lg border border-green-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <div>
                        <h4 class="text-sm font-medium text-green-900">Video tayyor</h4>
                        <p class="text-xs text-green-700">
                            Sifatlar: {{ qualities.join(', ') }}
                        </p>
                    </div>
                </div>
                <button @click="deleteVideo" class="text-red-600 hover:text-red-800 text-sm font-medium">
                    O'chirish
                </button>
            </div>
        </div>

        <!-- Failed Status -->
        <div v-if="status === 'failed'" class="bg-red-50 p-4 rounded-lg border border-red-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <h4 class="text-sm font-medium text-red-900">Xatolik yuz berdi</h4>
                        <p class="text-xs text-red-700">{{ errorMessage || 'Video qayta ishlashda xatolik.' }}</p>
                    </div>
                </div>
                <button @click="deleteVideo" class="text-red-600 hover:text-red-800 text-sm font-medium">
                    Qayta urinish
                </button>
            </div>
        </div>
    </div>
</template>
