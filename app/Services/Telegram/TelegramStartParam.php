<?php

namespace App\Services\Telegram;

use App\Enums\Exam\AttemptStatus;
use App\Enums\Exam\ExamSessionType;
use App\Models\Battle;
use App\Models\Exam\ExamAttempt;
use App\Models\Exam\ExamSession;
use App\Models\Exam\GuardianReport;
use App\Models\Exam\GuardianStudent;
use App\Models\Exam\Subject;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * Where the Mini App opens (Telegram WebView, docs/exam/03_RASM_QIYINLIK_TELEGRAM.md §3.1).
 *
 * The same grammar is used for the deep link t.me/<bot>?startapp=<param> (Telegram hands it to
 * the app as initData.start_param) and for our own web_app buttons (?to=<param> on /telegram/app,
 * because a web_app button opens the URL itself and has no start_param).
 *
 * Telegram allows A-Z a-z 0-9 _ - in start parameters, up to 64 characters
 * (core.telegram.org/bots/features#deep-linking), so ids are separated with "_".
 *
 *   exam                    exam home
 *   exam_s_<session uuid>   that Saturday session: the running attempt, the result, or the exam home
 *   placement_<math|physics> start the placement test (a POST the entry page submits)
 *   result_<attempt uuid>   the student's own result page
 *   progress | leaderboard  "O'sishim" / exam leaderboard
 *   battle                  battle lobby
 *   battle_<code>           a friend's battle challenge (8-character opaque invite code, not the battle id)
 *   report[_<report uuid>]  guardian: reports
 *   child_<student uuid>    guardian: that (approved) child's page                     (TW)
 *   invite                  student: "O'sishim" with the invite card                    (TW)
 *   ref_<code>              an invite (t.me/<bot>?startapp=ref_<code>): the registration records it,
 *                           a registered user just opens the home page                  (TW)
 *
 * Anything else (or a param that does not belong to the user) opens the exam home.
 * Legacy "result:<uuid>" (stage 6 buttons already sent) is still understood.
 */
class TelegramStartParam
{
    private const UUID = '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}';

    /** Syntactically valid start parameter (Telegram's own limits). */
    public static function isValid(?string $param): bool
    {
        return is_string($param) && preg_match('/^[A-Za-z0-9_\-]{1,64}$/', $param) === 1;
    }

    /**
     * @return array{url: string, post?: string} url: GET target; post: a URL the entry page
     *         submits as a form POST (starting a test needs a POST), url is the fallback.
     */
    public function resolve(User $user, ?string $param): array
    {
        $param = is_string($param) ? trim($param) : null;
        if ($param !== null && preg_match('/^result:('.self::UUID.')$/', $param, $m)) {
            $param = 'result_'.$m[1]; // stage 6 buttons
        }
        if ($param !== null && $param !== '' && ! self::isValid($param)) {
            $param = null;
        }

        if ($user->role === 'guardian') {
            return ['url' => $this->guardianTarget($user, $param)];
        }
        if ($user->role !== 'student') {
            return ['url' => '/'];
        }

        return $this->studentTarget($user, (string) $param);
    }

    /** @return array{url: string, post?: string} */
    private function studentTarget(User $user, string $param): array
    {
        $home = ['url' => $this->examHome()];

        if (preg_match('/^exam_s_('.self::UUID.')$/', $param, $m)) {
            return ['url' => $this->sessionTarget($user, $m[1]) ?? $home['url']];
        }

        if (preg_match('/^placement_([a-z]{2,20})$/', $param, $m) && Route::has('student.exam.placement.start')) {
            $subject = Subject::query()->active()->where('code', $m[1])->first();

            return $subject
                ? ['url' => $home['url'], 'post' => route('student.exam.placement.start', $subject->code, false)]
                : $home;
        }

        if (preg_match('/^result_('.self::UUID.')$/', $param, $m) && Route::has('student.exam.result')) {
            $own = ExamAttempt::query()->whereKey($m[1])->where('user_id', $user->id)->exists();

            return $own ? ['url' => route('student.exam.result', ['attempt' => $m[1]], false)] : $home;
        }

        if (preg_match('/^battle_([A-Za-z0-9]{8})$/', $param, $m) && Route::has('student.battles.challenge.show')) {
            $exists = Battle::query()->where('invite_code', $m[1])->exists();

            return $exists ? ['url' => route('student.battles.challenge.show', $m[1], false)] : ['url' => $this->named('student.battles.index')];
        }

        return match ($param) {
            'battle' => ['url' => $this->named('student.battles.index')],
            'progress' => ['url' => $this->named('student.exam.progress')],
            'leaderboard' => ['url' => $this->named('student.exam.leaderboard')],
            // TW: /taklif opens the invite card
            'invite' => ['url' => $this->named('student.exam.progress').'#taklif'],
            default => $home,
        };
    }

    /** The attempt of that session if there is one, else the exam home. */
    private function sessionTarget(User $user, string $sessionId): ?string
    {
        $session = ExamSession::query()->public()->whereKey($sessionId)->first();
        if ($session === null || $session->type === ExamSessionType::Placement) {
            return null;
        }

        $attempt = ExamAttempt::query()->where('session_id', $session->id)->where('user_id', $user->id)->latest('started_at')->first();
        if ($attempt === null) {
            return null;
        }
        if ($attempt->status === AttemptStatus::InProgress && ! $attempt->isExpired() && Route::has('student.exam.take')) {
            return route('student.exam.take', $attempt, false);
        }

        return Route::has('student.exam.result') ? route('student.exam.result', $attempt, false) : null;
    }

    private function guardianTarget(User $user, ?string $param): string
    {
        if (is_string($param) && preg_match('/^report_('.self::UUID.')$/', $param, $m) && Route::has('guardian.reports.show')) {
            $own = GuardianReport::query()->whereKey($m[1])->where('guardian_id', $user->id)->exists();
            if ($own) {
                return route('guardian.reports.show', $m[1], false);
            }
        }
        // TW: the guardian's copy of a result opens that child's page (approved links only)
        if (is_string($param) && preg_match('/^child_('.self::UUID.')$/', $param, $m) && Route::has('guardian.children.show')) {
            $own = GuardianStudent::query()->verified()->where('guardian_id', $user->id)->where('student_id', $m[1])->exists();
            if ($own) {
                return route('guardian.children.show', $m[1], false);
            }
        }
        if ($param === 'report' && Route::has('guardian.reports.index')) {
            return route('guardian.reports.index', [], false);
        }

        return Route::has('guardian.dashboard') ? route('guardian.dashboard', [], false) : '/';
    }

    private function examHome(): string
    {
        return $this->named('student.exam.index');
    }

    private function named(string $name): string
    {
        foreach ([$name, 'student.exam.index', 'student.dashboard'] as $candidate) {
            if (Route::has($candidate)) {
                return route($candidate, [], false);
            }
        }

        return '/student/dashboard';
    }

    /**
     * A same-site path the app may return to after a silent re-login
     * (the page the session was lost on). Only student/guardian pages.
     */
    public static function safeReturnPath(?string $path): ?string
    {
        if (! is_string($path) || strlen($path) > 300) {
            return null;
        }

        return preg_match('#^/(student|guardian)(/[A-Za-z0-9/_\-\.]*)?(\?[A-Za-z0-9_\-\.=&%]*)?$#', $path) === 1 && ! str_contains($path, '..')
            ? $path
            : null;
    }
}
