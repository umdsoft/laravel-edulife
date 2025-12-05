<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\CoinPackage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $profile = $user->studentProfile ?? $user->studentProfile()->create([]);
        
        // Aktiv coin paketlarini olish
        $packages = CoinPackage::where('is_active', true)
            ->orderBy('is_popular', 'desc')
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get();
        
        return Inertia::render('Student/Shop/Index', [
            'packages' => $packages,
            'balance' => $profile->coins ?? 0,
            'level' => $profile->level ?? 1,
        ]);
    }
    
    public function purchase(Request $request, CoinPackage $package)
    {
        $user = Auth::user();
        
        // Bu yerda to'lov integratsiyasi bo'lishi kerak
        // To'lov sahifasiga yo'naltirish
        
        return response()->json([
            'success' => true,
            'message' => "'{$package->name}' paketini sotib olish uchun to'lov sahifasiga yo'naltirilmoqda...",
            'redirect' => route('student.payment.checkout', ['package' => $package->id, 'type' => 'coin'])
        ]);
    }
}
