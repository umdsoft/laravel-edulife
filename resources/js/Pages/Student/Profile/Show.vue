<template>
    <StudentLayout title="Profil">
        <Head :title="fullName || 'Profil'" />

        <PageHeader :title="isOwnProfile ? 'Profil' : fullName" :description="isOwnProfile ? 'Shaxsiy ma\'lumotlaringiz va natijalaringiz' : null">
            <template #actions>
                <template v-if="isOwnProfile">
                    <Button v-if="route().has('student.settings.account')" variant="secondary" :icon-left="Cog6ToothIcon"
                        :href="route('student.settings.account')">Sozlamalar</Button>
                    <Button :icon-left="PencilSquareIcon" :href="route('student.profile.edit')">Tahrirlash</Button>
                </template>
                <Button v-else :variant="isFollowing ? 'secondary' : 'primary'" :loading="following"
                    :icon-left="isFollowing ? UserMinusIcon : UserPlusIcon" @click="toggleFollow">
                    {{ isFollowing ? 'Kuzatishni bekor qilish' : 'Kuzatish' }}
                </Button>
            </template>
        </PageHeader>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="min-w-0 space-y-6 lg:col-span-2">
                <!-- Identity -->
                <Card>
                    <div class="flex flex-col items-center gap-4 text-center sm:flex-row sm:items-start sm:text-left">
                        <Avatar :src="user.avatar_url || user.avatar" :name="fullName" size="xl" class="shrink-0" />
                        <div class="min-w-0 flex-1">
                            <h2 class="text-xl font-semibold text-fg">{{ fullName }}</h2>
                            <p v-if="user.username" class="text-sm text-fg-subtle">@{{ user.username }}</p>
                            <div class="mt-2 flex flex-wrap items-center justify-center gap-2 sm:justify-start">
                                <Badge variant="brand" :icon="SparklesIcon">
                                    <span class="tabular-nums">Daraja {{ level }}</span>
                                </Badge>
                                <Badge v-if="streak" variant="warning" :icon="FireIcon">
                                    <span class="tabular-nums">{{ streak }} kunlik seriya</span>
                                </Badge>
                                <span v-if="equippedBadge" class="inline-flex items-center gap-1.5 rounded-full bg-surface-2 py-0.5 pl-0.5 pr-2.5 text-13 font-medium text-fg ring-1 ring-inset ring-border">
                                    <img v-if="equippedBadge.image_url" :src="equippedBadge.image_url" alt="" class="h-5 w-5" aria-hidden="true" />
                                    {{ equippedBadge.name }}
                                </span>
                            </div>
                            <p v-if="user.bio" class="mt-3 whitespace-pre-line text-sm text-fg-muted">{{ user.bio }}</p>
                            <div v-if="user.location || safeWebsite" class="mt-3 flex flex-wrap items-center justify-center gap-x-4 gap-y-1 text-13 text-fg-muted sm:justify-start">
                                <span v-if="user.location" class="flex items-center gap-1">
                                    <MapPinIcon class="h-4 w-4 text-fg-subtle" aria-hidden="true" />{{ user.location }}
                                </span>
                                <a v-if="safeWebsite" :href="safeWebsite" target="_blank" rel="noopener noreferrer"
                                    class="focus-ring flex items-center gap-1 rounded-sm text-brand hover:underline">
                                    <LinkIcon class="h-4 w-4" aria-hidden="true" />{{ user.website }}
                                </a>
                            </div>
                        </div>
                    </div>
                </Card>

                <!-- Stats -->
                <div v-if="stats || profile" class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    <StatCard label="Daraja" :value="level" :icon="StarIcon" tone="brand" />
                    <StatCard label="XP" :value="xp" :icon="SparklesIcon" tone="brand" />
                    <StatCard label="Kuzatuvchilar" :value="stats?.followers ?? 0" :icon="UsersIcon" tone="info" />
                    <StatCard label="Kuzatilmoqda" :value="stats?.following ?? 0" :icon="UserPlusIcon" tone="neutral" />
                </div>

                <!-- Activities -->
                <Card title="Faoliyat" padding="none">
                    <EmptyState v-if="!activities || activities.length === 0" :icon="ClockIcon" size="sm"
                        title="Hozircha faoliyat yo'q" description="So'nggi harakatlar shu yerda ko'rinadi." />
                    <ul v-else class="divide-y divide-border">
                        <li v-for="activity in activities" :key="activity.id" class="flex items-start gap-3 px-5 py-4 sm:px-6">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-surface-2 text-base text-fg-muted">
                                <span v-if="activity.icon" aria-hidden="true">{{ activity.icon }}</span>
                                <BoltIcon v-else class="h-5 w-5" aria-hidden="true" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-fg">{{ activity.title }}</p>
                                <p v-if="activity.description" class="text-13 text-fg-muted">{{ activity.description }}</p>
                                <p class="mt-1 text-xs tabular-nums text-fg-subtle">{{ formatDate(activity.occurred_at) }}</p>
                            </div>
                        </li>
                    </ul>
                </Card>
            </div>

            <aside class="space-y-6">
                <Card title="Ma'lumot">
                    <dl class="-my-2.5 divide-y divide-border text-sm">
                        <div v-if="stats?.courses_enrolled !== undefined" class="flex justify-between gap-3 py-2.5">
                            <dt class="text-fg-muted">Kurslar</dt>
                            <dd class="font-medium tabular-nums">{{ stats.courses_enrolled }}</dd>
                        </div>
                        <div v-if="stats?.courses_completed !== undefined" class="flex justify-between gap-3 py-2.5">
                            <dt class="text-fg-muted">Tugatilgan kurslar</dt>
                            <dd class="font-medium tabular-nums">{{ stats.courses_completed }}</dd>
                        </div>
                        <div v-if="stats?.lessons_completed !== undefined" class="flex justify-between gap-3 py-2.5">
                            <dt class="text-fg-muted">Tugatilgan darslar</dt>
                            <dd class="font-medium tabular-nums">{{ stats.lessons_completed }}</dd>
                        </div>
                        <div v-if="stats?.certificates !== undefined" class="flex justify-between gap-3 py-2.5">
                            <dt class="text-fg-muted">Sertifikatlar</dt>
                            <dd class="font-medium tabular-nums">{{ stats.certificates }}</dd>
                        </div>
                        <div class="flex justify-between gap-3 py-2.5">
                            <dt class="text-fg-muted">Seriya</dt>
                            <dd class="font-medium tabular-nums">{{ streak }} kun</dd>
                        </div>
                        <div v-if="isOwnProfile && profile?.coins !== undefined" class="flex justify-between gap-3 py-2.5">
                            <dt class="text-fg-muted">Coinlar</dt>
                            <dd class="flex items-center gap-1 font-medium tabular-nums">
                                <CircleStackIcon class="h-4 w-4 text-warning" aria-hidden="true" />{{ fmt(profile.coins) }}
                            </dd>
                        </div>
                        <div v-if="user.created_at" class="flex justify-between gap-3 py-2.5">
                            <dt class="text-fg-muted">A'zo bo'lgan</dt>
                            <dd class="font-medium tabular-nums">{{ formatDate(user.created_at) }}</dd>
                        </div>
                    </dl>
                </Card>
            </aside>
        </div>
    </StudentLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    BoltIcon, ClockIcon, Cog6ToothIcon, FireIcon, LinkIcon, MapPinIcon, PencilSquareIcon, SparklesIcon, StarIcon,
    UserMinusIcon, UserPlusIcon, UsersIcon,
} from '@heroicons/vue/24/outline';
import { CircleStackIcon } from '@heroicons/vue/20/solid';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Avatar, Badge, Button, Card, EmptyState, PageHeader, StatCard } from '@/Components/UI';

