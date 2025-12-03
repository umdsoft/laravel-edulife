<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { debounce } from 'lodash';

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
</script>

<template>
    <div class="bg-white rounded-2xl border border-gray-100 p-6 space-y-6">
        <div class="flex items-center justify-between">
            <h3 class="font-bold text-gray-900">Filtrlar</h3>
            <button @click="form = { direction: '', level: '', price: '', rating: '', sort: 'popular' }"
                class="text-xs text-purple-600 hover:text-purple-700 font-medium">
                Tozalash
            </button>
        </div>

        <!-- Sort -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Saralash</label>
            <select v-model="form.sort"
                class="w-full rounded-xl border-gray-200 text-sm focus:ring-purple-500 focus:border-purple-500">
                <option value="popular">Eng mashhur</option>
                <option value="newest">Yangi qo'shilganlar</option>
                <option value="rating">Yuqori reyting</option>
                <option value="price_low">Arzonroq</option>
                <option value="price_high">Qimmatroq</option>
            </select>
        </div>

        <!-- Direction -->
        <div v-if="directions">
            <label class="block text-sm font-medium text-gray-700 mb-2">Yo'nalish</label>
            <select v-model="form.direction"
                class="w-full rounded-xl border-gray-200 text-sm focus:ring-purple-500 focus:border-purple-500">
                <option value="">Barchasi</option>
                <option v-for="direction in directions" :key="direction.id" :value="direction.id">
                    {{ direction.name }}
                </option>
            </select>
        </div>

        <!-- Level -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Daraja</label>
            <div class="space-y-2">
                <label class="flex items-center gap-2">
                    <input type="radio" v-model="form.level" value=""
                        class="text-purple-600 focus:ring-purple-500 border-gray-300">
                    <span class="text-sm text-gray-600">Barchasi</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="radio" v-model="form.level" value="beginner"
                        class="text-purple-600 focus:ring-purple-500 border-gray-300">
                    <span class="text-sm text-gray-600">Boshlang'ich</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="radio" v-model="form.level" value="intermediate"
                        class="text-purple-600 focus:ring-purple-500 border-gray-300">
                    <span class="text-sm text-gray-600">O'rta</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="radio" v-model="form.level" value="advanced"
                        class="text-purple-600 focus:ring-purple-500 border-gray-300">
                    <span class="text-sm text-gray-600">Yuqori</span>
                </label>
            </div>
        </div>

        <!-- Price -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Narx</label>
            <div class="space-y-2">
                <label class="flex items-center gap-2">
                    <input type="radio" v-model="form.price" value=""
                        class="text-purple-600 focus:ring-purple-500 border-gray-300">
                    <span class="text-sm text-gray-600">Barchasi</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="radio" v-model="form.price" value="free"
                        class="text-purple-600 focus:ring-purple-500 border-gray-300">
                    <span class="text-sm text-gray-600">Bepul</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="radio" v-model="form.price" value="paid"
                        class="text-purple-600 focus:ring-purple-500 border-gray-300">
                    <span class="text-sm text-gray-600">Pullik</span>
                </label>
            </div>
        </div>

        <!-- Rating -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Reyting</label>
            <div class="space-y-2">
                <label class="flex items-center gap-2">
                    <input type="radio" v-model="form.rating" value=""
                        class="text-purple-600 focus:ring-purple-500 border-gray-300">
                    <span class="text-sm text-gray-600">Barchasi</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="radio" v-model="form.rating" value="4.5"
                        class="text-purple-600 focus:ring-purple-500 border-gray-300">
                    <span class="text-sm text-gray-600">4.5 va yuqori</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="radio" v-model="form.rating" value="4.0"
                        class="text-purple-600 focus:ring-purple-500 border-gray-300">
                    <span class="text-sm text-gray-600">4.0 va yuqori</span>
                </label>
                <label class="flex items-center gap-2">
                    <input type="radio" v-model="form.rating" value="3.5"
                        class="text-purple-600 focus:ring-purple-500 border-gray-300">
                    <span class="text-sm text-gray-600">3.5 va yuqori</span>
                </label>
            </div>
        </div>
    </div>
</template>
