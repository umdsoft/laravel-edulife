<template>
    <AdminLayout>

        <Head title="Sertifikatlar" />

        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Sertifikatlar</h1>
            <div class="flex gap-2">
                <Link href="/admin/certificate-templates"
                    class="bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded-xl transition-colors">
                Shablonlar
                </Link>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-2xl shadow-sm p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <SearchInput v-model="filters.search" placeholder="Kod yoki talaba ismi..."
                    @update:model-value="search" />

                <select v-model="filters.course_id" @change="filter"
                    class="bg-gray-50 border-0 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary/20">
                    <option value="">Barcha kurslar</option>
                    <!-- Courses would be passed as props in a real scenario, or loaded async -->
                </select>

                <select v-model="filters.template_id" @change="filter"
                    class="bg-gray-50 border-0 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary/20">
                    <option value="">Barcha shablonlar</option>
                    <option v-for="t in templates" :key="t.id" :value="t.id">{{ t.name }}</option>
                </select>
            </div>
        </div>

        <!-- Table -->
        <DataTable :columns="columns" :rows="certificates.data" :loading="loading" empty-text="Sertifikatlar topilmadi">
            <template #default="{ rows }">
                <tr v-for="cert in rows" :key="cert.id" class="border-b border-gray-100 hover:bg-gray-50 transition">
                    <td class="px-6 py-4 font-mono text-sm text-primary font-medium">
                        {{ cert.code }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        {{ cert.student_name }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        {{ cert.course_title }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        {{ cert.template_name }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        {{ cert.issued_at }}
                    </td>
                    <td class="px-6 py-4 text-right space-x-2">
                        <Link :href="`/admin/certificates/${cert.id}`"
                            class="text-gray-400 hover:text-primary transition-colors" title="Ko'rish">
                        <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        </Link>
                        <a v-if="cert.pdf_url" :href="`/admin/certificates/${cert.id}/download`"
                            class="text-gray-400 hover:text-blue-600 transition-colors" title="Yuklab olish">
                            <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                        </a>
                    </td>
                </tr>
            </template>
        </DataTable>

        <Pagination :data="certificates" />
    </AdminLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/Admin/DataTable.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import SearchInput from '@/Components/Admin/SearchInput.vue';

const props = defineProps({
    certificates: Object,
    filters: Object,
    templates: Array,
});

const loading = ref(false);
const filters = reactive({
    search: props.filters?.search || '',
    course_id: props.filters?.course_id || '',
    template_id: props.filters?.template_id || '',
});

const columns = [
    { key: 'code', label: 'Kod' },
    { key: 'student', label: 'Talaba' },
    { key: 'course', label: 'Kurs' },
    { key: 'template', label: 'Shablon' },
    { key: 'date', label: 'Sana' },
    { key: 'actions', label: '' },
];

const search = () => {
    router.get('/admin/certificates', filters, {
        preserveState: true,
        preserveScroll: true,
    });
};

const filter = () => {
    router.get('/admin/certificates', filters, {
        preserveState: true,
    });
};
</script>
