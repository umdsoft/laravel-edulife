<?php

namespace App\Http\Controllers\Teacher\Exam;

use App\Http\Controllers\Controller;
use App\Models\Exam\Subject;
use App\Models\Exam\Topic;
use App\Services\Exam\TopicTreeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Mavzular": the topic tree per subject — add, rename, move, drag-sort,
 * deactivate (topics in use are never deleted).
 */
class TopicController extends Controller
{
    public function __construct(private readonly TopicTreeService $tree) {}

    public function index(Request $request): Response
    {
        $subjects = Subject::query()->orderBy('sort_order')->get(['id', 'code', 'name_uz']);
        $subject = $subjects->firstWhere('code', $request->query('subject')) ?? $subjects->first();

        return Inertia::render('Teacher/Exam/Topics/Index', [
            'subjects' => $subjects->map(fn ($s) => $s->only(['id', 'code', 'name_uz']))->values(),
            'subject' => $subject?->only(['id', 'code', 'name_uz']),
            'tree' => $subject ? $this->tree->tree($subject) : [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subject_id' => ['required', 'uuid', Rule::exists('exam_subjects', 'id')],
            'parent_id' => ['nullable', 'uuid', Rule::exists('exam_topics', 'id')->whereNull('deleted_at')],
            'name_uz' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9_.]+$/', Rule::unique('exam_topics', 'code')],
            'grade_from' => ['nullable', 'integer', 'between:1,11'],
            'grade_to' => ['nullable', 'integer', 'between:1,11', 'gte:grade_from'],
        ], ['code.regex' => 'Kod faqat kichik lotin harflari, raqam, nuqta va _ dan iborat bo\'lsin.'], ['name_uz' => 'nomi', 'code' => 'kod']);

        $subject = Subject::query()->findOrFail($data['subject_id']);
        try {
            $topic = $this->tree->create($subject, $data);
        } catch (\InvalidArgumentException) {
            throw ValidationException::withMessages(['parent_id' => 'Ota mavzu boshqa fanga tegishli.']);
        }

        return back()->with('success', "«{$topic->name_uz}» qo'shildi.");
    }

    public function update(Request $request, Topic $topic): RedirectResponse
    {
        $data = $request->validate([
            'name_uz' => ['sometimes', 'required', 'string', 'max:255'],
            'grade_from' => ['sometimes', 'nullable', 'integer', 'between:1,11'],
            'grade_to' => ['sometimes', 'nullable', 'integer', 'between:1,11'],
            'is_active' => ['sometimes', 'boolean'],
        ], [], ['name_uz' => 'nomi']);

        $this->tree->update($topic, $data);

        $message = array_key_exists('is_active', $data)
            ? ($data['is_active'] ? 'Mavzu faollashtirildi.' : 'Mavzu (va uning ichidagilar) faolsizlantirildi.')
            : 'Saqlandi.';

        return back()->with('success', $message);
    }

    public function move(Request $request, Topic $topic): RedirectResponse
    {
        $data = $request->validate([
            'parent_id' => ['nullable', 'uuid', Rule::exists('exam_topics', 'id')->whereNull('deleted_at')],
        ]);

        try {
            $this->tree->move($topic, $data['parent_id'] ?? null);
        } catch (\InvalidArgumentException) {
            throw ValidationException::withMessages(['parent_id' => 'Mavzuni o\'z ichiga yoki boshqa fanga ko\'chirib bo\'lmaydi.']);
        }

        return back()->with('success', 'Mavzu ko\'chirildi.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subject_id' => ['required', 'uuid', Rule::exists('exam_subjects', 'id')],
            'parent_id' => ['nullable', 'uuid'],
            'ids' => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['uuid'],
        ]);

        try {
            $this->tree->reorder(Subject::query()->findOrFail($data['subject_id']), $data['parent_id'] ?? null, $data['ids']);
        } catch (\InvalidArgumentException) {
            throw ValidationException::withMessages(['ids' => 'Faqat bitta bo\'lim ichidagi mavzularni tartiblash mumkin.']);
        }

        return back(303);
    }

    public function destroy(Topic $topic): RedirectResponse
    {
        $result = $this->tree->removeOrDeactivate($topic);

        return back()->with('success', $result === 'deleted'
            ? 'Mavzu o\'chirildi.'
            : 'Mavzu ishlatilmoqda (savollar yoki ichki mavzular bor), shuning uchun o\'chirilmadi — faolsizlantirildi.');
    }
}
