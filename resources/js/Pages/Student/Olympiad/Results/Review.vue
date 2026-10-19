<script setup>
import { Head } from '@inertiajs/vue3';
import StudentLayout from '@/Layouts/StudentLayout.vue';
import { ref } from 'vue';
import { Badge, Card, EmptyState, PageHeader } from '@/Components/UI';
import { ChevronDownIcon, ClipboardDocumentListIcon } from '@heroicons/vue/24/outline';
import { CheckCircleIcon, ClockIcon, ExclamationTriangleIcon, XCircleIcon } from '@heroicons/vue/20/solid';

const props = defineProps({
    olympiad: Object,
    attempt: Object,
    sections: Array,
});

const currentSectionIndex = ref(0);
const expandedQuestion = ref(null);

const currentSection = ref(props.sections[0]);

const selectSection = (index) => {
    currentSectionIndex.value = index;
    currentSection.value = props.sections[index];
    expandedQuestion.value = null;
};

const getAnswerStatus = (answer) => {
    if (answer.is_correct === true) return { icon: CheckCircleIcon, label: "To'g'ri", variant: 'success', class: 'bg-success-50 hover:bg-success-100/60', iconClass: 'text-success' };
    if (answer.is_correct === false) return { icon: XCircleIcon, label: "Noto'g'ri", variant: 'danger', class: 'bg-danger-50 hover:bg-danger-100/60', iconClass: 'text-danger' };
    if (answer.is_partially_correct) return { icon: ExclamationTriangleIcon, label: 'Qisman', variant: 'warning', class: 'bg-warning-50 hover:bg-warning-100/60', iconClass: 'text-warning' };
    return { icon: ClockIcon, label: 'Baholanmoqda', variant: 'neutral', class: 'bg-surface hover:bg-surface-2', iconClass: 'text-fg-subtle' };
};

// ---- Presentation helpers (tolerate both payload spellings; no contract change) ----
const isSet = (v) => v !== null && v !== undefined && v !== '';
const letter = (i) => String.fromCharCode(65 + Number(i));

const optionText = (answer, value) => {
    if (!isSet(value)) return null;
    const idx = typeof value === 'number' ? value : (/^\d+$/.test(String(value)) ? Number(value) : null);
    if (idx !== null && Array.isArray(answer.options) && answer.options[idx] !== undefined) {
        const opt = answer.options[idx];
        const text = opt && typeof opt === 'object' ? (opt.text ?? opt.label ?? opt.value ?? JSON.stringify(opt)) : opt;
        return `${letter(idx)}: ${text}`;
    }
    if (typeof value === 'object') return Array.isArray(value) ? value.join(', ') : JSON.stringify(value);
    return String(value);
};

const userAnswerText = (answer) => {
    if (isSet(answer.selected_option)) return optionText(answer, Number(answer.selected_option));
    if (isSet(answer.text_answer)) return answer.text_answer;
    return optionText(answer, answer.user_answer);
};

const correctAnswerText = (answer) => {
    if (isSet(answer.correct_option)) return `${letter(answer.correct_option)}: ${answer.correct_answer_text || answer.options?.[answer.correct_option] || ''}`;
    if (isSet(answer.correct_answer_text)) return answer.correct_answer_text;
    return optionText(answer, answer.correct_answer);
};

const pointsEarned = (answer) => answer.score ?? answer.points_earned ?? 0;
const pointsMax = (answer) => answer.max_score ?? answer.max_points ?? 0;
const feedbackText = (answer) => answer.feedback || answer.grader_feedback || null;

const sectionCorrect = (section) => section.correct_count ?? (section.answers || []).filter((a) => a.is_correct === true).length;
const sectionTotal = (section) => section.total_questions ?? (section.answers || []).length;

const richText = 'text-sm leading-relaxed text-fg [&_li]:mt-1 [&_ol]:list-decimal [&_ol]:pl-5 [&_p+p]:mt-3 [&_ul]:list-disc [&_ul]:pl-5';
</script>

