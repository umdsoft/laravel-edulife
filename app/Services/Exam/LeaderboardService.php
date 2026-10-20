<?php

namespace App\Services\Exam;

use App\Models\Exam\StudentExamProfile;
use App\Models\Exam\StudentWeeklyResult;
use App\Models\Exam\Subject;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Weekly exam leaderboards of one subject (spec section 9):
 *  - top: by score percent (competition ranking: 1, 2, 2, 4)
 *  - growth ("eng ko'p o'sganlar"): by delta_prev > 0
 *  - schools: average percent, only schools with
 *    config('exam.leaderboard.school_min_participants') participants
 *
 * The whole board of a week is cached (config exam.leaderboard.cache_minutes);
 * the cache version is bumped when results or name consents change.
 */
class LeaderboardService
{
    private const VERSION_KEY = 'exam:leaderboard:version';

    /** Weeks (Mondays, newest first) with results in the subject. */
    public function weeks(Subject $subject, int $limit = 12): array
    {
        return StudentWeeklyResult::query()
            ->where('subject_id', $subject->id)
            ->select('week_start')
            ->distinct()
            ->orderByDesc('week_start')
            ->limit($limit)
            ->pluck('week_start')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->unique()
            ->values()
            ->all();
    }

    public static function flush(): void
    {
        Cache::forever(self::VERSION_KEY, (int) Cache::get(self::VERSION_KEY, 0) + 1);
    }

    /**
     * @return array{week_start: string|null, participants: int, top: list<array>, growth: list<array>, schools: list<array>, school_min: int, positions: array<string, array>}
     */
    public function board(Subject $subject, ?string $weekStart = null): array
    {
        $weekStart ??= $this->weeks($subject, 1)[0] ?? null;
        if ($weekStart === null) {
            return $this->empty();
        }

        $version = (int) Cache::get(self::VERSION_KEY, 0);
        $key = "exam:leaderboard:v{$version}:{$subject->id}:{$weekStart}";
        $ttl = now()->addMinutes((int) config('exam.leaderboard.cache_minutes', 10));

        return Cache::remember($key, $ttl, fn () => $this->build($subject, $weekStart));
    }

    /** The viewer's own line on each board (null when not on it). */
    public function positionOf(array $board, User $user): ?array
    {
        return $board['positions'][$user->id] ?? null;
    }

    private function empty(): array
    {
        return [
            'week_start' => null,
            'participants' => 0,
            'top' => [],
            'growth' => [],
            'schools' => [],
            'school_min' => (int) config('exam.leaderboard.school_min_participants', 5),
            'positions' => [],
        ];
    }

