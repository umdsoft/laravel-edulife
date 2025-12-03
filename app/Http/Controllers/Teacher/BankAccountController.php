<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\TeacherBankAccount;
use App\Http\Requests\Teacher\StoreBankAccountRequest;
use App\Http\Requests\Teacher\UpdateBankAccountRequest;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class BankAccountController extends Controller
{
    public function index()
    {
        $bankAccounts = TeacherBankAccount::where('teacher_id', Auth::id())
            ->orderByDesc('is_primary')
            ->orderByDesc('created_at')
            ->get();
        
        return Inertia::render('Teacher/BankAccounts/Index', [
            'bankAccounts' => $bankAccounts,
        ]);
    }
    
    public function store(StoreBankAccountRequest $request)
    {
        $isFirst = !TeacherBankAccount::where('teacher_id', Auth::id())->exists();
        
        TeacherBankAccount::create([
            'teacher_id' => Auth::id(),
            'bank_name' => $request->bank_name,
            'account_number' => $request->account_number,
            'account_holder_name' => $request->account_holder_name,
            'card_number' => $request->card_number,
            'is_primary' => $isFirst, // First account is primary by default
        ]);
        
        return back()->with('success', 'Bank hisob qo\'shildi');
    }
    
    public function update(UpdateBankAccountRequest $request, TeacherBankAccount $bankAccount)
    {
        $this->authorize('update', $bankAccount);
        
        $bankAccount->update([
            'bank_name' => $request->bank_name,
            'account_number' => $request->account_number,
            'account_holder_name' => $request->account_holder_name,
            'card_number' => $request->card_number,
        ]);
        
        return back()->with('success', 'Bank hisob yangilandi');
    }
    
    public function destroy(TeacherBankAccount $bankAccount)
    {
        $this->authorize('delete', $bankAccount);
        
        if ($bankAccount->is_primary) {
            return back()->with('error', 'Asosiy bank hisobni o\'chirib bo\'lmaydi');
        }
        
        // Check for pending payouts
        if ($bankAccount->payouts()->where('status', 'pending')->exists()) {
            return back()->with('error', 'Bu hisobda kutilayotgan to\'lovlar mavjud');
        }
        
        $bankAccount->delete();
        
        return back()->with('success', 'Bank hisob o\'chirildi');
    }
    
    public function setPrimary(TeacherBankAccount $bankAccount)
    {
        $this->authorize('update', $bankAccount);
        
        // Unset current primary
        TeacherBankAccount::where('teacher_id', Auth::id())
            ->where('is_primary', true)
            ->update(['is_primary' => false]);
        
        // Set new primary
        $bankAccount->update(['is_primary' => true]);
        
        return back()->with('success', 'Asosiy bank hisob o\'zgartirildi');
    }
}
