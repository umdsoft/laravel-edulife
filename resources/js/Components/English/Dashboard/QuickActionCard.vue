<script setup>
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    title: String,
    description: String,
    icon: Object,
    href: String,
    gradient: { type: String, default: 'from-blue-500 to-blue-600' },
    badge: [String, Number],
})
</script>

<template>
    <Link :href="href"
        class="relative overflow-hidden rounded-2xl p-5 text-white transition-transform hover:scale-105 active:scale-95"
        :class="`bg-gradient-to-br ${gradient}`">
        <!-- Background decoration -->
        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 rounded-full bg-white/10"></div>
        <div class="absolute bottom-0 left-0 -mb-6 -ml-6 w-20 h-20 rounded-full bg-white/10"></div>
        
        <!-- Content -->
        <div class="relative">
            <div class="flex items-start justify-between">
                <component :is="icon" class="w-8 h-8 opacity-90" />
                <span v-if="badge" class="px-2 py-0.5 bg-white/20 rounded-full text-xs font-medium">
                    {{ badge }}
                </span>
            </div>
            <h3 class="mt-3 font-semibold">{{ title }}</h3>
            <p class="mt-1 text-sm opacity-90">{{ description }}</p>
        </div>
    </Link>
</template>
