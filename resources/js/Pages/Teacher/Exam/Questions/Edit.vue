<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { DocumentDuplicateIcon, ArchiveBoxXMarkIcon, TrashIcon, EllipsisVerticalIcon } from '@heroicons/vue/24/outline';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Button from '@/Components/UI/Button.vue';
import IconButton from '@/Components/UI/IconButton.vue';
import Badge from '@/Components/UI/Badge.vue';
import Alert from '@/Components/UI/Alert.vue';
import Dropdown from '@/Components/UI/Dropdown.vue';
import DropdownItem from '@/Components/UI/DropdownItem.vue';
import Modal from '@/Components/UI/Modal.vue';
import Textarea from '@/Components/UI/Textarea.vue';
import ConfirmDialog from '@/Components/Teacher/ConfirmDialog.vue';
import QuestionForm from '@/Components/Exam/Bank/QuestionForm.vue';
import { editorShortcut, formatDateTime } from '@/Components/Teacher/format';
import { isChoiceType, originLabel, statusMeta } from '@/Components/Exam/Bank/bank';

const props = defineProps({
    question: { type: Object, required: true },
    subjects: { type: Array, default: () => [] },
    topics: { type: Array, default: () => [] },
    vocabulary: { type: Object, default: () => ({}) },
    usedInExams: Boolean,
});

const q = props.question;
const form = useForm({
    subject_id: q.subject_id,
    topic_id: q.topic_id,
    question_type: q.question_type,
    question_text: q.question_text || '',
    difficulty_level: q.difficulty_level,
    exam_formats: q.exam_formats || [],
    source_ref: q.source_ref || '',
    options: isChoiceType(q.question_type) ? (q.options || []).map((o) => ({ text: o.text, is_correct: o.is_correct })) : [],
    answer: q.answer ? { ...q.answer, accepted_units: [...(q.answer.accepted_units || [])] } : null,
    units_note: q.units_note || '',
    constants: q.constants || [],
    solution_text: q.solution_text || '',
    solution_video_url: q.solution_video_url || '',
    intent: null,
});

const status = computed(() => statusMeta(props.question.review_status));
const isApproved = computed(() => props.question.review_status === 'approved');
const isRetired = computed(() => props.question.review_status === 'retired');
const violations = computed(() => Object.values(props.question.violations || {}));

const submit = (intent = null) => {
    if (form.processing || isRetired.value) return;
    form.intent = intent;
    form.put(route('teacher.exam.questions.update', props.question.id), { preserveScroll: true });
};

const onKeydown = (e) => {
    if (!editorShortcut(e)) return;
    e.preventDefault();
    submit(isApproved.value ? 'approve' : null);
};
onMounted(() => window.addEventListener('keydown', onKeydown));
onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown));

const duplicate = () => router.post(route('teacher.exam.questions.duplicate', props.question.id));
const confirmDelete = ref(false);
const destroy = () => router.delete(route('teacher.exam.questions.destroy', props.question.id), { onFinish: () => (confirmDelete.value = false) });
const retireOpen = ref(false);
const retireForm = useForm({ reason: '' });
const retire = () => retireForm.post(route('teacher.exam.questions.retire', props.question.id), {
    preserveScroll: true,
    onSuccess: () => (retireOpen.value = false),
});
</script>

