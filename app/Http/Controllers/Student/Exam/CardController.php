<?php

namespace App\Http\Controllers\Student\Exam;

use App\Http\Controllers\Controller;
use App\Models\Exam\StudentWeeklyResult;
use App\Services\Exam\ResultCardRenderer;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Result card PNG (1080x1920) of one of the student's own weekly results
 * (?result=<id>; default: the latest). ?download=1 saves it as a file.
 */
class CardController extends Controller
{
    public function show(Request $request, ResultCardRenderer $renderer): Response
    {
        $user = $request->user();

        $result = StudentWeeklyResult::query()
            ->where('user_id', $user->id)
            ->when($request->query('result'), fn ($q, $id) => $q->whereKey((string) $id))
            ->orderByDesc('week_start')
            ->orderByDesc('created_at')
            ->first();
        abort_if(! $result, 404);

        $png = $renderer->render($result, $user);
        $name = 'natija-kartasi-'.$result->week_start->toDateString().'.png';

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Content-Length' => (string) strlen($png),
            'Content-Disposition' => ($request->boolean('download') ? 'attachment' : 'inline').'; filename="'.$name.'"',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }
}
