<script setup>
import { computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import {
    AcademicCapIcon, ArrowLeftIcon, ClockIcon, ExclamationTriangleIcon, HomeIcon, LockClosedIcon, MagnifyingGlassIcon,
} from '@heroicons/vue/24/outline';
import { Button } from '@/Components/UI';

const props = defineProps({
    status: { type: Number, required: true },
});

const MESSAGES = {
    403: {
        title: "Bu sahifaga kirish huquqingiz yo'q",
        description: "Sahifa boshqa foydalanuvchiga tegishli yoki sizning rolingiz uchun yopiq.",
        icon: LockClosedIcon,
    },
    404: {
        title: 'Sahifa topilmadi',
        description: "Havola noto'g'ri yoki sahifa o'chirilgan bo'lishi mumkin.",
        icon: MagnifyingGlassIcon,
    },
    419: {
        title: 'Sahifa eskirdi',
        description: "Uzoq vaqt harakatsiz qoldingiz. Sahifani yangilab, qayta urinib ko'ring.",
        icon: ClockIcon,
    },
    429: {
        title: "Juda ko'p so'rov",
        description: "Birozdan so'ng qayta urinib ko'ring.",
        icon: ClockIcon,
    },
    500: {
        title: 'Kutilmagan xatolik',
        description: "Nimadir noto'g'ri ketdi. Biz bu haqda xabardormiz — birozdan so'ng qayta urinib ko'ring.",
        icon: ExclamationTriangleIcon,
    },
    503: {
        title: 'Texnik ishlar olib borilmoqda',
        description: "Platforma tez orada qayta ishga tushadi.",
        icon: ExclamationTriangleIcon,
    },
};

const message = computed(() => MESSAGES[props.status] || MESSAGES[500]);

// "/" sends a signed-in user to their own dashboard and a guest to the home page
const homeHref = '/';
const user = computed(() => usePage().props.auth?.user);

const goBack = () => window.history.back();
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center bg-bg px-4 py-10 text-fg">
        <Head :title="message.title" />

        <main class="w-full max-w-md text-center">
            <a href="/" class="focus-ring mb-8 inline-flex items-center gap-2 rounded-md">
                <span class="flex h-9 w-9 items-center justify-center rounded-md bg-brand-600 text-white">
                    <AcademicCapIcon class="h-5 w-5" aria-hidden="true" />
                </span>
                <span class="text-lg font-semibold tracking-tight text-fg">EDULIFE</span>
            </a>

            <section class="rounded-xl border border-border bg-surface px-6 py-10 shadow-sm">
                <span class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-surface-2 text-fg-muted">
                    <component :is="message.icon" class="h-7 w-7" aria-hidden="true" />
                </span>
                <p class="text-13 font-medium tabular-nums text-fg-subtle">Xato {{ status }}</p>
                <h1 class="mt-1 text-xl font-semibold text-fg">{{ message.title }}</h1>
                <p class="mt-2 text-sm text-fg-muted">{{ message.description }}</p>

                <div class="mt-8 flex flex-col-reverse justify-center gap-2 sm:flex-row">
                    <Button variant="secondary" :icon-left="ArrowLeftIcon" @click="goBack">Orqaga</Button>
                    <Button :href="homeHref" external :icon-left="HomeIcon">
                        {{ user ? 'Bosh sahifaga qaytish' : 'Bosh sahifa' }}
                    </Button>
                </div>
            </section>
        </main>
    </div>
</template>
