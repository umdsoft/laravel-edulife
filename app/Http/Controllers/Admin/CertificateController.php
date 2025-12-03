<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Services\CertificateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class CertificateController extends Controller
{
    public function __construct(
        protected CertificateService $certificateService
    ) {}

    public function index(Request $request)
    {
        $query = Certificate::with(['user', 'course', 'template']);

        // Search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('certificate_code', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by course
        if ($courseId = $request->get('course_id')) {
            $query->where('course_id', $courseId);
        }

        // Filter by template
        if ($templateId = $request->get('template_id')) {
            $query->where('template_id', $templateId);
        }

        $certificates = $query->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($cert) => [
                'id' => $cert->id,
                'code' => $cert->certificate_code,
                'student_name' => $cert->user->full_name ?? $cert->user->first_name . ' ' . $cert->user->last_name,
                'course_title' => $cert->course->title,
                'template_name' => $cert->template->name ?? 'Default',
                'issued_at' => $cert->issued_at->format('d.m.Y'),
                'pdf_url' => $cert->pdf_path ? Storage::url($cert->pdf_path) : null,
            ]);

        return Inertia::render('Admin/Certificates/Index', [
            'certificates' => $certificates,
            'filters' => $request->only(['search', 'course_id', 'template_id']),
            'templates' => CertificateTemplate::select('id', 'name')->get(),
        ]);
    }

    public function show(Certificate $certificate)
    {
        $certificate->load(['user', 'course', 'template']);

        return Inertia::render('Admin/Certificates/Show', [
            'certificate' => [
                'id' => $certificate->id,
                'code' => $certificate->certificate_code,
                'student' => [
                    'name' => $certificate->user->full_name ?? $certificate->user->first_name . ' ' . $certificate->user->last_name,
                    'email' => $certificate->user->email,
                    'phone' => $certificate->user->phone,
                ],
                'course' => [
                    'title' => $certificate->course->title,
                    'direction' => $certificate->course->direction->name ?? null,
                    'duration' => $certificate->course->duration ?? 0,
                ],
                'template_name' => $certificate->template->name ?? 'Default',
                'issued_at' => $certificate->issued_at->format('d F Y'),
                'verification_count' => $certificate->verification_count,
                'verification_url' => $certificate->verification_url,
                'pdf_url' => $certificate->pdf_path ? Storage::url($certificate->pdf_path) : null,
                'qr_code_url' => $certificate->qr_code_path ? Storage::url($certificate->qr_code_path) : null,
            ],
        ]);
    }

    public function regenerate(Certificate $certificate)
    {
        try {
            $this->certificateService->regenerateCertificate($certificate);
            return back()->with('success', 'Sertifikat qayta generatsiya qilindi');
        } catch (\Exception $e) {
            return back()->with('error', 'Xatolik yuz berdi: ' . $e->getMessage());
        }
    }

    public function destroy(Certificate $certificate)
    {
        // Delete files
        if ($certificate->pdf_path) {
            Storage::disk('public')->delete($certificate->pdf_path);
        }
        if ($certificate->qr_code_path) {
            Storage::disk('public')->delete($certificate->qr_code_path);
        }

        $certificate->delete();

        return back()->with('success', 'Sertifikat o\'chirildi');
    }

    public function download(Certificate $certificate)
    {
        if (!$certificate->pdf_path || !Storage::disk('public')->exists($certificate->pdf_path)) {
            // Try to regenerate if missing
            try {
                $this->certificateService->regenerateCertificate($certificate);
            } catch (\Exception $e) {
                return back()->with('error', 'Sertifikat fayli topilmadi va generatsiya qilib bo\'lmadi');
            }
        }

        return Storage::disk('public')->download($certificate->pdf_path);
    }
}
