<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    certificate: Object,
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString();
};
</script>

<template>
    <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden hover:shadow-lg transition-shadow group">
        <div class="aspect-[1.414] bg-gray-100 relative">
            <!-- Placeholder for certificate preview -->
            <div class="absolute inset-0 flex items-center justify-center text-gray-300">
                <svg class="w-20 h-20" fill="currentColor" viewBox="0 0 24 24">
                    <path
                        d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14zm-7-2h5v-2h-5v2zm0-4h5v-2h-5v2zm0-4h5V7h-5v2zM7 7h5v10H7V7z" />
                </svg>
            </div>

            <!-- Overlay -->
            <div
                class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                <Link :href="route('student.certificates.show', certificate.id)"
                    class="p-2 bg-white rounded-full hover:bg-gray-100 transition-colors">
                <svg class="w-6 h-6 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                </Link>
                <a :href="route('student.certificates.download', certificate.id)"
                    class="p-2 bg-white rounded-full hover:bg-gray-100 transition-colors">
                    <svg class="w-6 h-6 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                </a>
            </div>
        </div>

        <div class="p-4">
            <h3 class="font-bold text-gray-900 line-clamp-1 mb-1">{{ certificate.course.title }}</h3>
            <p class="text-sm text-gray-500 mb-3">Berilgan sana: {{ formatDate(certificate.issued_at) }}</p>

            <div class="flex items-center justify-between">
                <span class="text-xs font-mono bg-gray-100 px-2 py-1 rounded text-gray-600">
                    {{ certificate.certificate_number }}
                </span>
                <span class="text-xs text-green-600 font-medium flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Tasdiqlangan
                </span>
            </div>
        </div>
    </div>
</template>
