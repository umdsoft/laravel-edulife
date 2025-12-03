<script setup>
import { Head, router } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import BattleArena from '@/Components/Gamification/BattleArena.vue';

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

    <StudentLayout>
        <div class="max-w-5xl mx-auto py-8">
            <BattleArena :battle="battle" :current-user="$page.props.auth.user" @answer="handleAnswer" />
        </div>
    </StudentLayout>
</template>