<template>
    <StudentLayout title="Javoblarni ko'rish">
        <Head :title="`Javoblarni ko'rish - ${olympiad.title}`" />

        <PageHeader title="Javoblarni ko'rish" :description="olympiad.title" :breadcrumbs="[
            { label: 'Olimpiadalar', href: route('student.olympiads.index') },
            { label: olympiad.title, href: route('student.olympiads.show', olympiad.slug) },
            { label: 'Natijalar', href: route('student.olympiads.results', olympiad.slug) },
            { label: 'Javoblar' },
        ]" />

        <Card v-if="!sections?.length">
            <EmptyState :icon="ClipboardDocumentListIcon" title="Ko'rib chiqish uchun javoblar yo'q" />
        </Card>

        <div v-else class="grid gap-6 lg:grid-cols-[256px_minmax(0,1fr)]">
            <!-- Sections -->
            <aside>
                <div class="lg:sticky lg:top-24">
                    <h2 class="mb-2 hidden text-13 font-semibold uppercase tracking-wider text-fg-subtle lg:block">Bo'limlar</h2>
                    <div class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 scrollbar-thin lg:mx-0 lg:flex-col lg:overflow-visible lg:px-0"
                        role="group" aria-label="Bo'limlar">
                        <button v-for="(section, index) in sections" :key="section.section_id" type="button" :class="[
                            'focus-ring shrink-0 rounded-md border px-4 py-3 text-left transition-colors duration-150 lg:w-full',
                            currentSectionIndex === index
                                ? 'border-brand-200 bg-brand-50'
                                : 'border-border bg-surface hover:bg-surface-2',
                        ]" :aria-pressed="currentSectionIndex === index" @click="selectSection(index)">
                            <span :class="['block text-sm font-medium', currentSectionIndex === index ? 'text-brand' : 'text-fg']">
                                {{ section.section_title }}
                            </span>
                            <span class="mt-0.5 block text-13 text-fg-subtle tabular-nums">
                                <span class="text-success">{{ sectionCorrect(section) }}</span> / {{ sectionTotal(section) }} to'g'ri
                                <span v-if="section.score_percent !== undefined"> · {{ section.score_percent }}%</span>
                            </span>
                        </button>
                    </div>
                </div>
            </aside>

            <!-- Questions -->
            <div class="min-w-0 space-y-3">
                <div v-for="(answer, index) in currentSection?.answers" :key="answer.id ?? index"
                    class="overflow-hidden rounded-lg border border-border bg-surface shadow-xs">
                    <!-- Question Header -->
                    <button type="button" :id="`review-q-${index}`"
                        :aria-expanded="expandedQuestion === index" :aria-controls="`review-a-${index}`"
                        :class="['focus-ring flex w-full items-start gap-3 p-4 text-left transition-colors duration-150 sm:p-5', getAnswerStatus(answer).class]"
                        @click="expandedQuestion = expandedQuestion === index ? null : index">
                        <component :is="getAnswerStatus(answer).icon"
                            :class="['mt-0.5 h-5 w-5 shrink-0', getAnswerStatus(answer).iconClass]" aria-hidden="true" />
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="flex items-center gap-2">
                                    <span class="font-medium text-fg tabular-nums">Savol {{ answer.order_number || index + 1 }}</span>
                                    <Badge :variant="getAnswerStatus(answer).variant" size="sm">{{ getAnswerStatus(answer).label }}</Badge>
                                </span>
                                <span class="text-13 font-medium text-fg-muted tabular-nums">
                                    {{ pointsEarned(answer) }} / {{ pointsMax(answer) }} ball
                                </span>
                            </div>
                            <div class="mt-1.5 line-clamp-2 text-sm text-fg-muted" v-html="answer.question_text"></div>
                        </div>
                        <ChevronDownIcon :class="['mt-0.5 h-5 w-5 shrink-0 text-fg-subtle transition-transform duration-150', expandedQuestion === index ? 'rotate-180' : '']"
                            aria-hidden="true" />
                    </button>

                    <!-- Expanded Content -->
                    <div v-if="expandedQuestion === index" :id="`review-a-${index}`" role="region"
                        :aria-labelledby="`review-q-${index}`" class="space-y-5 border-t border-border p-4 sm:p-5">
                        <div>
                            <h4 class="mb-2 text-13 font-semibold text-fg-muted">Savol</h4>
                            <div :class="richText" v-html="answer.question_html || answer.question_text"></div>
                        </div>

                        <div>
                            <h4 class="mb-2 text-13 font-semibold text-fg-muted">Sizning javobingiz</h4>
                            <div v-if="userAnswerText(answer)" :class="['whitespace-pre-wrap rounded-md p-3 text-sm ring-1 ring-inset',
                                answer.is_correct === true ? 'bg-success-50 text-fg ring-success-100'
                                    : answer.is_correct === false ? 'bg-danger-50 text-fg ring-danger-100'
                                        : 'bg-surface-2 text-fg ring-border']">
                                {{ userAnswerText(answer) }}
                            </div>
                            <div v-else class="rounded-md bg-surface-2 p-3 text-sm italic text-fg-subtle">
                                Javob berilmagan
                            </div>
                        </div>

                        <!-- Correct Answer (if review allowed) -->
                        <div v-if="answer.correct_answer !== undefined && olympiad.show_correct_answers">
                            <h4 class="mb-2 text-13 font-semibold text-success">To'g'ri javob</h4>
                            <div class="rounded-md bg-success-50 p-3 text-sm text-fg ring-1 ring-inset ring-success-100">
                                {{ correctAnswerText(answer) }}
                            </div>
                        </div>

                        <div v-if="answer.explanation">
                            <h4 class="mb-2 text-13 font-semibold text-info">Tushuntirish</h4>
                            <div :class="['rounded-md bg-info-50 p-3 ring-1 ring-inset ring-info-100', richText]"
                                v-html="answer.explanation"></div>
                        </div>

                        <div v-if="feedbackText(answer)">
                            <h4 class="mb-2 text-13 font-semibold text-warning">Baholovchi izohi</h4>
                            <div class="rounded-md bg-warning-50 p-3 text-sm text-fg ring-1 ring-inset ring-warning-100">
                                {{ feedbackText(answer) }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty state -->
                <Card v-if="!currentSection?.answers?.length">
                    <EmptyState :icon="ClipboardDocumentListIcon" title="Bu bo'limda savollar yo'q" />
                </Card>
            </div>
        </div>
    </StudentLayout>
</template>
