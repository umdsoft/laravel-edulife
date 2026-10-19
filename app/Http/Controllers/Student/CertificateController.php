<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CertificateController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        $certificates = Certificate::with(['course.teacher', 'course.direction'])
            ->where('user_id', $user->id)
            ->orderByDesc('issued_at')
            ->paginate(12);
        
        return Inertia::render('Student/Certificates/Index', [
            'certificates' => $certificates,
        ]);
    }
    
    public function show(Certificate $certificate)
    {
        $this->authorize('view', $certificate);
        
        $certificate->load(['course.teacher', 'user']);
        
        return Inertia::render('Student/Certificates/Show', [
            'certificate' => $certificate,
        ]);
    }
    
    public function download(Certificate $certificate)
    {
        $this->authorize('view', $certificate);
        
        // built on demand when it is missing (or an old placeholder file)
        try {
            $path = app(\App\Services\CertificateService::class)->ensurePdf($certificate);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', "Sertifikat PDF faylini tayyorlab bo'lmadi. Birozdan so'ng qayta urinib ko'ring.");
        }

        $certificate->increment('downloads_count');

        return Storage::disk('public')->download($path, 'sertifikat-' . $certificate->certificate_number . '.pdf', [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
