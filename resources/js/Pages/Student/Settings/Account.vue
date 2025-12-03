<template>
    <StudentLayout title="Sozlamalar - Hisob">
        <div class="max-w-4xl mx-auto py-6 px-4">

            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <!-- Tabs -->
                <div class="flex border-b">
                    <Link :href="route('student.settings.account')"
                        class="px-6 py-4 font-semibold border-b-2 border-purple-600 text-purple-600">
                    📝 Hisob
                    </Link>
                    <Link :href="route('student.settings.privacy')"
                        class="px-6 py-4 font-semibold text-gray-600 hover:bg-gray-50">
                    🔒 Maxfiylik
                    </Link>
                </div>

                <div class="p-6 space-y-6">
                    <!-- Account Form -->
                    <form @submit.prevent="updateAccount">
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold mb-2">Ism</label>
                                    <input v-model="form.first_name" type="text"
                                        class="w-full px-4 py-2 border rounded-lg" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold mb-2">Familiya</label>
                                    <input v-model="form.last_name" type="text"
                                        class="w-full px-4 py-2 border rounded-lg" required>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold mb-2">Username</label>
                                <input v-model="form.username" type="text" class="w-full px-4 py-2 border rounded-lg"
                                    placeholder="faqat kichik harf va raqamlar">
                                <p class="text-xs text-gray-500 mt-1">
                                    Sizning profilingiz: edulife.uz/u/{{ form.username || 'username' }}
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold mb-2">Email</label>
                                <input v-model="form.email" type="email"
                                    class="w-full px-4 py-2 border rounded-lg bg-gray-100" disabled>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold mb-2">Telefon</label>
                                <input v-model="form.phone" type="tel" class="w-full px-4 py-2 border rounded-lg">
                            </div>

                            <button type="submit" class="btn-primary">
                                Saqlash
                            </button>
                        </div>
                    </form>

                    <hr class="my-8">

                    <!-- Password Form -->
                    <form @submit.prevent="updatePassword">
                        <h3 class="text-lg font-bold mb-4">Parolni o'zgartirish</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-semibold mb-2">Joriy parol</label>
                                <input v-model="passwordForm.current_password" type="password"
                                    class="w-full px-4 py-2 border rounded-lg" required>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold mb-2">Yangi parol</label>
                                <input v-model="passwordForm.password" type="password"
                                    class="w-full px-4 py-2 border rounded-lg" required>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold mb-2">Yangi parolni tasdiqlang</label>
                                <input v-model="passwordForm.password_confirmation" type="password"
                                    class="w-full px-4 py-2 border rounded-lg" required>
                            </div>
                            <button type="submit" class="btn-primary">
                                Parolni yangilash
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </StudentLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    user: Object,
});

const form = ref({
    first_name: props.user.first_name,
    last_name: props.user.last_name,
    username: props.user.username,
    email: props.user.email,
    phone: props.user.phone,
});

const passwordForm = ref({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updateAccount = () => {
    router.put(route('student.settings.account.update'), form.value);
};

const updatePassword = () => {
    router.put(route('student.settings.password.update'), passwordForm.value, {
        onSuccess: () => {
            passwordForm.value = {
                current_password: '',
                password: '',
                password_confirmation: '',
            };
        },
    });
};
</script>
