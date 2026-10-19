<script setup>
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';
import { AdjustmentsHorizontalIcon, ChevronDownIcon } from '@heroicons/vue/20/solid';
import Select from '@/Components/UI/Select.vue';
import Radio from '@/Components/UI/Radio.vue';
import Button from '@/Components/UI/Button.vue';
import Badge from '@/Components/UI/Badge.vue';

const props = defineProps({
    filters: Object,
    directions: Array,
});

const form = ref({
    direction: props.filters.direction || '',
    level: props.filters.level || '',
    price: props.filters.price || '',
    rating: props.filters.rating || '',
    sort: props.filters.sort || 'popular',
});

const updateFilters = debounce(() => {
    router.get(window.location.pathname, form.value, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}, 300);

watch(form, updateFilters, { deep: true });

const reset = () => {
    form.value = { direction: '', level: '', price: '', rating: '', sort: 'popular' };
};

// Mobile: filters collapse behind a toggle; always visible on lg+
const open = ref(false);
const activeCount = computed(() => ['direction', 'level', 'price', 'rating'].filter((k) => form.value[k] !== '').length);

const sortOptions = [
    { value: 'popular', label: 'Eng mashhur' },
    { value: 'newest', label: "Yangi qo'shilganlar" },
    { value: 'rating', label: 'Yuqori reyting' },
    { value: 'price_low', label: 'Arzonroq' },
    { value: 'price_high', label: 'Qimmatroq' },
];
const directionOptions = computed(() => [
    { value: '', label: 'Barchasi' },
    ...(props.directions || []).map((d) => ({ value: d.id, label: d.name })),
]);
const groups = [
    {
        key: 'level',
        legend: 'Daraja',
        options: [
            { value: '', label: 'Barchasi' },
            { value: 'beginner', label: "Boshlang'ich" },
            { value: 'intermediate', label: "O'rta" },
            { value: 'advanced', label: 'Yuqori' },
        ],
    },
    {
        key: 'price',
        legend: 'Narx',
        options: [
            { value: '', label: 'Barchasi' },
            { value: 'free', label: 'Bepul' },
            { value: 'paid', label: 'Pullik' },
        ],
    },
    {
        key: 'rating',
        legend: 'Reyting',
        options: [
            { value: '', label: 'Barchasi' },
            { value: '4.5', label: '4.5 va yuqori' },
            { value: '4.0', label: '4.0 va yuqori' },
            { value: '3.5', label: '3.5 va yuqori' },
        ],
    },
];
</script>

<template>
    <section class="rounded-lg border border-border bg-surface shadow-xs" aria-labelledby="course-filters-title">
        <!-- Header / mobile toggle -->
        <div class="flex items-center justify-between gap-3 px-4 py-3 lg:px-5 lg:py-4">
            <button type="button" class="focus-ring -m-1 flex items-center gap-2 rounded-sm p-1 lg:pointer-events-none"
                :aria-expanded="open" aria-controls="course-filters-body" @click="open = !open">
                <AdjustmentsHorizontalIcon class="h-5 w-5 text-fg-subtle" aria-hidden="true" />
                <h2 id="course-filters-title" class="text-sm font-semibold text-fg">Filtrlar</h2>
                <Badge v-if="activeCount" variant="brand" size="sm">{{ activeCount }}</Badge>
                <ChevronDownIcon :class="['h-5 w-5 text-fg-subtle transition-transform duration-150 lg:hidden', open ? 'rotate-180' : '']"
                    aria-hidden="true" />
            </button>
            <Button variant="link" size="sm" @click="reset">Tozalash</Button>
        </div>

        <div id="course-filters-body" :class="['space-y-5 border-t border-border px-4 py-4 lg:block lg:px-5 lg:py-5', open ? 'block' : 'hidden']">
            <Select v-model="form.sort" label="Saralash" :options="sortOptions" size="sm" />

            <Select v-if="directions" v-model="form.direction" label="Yo'nalish" :options="directionOptions" size="sm" />

            <fieldset v-for="group in groups" :key="group.key">
                <legend class="mb-2 text-13 font-medium text-fg">{{ group.legend }}</legend>
                <div class="space-y-1.5">
                    <Radio v-for="opt in group.options" :key="opt.value" v-model="form[group.key]" :value="opt.value"
                        :name="`filter-${group.key}`" :label="opt.label" />
                </div>
            </fieldset>
        </div>
    </section>
</template>
