<script setup>
/**
 * PageHeader — first element of every page.
 *   <PageHeader title="Kurslar" description="Barcha kurslaringiz"
 *               :breadcrumbs="[{ label: 'Dashboard', href: route('teacher.dashboard') }, { label: 'Kurslar' }]"
 *               :back="route('teacher.dashboard')">
 *     <template #actions><Button :href="route('teacher.courses.create')" :icon-left="PlusIcon">Yangi kurs</Button></template>
 *     <template #meta><Badge variant="success">Nashr qilingan</Badge></template>
 *     <template #tabs><Tabs … /></template>
 *   </PageHeader>
 */
import { Link } from '@inertiajs/vue3';
import { ArrowLeftIcon } from '@heroicons/vue/20/solid';
import Breadcrumbs from './Breadcrumbs.vue';

defineProps({
    title: { type: String, default: null },
    description: { type: String, default: null },
    breadcrumbs: { type: Array, default: () => [] },
    back: { type: String, default: null },
    backLabel: { type: String, default: 'Orqaga' },
});
</script>

<template>
    <header class="mb-6">
        <Breadcrumbs v-if="breadcrumbs.length" :items="breadcrumbs" class="mb-3" />
        <Link v-else-if="back" :href="back"
            class="focus-ring mb-3 inline-flex items-center gap-1.5 rounded-sm text-13 font-medium text-fg-subtle hover:text-fg">
            <ArrowLeftIcon class="h-4 w-4" aria-hidden="true" />
            {{ backLabel }}
        </Link>

        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <slot name="title">
                    <h1 v-if="title" class="text-2xl font-semibold tracking-tight text-fg">{{ title }}</h1>
                </slot>
                <p v-if="description" class="mt-1 max-w-2xl text-sm text-fg-muted">{{ description }}</p>
                <div v-if="$slots.meta" class="mt-2 flex flex-wrap items-center gap-2">
                    <slot name="meta" />
                </div>
            </div>
            <div v-if="$slots.actions" class="flex shrink-0 flex-wrap items-center gap-2">
                <slot name="actions" />
            </div>
        </div>

        <div v-if="$slots.tabs" class="mt-5">
            <slot name="tabs" />
        </div>
    </header>
</template>
