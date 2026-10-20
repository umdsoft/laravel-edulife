<?php

namespace App\Http\Controllers\Guardian;

use App\Enums\Exam\GuardianRelation;
use App\Http\Controllers\Controller;
use App\Models\Exam\GuardianReport;
use App\Models\Exam\GuardianStudent;
use App\Models\User;
use App\Services\Exam\ReportBuilder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Guardian (ota-ona) panel: only the guardian's own approved children.
 */
class DashboardController extends Controller
{
    public function __construct(private readonly ReportBuilder $reports) {}

    public function index(Request $request): Response
    {
        $guardian = $request->user();

        $links = GuardianStudent::query()
            ->where('guardian_id', $guardian->id)
            ->whereNull('rejected_at')
            ->with('student:id,first_name,last_name,avatar')
            ->orderBy('created_at')
            ->get();

        $children = $links->filter(fn (GuardianStudent $l) => $l->isVerified() && $l->student)
            ->map(fn (GuardianStudent $l) => $this->childSummary($l->student))
            ->values();

        $pending = $links->filter(fn (GuardianStudent $l) => ! $l->isVerified())
            ->map(fn (GuardianStudent $l) => [
                'id' => $l->id,
                'name' => $l->student ? trim($l->student->first_name.' '.mb_substr((string) $l->student->last_name, 0, 1).'.') : null,
                'requested_at' => $l->updated_at?->format('d.m.Y'),
            ])->values();

        $rejected = GuardianStudent::query()->where('guardian_id', $guardian->id)->whereNotNull('rejected_at')
            ->latest('rejected_at')->limit(3)->get(['id', 'rejected_at', 'reject_reason'])
            ->map(fn ($l) => ['id' => $l->id, 'rejected_at' => $l->rejected_at?->format('d.m.Y'), 'reason' => $l->reject_reason]);

        return Inertia::render('Guardian/Dashboard', [
            'children' => $children,
            'pending' => $pending,
            'rejected' => $rejected,
            'reports' => $this->recentReports($guardian),
            'relations' => array_map(fn (GuardianRelation $r) => ['value' => $r->value, 'label' => self::relationLabel($r)], GuardianRelation::cases()),
        ]);
    }

    public function child(Request $request, User $child): Response
    {
        $this->ensureWard($request->user(), $child);

        return Inertia::render('Guardian/Child', [
            'child' => $this->childSummary($child) + [
                'topicMap' => $this->reports->topicMap($child),
                'weakest' => $this->reports->weakestTopics($child, 5),
            ],
            'reports' => $this->recentReports($request->user(), $child),
        ]);
    }

    /** 404 (not 403) so a guardian can not probe which ids exist. */
    public static function ensureWard(User $guardian, User $child): void
    {
        $ok = GuardianStudent::query()->verified()
            ->where('guardian_id', $guardian->id)
            ->where('student_id', $child->id)
            ->exists();
        abort_unless($ok, 404);
    }

    private function childSummary(User $child): array
    {
        $history = $this->reports->history($child);
        $bySubject = collect($history)->groupBy('subject');
        $subjects = $this->reports->subjectsFor($child);

        return [
            'id' => $child->id,
            'first_name' => $child->first_name,
            'last_name' => $child->last_name,
            'subjects' => $subjects->map(function ($s) use ($bySubject) {
                $rows = $bySubject->get($s->code, collect())->values();

                return [
                    'code' => $s->code,
                    'name' => $s->name_uz,
                    'last' => $rows->last(),
                    'history' => $rows->take(-12)->values()->all(),
                ];
            })->values()->all(),
            'sections' => collect($this->reports->topicMap($child, $subjects))
                ->map(fn ($m) => [
                    'code' => $m['code'],
                    'name' => $m['name'],
                    'score' => $m['score'],
                    'sections' => array_map(fn ($s) => ['code' => $s['code'], 'name' => $s['name'], 'score' => $s['score']], $m['sections']),
                ])->all(),
            'recommendations' => $this->reports->recommendations($child),
        ];
    }

    private function recentReports(User $guardian, ?User $child = null): array
    {
        return GuardianReport::query()
            ->where('guardian_id', $guardian->id)
            ->whereIn('guardian_student_id', GuardianStudent::query()->verified()->where('guardian_id', $guardian->id)->select('id'))
            ->when($child, fn ($q) => $q->where('student_id', $child->id))
            ->latest('week_start')
            ->limit(8)
            ->get(['id', 'student_id', 'week_start', 'payload', 'read_at'])
            ->map(fn (GuardianReport $r) => [
                'id' => $r->id,
                'week_start' => $r->week_start->format('d.m.Y'),
                'week_label' => $r->payload['week_label'] ?? null,
                'student' => $r->payload['student']['first_name'] ?? null,
                'participated' => (bool) ($r->payload['participated'] ?? false),
                'unread' => $r->read_at === null,
            ])->all();
    }

    public static function relationLabel(GuardianRelation $relation): string
    {
        return match ($relation) {
            GuardianRelation::Mother => 'Onasi',
            GuardianRelation::Father => 'Otasi',
            GuardianRelation::Guardian => 'Vasiy',
            GuardianRelation::Other => 'Boshqa',
        };
    }
}
