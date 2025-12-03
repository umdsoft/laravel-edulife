<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import Modal from '@/Components/UI/Modal.vue';
import Input from '@/Components/UI/Input.vue';
import Button from '@/Components/UI/Button.vue';

const props = defineProps({
    bankAccounts: Array,
});

const showModal = ref(false);
const editingAccount = ref(null);

const form = useForm({
    bank_name: '',
    account_number: '',
    account_holder_name: '',
    card_number: '',
});

const openCreateModal = () => {
    editingAccount.value = null;
    form.reset();
    showModal.value = true;
};

const openEditModal = (account) => {
    editingAccount.value = account;
    form.bank_name = account.bank_name;
    form.account_number = account.account_number;
    form.account_holder_name = account.account_holder_name;
    form.card_number = account.card_number;
    showModal.value = true;
};

const submit = () => {
    if (editingAccount.value) {
        form.put(route('teacher.bank-accounts.update', editingAccount.value.id), {
            onSuccess: () => showModal.value = false,
        });
    } else {
        form.post(route('teacher.bank-accounts.store'), {
            onSuccess: () => showModal.value = false,
        });
    }
};

const deleteAccount = (account) => {
    if (confirm('Rostdan ham bu hisobni o\'chirmoqchimisiz?')) {
        useForm({}).delete(route('teacher.bank-accounts.destroy', account.id));
    }
};

const setPrimary = (account) => {
    useForm({}).patch(route('teacher.bank-accounts.set-primary', account.id));
};
</script>

<template>

    <Head title="Bank hisoblar" />

    <TeacherLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800">Bank hisoblar</h2>
                <Button @click="openCreateModal">
                    + Yangi hisob
                </Button>
            </div>
        </template>

        <div class="space-y-6">
            <div v-if="bankAccounts.length === 0" class="text-center py-12 bg-white rounded-2xl border border-gray-100">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl">
                    🏦
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Bank hisoblar mavjud emas</h3>
                <p class="text-gray-500 mb-6">To'lovlarni qabul qilish uchun bank hisob qo'shing</p>
                <Button @click="openCreateModal">Hisob qo'shish</Button>
            </div>

            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div v-for="account in bankAccounts" :key="account.id"
                    class="p-6 bg-white border border-gray-100 rounded-2xl relative group">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-emerald-50 rounded-lg flex items-center justify-center text-xl">
                                🏦
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900">{{ account.bank_name }}</h3>
                                <div v-if="account.is_primary"
                                    class="text-xs font-medium text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full inline-block mt-1">
                                    Asosiy hisob
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button @click="openEditModal(account)"
                                class="p-2 text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg">
                                ✎
                            </button>
                            <button @click="deleteAccount(account)"
                                class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg">
                                🗑
                            </button>
                        </div>
                    </div>

                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between py-2 border-b border-gray-50">
                            <span class="text-gray-500">Karta raqami</span>
                            <span class="font-mono font-medium">{{ account.card_number || '---' }}</span>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-50">
                            <span class="text-gray-500">Hisob raqami</span>
                            <span class="font-mono font-medium">{{ account.account_number }}</span>
                        </div>
                        <div class="flex justify-between py-2">
                            <span class="text-gray-500">Hisob egasi</span>
                            <span class="font-medium uppercase">{{ account.account_holder_name }}</span>
                        </div>
                    </div>

                    <div class="mt-6 pt-4 border-t border-gray-100 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span
                                :class="['w-2 h-2 rounded-full', account.is_verified ? 'bg-green-500' : 'bg-yellow-500']"></span>
                            <span class="text-xs font-medium text-gray-500">
                                {{ account.is_verified ? 'Tasdiqlangan' : 'Tekshiruvda' }}
                            </span>
                        </div>
                        <button v-if="!account.is_primary" @click="setPrimary(account)"
                            class="text-sm font-medium text-emerald-600 hover:text-emerald-700">
                            Asosiy qilish
                        </button>
                    </div>
                </div>
            </div>

            <div class="p-4 bg-blue-50 text-blue-700 rounded-xl text-sm flex gap-3">
                <span class="text-xl">ℹ️</span>
                <p>
                    To'lov olish uchun kamida bitta tasdiqlangan bank hisobi bo'lishi kerak.
                    Yangi qo'shilgan hisoblar moderator tomonidan 24 soat ichida tekshiriladi.
                </p>
            </div>
        </div>

        <Modal :show="showModal" @close="showModal = false">
            <div class="p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">
                    {{ editingAccount ? 'Hisobni tahrirlash' : 'Yangi hisob qo\'shish' }}
                </h3>

                <form @submit.prevent="submit" class="space-y-4">
                    <Input v-model="form.bank_name" label="Bank nomi" placeholder="Masalan: NBU, Kapital Bank"
                        required />
                    <Input v-model="form.card_number" label="Karta raqami" placeholder="8600 0000 0000 0000" />
                    <Input v-model="form.account_number" label="Hisob raqami (20 ta raqam)" placeholder="2020 8000 ..."
                        required />
                    <Input v-model="form.account_holder_name" label="Hisob egasi (F.I.O)" placeholder="IVANOV IVAN"
                        required />

                    <div class="flex justify-end gap-3 mt-6">
                        <button type="button" @click="showModal = false"
                            class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-xl">
                            Bekor qilish
                        </button>
                        <Button type="submit" :loading="form.processing">
                            Saqlash
                        </Button>
                    </div>
                </form>
            </div>
        </Modal>
    </TeacherLayout>
</template>
