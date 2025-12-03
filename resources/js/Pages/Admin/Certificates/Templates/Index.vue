<template>
    <AdminLayout>

        <Head title="Sertifikat Shablonlari" />

        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Sertifikat Shablonlari</h1>
            <Link href="/admin/certificate-templates/create"
                class="bg-primary hover:bg-primary/90 text-white px-4 py-2 rounded-xl flex items-center gap-2 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Yangi shablon
            </Link>
        </div>

        <!-- Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <div v-for="template in templates.data" :key="template.id"
                class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden group hover:shadow-md transition-all duration-300">
                <!-- Preview Image -->
                <div class="relative aspect-[1.414] bg-gray-100">
                    <img v-if="template.thumbnail_path" :src="`/storage/${template.thumbnail_path}`"
                        class="w-full h-full object-cover" alt="Template preview" />
                    <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>

                    <!-- Overlay Actions -->
                    <div
                        class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                        <Link :href="`/admin/certificate-templates/${template.id}`"
                            class="p-2 bg-white rounded-lg hover:bg-gray-100 text-gray-900" title="Ko'rish">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        </Link>
                        <Link :href="`/admin/certificate-templates/${template.id}/edit`"
                            class="p-2 bg-white rounded-lg hover:bg-gray-100 text-blue-600" title="Tahrirlash">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        </Link>
                    </div>

                    <!-- Badges -->
                    <div class="absolute top-2 right-2 flex flex-col gap-1">
                        <span v-if="template.is_default"
                            class="bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded-md font-medium shadow-sm">
                            Default ⭐
                        </span>
                        <span v-if="!template.is_active"
                            class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-md font-medium shadow-sm">
                            Inactive
                        </span>
                    </div>
                </div>

                <!-- Info -->
                <div class="p-4">
                    <h3 class="font-semibold text-gray-900 mb-1 truncate">{{ template.name }}</h3>
                    <p class="text-sm text-gray-500 mb-3 truncate">
                        {{ template.course?.title || template.direction?.name || 'Umumiy shablon' }}
                    </p>

                    <div class="flex items-center justify-between text-xs text-gray-400">
                        <span>{{ template.usage_count }} ta berilgan</span>
                        <span>{{ new Date(template.created_at).toLocaleDateString() }}</span>
                    </div>
                </div>
            </div>
        </div>

        <Pagination :data="templates" class="mt-6" />
    </AdminLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Pagination from '@/Components/UI/Pagination.vue';

defineProps({
    templates: Object,
});
</script>
