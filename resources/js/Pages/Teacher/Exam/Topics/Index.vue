<script setup>
import { computed, provide, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { PlusIcon, FolderIcon, InformationCircleIcon, QueueListIcon } from '@heroicons/vue/24/outline';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import PageHeader from '@/Components/UI/PageHeader.vue';
import Card from '@/Components/UI/Card.vue';
import Button from '@/Components/UI/Button.vue';
import Input from '@/Components/UI/Input.vue';
import Select from '@/Components/UI/Select.vue';
import Tabs from '@/Components/UI/Tabs.vue';
import Modal from '@/Components/UI/Modal.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import ConfirmDialog from '@/Components/Teacher/ConfirmDialog.vue';
import TopicTree from '@/Components/Exam/Bank/TopicTree.vue';

const props = defineProps({
    subjects: { type: Array, default: () => [] },
    subject: { type: Object, default: null },
    tree: { type: Array, default: () => [] },
});

const tabs = computed(() => props.subjects.map((s) => ({
    key: s.code, label: s.name_uz, href: route('teacher.exam.topics.index', { subject: s.code }), active: s.code === props.subject?.code,
})));

const flat = computed(() => {
    const out = [];
    const walk = (nodes, depth) => nodes.forEach((n) => {
        out.push({ ...n, depth });
        walk(n.children, depth + 1);
    });
    walk(props.tree, 0);
    return out;
});
const totals = computed(() => ({
    sections: props.tree.length,
    topics: flat.value.length - props.tree.length,
    questions: props.tree.reduce((s, n) => s + n.questions_total, 0),
    approved: props.tree.reduce((s, n) => s + n.questions_approved, 0),
}));

const opts = { preserveScroll: true, preserveState: true };

// ---- add ----
const adding = ref(null); // { parent } | null
const addForm = useForm({ subject_id: null, parent_id: null, name_uz: '', code: '', grade_from: null, grade_to: null });
const openAdd = (parent = null) => {
    addForm.reset();
    addForm.clearErrors();
    Object.assign(addForm, { subject_id: props.subject?.id, parent_id: parent?.id || null });
    adding.value = { parent };
};
const submitAdd = () => addForm.post(route('teacher.exam.topics.store'), { ...opts, onSuccess: () => (adding.value = null) });
const gradeOptions = Array.from({ length: 7 }, (_, i) => ({ value: i + 5, label: `${i + 5}-sinf` }));

// ---- move ----
const moving = ref(null);
const moveTarget = ref(null);
const moveError = ref(null);
const subtree = (node) => {
    const ids = new Set([node.id]);
    const walk = (n) => n.children.forEach((c) => {
        ids.add(c.id);
        walk(c);
    });
    walk(node);
    return ids;
};
const moveOptions = computed(() => {
    if (!moving.value) return [];
    const blocked = subtree(moving.value);
    return [
        { value: '', label: '— Yuqori daraja (bo\'lim) —' },
        ...flat.value.filter((n) => !blocked.has(n.id)).map((n) => ({ value: n.id, label: `${'   '.repeat(n.depth)}${n.name_uz}` })),
    ];
});
const openMove = (node) => {
    moving.value = node;
    moveTarget.value = node.parent_id || '';
    moveError.value = null;
};
const submitMove = () => router.post(route('teacher.exam.topics.move', moving.value.id), { parent_id: moveTarget.value || null }, {
    ...opts,
    onSuccess: () => (moving.value = null),
    onError: (e) => (moveError.value = e.parent_id || null),
});

// ---- remove ----
const removing = ref(null);
const submitRemove = () => router.delete(route('teacher.exam.topics.destroy', removing.value.id), { ...opts, onFinish: () => (removing.value = null) });

provide('topicActions', {
    add: openAdd,
    move: openMove,
    remove: (node) => (removing.value = node),
    rename: (node, name) => router.patch(route('teacher.exam.topics.update', node.id), { name_uz: name }, opts),
    toggle: (node, active) => router.patch(route('teacher.exam.topics.update', node.id), { is_active: active }, opts),
    reorder: (parentId, ids) => router.post(route('teacher.exam.topics.reorder'), { subject_id: props.subject.id, parent_id: parentId, ids }, opts),
    subjectCode: () => props.subject?.code,
});
</script>

<template>
    <Head title="Mavzular" />

    <TeacherLayout>
        <PageHeader title="Mavzular" description="Fan → bo'lim → mavzu. Imtihon rejasi va savollar shu daraxtga bog'lanadi."
            :breadcrumbs="[{ label: 'Savollar banki', href: route('teacher.exam.questions.index') }, { label: 'Mavzular' }]">
            <template #actions>
                <Button variant="secondary" :icon-left="QueueListIcon" :href="route('teacher.exam.questions.index', subject ? { subject: subject.code } : {})">Savollar</Button>
                <Button v-if="subject" :icon-left="PlusIcon" @click="openAdd(null)">Bo'lim qo'shish</Button>
            </template>
            <template #tabs>
                <Tabs v-if="tabs.length > 1" :tabs="tabs" label="Fan" />
            </template>
        </PageHeader>

        <div class="max-w-4xl space-y-4">
            <p class="flex items-start gap-2 text-13 text-fg-subtle">
                <InformationCircleIcon class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
                Tartibni tutqich bilan sudrang, nomini o'zgartirish uchun ustiga bosing. Savollari bor mavzu o'chirilmaydi — faolsizlantiriladi.
                Raqamlar: tasdiqlangan / jami savollar.
            </p>

            <Card v-if="tree.length" padding="sm">
                <template #header>
                    <div class="flex flex-wrap gap-x-5 gap-y-1 text-13 text-fg-muted">
                        <span><span class="font-semibold tabular-nums text-fg">{{ totals.sections }}</span> bo'lim</span>
                        <span><span class="font-semibold tabular-nums text-fg">{{ totals.topics }}</span> mavzu</span>
                        <span><span class="font-semibold tabular-nums text-fg">{{ totals.approved }}</span> / {{ totals.questions }} savol tasdiqlangan</span>
                    </div>
                </template>
                <TopicTree :nodes="tree" :parent-id="null" />
            </Card>
            <EmptyState v-else :icon="FolderIcon" bordered title="Mavzular yo'q" description="Birinchi bo'limni qo'shing.">
                <Button v-if="subject" :icon-left="PlusIcon" @click="openAdd(null)">Bo'lim qo'shish</Button>
            </EmptyState>
        </div>

        <Modal :show="!!adding" :title="adding?.parent ? 'Mavzu qo\'shish' : 'Bo\'lim qo\'shish'"
            :description="adding?.parent ? `«${adding.parent.name_uz}» ichiga` : subject?.name_uz" size="md" @close="adding = null">
            <form class="space-y-4" @submit.prevent="submitAdd">
                <Input v-model="addForm.name_uz" label="Nomi" required autofocus maxlength="255" :error="addForm.errors.name_uz" />
                <Input v-model="addForm.code" label="Kod" optional placeholder="avtomatik" hint="Import va AI shu kod bo'yicha topadi; keyin o'zgarmaydi."
                    :error="addForm.errors.code" class="font-mono" />
                <div class="grid grid-cols-2 gap-4">
                    <Select v-model="addForm.grade_from" label="Sinfdan" placeholder="—" :options="gradeOptions" :error="addForm.errors.grade_from" />
                    <Select v-model="addForm.grade_to" label="Sinfgacha" placeholder="—" :options="gradeOptions" :error="addForm.errors.grade_to" />
                </div>
                <p v-if="addForm.errors.parent_id" class="text-13 text-danger">{{ addForm.errors.parent_id }}</p>
            </form>
            <template #footer>
                <Button variant="secondary" @click="adding = null">Bekor qilish</Button>
                <Button :loading="addForm.processing" @click="submitAdd">Qo'shish</Button>
            </template>
        </Modal>

        <Modal :show="!!moving" title="Ko'chirish" :description="moving ? `«${moving.name_uz}» qayerga?` : ''" size="md" @close="moving = null">
            <Select v-model="moveTarget" label="Yangi o'rni" :options="moveOptions" :error="moveError" />
            <template #footer>
                <Button variant="secondary" @click="moving = null">Bekor qilish</Button>
                <Button @click="submitMove">Ko'chirish</Button>
            </template>
        </Modal>

        <ConfirmDialog :show="!!removing" :title="removing ? `«${removing.name_uz}» ni o'chirish` : ''"
            description="Mavzuda savollar, ichki mavzular yoki imtihon qoidalari bo'lsa, u o'chirilmaydi — faolsizlantiriladi (imtihonga yangi savol tanlanmaydi, tarix saqlanadi)."
            confirm-label="O'chirish" @confirm="submitRemove" @close="removing = null" />
    </TeacherLayout>
</template>
