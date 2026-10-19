<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { RectangleStackIcon } from '@heroicons/vue/24/outline';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Alert, Badge, Card, EmptyState, PageHeader } from '@/Components/UI';
import OlympiadForm from '@/Components/Admin/OlympiadForm.vue';

const props = defineProps({
    olympiad: Object,
    form: Object, // current values in the form's shape (registration_fee, datetime-local strings, ...)
    sections: { type: Array, default: () => [] },
    types: Array,
    stages: Array,
    series: Array,
    regions: Array,
});

const form = useForm({ ...props.form });

const submit = () => {
    form.put(route('admin.olympiads.update', props.olympiad.id), { preserveScroll: true });
};

const SECTION_TYPES = { test: 'Test', open_ended: 'Ochiq savollar', practical: 'Amaliy', essay: 'Insho' };
</script>

<template>
    <AdminLayout>
        <Head :title="`${olympiad.title} — tahrirlash`" />

        <PageHeader title="Olimpiadani tahrirlash" :description="olympiad.title"
            :breadcrumbs="[
                { label: 'Olimpiadalar', href: route('admin.olympiads.index') },
                { label: olympiad.title, href: route('admin.olympiads.show', olympiad.id) },
                { label: 'Tahrirlash' },
            ]" />

        <div class="space-y-6">
            <Alert v-if="!olympiad.editable" variant="warning" title="Tahrirlab bo'lmaydi">
                Jonli yoki yakunlangan olimpiadaning sozlamalari o'zgartirilmaydi.
            </Alert>

            <OlympiadForm :form="form" :types="types" :stages="stages" :series="series" :regions="regions" mode="edit"
                submit-label="Saqlash" :cancel-href="route('admin.olympiads.show', olympiad.id)" @submit="submit" />

            <Card id="sections" title="Bo'limlar" description="Olimpiada turiga ko'ra yaratilgan bo'limlar" padding="none"
                class="max-w-3xl scroll-mt-24 lg:ml-[204px]">
                <ul v-if="sections.length" class="divide-y divide-border">
                    <li v-for="s in sections" :key="s.id" class="flex flex-wrap items-center gap-x-4 gap-y-1 px-5 py-3.5">
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-fg">{{ s.title }}</p>
                            <p class="text-13 text-fg-subtle">{{ SECTION_TYPES[s.section_type] || s.section_type }}</p>
                        </div>
                        <span class="text-13 tabular-nums text-fg-muted">{{ s.duration_minutes }} daq</span>
                        <span class="text-13 tabular-nums text-fg-muted">{{ s.max_points }} ball · {{ s.weight_percent }}%</span>
                        <Badge :variant="s.questions_count ? 'success' : 'warning'" size="sm">{{ s.questions_count }} savol</Badge>
                    </li>
                </ul>
                <EmptyState v-else :icon="RectangleStackIcon" size="sm" title="Bo'limlar yo'q"
                    description="Olimpiada yaratishda «Standart bo'limlarni yaratish» yoqilmagan." />
            </Card>
        </div>
    </AdminLayout>
</template>
