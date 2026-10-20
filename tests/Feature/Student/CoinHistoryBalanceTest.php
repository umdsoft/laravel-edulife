<?php

namespace Tests\Feature\Student;

use App\Models\CoinTransaction;
use App\Models\User;
use App\Services\CoinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

/**
 * The coin history "Balans" column jumped (330, 335, 10, 13, 348 …): rows written
 * before the wallet migration stored balance_after from users.coin_balance.
 */
class CoinHistoryBalanceTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    private function row(User $user, int $minute, int $amount, int $storedBalance, string $source): CoinTransaction
    {
        return CoinTransaction::forceCreate([
            'user_id' => $user->id,
            'type' => $amount < 0 ? 'spend' : 'earn',
            'amount' => $amount,
            'balance_after' => $storedBalance,
            'source' => $source,
            'description' => $source,
            'created_at' => now()->startOfDay()->addMinutes($minute),
            'updated_at' => now()->startOfDay()->addMinutes($minute),
        ]);
    }

    /** @return array<int, array{amount:int, balance_after:int, source:string, balance_neutral:bool}> newest first */
    private function pageRows(User $user, int $page = 1): array
    {
        $rows = [];
        $this->actingAs($user)->get(route('student.coins.index', ['page' => $page]))
            ->assertOk()
            ->assertInertia(function (Assert $p) use (&$rows) {
                $p->component('Student/Coins/History');
                $rows = $p->toArray()['props']['transactions']['data'];
            });

        return $rows;
    }

    public function test_legacy_rows_get_a_running_balance_anchored_to_the_current_balance(): void
    {
        $student = $this->studentUser();
        // the real sequence from the QA database (stored balance_after as written)
        $this->row($student, 1, 30, 330, 'lab_experiment');      // legacy wallet (users.coin_balance)
        $this->row($student, 2, 5, 335, 'lab_badge');            // legacy wallet
        $this->row($student, 3, 10, 10, 'battle_win');           // profile wallet
        $this->row($student, 4, 3, 13, 'mission');               // profile wallet
        $this->row($student, 5, 335, 348, 'legacy_transfer');    // legacy -> profile
        $this->row($student, 6, 102, 450, 'olympiad_reward');
        $this->row($student, 7, -50, 400, 'tournament');
        $this->row($student, 8, 50, 450, 'tournament_refund');
        $student->studentProfile->update(['coins' => 450]);

        $rows = $this->pageRows($student);

        $this->assertSame(
            [450, 400, 450, 348, 348, 345, 335, 330],
            array_column($rows, 'balance_after')
        );
        $this->assertSame(450, $rows[0]['balance_after'], 'the newest row equals the current balance');
        $this->assertTrue($rows[3]['balance_neutral']);
        $this->assertFalse($rows[0]['balance_neutral']);

        // every non-neutral row: balance = previous (older) balance + amount
        $chrono = array_reverse($rows);
        for ($i = 1; $i < count($chrono); $i++) {
            $delta = $chrono[$i]['balance_neutral'] ? 0 : $chrono[$i]['amount'];
            $this->assertSame($chrono[$i - 1]['balance_after'] + $delta, $chrono[$i]['balance_after']);
        }

        // display-only: the stored history is untouched
        $this->assertSame(10, (int) CoinTransaction::where('source', 'battle_win')->value('balance_after'));
    }

    public function test_the_running_balance_continues_across_pages(): void
    {
        $student = $this->studentUser();
        $coins = app(CoinService::class);
        for ($i = 1; $i <= 35; $i++) {
            $this->travelTo(now()->startOfDay()->addMinutes($i));
            $i % 5 === 0
                ? $coins->spendCoins($student->fresh(), 3, 'shop', "Xarid {$i}")
                : $coins->addCoins($student->fresh(), 10, 'lesson', "Dars {$i}");
        }
        $balance = (int) $student->studentProfile()->first()->coins;

        $first = $this->pageRows($student, 1);
        $second = $this->pageRows($student, 2);

        $this->assertCount(30, $first);
        $this->assertCount(5, $second);
        $this->assertSame($balance, $first[0]['balance_after']);

        $last = end($first);
        $this->assertSame($last['balance_after'] - $last['amount'], $second[0]['balance_after']);

        // rows written by CoinService after the migration already agree with the stored value
        $all = array_merge($first, $second);
        foreach ($all as $row) {
            $this->assertSame(
                (int) CoinTransaction::whereKey($row['id'])->value('balance_after'),
                $row['balance_after']
            );
        }
        $this->assertSame(0, end($second)['balance_after'] - end($second)['amount']);
    }
}
