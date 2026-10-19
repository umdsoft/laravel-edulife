<script setup>
import { computed } from 'vue';
import { BookmarkIcon } from '@heroicons/vue/20/solid';
import Avatar from '@/Components/UI/Avatar.vue';
import Badge from '@/Components/UI/Badge.vue';
import RichContent from './RichContent.vue';
import { formatDate } from './format.js';

const props = defineProps({
    announcement: Object,
});

const teacherName = computed(() => {
    const t = props.announcement.teacher || {};
    return t.full_name || [t.first_name, t.last_name].filter(Boolean).join(' ');
});
</script>

<template>
    <article :class="[
        'rounded-lg border bg-surface p-5 shadow-xs sm:p-6',
        announcement.is_pinned ? 'border-brand-200' : 'border-border',
    ]">
        <header class="mb-4 flex items-start gap-3">
            <Avatar :src="announcement.teacher?.avatar_url" :name="teacherName" size="md" />
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h3 class="text-base font-semibold text-fg">{{ announcement.title }}</h3>
                    <Badge v-if="announcement.is_pinned" variant="brand" size="sm" :icon="BookmarkIcon">Muhim</Badge>
                </div>
                <p class="mt-0.5 text-13 text-fg-subtle">
                    <span v-if="teacherName">{{ teacherName }} · </span>
                    <time :datetime="announcement.published_at" class="tabular-nums">{{ formatDate(announcement.published_at) }}</time>
                </p>
            </div>
        </header>

        <RichContent :html="announcement.content" size="sm" />
    </article>
</template>