const props = defineProps({
    user: Object,
    profile: Object,
    stats: Object,
    activities: Array,
    isOwnProfile: Boolean,
    isFollowing: Boolean,
    // the shop badge the student wears: { name, image_url }
    equippedBadge: { type: Object, default: null },
});

const following = ref(false);

const toggleFollow = () => {
    following.value = true;
    const opts = { preserveScroll: true, onFinish: () => { following.value = false; } };
    if (props.isFollowing) {
        router.delete(route('student.friends.unfollow', props.user.id), opts);
    } else {
        router.post(route('student.friends.follow', props.user.id), {}, opts);
    }
};

const fullName = computed(() => props.user?.full_name || [props.user?.first_name, props.user?.last_name].filter(Boolean).join(' '));
const level = computed(() => props.stats?.level || props.profile?.level || 1);
const xp = computed(() => props.stats?.xp ?? props.profile?.xp ?? 0);
const streak = computed(() => props.stats?.streak_days ?? props.profile?.streak_days ?? 0);
const safeWebsite = computed(() => {
    const w = props.user?.website;
    if (!w) return null;
    return /^https?:\/\//i.test(w) ? w : `https://${w}`;
});

const fmt = (n) => new Intl.NumberFormat('ru-RU').format(Number(n) || 0).replace(/ /g, ' ');
const pad = (n) => String(n).padStart(2, '0');
const formatDate = (date) => {
    if (!date) return '—';
    const d = new Date(date);
    if (Number.isNaN(d.getTime())) return '—';
    return `${pad(d.getDate())}.${pad(d.getMonth() + 1)}.${d.getFullYear()}`;
};
</script>
