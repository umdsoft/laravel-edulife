<script setup>
/**
 * Imtihon rejasi editor: general fields, the rules (topic × difficulty × part × count × points)
 * and a live bank sufficiency check of the unsaved rules.
 */
import { computed, onBeforeUnmount, reactive, ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { PlusIcon, TrashIcon, ArrowPathIcon, CheckCircleIcon, ExclamationTriangleIcon, XCircleIcon } from '@heroicons/vue/24/outline';
import TeacherLayout from '@/Layouts/TeacherLayout.vue';
import { Alert, Badge, Button, Card, IconButton, Input, Modal, PageHeader, Select, Spinner, Switch, Textarea } from '@/Components/UI';
import { LEVELS, LEVEL_LABELS } from '@/Utils/difficulty';

const props = defineProps({
    blueprint: { type: Object, required: true },
    subjects: { type: Array, default: () => [] },
    topics: { type: Object, default: () => ({}) },
    purposes: { type: Array, default: () => [] },
    formats: { type: Array, default: () => [] },
    readiness: { type: Object, default: null },
});

const ANY = 'any';
let seq = 0;
const uid = () => `new-${Date.now().toString(36)}-${++seq}`;
const toRule = (r = {}) => ({
    key: r.id || uid(),
    id: r.id || null,
    topic_id: r.topic_id || null,
    // explicit choice: 1..5 or 'any' (saved rules without a level are "any"); a new rule starts unselected
    difficulty_level: r.difficulty_level ?? (r.id ? ANY : null),
    part: r.part || 'closed',
    count: r.count ?? 1,
    points_per_question: r.points_per_question ?? 1,
});

const form = useForm({
    subject_id: props.blueprint.subject_id,
    name: props.blueprint.name || '',
    purpose: props.blueprint.purpose || 'weekly',
    format: props.blueprint.format || null,
    duration_min: props.blueprint.duration_min ?? 90,
    wrong_unit_ratio: props.blueprint.wrong_unit_ratio ?? 0.5,
    avoid_repeat_weeks: props.blueprint.avoid_repeat_weeks ?? 4,
    allow_adjacent_difficulty: props.blueprint.allow_adjacent_difficulty ?? true,
    shuffle_questions: props.blueprint.shuffle_questions ?? true,
    shuffle_options: props.blueprint.shuffle_options ?? true,
    description: props.blueprint.description || '',
    is_active: props.blueprint.is_active ?? true,
    rules: (props.blueprint.rules || []).map(toRule),
});

const isEdit = computed(() => !!props.blueprint.id);
const subjectOptions = computed(() => props.subjects.map((s) => ({ value: s.id, label: s.name })));
const topicOptions = computed(() =>
    (props.topics[form.subject_id] || []).map((t) => ({ value: t.id, label: t.level > 1 ? ` ${t.name}` : `${t.name} (butun bo'lim)` })),
);
// the one difficulty scale; each level shows how many approved questions the rule's topic has
const levelsOf = (rule) => lineFor(rule)?.levels || null;
const difficultyOptionsFor = (rule) => {
    const counts = levelsOf(rule);
    const total = counts ? LEVELS.reduce((s, l) => s + Number(counts[l] || 0), 0) : null;
    return [
        { value: ANY, label: `Istalgan (1–5)${total === null ? '' : ` · ${total} ta`}` },
        ...LEVELS.map((l) => ({ value: l, label: `${LEVEL_LABELS[l]}${counts ? ` · ${Number(counts[l] || 0)} ta` : ''}` })),
    ];
};
const levelForApi = (v) => (v === ANY || v === null || v === '' ? null : Number(v));
const partOptions = [
    { value: 'closed', label: 'Yopiq (variantli)' },
    { value: 'open', label: 'Ochiq (javob yoziladi)' },
];
const totalQuestions = computed(() => form.rules.reduce((sum, r) => sum + (Number(r.count) || 0), 0));
const totalPoints = computed(() => form.rules.reduce((sum, r) => sum + (Number(r.count) || 0) * (Number(r.points_per_question) || 0), 0));
const num = (v) => String(Math.round((Number(v) || 0) * 100) / 100).replace('.', ',');

const addRule = () => form.rules.push(toRule({ part: 'closed' }));
const removeRule = (i) => form.rules.splice(i, 1);

watch(() => form.subject_id, () => {
    const allowed = new Set((props.topics[form.subject_id] || []).map((t) => t.id));
    form.rules.forEach((r) => { if (!allowed.has(r.topic_id)) r.topic_id = null; });
});

// --- live bank sufficiency --------------------------------------------------------------
const capacity = reactive({ loading: false, error: false, data: props.readiness || null });
let timer = null;
let requestSeq = 0;
const checkCapacity = () => {
    clearTimeout(timer);
    timer = setTimeout(async () => {
        const rules = form.rules.filter((r) => r.topic_id && Number(r.count) > 0).map((r) => ({
            key: r.key, topic_id: r.topic_id, difficulty_level: levelForApi(r.difficulty_level), part: r.part, count: Number(r.count),
        }));
        if (!form.subject_id) return;
        const mine = ++requestSeq;
        capacity.loading = true;
        capacity.error = false;
        try {
            const { data } = await axios.post(route('teacher.exam.blueprints.capacity'), {
                subject_id: form.subject_id, allow_adjacent_difficulty: form.allow_adjacent_difficulty, rules,
            });
            if (mine === requestSeq) capacity.data = data;
        } catch {
            if (mine === requestSeq) capacity.error = true;
        } finally {
            if (mine === requestSeq) capacity.loading = false;
        }
    }, 400);
};
watch(() => [form.subject_id, form.allow_adjacent_difficulty, JSON.stringify(form.rules.map((r) => [r.key, r.topic_id, r.difficulty_level, r.part, r.count]))], checkCapacity, { immediate: !props.readiness });
onBeforeUnmount(() => clearTimeout(timer));

const lineFor = (rule) => capacity.data?.rules?.find((l) => l.key === rule.key) || null;
const STATUS = {
    ok: { label: 'Bank yetarli', variant: 'success', icon: CheckCircleIcon, text: "Har bir qoida tasdiqlangan savollar bilan to'liq to'ldiriladi." },
    fallback: { label: "Qo'shni qiyinlik kerak", variant: 'warning', icon: ExclamationTriangleIcon, text: "Ba'zi qoidalarga savol yetmaydi — generator ±1 qiyinlikdan to'ldiradi va ogohlantiradi." },
    short: { label: 'Bank yetarli emas', variant: 'danger', icon: XCircleIcon, text: "Qoidalarni kamaytiring yoki savollar bankini to'ldiring: variantlar to'liq chiqmaydi." },
};
const overall = computed(() => STATUS[capacity.data?.status] || null);

const ruleError = (i, field) => form.errors[`rules.${i}.${field}`];

const submit = () => {
    // 'any' → null; an unselected level is left out so the server asks for it
    const payload = (data) => ({
        ...data,
        rules: data.rules.map(({ key, difficulty_level: level, ...r }) => (level === null || level === ''
            ? r : { ...r, difficulty_level: levelForApi(level) })),
    });
    if (isEdit.value) form.transform(payload).put(route('teacher.exam.blueprints.update', props.blueprint.id), { preserveScroll: true });
    else form.transform(payload).post(route('teacher.exam.blueprints.store'), { preserveScroll: true });
};

const confirmDelete = ref(false);
const deleting = ref(false);
const destroy = () => {
    deleting.value = true;
    router.delete(route('teacher.exam.blueprints.destroy', props.blueprint.id), { onFinish: () => { deleting.value = false; confirmDelete.value = false; } });
};
</script>

<template>
    <Head :title="isEdit ? blueprint.name : 'Yangi imtihon rejasi'" />

    <TeacherLayout>
        <PageHeader :title="isEdit ? blueprint.name : 'Yangi imtihon rejasi'"
            :breadcrumbs="[{ label: 'Imtihon rejalari', href: route('teacher.exam.blueprints.index') }, { label: isEdit ? 'Tahrirlash' : 'Yangi' }]">
            <template v-if="isEdit" #actions>
                <Button variant="ghost" :icon-left="TrashIcon" @click="confirmDelete = true">O'chirish</Button>
            </template>
        </PageHeader>

        <form class="grid gap-6 lg:grid-cols-3" @submit.prevent="submit">
            <div class="min-w-0 space-y-6 lg:col-span-2">
                <Card title="Asosiy">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <Input v-model="form.name" label="Nomi" required class="sm:col-span-2" :error="form.errors.name" placeholder="Masalan: Matematika — shanba imtihoni" />
                        <Select v-model="form.subject_id" label="Fan" :options="subjectOptions" required :error="form.errors.subject_id" />
                        <Select v-model="form.purpose" label="Turi" :options="purposes" required :error="form.errors.purpose" />
                        <Select v-model="form.format" label="Format" :options="formats" placeholder="Tanlanmagan" optional :error="form.errors.format" />
                        <Input v-model.number="form.duration_min" type="number" min="5" max="300" label="Davomiyligi (daqiqa)" required :error="form.errors.duration_min" />
                        <Textarea v-model="form.description" label="Tavsif" :rows="2" optional class="sm:col-span-2" :error="form.errors.description" />
                    </div>
                </Card>

                <Card title="Qoidalar" :description="`Jami ${totalQuestions} savol · ${num(totalPoints)} ball`">
                    <template #actions>
                        <Button size="sm" variant="secondary" :icon-left="PlusIcon" @click="addRule">Qoida</Button>
                    </template>

                    <Alert v-if="form.errors.rules" variant="danger" class="mb-4">{{ form.errors.rules }}</Alert>

                    <ol v-if="form.rules.length" class="space-y-3">
                        <li v-for="(rule, i) in form.rules" :key="rule.key" class="rounded-md border border-border p-3 sm:p-4">
                            <div class="mb-3 flex items-center justify-between gap-2">
                                <span class="text-13 font-medium text-fg-subtle">Qoida {{ i + 1 }}</span>
                                <div class="flex items-center gap-2">
                                    <template v-if="lineFor(rule)">
                                        <Badge :variant="lineFor(rule).status === 'ok' ? 'success' : lineFor(rule).status === 'fallback' ? 'warning' : 'danger'" size="sm">
                                            Bankda {{ lineFor(rule).candidates }} ta<template v-if="lineFor(rule).shortfall > 0"> · {{ lineFor(rule).shortfall }} ta yetmaydi</template>
                                        </Badge>
                                    </template>
                                    <IconButton :icon="TrashIcon" label="Qoidani o'chirish" size="sm" variant="ghost" @click="removeRule(i)" />
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3 sm:grid-cols-6">
                                <Select v-model="rule.topic_id" label="Mavzu" :options="topicOptions" placeholder="Tanlang" class="col-span-2 sm:col-span-6"
                                    :error="ruleError(i, 'topic_id')" />
                                <Select v-model="rule.difficulty_level" label="Qiyinlik" :options="difficultyOptionsFor(rule)" placeholder="Tanlang" required
                                    class="col-span-2 sm:col-span-2" :error="ruleError(i, 'difficulty_level')" />
                                <Select v-model="rule.part" label="Qism" :options="partOptions" class="col-span-2 sm:col-span-2" :error="ruleError(i, 'part')" />
                                <Input v-model.number="rule.count" type="number" min="1" max="200" label="Soni" :error="ruleError(i, 'count')" />
                                <Input v-model.number="rule.points_per_question" type="number" min="0.25" max="100" step="0.25" label="Ball" :error="ruleError(i, 'points_per_question')" />
                            </div>
                            <!-- approved questions per level for this topic and part -->
                            <div v-if="rule.topic_id && levelsOf(rule)" class="mt-3 flex flex-wrap items-center gap-1.5 text-xs" aria-label="Bankda har daraja bo'yicha">
                                <span class="text-fg-subtle">Bankda:</span>
                                <span v-for="l in LEVELS" :key="l" :title="LEVEL_LABELS[l]" :class="[
                                    'rounded-sm px-1.5 py-0.5 tabular-nums',
                                    Number(rule.difficulty_level) === l ? 'bg-brand-50 font-semibold text-brand ring-1 ring-inset ring-brand-500'
                                        : Number(levelsOf(rule)[l] || 0) ? 'bg-surface-2 text-fg-muted' : 'bg-surface-2 text-fg-subtle line-through',
                                ]">{{ l }}: {{ levelsOf(rule)[l] ?? 0 }}</span>
                                <span v-if="lineFor(rule).shortfall > 0" class="font-medium text-warning">
                                    · tanlangan darajada {{ lineFor(rule).candidates }} ta, kerak {{ lineFor(rule).required }} ta
                                </span>
                            </div>
                            <p v-if="!rule.difficulty_level" class="mt-2 text-xs text-warning">Qiyinlikni tanlang — 1 dan 5 gacha yoki «Istalgan».</p>
                        </li>
                    </ol>
                    <div v-else class="rounded-md border border-dashed border-border-strong p-6 text-center">
                        <p class="text-sm text-fg-muted">Hali qoida yo'q. Har qoida — bitta mavzu (yoki butun bo'lim), qiyinlik va savollar soni.</p>
                        <Button class="mt-3" size="sm" :icon-left="PlusIcon" @click="addRule">Birinchi qoida</Button>
                    </div>
                </Card>
            </div>

            <aside class="space-y-6 lg:sticky lg:top-24 lg:self-start">
                <Card title="Bank yetarliligi">
                    <template #actions>
                        <Spinner v-if="capacity.loading" size="h-4 w-4" label="Tekshirilmoqda" />
                        <IconButton v-else :icon="ArrowPathIcon" label="Qayta tekshirish" size="sm" variant="ghost" @click="checkCapacity" />
                    </template>
                    <div v-if="overall" class="space-y-2">
                        <Badge :variant="overall.variant" :icon="overall.icon">{{ overall.label }}</Badge>
                        <p class="text-13 text-fg-muted">{{ overall.text }}</p>
                        <p class="text-13 text-fg-subtle tabular-nums">Kerak: {{ capacity.data.total_required }} ta tasdiqlangan savol</p>
                    </div>
                    <p v-else-if="capacity.error" class="text-13 text-danger">Tekshirib bo'lmadi. Qayta urinib ko'ring.</p>
                    <p v-else class="text-13 text-fg-subtle">Qoida qo'shing — bank darhol tekshiriladi.</p>
                </Card>

                <Card title="Sozlamalar">
                    <div class="space-y-4">
                        <Input v-model.number="form.wrong_unit_ratio" type="number" min="0" max="1" step="0.05" label="Birlik xato bo'lsa ball ulushi"
                            hint="0 — ball yo'q, 0,5 — yarim ball (son to'g'ri, birlik noto'g'ri)" :error="form.errors.wrong_unit_ratio" />
                        <Input v-model.number="form.avoid_repeat_weeks" type="number" min="0" max="52" label="Takrorlamaslik (hafta)"
                            hint="O'quvchi oxirgi N haftada ko'rgan savollar imkon qadar berilmaydi" :error="form.errors.avoid_repeat_weeks" />
                        <Switch v-model="form.allow_adjacent_difficulty" label="Qo'shni qiyinlikdan to'ldirish" description="Bank yetmasa ±1 qiyinlikdagi savol olinadi" />
                        <Switch v-model="form.shuffle_questions" label="Savollarni aralashtirish" />
                        <Switch v-model="form.shuffle_options" label="Javob variantlarini aralashtirish" />
                        <Switch v-model="form.is_active" label="Faol" description="Faol bo'lmagan reja yangi imtihonga tanlanmaydi" />
                    </div>
                </Card>

                <div class="flex justify-end gap-2">
                    <Button variant="secondary" :href="route('teacher.exam.blueprints.index')">Bekor qilish</Button>
                    <Button type="submit" :loading="form.processing">Saqlash</Button>
                </div>
            </aside>
        </form>

        <Modal :show="confirmDelete" size="sm" title="Rejani o'chirish" description="Reja arxivga o'tadi. O'tgan imtihon natijalari saqlanadi." @close="confirmDelete = false">
            <template #footer>
                <Button variant="secondary" @click="confirmDelete = false">Bekor qilish</Button>
                <Button variant="danger" :loading="deleting" @click="destroy">O'chirish</Button>
            </template>
        </Modal>
    </TeacherLayout>
</template>
