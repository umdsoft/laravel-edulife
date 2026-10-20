<?php

namespace App\Http\Controllers\Teacher\Exam;

use App\Http\Controllers\Admin\Exam\AiUsageController;
use App\Http\Controllers\Controller;
use App\Http\Requests\Exam\StartQuestionDraftRunRequest;
use App\Models\Exam\ExamSourceDocument;
use App\Models\Exam\Topic;
use App\Services\Exam\QuestionDraftDashboard;
use App\Services\Exam\QuestionDraftService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Teacher "AI qoralamalar" (stage 4): the teacher's source material
 * (storage/app/exam-sources/ + exam_source_documents) and a form to draft
 * questions from it. Drafts land in the review queue.
 */
class AiSourceController extends Controller
{
    public function index(QuestionDraftDashboard $dashboard): Response
    {
        $documents = ExamSourceDocument::query()
            ->with(['subject:id,code,name_uz', 'topic:id,name_uz'])
            ->latest()
            ->get()
            ->map(fn (ExamSourceDocument $d) => [
                'id' => $d->id,
                'title' => $d->title,
                'source_ref' => $d->source_ref,
                'subject' => $d->subject ? ['code' => $d->subject->code, 'name' => $d->subject->name_uz] : null,
                'topic' => $d->topic?->name_uz,
                'original_name' => $d->original_name,
                'char_count' => $d->char_count,
                'is_active' => $d->is_active,
                'created_at' => $d->created_at?->toIso8601String(),
            ]);

        return Inertia::render('Teacher/Exam/AiDrafts/Index', [
            'documents' => $documents,
            'runs' => $dashboard->runs(10),
            'settings' => $dashboard->settings(),
            'options' => $dashboard->formOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $maxKb = (int) config('exam.ai.source_max_kb', 2048);
        $data = $request->validate([
            'subject_id' => ['required', 'uuid', Rule::exists('exam_subjects', 'id')->whereNull('deleted_at')],
            'topic_id' => ['nullable', 'uuid', Rule::exists('exam_topics', 'id')->whereNull('deleted_at')],
            'title' => ['required', 'string', 'max:255'],
            'source_ref' => ['nullable', 'string', 'max:255'],
            'file' => ['nullable', 'required_without:content_text', 'file', 'max:'.$maxKb, 'extensions:txt,md,tex'],
            'content_text' => ['nullable', 'required_without:file', 'string', 'max:'.($maxKb * 1024)],
        ], [], [
            'subject_id' => 'fan', 'topic_id' => 'mavzu', 'title' => 'nomi', 'source_ref' => 'manba belgisi',
            'file' => 'fayl', 'content_text' => 'matn',
        ]);

        if (! empty($data['topic_id']) && Topic::query()->whereKey($data['topic_id'])->value('subject_id') !== $data['subject_id']) {
            throw ValidationException::withMessages(['topic_id' => 'Mavzu tanlangan fanga tegishli emas.']);
        }

        $attributes = [
            'subject_id' => $data['subject_id'],
            'topic_id' => $data['topic_id'] ?? null,
            'title' => $data['title'],
            'source_ref' => $data['source_ref'] ?? null,
            'uploaded_by' => $request->user()->id,
            'is_active' => true,
        ];

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $text = (string) file_get_contents($file->getRealPath());
            $text = preg_replace('/^\xEF\xBB\xBF/', '', $text); // BOM
            if (! mb_check_encoding($text, 'UTF-8')) {
                throw ValidationException::withMessages(['file' => 'Fayl UTF-8 kodlashda bo\'lishi kerak.']);
            }
            $disk = (string) config('exam.ai.source_disk', 'local');
            $path = $file->store((string) config('exam.ai.source_dir', 'exam-sources'), $disk);
            $attributes += [
                'disk' => $disk,
                'path' => $path,
                'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
                'mime' => $file->getClientMimeType(),
                'size_bytes' => $file->getSize(),
                'content_text' => $text,
            ];
        } else {
            $attributes += [
                'content_text' => (string) $data['content_text'],
                'size_bytes' => strlen((string) $data['content_text']),
            ];
        }

        if (trim($attributes['content_text']) === '') {
            throw ValidationException::withMessages(['content_text' => 'Manba matni bo\'sh.']);
        }

        ExamSourceDocument::create($attributes);

        return back()->with('success', 'Manba qo\'shildi.');
    }

    public function toggle(ExamSourceDocument $document): RedirectResponse
    {
        $document->update(['is_active' => ! $document->is_active]);

        return back()->with('success', $document->is_active ? 'Manba yoqildi.' : 'Manba o\'chirib qo\'yildi.');
    }

    public function destroy(ExamSourceDocument $document): RedirectResponse
    {
        if ($document->path && $document->disk) {
            Storage::disk($document->disk)->delete($document->path);
        }
        $document->delete();

        return back()->with('success', 'Manba o\'chirildi.');
    }

    public function run(StartQuestionDraftRunRequest $request, QuestionDraftService $drafts): RedirectResponse
    {
        $run = $drafts->start($request->validated(), $request->user());

        return back()->with(...AiUsageController::flash($run));
    }
}
