<script setup>
/**
 * WebviewShell — compact chrome of the student panel inside the Telegram Mini App
 * (StudentLayout switches to it when the Inertia prop `webview` is "telegram").
 *
 * No sidebar, top header or footer: a slim title row (Telegram draws its own header with the
 * Back button), the page, and four bottom tabs — Imtihon / Battle / Natijalar / Profil.
 * Safe-area aware (--tg-safe-* set by useTelegram from safeAreaInset + contentSafeAreaInset).
 * Sections that are not built for the Mini App (courses, lab, shop…) get a "Saytda ochish" note.
 * The tab bar hides while the on-screen keyboard is up, so it never covers an input.
 */
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowTopRightOnSquareIcon, BoltIcon, ChartBarIcon, ClipboardDocumentCheckIcon, UserCircleIcon } from '@heroicons/vue/24/outline';
import {
    BoltIcon as BoltSolid, ChartBarIcon as ChartBarSolid, ClipboardDocumentCheckIcon as ClipboardSolid, UserCircleIcon as UserCircleSolid,
} from '@heroicons/vue/24/solid';
import ToastViewport from '@/Components/UI/ToastViewport.vue';
import { isItemActive, resolveNavItems } from '@/Utils/nav';
import { openLink } from '@/Composables/useTelegram';

const props = defineProps({
    title: { type: String, default: null },
});

const page = usePage();

const tabs = computed(() => resolveNavItems([
    { label: 'Imtihon', route: ['student.exam.index'], icon: ClipboardDocumentCheckIcon, activeIcon: ClipboardSolid, exact: true, match: ['/student/exam/attempts', '/student/exam/onboarding', '/student/exam/leaderboard'] },
    { label: 'Battle', route: 'student.battles.index', icon: BoltIcon, activeIcon: BoltSolid },
    { label: 'Natijalar', route: 'student.exam.progress', icon: ChartBarIcon, activeIcon: ChartBarSolid },
    { label: 'Profil', route: 'student.profile.show', icon: UserCircleIcon, activeIcon: UserCircleSolid },
]));

const path = computed(() => String(page.url || '').split('?')[0]);
// the pages made for the Mini App; everything else is better on the website
const supported = computed(() => /^\/student\/(exam|battles|profile)(\/|$)/.test(path.value));

const siteUrl = computed(() => {
    const base = page.props.telegram?.site_url || (typeof window !== 'undefined' ? window.location.origin : '');
    return base + (page.url || '/');
});
const openOnSite = () => {
    if (!openLink(siteUrl.value)) window.open(siteUrl.value, '_blank', 'noopener');
};

// on-screen keyboard: the focused field must stay visible
const typing = ref(false);
const isField = (el) => !!el && (/^(TEXTAREA|SELECT)$/.test(el.tagName) || (el.tagName === 'INPUT' && !['checkbox', 'radio', 'button', 'submit'].includes(el.type)));
const onFocusIn = (e) => { typing.value = isField(e.target); };
const onFocusOut = () => { setTimeout(() => { typing.value = isField(document.activeElement); }, 50); };
onMounted(() => {
    document.addEventListener('focusin', onFocusIn);
    document.addEventListener('focusout', onFocusOut);
});
onBeforeUnmount(() => {
    document.removeEventListener('focusin', onFocusIn);
    document.removeEventListener('focusout', onFocusOut);
});
</script>

<template>
    <div class="flex min-h-[var(--tg-app-height,100vh)] flex-col bg-bg text-fg"
        :style="{ paddingLeft: 'var(--tg-safe-left, 0px)', paddingRight: 'var(--tg-safe-right, 0px)' }" data-webview="telegram">
        <header class="sticky top-0 z-topbar border-b border-border bg-surface/95 backdrop-blur"
            :style="{ paddingTop: 'var(--tg-safe-top, 0px)' }">
            <div class="flex h-12 items-center gap-2 px-4">
                <p class="min-w-0 flex-1 truncate text-[15px] font-semibold text-fg">{{ props.title || 'Shanba imtihoni' }}</p>
                <button type="button"
                    class="focus-ring inline-flex h-9 shrink-0 items-center gap-1.5 rounded-md px-2 text-13 font-medium text-fg-muted hover:bg-surface-2 hover:text-fg"
                    @click="openOnSite">
                    <ArrowTopRightOnSquareIcon class="h-4 w-4" aria-hidden="true" />
                    <span>Saytda ochish</span>
                </button>
            </div>
        </header>

        <main id="main-content" tabindex="-1" class="min-w-0 flex-1 focus:outline-none"
            :style="{ paddingBottom: typing ? '1rem' : 'calc(4.5rem + var(--tg-safe-bottom, 0px))' }">
            <div class="mx-auto w-full max-w-content px-4 py-4">
                <div v-if="!supported" class="mb-4 flex items-start gap-3 rounded-lg border border-border bg-surface p-3 text-13 text-fg-muted" role="note">
                    <p class="min-w-0 flex-1">Bu bo'lim saytda to'liq ishlaydi. Telegram ichida imtihon va battle'dan foydalaning.</p>
                    <button type="button" class="focus-ring shrink-0 rounded font-medium text-brand hover:underline" @click="openOnSite">Saytda ochish</button>
                </div>
                <slot />
            </div>
        </main>

        <nav v-show="!typing" aria-label="Tezkor navigatsiya"
            class="fixed inset-x-0 bottom-0 z-topbar border-t border-border bg-surface/95 backdrop-blur"
            :style="{ paddingBottom: 'var(--tg-safe-bottom, 0px)' }">
            <ul class="grid h-16" :style="{ gridTemplateColumns: `repeat(${tabs.length || 1}, minmax(0, 1fr))` }">
                <li v-for="tab in tabs" :key="tab.href" class="h-full">
                    <Link :href="tab.href" :aria-current="isItemActive(page.url, tab) ? 'page' : undefined" :class="[
                        'focus-ring flex h-full flex-col items-center justify-center gap-1 rounded-md text-[11px] font-medium transition-colors',
                        isItemActive(page.url, tab) ? 'text-brand' : 'text-fg-subtle hover:text-fg',
                    ]">
                        <component :is="isItemActive(page.url, tab) ? tab.activeIcon : tab.icon" class="h-6 w-6" aria-hidden="true" />
                        <span class="max-w-full truncate px-1">{{ tab.label }}</span>
                    </Link>
                </li>
            </ul>
        </nav>

        <ToastViewport />
    </div>
</template>
