<script setup>
import { computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { PhoneIcon, ShieldCheckIcon } from '@heroicons/vue/24/outline';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Alert, Button, Card, Checkbox, Input, PageHeader, Switch } from '@/Components/UI';
import ChoiceGroup from '@/Components/Exam/People/ChoiceGroup.vue';

const props = defineProps({
    profile: { type: Object, default: null },
    isComplete: Boolean,
});

const form = useForm({
    filled_by: props.profile?.filled_by ?? null,
    grade: props.profile?.grade ?? null,
    subject: props.profile?.subject ?? null,
    goal: props.profile?.goal ?? null,
    school_name: props.profile?.school_name ?? '',
    parent_phone: props.profile?.parent_phone ?? '+998',
    parent_consent: props.profile?.parent_consent ?? false,
    show_name_publicly: props.profile?.show_name_publicly ?? false,
});

const whoOptions = [
    { value: 'student', label: 'O\'quvchi', hint: 'O\'zim to\'ldiryapman' },
    { value: 'parent', label: 'Ota-ona', hint: 'Farzandim uchun' },
];
const gradeOptions = [
    { value: '9', label: '9-sinf' },
    { value: '10', label: '10-sinf' },
    { value: '11', label: '11-sinf' },
    { value: 'graduate', label: 'Bitirgan' },
];
const subjectOptions = [
    { value: 'math', label: 'Matematika' },
    { value: 'physics', label: 'Fizika' },
    { value: 'both', label: 'Ikkalasi' },
];
const goalOptions = [
    { value: 'otm_grant', label: 'OTM grant', hint: 'Oliy ta\'limga grant asosida' },
    { value: 'milliy_sertifikat', label: 'Milliy sertifikat', hint: 'A / B / C daraja' },
    { value: 'sat', label: 'SAT', hint: 'Xorijiy universitetlar' },
];

const byParent = computed(() => form.filled_by === 'parent');
const consentLabel = computed(() => (byParent.value
    ? 'Men — ota-onaman va farzandimning ma\'lumotlarini platformada qayta ishlashga roziman'
    : 'Ota-onam ma\'lumotlarimni platformada qayta ishlashga rozi'));

const submit = () => form.post(route('student.exam.onboarding.store'), { preserveScroll: true });
</script>

<template>
    <StudentLayout title="Anketa">
        <Head title="Shanba imtihoni — anketa" />

        <div class="mx-auto max-w-xl">
            <PageHeader title="Shanba imtihoni" description="Qisqa anketa — 1 daqiqa. Natijalar va tavsiyalar shunga qarab tuziladi." />

            <Alert v-if="!isComplete && profile" variant="warning" class="mb-6">
                Imtihonlarda qatnashish uchun ota-onaning roziligi kerak.
            </Alert>

            <form class="space-y-6" novalidate @submit.prevent="submit">
                <Card>
                    <div class="space-y-6">
                        <ChoiceGroup v-model="form.filled_by" name="filled_by" label="Anketani kim to'ldiryapti?"
                            :options="whoOptions" :error="form.errors.filled_by" />
                        <ChoiceGroup v-model="form.grade" name="grade" label="Sinf" :options="gradeOptions"
                            :columns="4" :error="form.errors.grade" />
                        <ChoiceGroup v-model="form.subject" name="subject" label="Fan" :options="subjectOptions"
                            :columns="3" :error="form.errors.subject" />
                        <ChoiceGroup v-model="form.goal" name="goal" label="Maqsad" :options="goalOptions"
                            :columns="1" :error="form.errors.goal" />
                        <Input v-model="form.school_name" label="Maktab" name="school_name" optional maxlength="150"
                            placeholder="Masalan: 14-maktab, Urganch" autocomplete="off"
                            hint="Maktablar reytingi uchun" :error="form.errors.school_name" />
                    </div>
                </Card>

                <Card title="Ota-ona" description="Haftalik hisobot shu raqam egasiga bog'lanadi.">
                    <div class="space-y-5">
                        <Input v-model="form.parent_phone" type="tel" inputmode="tel" name="parent_phone"
                            label="Ota-onaning telefon raqami" placeholder="+998901234567" autocomplete="tel"
                            :leading-icon="PhoneIcon" required :error="form.errors.parent_phone" />
                        <Checkbox v-model="form.parent_consent" name="parent_consent" :label="consentLabel"
                            description="Ism, sinf, natijalar faqat o'quv jarayoni uchun ishlatiladi va uchinchi shaxslarga berilmaydi."
                            :error="form.errors.parent_consent" />
                    </div>
                </Card>

                <Card>
                    <Switch v-model="form.show_name_publicly" label="Reytingda ismim ko'rinsin"
                        description="O'chiq bo'lsa, reytingda taxallus ko'rsatiladi (masalan, «Zukko Burgut 42»)." />
                </Card>

                <div class="flex items-center justify-between gap-3">
                    <p class="flex items-center gap-1.5 text-xs text-fg-subtle">
                        <ShieldCheckIcon class="h-4 w-4" aria-hidden="true" />
                        Rozilik vaqti saqlanadi
                    </p>
                    <Button type="submit" size="lg" :loading="form.processing">Saqlash</Button>
                </div>
            </form>
        </div>
    </StudentLayout>
</template>
