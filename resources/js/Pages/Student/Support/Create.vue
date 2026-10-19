<script setup>
import { computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { PaperAirplaneIcon, QuestionMarkCircleIcon } from '@heroicons/vue/24/outline';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Alert, Button, Card, Input, PageHeader, Select, Textarea } from '@/Components/UI';

const props = defineProps({
    // { technical: 'Texnik muammo', ... }
    categories: { type: Object, default: () => ({}) },
});

const form = useForm({
    subject: '',
    category: 'technical',
    priority: 'medium',
    description: '',
});

const categoryOptions = computed(() => Object.entries(props.categories).map(([value, label]) => ({ value, label })));
const priorityOptions = [
    { value: 'low', label: 'Past' },
    { value: 'medium', label: "O'rta" },
    { value: 'high', label: 'Yuqori' },
    { value: 'urgent', label: 'Shoshilinch' },
];

const submit = () => form.post(route('student.support.store'));
</script>

<template>
    <StudentLayout title="Yangi so'rov">
        <Head title="Yangi so'rov" />

        <PageHeader title="Yangi so'rov" description="Muammoni batafsil yozing — javob shu so'rov sahifasida keladi."
            :breadcrumbs="[{ label: 'Yordam', href: route('student.support.index') }, { label: 'Yangi so\'rov' }]" />

        <div class="max-w-xl space-y-6">
            <Alert variant="info" :icon="QuestionMarkCircleIcon">
                Javob tezroq kerakmi?
                <a :href="route('student.support.faq')" class="focus-ring rounded-sm font-medium text-brand hover:underline">Ko'p so'raladigan savollar</a>ni ko'rib chiqing.
            </Alert>

            <Card>
                <form class="space-y-5" novalidate @submit.prevent="submit">
                    <Input v-model="form.subject" label="Mavzu" required maxlength="255" placeholder="Masalan: Video dars ochilmayapti"
                        :error="form.errors.subject" />
                    <div class="grid gap-5 sm:grid-cols-2">
                        <Select v-model="form.category" label="Bo'lim" required :options="categoryOptions" :error="form.errors.category" />
                        <Select v-model="form.priority" label="Muhimlik" :options="priorityOptions" :error="form.errors.priority" />
                    </div>
                    <Textarea v-model="form.description" label="Batafsil" required :rows="6" autoresize :maxlength="5000" show-count
                        hint="Kamida 20 ta belgi. Qaysi sahifada, nima qilganingizda muammo chiqdi?" :error="form.errors.description" />
                    <div class="flex justify-end gap-2 pt-2">
                        <Button variant="secondary" :href="route('student.support.index')">Bekor qilish</Button>
                        <Button type="submit" :icon-left="PaperAirplaneIcon" :loading="form.processing">Yuborish</Button>
                    </div>
                </form>
            </Card>
        </div>
    </StudentLayout>
</template>