    private function build(Subject $subject, string $weekStart): array
    {
        $limit = (int) config('exam.leaderboard.top', 50);
        $schoolMin = max(1, (int) config('exam.leaderboard.school_min_participants', 5));

        // one line per student: the best result of the week in this subject
        $results = StudentWeeklyResult::query()
            ->where('subject_id', $subject->id)
            ->whereDate('week_start', $weekStart)
            ->orderByDesc('score_percent')
            ->orderBy('created_at')
            ->get(['id', 'user_id', 'score', 'max_score', 'score_percent', 'delta_prev'])
            ->unique('user_id')
            ->values();

        $userIds = $results->pluck('user_id')->all();
        $users = User::query()->whereIn('id', $userIds ?: ['__none__'])->get(['id', 'first_name', 'last_name'])->keyBy('id');
        $profiles = StudentExamProfile::query()->whereIn('user_id', $userIds ?: ['__none__'])
            ->get(['user_id', 'show_name_publicly', 'public_alias', 'school_id', 'school_name'])->keyBy('user_id');

        $name = function (string $userId) use ($users, $profiles): string {
            $p = $profiles->get($userId);
            $u = $users->get($userId);

            return LeaderboardNames::display(
                $userId,
                ['first_name' => $u?->first_name, 'last_name' => $u?->last_name],
                (bool) $p?->show_name_publicly,
                $p?->public_alias,
            );
        };

        // --- top ---
        $ranked = [];
        $rank = 0;
        $prev = null;
        foreach ($results as $i => $r) {
            $percent = round((float) $r->score_percent, 2);
            if ($prev === null || $percent < $prev) {
                $rank = $i + 1;
                $prev = $percent;
            }
            $ranked[] = [
                'user_id' => $r->user_id,
                'rank' => $rank,
                'name' => $name($r->user_id),
                'percent' => round($percent, 1),
                'score' => round((float) $r->score, 2),
                'max_score' => round((float) $r->max_score, 2),
                'delta' => $r->delta_prev === null ? null : round((float) $r->delta_prev, 1),
            ];
        }

        // --- growth ---
        $growing = collect($ranked)->filter(fn ($row) => $row['delta'] !== null && $row['delta'] > 0)
            ->sortByDesc('delta')->values();
        $growth = [];
        $gRank = 0;
        $gPrev = null;
        foreach ($growing as $i => $row) {
            if ($gPrev === null || $row['delta'] < $gPrev) {
                $gRank = $i + 1;
                $gPrev = $row['delta'];
            }
            $growth[] = ['rank' => $gRank] + $row;
        }

        // --- schools ---
        $schoolNames = School::query()->whereIn('id', $profiles->pluck('school_id')->filter()->unique()->values()->all() ?: ['__none__'])
            ->pluck('name_uz', 'id');
        $groups = [];
        foreach ($ranked as $row) {
            $p = $profiles->get($row['user_id']);
            if (! $p) {
                continue;
            }
            if ($p->school_id) {
                $key = 'id:'.$p->school_id;
                $label = (string) ($schoolNames[$p->school_id] ?? $p->school_name ?? '');
            } else {
                $label = self::cleanSchoolName((string) $p->school_name);
                $key = 'name:'.mb_strtolower($label);
            }
            if ($label === '') {
                continue;
            }
            $groups[$key] ??= ['key' => $key, 'labels' => [], 'sum' => 0.0, 'count' => 0, 'members' => []];
            $groups[$key]['labels'][] = $label;
            $groups[$key]['sum'] += $row['percent'];
            $groups[$key]['count']++;
            $groups[$key]['members'][] = $row['user_id'];
        }
        $schoolRows = collect($groups)
            ->filter(fn ($g) => $g['count'] >= $schoolMin)
            ->map(fn ($g) => [
                'key' => $g['key'],
                // the most common spelling among its students
                'name' => collect($g['labels'])->countBy()->sortDesc()->keys()->first(),
                'average' => round($g['sum'] / $g['count'], 1),
                'participants' => $g['count'],
                'members' => $g['members'],
            ])
            ->sortByDesc('average')
            ->values();
        $schools = [];
        $sRank = 0;
        $sPrev = null;
        foreach ($schoolRows as $i => $row) {
            if ($sPrev === null || $row['average'] < $sPrev) {
                $sRank = $i + 1;
                $sPrev = $row['average'];
            }
            $schools[] = ['rank' => $sRank] + $row;
        }

        // --- positions of every participant (for "your place") ---
        $positions = [];
        foreach ($ranked as $row) {
            $positions[$row['user_id']] = ['top' => $row, 'growth' => null, 'school' => null];
        }
        foreach ($growth as $row) {
            $positions[$row['user_id']]['growth'] = $row;
        }
        foreach ($schools as $row) {
            foreach ($row['members'] as $member) {
                $positions[$member]['school'] = ['rank' => $row['rank'], 'name' => $row['name'], 'average' => $row['average'], 'participants' => $row['participants']];
            }
        }

        $strip = fn (array $rows) => array_map(function ($row) {
            unset($row['user_id'], $row['members']);

            return $row;
        }, $rows);
        $mark = fn (array $rows) => array_map(fn ($row) => $row + ['uid' => substr(hash('sha256', 'lb:'.$row['user_id']), 0, 12)], $rows);

        return [
            'week_start' => $weekStart,
            'participants' => count($ranked),
            // user ids are not sent to the browser; uid is an opaque key so the
            // viewer's own line can be highlighted
            'top' => $strip($mark(array_slice($ranked, 0, $limit))),
            'growth' => $strip($mark(array_slice($growth, 0, $limit))),
            'schools' => $strip(array_slice($schools, 0, $limit)),
            'school_min' => $schoolMin,
            'positions' => $positions,
        ];
    }

    public static function uid(string $userId): string
    {
        return substr(hash('sha256', 'lb:'.$userId), 0, 12);
    }

    /** "  14-maktab " -> "14-maktab" (collapse spaces; free text from onboarding). */
    public static function cleanSchoolName(string $name): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $name));
    }
}
