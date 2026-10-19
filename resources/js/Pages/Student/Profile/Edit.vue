<script setup>
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowUpTrayIcon, TrashIcon } from '@heroicons/vue/24/outline';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Avatar, Button, Card, Input, PageHeader, Textarea } from '@/Components/UI';

const props = defineProps({
    user: Object,
});

// social_links may arrive as [] / null when empty — keyed links need an object
const socials = props.user.social_links && !Array.isArray(props.user.social_links) ? props.user.social_links : {};

const form = useForm({
    first_name: props.user.first_name || '',
    last_name: props.user.last_name || '',
    bio: props.user.bio || '',
    location: props.user.location || '',
    website: props.user.website || '',
    social_links: {
        telegram: socials.telegram || '',
        github: socials.github || '',
        linkedin: socials.linkedin || '',
        twitter: socials.twitter || '',
    },
});

const save = () => form.put(route('student.profile.update'), { preserveScroll: true });

const fileInput = ref(null);
const avatarForm = useForm({ avatar: null });
const onAvatar = (e) => {
    const file = e.target.files?.[0];
    if (!file) return;
    avatarForm.avatar = file;
    avatarForm.post(route('student.profile.avatar'), {
        preserveScroll: true,
        forceFormData: true,
        onFinish: () => {
            e.target.value = '';
            avatarForm.reset();
        },
    });
};
const removing = ref(false);
const removeAvatar = () => router.delete(route('student.profile.avatar.delete'), {
    preserveScroll: true,
    onStart: () => (removing.value = true),
    onFinish: () => (removing.value = false),
});

const fullName = () => [props.user.first_name, props.user.last_name].filter(Boolean).join(' ');
const SOCIAL = [
    { key: 'telegram', label: 'Telegram', placeholder: 'https://t.me/…' },
    { key: 'github', label: 'GitHub', placeholder: 'https://github.com/…' },
    { key: 'linkedin', label: 'LinkedIn', placeholder: 'https://linkedin.com/in/…' },
    { key: 'twitter', label: 'X (Twitter)', placeholder: 'https://x.com/…' },
];
</script>

<template>
    <StudentLayout title="Profilni tahrirlash">
        <Head title="Profilni tahrirlash" />

        <PageHeader title="Profilni tahrirlash"
            :breadcrumbs="[{ label: 'Profil', href: route('student.profile.show') }, { label: 'Tahrirlash' }]" />

        <div class="max-w-xl space-y-6">
            <Card title="Profil rasmi" description="JPG, PNG yoki WEBP, 2 MB gacha.">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                    <Avatar :src="user.avatar_url || user.avatar" :name="fullName()" size="xl" />
                    <div class="flex flex-wrap gap-2">
                        <input ref="fileInput" type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" tabindex="-1" aria-hidden="true" @change="onAvatar">
                        <Button variant="secondary" :icon-left="ArrowUpTrayIcon" :loading="avatarForm.processing" @click="fileInput?.click()">
                            {{ user.avatar ? 'Almashtirish' : 'Rasm yuklash' }}
                        </Button>
                        <Button v-if="user.avatar" variant="ghost" :icon-left="TrashIcon" :loading="removing" @click="removeAvatar">O'chirish</Button>
                    </div>
                </div>
                <p v-if="avatarForm.errors.avatar" class="mt-3 text-13 text-danger" role="alert">{{ avatarForm.errors.avatar }}</p>
            </Card>

            <form class="space-y-6" novalidate @submit.prevent="save">
                <Card title="Shaxsiy ma'lumotlar">
                    <div class="space-y-5">
                        <div class="grid gap-5 sm:grid-cols-2">
                            <Input v-model="form.first_name" label="Ism" required autocomplete="given-name" :error="form.errors.first_name" />
                            <Input v-model="form.last_name" label="Familiya" required autocomplete="family-name" :error="form.errors.last_name" />
                        </div>
                        <Textarea v-model="form.bio" label="O'zim haqimda" optional :rows="3" autoresize :maxlength="500" show-count :error="form.errors.bio" />
                        <div class="grid gap-5 sm:grid-cols-2">
                            <Input v-model="form.location" label="Shahar" optional maxlength="100" :error="form.errors.location" />
                            <Input v-model="form.website" label="Vebsayt" type="url" optional placeholder="https://…" :error="form.errors.website" />
                        </div>
                    </div>
                </Card>

                <Card title="Ijtimoiy tarmoqlar">
                    <div class="space-y-5">
                        <Input v-for="s in SOCIAL" :key="s.key" v-model="form.social_links[s.key]" :label="s.label" optional
                            :placeholder="s.placeholder" :error="form.errors[`social_links.${s.key}`]" />
                    </div>
                </Card>

                <div class="flex justify-end gap-2">
                    <Button variant="secondary" :href="route('student.profile.show')">Bekor qilish</Button>
                    <Button type="submit" :loading="form.processing">Saqlash</Button>
                </div>
            </form>
        </div>
    </StudentLayout>
</template>