<template>
    <Head title="Savolni tahrirlash — Savollar banki" />

    <TeacherLayout>
        <PageHeader title="Savolni tahrirlash"
            :breadcrumbs="[{ label: 'Savollar banki', href: route('teacher.exam.questions.index') }, { label: 'Tahrirlash' }]">
            <template #meta>
                <Badge :variant="status.variant" dot>{{ status.label }}</Badge>
                <Badge variant="neutral">{{ originLabel(question.origin) }}</Badge>
                <Badge v-if="question.is_demo" variant="info">Namuna</Badge>
                <span v-if="question.reviewed_at" class="text-13 text-fg-subtle">
                    {{ question.reviewer ? `${question.reviewer}, ` : '' }}{{ formatDateTime(question.reviewed_at) }}
                </span>
            </template>
            <template #actions>
                <Dropdown align="right">
                    <template #trigger><IconButton :icon="EllipsisVerticalIcon" label="Amallar" variant="secondary" /></template>
                    <DropdownItem :icon="DocumentDuplicateIcon" @click="duplicate">Nusxa yaratish</DropdownItem>
                    <DropdownItem v-if="isApproved" :icon="ArchiveBoxXMarkIcon" @click="retireOpen = true">Muomaladan chiqarish</DropdownItem>
                    <DropdownItem v-if="!isApproved && !usedInExams" danger :icon="TrashIcon" @click="confirmDelete = true">O'chirish</DropdownItem>
                </Dropdown>
            </template>
        </PageHeader>

        <div class="max-w-3xl space-y-4">
            <Alert v-if="isRetired" variant="warning" title="Muomaladan chiqarilgan">
                Bu savol imtihonlarga tushmaydi va tahrirlanmaydi. Tuzatish uchun nusxasini yarating.
            </Alert>
            <Alert v-else-if="question.review_status === 'rejected' && question.reject_reason" variant="danger" title="Rad etilgan">
                {{ question.reject_reason }}
            </Alert>
            <Alert v-if="isApproved" variant="info">
                Savol tasdiqlangan. Shart yoki javobni o'zgartirsangiz, u qayta tekshiruvga tushadi — «Saqlash va tasdiqlash» bilan darhol qayta tasdiqlang.
            </Alert>
            <Alert v-if="violations.length && !isApproved && !isRetired" variant="warning" title="Tasdiqlash uchun yetishmaydi">
                <ul class="list-disc pl-5">
                    <li v-for="v in violations" :key="v">{{ v }}</li>
                </ul>
            </Alert>

            <form novalidate @submit.prevent="submit()">
                <fieldset :disabled="isRetired" class="min-w-0">
                    <QuestionForm :form="form" :subjects="subjects" :topics="topics" />
                </fieldset>

                <div v-if="!isRetired" class="sticky bottom-0 z-10 -mx-4 mt-4 flex flex-wrap items-center justify-end gap-2 border-t border-border bg-bg/90 px-4 py-3 backdrop-blur sm:mx-0 sm:px-0">
                    <Button variant="ghost" class="hidden sm:inline-flex" :href="route('teacher.exam.questions.index')">Orqaga</Button>
                    <Button v-if="!isApproved" variant="secondary" :disabled="form.processing" @click="submit('draft')">Qoralamaga</Button>
                    <Button v-if="!isApproved" variant="secondary" :disabled="form.processing" @click="submit('review')">Tekshiruvga</Button>
                    <Button v-if="!isApproved" variant="secondary" title="Ctrl+S" :disabled="form.processing" @click="submit()">Saqlash</Button>
                    <Button type="button" :title="isApproved ? 'Ctrl+S' : null" :loading="form.processing && form.intent === 'approve'"
                        :disabled="form.processing" @click="submit('approve')">
                        Saqlash va tasdiqlash
                    </Button>
                </div>
            </form>
        </div>

        <ConfirmDialog :show="confirmDelete" title="Savolni o'chirish" description="Savol bankdan o'chiriladi."
            @confirm="destroy" @close="confirmDelete = false" />

        <Modal :show="retireOpen" title="Muomaladan chiqarish" description="Savol keyingi imtihonlarga tushmaydi; o'tgan natijalar saqlanadi."
            size="md" @close="retireOpen = false">
            <Textarea v-model="retireForm.reason" label="Sabab" optional :rows="3" :error="retireForm.errors.reason"
                placeholder="Masalan: javobda xato topildi" />
            <template #footer>
                <Button variant="secondary" @click="retireOpen = false">Bekor qilish</Button>
                <Button variant="danger" :loading="retireForm.processing" @click="retire">Chiqarish</Button>
            </template>
        </Modal>
    </TeacherLayout>
</template>
