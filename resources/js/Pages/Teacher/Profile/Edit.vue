<script setup>
import { computed, ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ArrowUpTrayIcon, TrashIcon } from '@heroicons/vue/24/outline';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Card from '@/Components/UI/Card.vue';
import Avatar from '@/Components/UI/Avatar.vue';
import Input from '@/Components/UI/Input.vue';
import Textarea from '@/Components/UI/Textarea.vue';
import Button from '@/Components/UI/Button.vue';
import ListEditor from '@/Components/Teacher/ListEditor.vue';
import ConfirmDialog from '@/Components/Teacher/ConfirmDialog.vue';
import { fullName } from '@/Components/Teacher/format';

const props = defineProps({
    user: Object,
});

// social_links may arrive as [] when empty — keyed links need an object
const initialSocials = (() => {
    const s = props.user.profile?.social_links;
    return s && !Array.isArray(s) && typeof s === 'object' ? { ...s } : {};
})();

const form = useForm({
    first_name: props.user.first_name,
    last_name: props.user.last_name,
    email: props.user.email,
    bio: props.user.profile?.bio || '',
    headline: props.user.teacher_profile?.headline || '',
    website: props.user.profile?.website || '',
    experience_years: props.user.teacher_profile?.experience_years || 0,
    expertise: props.user.teacher_profile?.expertise || [],
    social_links: initialSocials,
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

const avatarInput = ref(null);
const updateAvatar = (e) => {
    const file = e.target.files[0];
    if (!file) return;

    avatarForm.avatar = file;
    avatarForm.post(route('teacher.profile.avatar'), {
        preserveScroll: true,
        onSuccess: () => {
            avatarForm.reset();
        },
        onFinish: () => {
            e.target.value = '';
        },
    });
};

const confirmAvatarDelete = ref(false);
const deletingAvatar = ref(false);
const deleteAvatar = () => {
    router.delete(route('teacher.profile.delete-avatar'), {
        preserveScroll: true,
        onStart: () => (deletingAvatar.value = true),
        onFinish: () => {
            deletingAvatar.value = false;
            confirmAvatarDelete.value = false;
        },
    });
};

const SOCIAL_KEYS = [
    { key: 'telegram', label: 'Telegram', placeholder: 'https://t.me/…' },
    { key: 'instagram', label: 'Instagram', placeholder: 'https://instagram.com/…' },
    { key: 'youtube', label: 'YouTube', placeholder: 'https://youtube.com/@…' },
];
const socialFields = computed(() => {
    const known = SOCIAL_KEYS.map((s) => s.key);
    const extra = Object.keys(form.social_links || {}).filter((k) => !known.includes(k))
        .map((k) => ({ key: k, label: k.charAt(0).toUpperCase() + k.slice(1), placeholder: 'https://…' }));
    return [...SOCIAL_KEYS, ...extra];
});
</script>

<template>
    <Head title="Profilni tahrirlash" />

    <TeacherLayout>
        <PageHeader title="Profilni tahrirlash"
            :breadcrumbs="[{ label: 'Profil', href: route('teacher.profile.index') }, { label: 'Tahrirlash' }]" />

        <div class="max-w-[40rem] space-y-6">
            <Card title="Profil rasmi" description="JPG yoki PNG, 2 MB gacha.">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                    <Avatar :src="user.avatar_url" :name="fullName(user)" size="xl" />
                    <div class="flex flex-wrap gap-2">
                        <input ref="avatarInput" type="file" class="sr-only" accept="image/*" tabindex="-1" aria-hidden="true" @change="updateAvatar">
                        <Button variant="secondary" :icon-left="ArrowUpTrayIcon" :loading="avatarForm.processing" @click="avatarInput?.click()">
                            {{ user.avatar_url ? 'Almashtirish' : 'Rasm yuklash' }}
                        </Button>
                        <Button v-if="user.avatar_url" variant="ghost" :icon-left="TrashIcon" @click="confirmAvatarDelete = true">O'chirish</Button>
                    </div>
                </div>
                <p v-if="avatarForm.errors.avatar" class="mt-3 text-13 text-danger" role="alert">{{ avatarForm.errors.avatar }}</p>
            </Card>

            <form novalidate @submit.prevent="submit">
                <div class="space-y-6">
                    <Card title="Shaxsiy ma'lumotlar">
                        <div class="space-y-5">
                            <div class="grid gap-5 sm:grid-cols-2">
                                <Input v-model="form.first_name" label="Ism" required autocomplete="given-name" :error="form.errors.first_name" />
                                <Input v-model="form.last_name" label="Familiya" required autocomplete="family-name" :error="form.errors.last_name" />
                            </div>
                            <Input v-model="form.email" label="Email" type="email" autocomplete="email" optional :error="form.errors.email" />
                            <Input v-model="form.headline" label="Qisqa tavsif (headline)" placeholder="Masalan: Matematika va fizika o'qituvchisi"
                                maxlength="255" optional :error="form.errors.headline" />
                            <Textarea v-model="form.bio" label="Bio" :rows="4" autoresize maxlength="2000" show-count optional
                                placeholder="O'zingiz haqingizda qisqacha…" :error="form.errors.bio" />
                        </div>
                    </Card>

                    <Card title="Kasbiy ma'lumotlar">
                        <div class="space-y-5">
                            <div class="grid gap-5 sm:grid-cols-2">
                                <Input v-model="form.experience_years" label="Tajriba (yil)" type="number" min="0" max="50" inputmode="numeric"
                                    :error="form.errors.experience_years" />
                                <Input v-model="form.website" label="Vebsayt" type="url" placeholder="https://…" optional :error="form.errors.website" />
                            </div>
                            <ListEditor v-model="form.expertise" variant="chips" label="Ko'nikmalar" placeholder="Masalan: Olimpiada masalalari"
                                hint="Enter bilan qo'shing" optional :error="form.errors.expertise" />
                        </div>
                    </Card>

                    <Card title="Ijtimoiy tarmoqlar">
                        <div class="space-y-5">
                            <Input v-for="s in socialFields" :key="s.key" v-model="form.social_links[s.key]" :label="s.label" type="url"
                                :placeholder="s.placeholder" optional :error="form.errors[`social_links.${s.key}`]" />
                        </div>
                    </Card>
                </div>

                <div class="sticky bottom-0 z-10 -mx-4 mt-6 flex justify-end gap-2 border-t border-border bg-bg/90 px-4 py-3 backdrop-blur sm:mx-0 sm:px-0">
                    <Button variant="secondary" :href="route('teacher.profile.index')">Bekor qilish</Button>
                    <Button type="submit" :loading="form.processing">Saqlash</Button>
                </div>
            </form>
        </div>

        <ConfirmDialog :show="confirmAvatarDelete" title="Profil rasmini o'chirasizmi?" description="Rasm o'rniga ism bosh harflari ko'rsatiladi."
            :loading="deletingAvatar" @confirm="deleteAvatar" @close="confirmAvatarDelete = false" />
    </TeacherLayout>
</template>
