<template>
    <AdminLayout>

        <Head title="Savollar Banki" />

        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Savollar Banki</h1>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-2xl shadow-sm p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <SearchInput v-model="filters.search" placeholder="Savol matni..." @update:model-value="search" />
            </div>
        </div>

        <!-- Table -->
        <DataTable :columns="columns" :rows="questions.data" :loading="loading" empty-text="Savollar topilmadi">
            <template #default="{ rows }">
                <tr v-for="question in rows" :key="question.id"
                    class="border-b border-gray-100 hover:bg-gray-50 transition">
                    <td class="px-6 py-4 text-sm text-gray-900">
                        <div class="line-clamp-2">{{ question.question_text }}</div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        {{ question.quiz?.title || 'N/A' }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">
                        {{ question.quiz?.course?.title || 'N/A' }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        {{ question.type }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        {{ question.points }} ball
                    </td>
                    <td class="px-6 py-4 text-right space-x-2">
                        <button @click="openModal(question)" class="text-blue-600 hover:text-blue-800 transition-colors"
                            title="Ko'rish">
                            <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                        <button @click="deleteQuestion(question)"
                            class="text-red-600 hover:text-red-800 transition-colors" title="O'chirish">
                            <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </td>
                </tr>
            </template>
        </DataTable>

        <Pagination :data="questions" />

        <!-- Modal -->
        <Modal :show="showModal" @close="closeModal">
            <div class="p-6" v-if="selectedQuestion">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Savol tafsilotlari</h2>

                <div class="space-y-4">
                    <div>
                        <span class="text-sm text-gray-500 block">Savol</span>
                        <p class="text-gray-900 font-medium mt-1">{{ selectedQuestion.question_text }}</p>
                    </div>

                    <div v-if="selectedQuestion.answers">
                        <span class="text-sm text-gray-500 block mb-2">Javob variantlari</span>
                        <div class="space-y-2">
                            <div v-for="answer in selectedQuestion.answers" :key="answer.id"
                                class="p-3 rounded-lg border"
                                :class="answer.is_correct ? 'bg-green-50 border-green-200' : 'bg-gray-50 border-gray-200'">
                                <div class="flex items-center justify-between">
                                    <span :class="answer.is_correct ? 'text-green-900' : 'text-gray-900'">
                                        {{ answer.answer_text }}
                                    </span>
                                    <span v-if="answer.is_correct" class="text-green-600 text-sm font-medium">
                                        To'g'ri javob
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end mt-6">
                        <Button type="button" variant="secondary" @click="closeModal">Yopish</Button>
                    </div>
                </div>
            </div>
        </Modal>
    </AdminLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import DataTable from '@/Components/Admin/DataTable.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import SearchInput from '@/Components/Admin/SearchInput.vue';
import Modal from '@/Components/UI/Modal.vue';
import Button from '@/Components/UI/Button.vue';

const props = defineProps({
    questions: Object,
    filters: Object,
});

const loading = ref(false);
const showModal = ref(false);
const selectedQuestion = ref(null);

const filters = reactive({
    search: props.filters?.search || '',
});

const columns = [
    { key: 'question', label: 'Savol' },
    { key: 'quiz', label: 'Test' },
    { key: 'course', label: 'Kurs' },
    { key: 'type', label: 'Tur' },
    { key: 'points', label: 'Ball' },
    { key: 'actions', label: '' },
];

const search = () => {
    router.get('/admin/questions', filters, {
        preserveState: true,
        preserveScroll: true,
    });
};

const openModal = (question) => {
    // Fetch full details including answers if not present
    // For now assuming they are loaded or we fetch them
    selectedQuestion.value = question;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    selectedQuestion.value = null;
};

const deleteQuestion = (question) => {
    if (confirm('Savolni o\'chirmoqchimisiz?')) {
        router.delete(`/admin/questions/${question.id}`);
    }
};
</script>
