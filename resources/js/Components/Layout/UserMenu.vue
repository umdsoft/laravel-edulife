<script setup>
/**
 * UserMenu — avatar button + dropdown (profile links, theme toggle, logout).
 * items: resolved nav items [{ label, href, icon }]. Extra content via the #extra slot (header area).
 */
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { MoonIcon, SunIcon, ArrowRightStartOnRectangleIcon } from '@heroicons/vue/24/outline';
import Avatar from '@/Components/UI/Avatar.vue';
import Dropdown from '@/Components/UI/Dropdown.vue';
import DropdownItem from '@/Components/UI/DropdownItem.vue';
import { useTheme } from '@/Composables/useTheme';
import { routePath } from '@/Utils/nav';

const props = defineProps({
    user: { type: Object, default: null },
    roleLabel: { type: String, default: null },
    items: { type: Array, default: () => [] },
    showName: Boolean,
});

const { isDark, toggle } = useTheme();
const fullName = computed(() => props.user?.full_name || [props.user?.first_name, props.user?.last_name].filter(Boolean).join(' ') || 'Foydalanuvchi');
const avatarSrc = computed(() => props.user?.avatar_url || props.user?.avatar || null);

const logout = () => router.post(routePath('logout') || '/logout');
</script>

<template>
    <Dropdown align="right" width="w-64">
        <template #trigger>
            <button type="button"
                class="focus-ring flex items-center gap-2 rounded-full p-0.5 pr-0.5 transition-colors hover:bg-surface-2 lg:rounded-md lg:pr-2"
                :aria-label="`Profil menyusi: ${fullName}`">
                <Avatar :src="avatarSrc" :name="fullName" size="sm" />
                <span v-if="showName" class="hidden max-w-[10rem] truncate text-sm font-medium text-fg lg:inline">{{ fullName }}</span>
            </button>
        </template>

        <template #header>
            <div class="flex items-center gap-3">
                <Avatar :src="avatarSrc" :name="fullName" size="md" />
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-fg">{{ fullName }}</p>
                    <p class="truncate text-xs text-fg-subtle">{{ user?.email || user?.phone || roleLabel }}</p>
                </div>
            </div>
            <slot name="extra" />
        </template>

        <DropdownItem v-for="item in items" :key="item.href" :href="item.href" :icon="item.icon">
            {{ item.label }}
        </DropdownItem>
        <DropdownItem v-if="items.length" divider />
        <DropdownItem :icon="isDark ? SunIcon : MoonIcon" @click="toggle">
            {{ isDark ? 'Yorug\' rejim' : 'Tungi rejim' }}
        </DropdownItem>
        <DropdownItem divider />
        <DropdownItem danger :icon="ArrowRightStartOnRectangleIcon" @click="logout">Chiqish</DropdownItem>
    </Dropdown>
</template>
