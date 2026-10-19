<script setup>
/**
 * PeopleList — a paginated list of students (followers / following) with a
 * follow toggle. Rows link to the student's profile.
 *   <PeopleList :people="followers" empty-title="…" />
 */
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { UserMinusIcon, UserPlusIcon, UsersIcon } from '@heroicons/vue/24/outline';
import { Avatar, Badge, Button, Card, EmptyState, Pagination } from '@/Components/UI';

const props = defineProps({
    people: { type: Object, required: true }, // paginator of users (+ studentProfile, is_following)
    emptyTitle: { type: String, default: "Hech kim yo'q" },
    emptyDescription: { type: String, default: null },
    // every row is someone the student follows (Following page)
    allFollowed: Boolean,
});

const busy = ref(null);
const isFollowed = (u) => props.allFollowed || !!u.is_following;
const name = (u) => u.full_name || [u.first_name, u.last_name].filter(Boolean).join(' ');

const toggle = (u) => {
    const opts = { preserveScroll: true, onStart: () => (busy.value = u.id), onFinish: () => (busy.value = null) };
    if (isFollowed(u)) router.delete(route('student.friends.unfollow', u.id), opts);
    else router.post(route('student.friends.follow', u.id), {}, opts);
};
</script>

<template>
    <Card padding="none">
        <EmptyState v-if="!people.data?.length" :icon="UsersIcon" size="sm" :title="emptyTitle" :description="emptyDescription" />
        <ul v-else class="divide-y divide-border">
            <li v-for="u in people.data" :key="u.id" class="flex items-center gap-3 px-5 py-3.5 sm:px-6">
                <Link :href="route('student.users.show', u.id)" class="focus-ring flex min-w-0 flex-1 items-center gap-3 rounded-sm">
                    <Avatar :src="u.avatar_url || u.avatar" :name="name(u)" size="md" />
                    <div class="min-w-0">
                        <p class="truncate font-medium text-fg hover:text-brand">{{ name(u) }}</p>
                        <p class="text-13 tabular-nums text-fg-subtle">{{ u.student_profile?.level ?? 1 }}-daraja · {{ u.student_profile?.xp ?? 0 }} XP</p>
                    </div>
                </Link>
                <Badge v-if="!allFollowed && u.is_following" variant="neutral" size="sm" class="hidden sm:inline-flex">O'zaro</Badge>
                <Button size="sm" :variant="isFollowed(u) ? 'secondary' : 'primary'" :icon-left="isFollowed(u) ? UserMinusIcon : UserPlusIcon"
                    :loading="busy === u.id" @click="toggle(u)">
                    {{ isFollowed(u) ? "Kuzatmaslik" : 'Kuzatish' }}
                </Button>
            </li>
        </ul>
        <Pagination :data="people" />
    </Card>
</template>
