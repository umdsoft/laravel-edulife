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
        
        if (!$certificate->pdf_path || !Storage::disk('public')->exists($certificate->pdf_path)) {
            return back()->with('error', 'Sertifikat fayli topilmadi');
        }
        
        return Storage::disk('public')->download(
            $certificate->pdf_path,
            'certificate-' . $certificate->certificate_number . '.pdf'
        );
    }
}
