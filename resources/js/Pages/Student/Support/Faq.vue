<script setup>
import { computed, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import { ChevronDownIcon, MagnifyingGlassIcon, PlusIcon, QuestionMarkCircleIcon } from '@heroicons/vue/24/outline';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Button, Card, EmptyState, Input, PageHeader } from '@/Components/UI';

const props = defineProps({
    // [{ category, questions: [{ q, a }] }]
    faqs: { type: Array, default: () => [] },
});

const search = ref('');
const open = ref(null);
const groups = computed(() => {
    const q = search.value.trim().toLowerCase();
    return props.faqs
        .map((g) => ({ ...g, questions: (g.questions || []).filter((item) => !q || `${item.q} ${item.a}`.toLowerCase().includes(q)) }))
        .filter((g) => g.questions.length);
});
const toggle = (key) => (open.value = open.value === key ? null : key);
</script>

<template>
    <StudentLayout title="Ko'p so'raladigan savollar">
        <Head title="Ko'p so'raladigan savollar" />

        <PageHeader title="Ko'p so'raladigan savollar" description="Tez-tez beriladigan savollarga javoblar."
            :breadcrumbs="[{ label: 'Yordam', href: route('student.support.index') }, { label: 'Savol-javob' }]">
            <template #actions>
                <Button :icon-left="PlusIcon" :href="route('student.support.create')">Savol yuborish</Button>
            </template>
        </PageHeader>

        <div class="max-w-3xl space-y-6">
            <Input v-model="search" type="search" placeholder="Savol bo'yicha qidirish" :leading-icon="MagnifyingGlassIcon" aria-label="Qidirish" />

            <EmptyState v-if="!groups.length" :icon="QuestionMarkCircleIcon" bordered title="Mos savol topilmadi"
                description="Javob topilmasa, yordam xizmatiga so'rov yuboring.">
                <Button :href="route('student.support.create')">So'rov yuborish</Button>
            </EmptyState>

            <Card v-for="group in groups" :key="group.category" :title="group.category" padding="none">
                <ul class="divide-y divide-border">
                    <li v-for="(item, i) in group.questions" :key="i">
                        <button type="button" :aria-expanded="open === `${group.category}-${i}`" :aria-controls="`faq-${group.category}-${i}`"
                            class="focus-ring flex w-full items-center justify-between gap-4 px-5 py-4 text-left transition-colors duration-150 hover:bg-surface-2 sm:px-6"
                            @click="toggle(`${group.category}-${i}`)">
                            <span class="text-sm font-medium text-fg">{{ item.q }}</span>
                            <ChevronDownIcon :class="['h-5 w-5 shrink-0 text-fg-subtle transition-transform duration-150', open === `${group.category}-${i}` ? 'rotate-180' : '']" aria-hidden="true" />
                        </button>
                        <p v-show="open === `${group.category}-${i}` || search" :id="`faq-${group.category}-${i}`" class="px-5 pb-4 text-sm leading-6 text-fg-muted sm:px-6">
                            {{ item.a }}
                        </p>
                    </li>
                </ul>
            </Card>
        </div>
    </StudentLayout>
</template>
