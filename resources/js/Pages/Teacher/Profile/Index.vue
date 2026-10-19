<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { PencilSquareIcon, EnvelopeIcon, PhoneIcon, GlobeAltIcon, LinkIcon } from '@heroicons/vue/24/outline';
import { StarIcon } from '@heroicons/vue/20/solid';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Card from '@/Components/UI/Card.vue';
import Avatar from '@/Components/UI/Avatar.vue';
import Badge from '@/Components/UI/Badge.vue';
import Button from '@/Components/UI/Button.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import { formatNumber, fullName } from '@/Components/Teacher/format';

const props = defineProps({
    user: Object,
});

const tp = computed(() => props.user.teacher_profile || {});
const socials = computed(() => Object.entries(props.user.social_links || {}).filter(([, link]) => !!link));
const expertise = computed(() => tp.value.expertise || []);

const stats = computed(() => [
    { label: 'Kurslar', value: formatNumber(tp.value.total_courses || 0) },
    { label: 'O\'quvchilar', value: formatNumber(tp.value.total_students || 0) },
    { label: 'Tajriba', value: `${tp.value.experience_years || 0} yil` },
    { label: 'Reyting', value: Number(tp.value.avg_rating || 0).toFixed(1), star: true },
]);
</script>

<template>
    <Head title="Profil" />

    <TeacherLayout>
        <PageHeader title="Profil" description="O'quvchilar kurs sahifasida ko'radigan ma'lumotlaringiz.">
            <template #actions>
                <Button :href="route('teacher.profile.edit')" variant="secondary" :icon-left="PencilSquareIcon">Tahrirlash</Button>
            </template>
        </PageHeader>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <Card>
                    <div class="flex flex-col gap-5 sm:flex-row sm:items-center">
                        <Avatar :src="user.avatar_url" :name="fullName(user)" size="2xl" />
                        <div class="min-w-0 flex-1">
                            <h2 class="text-xl font-semibold text-fg">{{ fullName(user) }}</h2>
                            <p class="mt-0.5 text-sm text-fg-muted">{{ tp.headline || 'O\'qituvchi' }}</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <Badge v-if="tp.is_verified" variant="success" dot>Tasdiqlangan</Badge>
                            </div>
                        </div>
                    </div>

                    <dl class="mt-6 grid grid-cols-2 gap-px overflow-hidden rounded-md border border-border bg-border sm:grid-cols-4">
                        <div v-for="s in stats" :key="s.label" class="bg-surface px-4 py-3">
                            <dt class="text-13 text-fg-subtle">{{ s.label }}</dt>
                            <dd class="mt-0.5 flex items-center gap-1 text-lg font-semibold tabular-nums text-fg">
                                <StarIcon v-if="s.star" class="h-4 w-4 text-warning" aria-hidden="true" />{{ s.value }}
                            </dd>
                        </div>
                    </dl>
                </Card>

                <Card title="Bio">
                    <p v-if="user.bio" class="whitespace-pre-line text-sm leading-6 text-fg">{{ user.bio }}</p>
                    <EmptyState v-else size="sm" title="Bio kiritilmagan" description="O'zingiz, tajribangiz va dars uslubingiz haqida yozing.">
                        <Button size="sm" variant="secondary" :href="route('teacher.profile.edit')">Qo'shish</Button>
                    </EmptyState>
                </Card>

                <Card title="Mutaxassislik">
                    <div v-if="expertise.length" class="flex flex-wrap gap-2">
                        <Badge v-for="(skill, index) in expertise" :key="index" variant="neutral">{{ skill }}</Badge>
                    </div>
                    <p v-else class="text-sm text-fg-subtle">Kiritilmagan</p>
                </Card>
            </div>

            <aside class="space-y-6">
                <Card title="Aloqa">
                    <dl class="space-y-3 text-sm">
                        <div class="flex items-center gap-3">
                            <dt class="sr-only">Email</dt>
                            <EnvelopeIcon class="h-5 w-5 shrink-0 text-fg-subtle" aria-hidden="true" />
                            <dd class="min-w-0 truncate text-fg">{{ user.email || '—' }}</dd>
                        </div>
                        <div v-if="user.phone" class="flex items-center gap-3">
                            <dt class="sr-only">Telefon</dt>
                            <PhoneIcon class="h-5 w-5 shrink-0 text-fg-subtle" aria-hidden="true" />
                            <dd class="tabular-nums text-fg">{{ user.phone }}</dd>
                        </div>
                        <div v-if="user.website" class="flex items-center gap-3">
                            <dt class="sr-only">Vebsayt</dt>
                            <GlobeAltIcon class="h-5 w-5 shrink-0 text-fg-subtle" aria-hidden="true" />
                            <dd class="min-w-0 truncate">
                                <a :href="user.website" target="_blank" rel="noopener" class="focus-ring rounded-sm text-brand hover:underline">
                                    {{ user.website }}
                                </a>
                            </dd>
                        </div>
                    </dl>
                </Card>

                <Card title="Ijtimoiy tarmoqlar">
                    <ul v-if="socials.length" class="space-y-3 text-sm">
                        <li v-for="[platform, link] in socials" :key="platform" class="flex items-center gap-3">
                            <LinkIcon class="h-5 w-5 shrink-0 text-fg-subtle" aria-hidden="true" />
                            <span class="w-20 shrink-0 capitalize text-fg-muted">{{ platform }}</span>
                            <a :href="link" target="_blank" rel="noopener" class="focus-ring min-w-0 truncate rounded-sm text-brand hover:underline">{{ link }}</a>
                        </li>
                    </ul>
                    <p v-else class="text-sm text-fg-subtle">Kiritilmagan</p>
                </Card>
            </aside>
        </div>
    </TeacherLayout>
</template>
