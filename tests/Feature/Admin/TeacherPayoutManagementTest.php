<?php

namespace Tests\Feature\Admin;

use App\Models\TeacherBankAccount;
use App\Models\TeacherPayout;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

class TeacherPayoutManagementTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    private function account(User $teacher, array $attributes = []): TeacherBankAccount
    {
        return TeacherBankAccount::forceCreate(array_merge([
            'teacher_id' => $teacher->id,
            'bank_name' => 'Kapitalbank',
            'card_number' => '8600123412341234',
            'card_holder_name' => 'TEST TEACHER',
        ], $attributes));
    }

    private function payout(TeacherBankAccount $account, string $status = 'pending'): TeacherPayout
    {
        return TeacherPayout::forceCreate([
            'teacher_id' => $account->teacher_id,
            'bank_account_id' => $account->id,
            'amount' => 250000,
            'status' => $status,
        ]);
    }

    public function test_mark_paid_completes_the_payout(): void
    {
        $admin = $this->admin();
        $payout = $this->payout($this->account($this->teacher()));

        $this->actingAs($admin)
            ->patch(route('admin.teacher-payouts.mark-paid', $payout))
            ->assertRedirect()
            ->assertSessionHas('success');

        $payout->refresh();
        $this->assertSame('completed', $payout->status);
        $this->assertNotNull($payout->processed_at);
        $this->assertSame($admin->id, $payout->processed_by);
    }

    public function test_paid_filter_lists_completed_payouts(): void
    {
        $account = $this->account($this->teacher());
        $done = $this->payout($account, 'completed');
        $this->payout($account, 'pending');

        $this->actingAs($this->admin())
            ->get(route('admin.teacher-payouts.index', ['status' => 'paid']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/TeacherPayouts/Index')
                ->has('payouts.data', 1)
                ->where('payouts.data.0.id', $done->id));
    }

    public function test_verifying_a_bank_account_sets_is_verified(): void
    {
        $account = $this->account($this->teacher());

        $this->actingAs($this->admin())
            ->patch(route('admin.teacher-payouts.verify-bank', $account))
            ->assertRedirect();

        $account->refresh();
        $this->assertTrue($account->is_verified);
        $this->assertNotNull($account->verified_at);
    }

    public function test_bank_accounts_can_be_filtered_by_verification(): void
    {
        $teacher = $this->teacher();
        $verified = $this->account($teacher, ['is_verified' => true, 'verified_at' => now()]);
        $pending = $this->account($teacher);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.teacher-payouts.bank-accounts', ['status' => 'verified']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/TeacherPayouts/BankAccounts')
                ->has('accounts.data', 1)
                ->where('accounts.data.0.id', $verified->id)
                ->where('accounts.data.0.status', 'verified'));

        $this->actingAs($admin)
            ->get(route('admin.teacher-payouts.bank-accounts', ['status' => 'pending']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('accounts.data', 1)
                ->where('accounts.data.0.id', $pending->id)
                ->where('accounts.data.0.status', 'pending'));
    }
}
