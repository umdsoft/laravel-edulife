<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { ArrowRightIcon, BoltIcon, TrophyIcon } from '@heroicons/vue/24/outline';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { Alert, Button, Card, PageHeader } from '@/Components/UI';

defineProps({
    activeBattle: Object,
});

const isSearching = ref(false);

const findOpponent = () => {
    isSearching.value = true;
    router.post(route('student.battles.find'), {}, {
        onFinish: () => isSearching.value = false,
    });
};
</script>

<template>

    <Head title="Janglar maydoni" />

    <StudentLayout title="Janglar maydoni">
        <PageHeader title="Janglar maydoni" description="Boshqa o'quvchilar bilan bilimingizni sinang va XP yig'ing." />

        <div class="space-y-6">
            <!-- Active battle -->
            <Alert v-if="activeBattle" variant="info" title="Faol jangingiz mavjud" :icon="BoltIcon">
                Jangni davom ettirish uchun bosing.
                <template #actions>
                    <Button size="sm" :icon-right="ArrowRightIcon" :href="route('student.battles.show', activeBattle.id)">
                        Davom ettirish
                    </Button>
                </template>
            </Alert>

            <div class="grid gap-4 md:grid-cols-2">
                <!-- 1v1 Battle -->
                <Card class="flex flex-col">
                    <div class="flex h-full flex-col">
                        <span class="flex h-11 w-11 items-center justify-center rounded-md bg-brand-50 text-brand">
                            <BoltIcon class="h-6 w-6" aria-hidden="true" />
                        </span>
                        <h2 class="mt-4 text-lg font-semibold text-fg">1 ga 1 jang</h2>
                        <p class="mt-1 flex-1 text-sm text-fg-muted">Tasodifiy raqib bilan 5 ta savol bo'yicha bellashuv.</p>
                        <Button class="mt-6" block :loading="isSearching" :icon-left="BoltIcon" @click="findOpponent">
                            {{ isSearching ? 'Raqib qidirilmoqda…' : 'Jangni boshlash' }}
                        </Button>
                    </div>
                </Card>

                <!-- Tournaments -->
                <Card class="flex flex-col">
                    <div class="flex h-full flex-col">
                        <span class="flex h-11 w-11 items-center justify-center rounded-md bg-warning-50 text-warning">
                            <TrophyIcon class="h-6 w-6" aria-hidden="true" />
                        </span>
                        <h2 class="mt-4 text-lg font-semibold text-fg">Turnirlar</h2>
                        <p class="mt-1 flex-1 text-sm text-fg-muted">Katta sovrinlar uchun turnirlarda qatnashing.</p>
                        <Button class="mt-6" variant="secondary" block :icon-right="ArrowRightIcon"
                            :href="route('student.tournaments.index')">
                            Turnirlarni ko'rish
                        </Button>
                    </div>
                </Card>
            </div>
        </div>
    </StudentLayout>
</template>
