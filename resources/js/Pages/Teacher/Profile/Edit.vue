<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import Input from '@/Components/UI/Input.vue';
import Button from '@/Components/UI/Button.vue';

const props = defineProps({
    user: Object,
});

const form = useForm({
    first_name: props.user.first_name,
    last_name: props.user.last_name,
    email: props.user.email,
    bio: props.user.profile?.bio || '',
    headline: props.user.teacher_profile?.headline || '',
    website: props.user.profile?.website || '',
    experience_years: props.user.teacher_profile?.experience_years || 0,
    expertise: props.user.teacher_profile?.expertise || [],
    social_links: props.user.profile?.social_links || {},
    // Helper for expertise input
    new_skill: '',
});

const avatarForm = useForm({
    avatar: null,
});

const submit = () => {
    form.put(route('teacher.profile.update'), {
        preserveScroll: true,
    });
};

const updateAvatar = (e) => {
    const file = e.target.files[0];
    if (!file) return;

    avatarForm.avatar = file;
    avatarForm.post(route('teacher.profile.avatar'), {
        preserveScroll: true,
        onSuccess: () => {
            avatarForm.reset();
        },
    });
};

const deleteAvatar = () => {
    if (confirm('Avatarni o\'chirmoqchimisiz?')) {
        router.delete(route('teacher.profile.delete-avatar'));
    }
};

const addSkill = () => {
    if (!form.new_skill) return;
    if (!form.expertise) form.expertise = [];

    form.expertise.push(form.new_skill);
    form.new_skill = '';
};

const removeSkill = (index) => {
    form.expertise.splice(index, 1);
};
</script>

<template>

    <Head title="Profilni tahrirlash" />

    <TeacherLayout>
        <template #header>
            <div class="flex items-center gap-4">
                <Link href="/teacher/profile" class="text-gray-500 hover:text-gray-700">
                ← Orqaga
                </Link>
                <h2 class="text-xl font-semibold text-gray-800">Profilni tahrirlash</h2>
            </div>
        </template>

        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Avatar -->
            <div class="p-6 bg-white border border-gray-100 rounded-2xl">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Avatar</h3>
                <div class="flex items-center gap-6">
                    <div class="relative w-24 h-24">
                        <img v-if="user.avatar_url" :src="user.avatar_url"
                            class="w-full h-full rounded-full object-cover" />
                        <div v-else
                            class="w-full h-full rounded-full bg-emerald-100 flex items-center justify-center text-3xl font-bold text-emerald-600">
                            {{ user.first_name[0] }}{{ user.last_name[0] }}
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label
                            class="inline-block px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-xl cursor-pointer hover:bg-emerald-700 transition-colors">
                            Yuklash
                            <input type="file" class="hidden" accept="image/*" @change="updateAvatar">
                        </label>
                        <button v-if="user.avatar_url" @click="deleteAvatar"
                            class="block px-4 py-2 text-red-600 text-sm font-medium hover:bg-red-50 rounded-xl transition-colors">
                            O'chirish
                        </button>
                    </div>
                </div>
            </div>

            <!-- Main Form -->
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Personal Info -->
                <div class="p-6 bg-white border border-gray-100 rounded-2xl space-y-4">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Shaxsiy ma'lumotlar</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <Input v-model="form.first_name" label="Ism" required />
                        <Input v-model="form.last_name" label="Familiya" required />
                    </div>

                    <Input v-model="form.email" label="Email" type="email" required />
                    <Input v-model="form.headline" label="Kasb (Headline)"
                        placeholder="Masalan: Senior JavaScript Developer" />

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bio</label>
                        <textarea v-model="form.bio" rows="4"
                            class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-shadow"
                            placeholder="O'zingiz haqingizda qisqacha..."></textarea>
                    </div>
                </div>

                <!-- Professional Info -->
                <div class="p-6 bg-white border border-gray-100 rounded-2xl space-y-4">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Professional ma'lumotlar</h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <Input v-model="form.experience_years" label="Tajriba (yil)" type="number" min="0" />
                        <Input v-model="form.website" label="Vebsayt" placeholder="https://..." />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ko'nikmalar</label>
                        <div class="flex gap-2 mb-2">
                            <input v-model="form.new_skill" @keydown.enter.prevent="addSkill" type="text"
                                class="flex-1 px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                                placeholder="Ko'nikma qo'shish va Enter bosing">
                            <button type="button" @click="addSkill"
                                class="px-4 py-2 bg-gray-100 text-gray-700 font-medium rounded-xl hover:bg-gray-200">
                                Qo'shish
                            </button>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <span v-for="(skill, index) in form.expertise" :key="index"
                                class="inline-flex items-center gap-1 px-3 py-1 bg-emerald-50 text-emerald-700 rounded-lg text-sm">
                                {{ skill }}
                                <button type="button" @click="removeSkill(index)"
                                    class="text-emerald-500 hover:text-emerald-800">×</button>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <Button type="submit" :loading="form.processing">
                        Saqlash
                    </Button>
                </div>
            </form>
        </div>
    </TeacherLayout>
</template>
