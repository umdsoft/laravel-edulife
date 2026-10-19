<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { PageHeader } from '@/Components/UI';
import OlympiadForm from '@/Components/Admin/OlympiadForm.vue';

const props = defineProps({
    types: Array,
    stages: Array,
    series: Array,
    regions: Array,
});

const form = useForm({
    title: '',
    description: '',
    short_description: '',
    rules: '',
    olympiad_type_id: '',
    stage_id: '',
    series_id: '',
    region_id: '',
    registration_fee: 0,
    registration_start_at: '',
    registration_end_at: '',
    olympiad_start_at: '',
    olympiad_end_at: '',
    max_participants: null,
    demo_config: {
        enabled: false,
        price: 0,
        max_attempts: 3,
    },
    anti_cheat_config: {
        fullscreen_required: true,
        device_lock: true,
        max_tab_switches: 3,
        max_warnings: 5,
    },
    reward_config: {
        coins_per_correct: 1,
        top_10_coins: 100,
    },
    create_default_sections: true,
});

const submit = () => {
    form.post(route('admin.olympiads.store'), { preserveScroll: true });
};
</script>

<template>
    <AdminLayout>
        <Head title="Yangi olimpiada" />

        <PageHeader title="Yangi olimpiada" description="Olimpiada qoralama sifatida yaratiladi; e'lon qilishni keyin tanlaysiz"
            :breadcrumbs="[{ label: 'Olimpiadalar', href: route('admin.olympiads.index') }, { label: 'Yangi olimpiada' }]" />

        <OlympiadForm :form="form" :types="types" :stages="stages" :series="series" :regions="regions" mode="create"
            submit-label="Olimpiadani yaratish" :cancel-href="route('admin.olympiads.index')" @submit="submit" />
    </AdminLayout>
</template>
