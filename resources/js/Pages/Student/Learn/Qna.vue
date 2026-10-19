<script setup>
import { ref, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ChatBubbleLeftRightIcon, PlusIcon } from '@heroicons/vue/24/outline';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { PageHeader, Button, EmptyState, Pagination, Modal, Select, Textarea } from '@/Components/UI';
import LearnFrame from '@/Components/Student/LearnFrame.vue';
import QnaQuestion from '@/Components/Student/QnaQuestion.vue';

const props = defineProps({
    course: Object,
    questions: Object,
});

const showAskModal = ref(false);
const form = useForm({
    content: '',
    lesson_id: null,
});

const submitQuestion = () => {
    form.post(route('student.learn.qna.store', props.course.id), {
        preserveScroll: true,
        onSuccess: () => {
            showAskModal.value = false;
            form.reset();
        },
    });
};
</script>

<template>

    <Head :title="`${course.title} - Savol-javoblar`" />

    <StudentLayout title="Savol-javob">
        <LearnFrame :course="course">
            <template #header>
                <PageHeader title="Savol-javoblar" :description="course.title"
                    :back="route('student.learn.course', course.slug)" back-label="Kursga qaytish">
                    <template #actions>
                        <Button :icon-left="PlusIcon" @click="showAskModal = true">Savol berish</Button>
                    </template>
                </PageHeader>
            </template>

            <div v-if="questions.data.length > 0" class="space-y-3">
                <QnaQuestion v-for="question in questions.data" :key="question.id" :question="question" />
            </div>

            <EmptyState v-else bordered size="lg" :icon="ChatBubbleLeftRightIcon" title="Savollar yo'q"
                description="Tushunmagan joyingiz bormi? Birinchi bo'lib savol bering — ustoz javob beradi.">
                <Button :icon-left="PlusIcon" @click="showAskModal = true">Savol berish</Button>
            </EmptyState>

            <Pagination :data="questions" />
        </LearnFrame>

        <!-- Ask a question -->
        <Modal :show="showAskModal" size="md" title="Savol berish" description="Savolingiz kurs ishtirokchilari va ustozga ko'rinadi."
            @close="showAskModal = false">
            <form id="ask-question-form" class="space-y-4 pb-2" @submit.prevent="submitQuestion">
                <Select v-model="form.lesson_id" label="Dars" optional placeholder="Umumiy savol" :error="form.errors.lesson_id">
                    <optgroup v-for="module in course.modules" :key="module.id" :label="module.title">
                        <option v-for="lesson in module.lessons" :key="lesson.id" :value="String(lesson.id)">
                            {{ lesson.title }}
                        </option>
                    </optgroup>
                </Select>
                <Textarea v-model="form.content" label="Savolingiz" required :rows="5" autoresize
                    placeholder="Savolingizni batafsil yozing: qaysi masala, qaysi qadam tushunarsiz…" :error="form.errors.content" />
            </form>
            <template #footer>
                <Button variant="secondary" @click="showAskModal = false">Bekor qilish</Button>
                <Button type="submit" form="ask-question-form" :loading="form.processing" :disabled="!form.content.trim()">
                    Yuborish
                </Button>
            </template>
        </Modal>
    </StudentLayout>
</template>
