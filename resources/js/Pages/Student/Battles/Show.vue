<script setup>
import { Head, router } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import BattleArena from '@/Components/Gamification/BattleArena.vue';
import { Card, PageHeader } from '@/Components/UI';

const props = defineProps({
    battle: Object,
    currentPlayerId: Number,
});

const handleAnswer = (data) => {
    router.post(route('student.battles.answer', props.battle.id), data, {
        preserveScroll: true,
        preserveState: false, // Refresh state to get updated scores/questions
    });
};
</script>

<template>

    <Head title="Jang" />

    <StudentLayout title="Jang">
        <PageHeader title="Jang" description="Har bir savolga 15 soniya. Tez va aniq javob bering."
            :back="route('student.battles.index')" back-label="Janglar" />

        <Card padding="lg">
            <BattleArena :battle="battle" :current-user="$page.props.auth.user" @answer="handleAnswer" />
        </Card>
    </StudentLayout>
</template>
