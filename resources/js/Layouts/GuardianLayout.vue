<script setup>
/**
 * GuardianLayout — app shell for the parent (ota-ona) panel.
 * Usage: <GuardianLayout title="…"> …page… </GuardianLayout>
 * Nav items only render when their Ziggy route exists.
 */
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { HomeIcon, DocumentChartBarIcon } from '@heroicons/vue/24/outline';
import { HomeIcon as HomeSolid, DocumentChartBarIcon as ReportSolid } from '@heroicons/vue/24/solid';
import AppShell from '@/Components/Layout/AppShell.vue';
import UserMenu from '@/Components/Layout/UserMenu.vue';
import { resolveNavItems, resolveNavSections, routePath } from '@/Utils/nav';
import { useFlashToasts } from '@/Composables/useFlashToasts';
// TW (Telegram WebView-first): compact chrome inside the Mini App (prop `webview` only for Mini App sessions)
import WebviewShell from '@/Components/Layout/WebviewShell.vue';

defineProps({
    title: { type: String, default: null },
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const webview = computed(() => page.props.webview === 'telegram');
const GUARDIAN_PAGES = /^\/guardian(\/|$)/;

useFlashToasts();

const sections = computed(() =>
    resolveNavSections([
        {
            items: [
                { label: 'Farzandlarim', route: 'guardian.dashboard', icon: HomeIcon, match: ['/guardian/children'] },
                { label: 'Hisobotlar', route: 'guardian.reports.index', icon: DocumentChartBarIcon },
            ],
        },
    ]),
);

const tabs = computed(() =>
    resolveNavItems([
        { label: 'Farzandlarim', short: 'Farzandlar', route: 'guardian.dashboard', icon: HomeIcon, activeIcon: HomeSolid, match: ['/guardian/children'] },
        { label: 'Hisobotlar', route: 'guardian.reports.index', icon: DocumentChartBarIcon, activeIcon: ReportSolid },
    ]),
);

const homeHref = computed(() => routePath('guardian.dashboard') || '/guardian/dashboard');
</script>

<template>
    <WebviewShell v-if="webview" :title="title" :tabs="tabs" :supported="GUARDIAN_PAGES" app-title="Farzandlarim">
        <slot />
    </WebviewShell>
    <AppShell v-else :sections="sections" :tabs="tabs" role-tag="Ota-ona" :home-href="homeHref" :title="title">
        <template #user>
            <UserMenu :user="user" :items="[]" role-label="Ota-ona" />
        </template>

        <slot />
    </AppShell>
</template>
