<script setup>
/**
 * SidebarNav — renders resolved nav sections (see Utils/nav.js).
 * section: { title?, items: [{ label, href, icon, badge?, exact?, match? }] }
 */
import { Link, usePage } from '@inertiajs/vue3';
import { isItemActive } from '@/Utils/nav';

defineProps({
    sections: { type: Array, required: true },
    rail: Boolean,
});
const emit = defineEmits(['navigate']);
const page = usePage();
</script>

<template>
    <nav aria-label="Asosiy navigatsiya" class="flex flex-col gap-5">
        <div v-for="(section, si) in sections" :key="section.title || si">
            <p v-if="section.title && !rail"
                class="mb-1.5 px-3 text-[11px] font-semibold uppercase tracking-wider text-fg-subtle">
                {{ section.title }}
            </p>
            <div v-else-if="si > 0 && rail" class="mx-auto mb-3 h-px w-6 bg-border" aria-hidden="true" />

            <ul class="flex flex-col gap-0.5">
                <li v-for="item in section.items" :key="item.href">
                    <Link :href="item.href" :title="rail ? item.label : undefined"
                        :aria-current="isItemActive(page.url, item) ? 'page' : undefined" :class="[
                            'focus-ring group relative flex items-center rounded-md text-sm font-medium transition-colors duration-150',
                            rail ? 'mx-auto h-10 w-10 justify-center' : 'h-9 gap-3 px-3',
                            isItemActive(page.url, item)
                                ? 'bg-brand-50 text-brand'
                                : 'text-fg-muted hover:bg-surface-2 hover:text-fg',
                        ]" @click="emit('navigate')">
                        <component :is="item.icon" v-if="item.icon" :class="[
                            'h-5 w-5 shrink-0',
                            isItemActive(page.url, item) ? 'text-brand' : 'text-fg-subtle group-hover:text-fg-muted',
                        ]" aria-hidden="true" />
                        <span :class="rail ? 'sr-only' : 'min-w-0 flex-1 truncate'">{{ item.label }}</span>
                        <span v-if="item.badge && !rail"
                            class="ml-auto rounded-full bg-brand-600 px-1.5 py-px text-[11px] font-semibold text-white tabular-nums">
                            {{ item.badge }}
                        </span>
                        <span v-else-if="item.badge && rail" class="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-brand-600"
                            aria-hidden="true" />
                    </Link>
                </li>
            </ul>
        </div>
    </nav>
</template>
