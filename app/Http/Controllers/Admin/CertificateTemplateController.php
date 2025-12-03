<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CertificateTemplate;
use App\Models\Direction;
use App\Models\Course;
use App\Http\Requests\Admin\StoreCertificateTemplateRequest;
use App\Http\Requests\Admin\UpdateCertificateTemplateRequest;
use App\Services\CertificateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CertificateTemplateController extends Controller
{
    public function __construct(
        protected CertificateService $certificateService
    ) {}

    public function index()
    {
        $templates = CertificateTemplate::with(['direction', 'course'])
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        return Inertia::render('Admin/Certificates/Templates/Index', [
            'templates' => $templates,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Certificates/Templates/Create', [
            'directions' => Direction::where('status', 'active')->orderBy('sort_order')->get(),
            'courses' => Course::where('status', 'published')->get(['id', 'title', 'direction_id']),
        ]);
    }

    public function store(StoreCertificateTemplateRequest $request)
    {
        // Handle PDF template upload
        $pdfPath = $request->file('pdf_template')->store('certificate-templates', 'public');
        
        // Handle thumbnail upload
        $thumbnailPath = null;
        if ($request->hasFile('thumbnail')) {
            $thumbnailPath = $request->file('thumbnail')->store('certificate-templates/thumbnails', 'public');
        }

        // Create template
        $template = CertificateTemplate::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'pdf_template_path' => $pdfPath,
            'thumbnail_path' => $thumbnailPath,
            'direction_id' => $request->direction_id,
            'course_id' => $request->course_id,
            'placeholders' => $request->placeholders,
            'settings' => $request->settings ?? [
                'page_size' => 'A4',
                'orientation' => 'landscape',
            ],
            'is_active' => $request->boolean('is_active', true),
            'is_default' => $request->boolean('is_default', false),
        ]);

        // If set as default, unset other defaults
        if ($template->is_default) {
            CertificateTemplate::where('id', '!=', $template->id)
                ->update(['is_default' => false]);
        }

        return redirect()->route('admin.certificate-templates.index')
            ->with('success', 'Shablon muvaffaqiyatli yaratildi');
    }

    public function show(CertificateTemplate $template)
    {
        $template->load(['direction', 'course']);
        
        $recentCertificates = $template->certificates()
            ->with(['user', 'course'])
            ->latest()
            ->take(10)
            ->get();

        return Inertia::render('Admin/Certificates/Templates/Show', [
            'template' => $template,
            'recentCertificates' => $recentCertificates,
        ]);
    }

    public function edit(CertificateTemplate $template)
    {
        return Inertia::render('Admin/Certificates/Templates/Edit', [
            'template' => $template,
            'directions' => Direction::where('status', 'active')->orderBy('sort_order')->get(),
            'courses' => Course::where('status', 'published')->get(['id', 'title', 'direction_id']),
        ]);
    }

    public function update(UpdateCertificateTemplateRequest $request, CertificateTemplate $template)
    {
        $data = [
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'direction_id' => $request->direction_id,
            'course_id' => $request->course_id,
            'placeholders' => $request->placeholders,
            'settings' => $request->settings,
            'is_active' => $request->boolean('is_active'),
            'is_default' => $request->boolean('is_default'),
        ];

        // Handle new PDF template upload
        if ($request->hasFile('pdf_template')) {
            // Delete old file
            if ($template->pdf_template_path) {
                Storage::disk('public')->delete($template->pdf_template_path);
            }
            $data['pdf_template_path'] = $request->file('pdf_template')->store('certificate-templates', 'public');
        }

        // Handle new thumbnail upload
        if ($request->hasFile('thumbnail')) {
            if ($template->thumbnail_path) {
                Storage::disk('public')->delete($template->thumbnail_path);
            }
            $data['thumbnail_path'] = $request->file('thumbnail')->store('certificate-templates/thumbnails', 'public');
        }

        $template->update($data);

        // If set as default, unset other defaults
        if ($template->is_default) {
            CertificateTemplate::where('id', '!=', $template->id)
                ->update(['is_default' => false]);
        }

        return redirect()->route('admin.certificate-templates.index')
            ->with('success', 'Shablon muvaffaqiyatli yangilandi');
    }

    public function destroy(CertificateTemplate $template)
    {
        // Check if template is used
        if ($template->usage_count > 0) {
            return back()->with('error', 'Bu shablon ishlatilgan sertifikatlar mavjud. O\'chirib bo\'lmaydi.');
        }

        // Delete files
        if ($template->pdf_template_path) {
            Storage::disk('public')->delete($template->pdf_template_path);
        }
        if ($template->thumbnail_path) {
            Storage::disk('public')->delete($template->thumbnail_path);
        }

        $template->delete();

        return back()->with('success', 'Shablon o\'chirildi');
    }

    public function toggleDefault(CertificateTemplate $template)
    {
        if (!$template->is_default) {
            // Unset all other defaults
            CertificateTemplate::where('is_default', true)->update(['is_default' => false]);
            $template->update(['is_default' => true]);
        }

        return back()->with('success', 'Default shablon o\'zgartirildi');
    }

    public function preview(Request $request, CertificateTemplate $template)
    {
        // Generate preview with sample data
        $sampleData = [
            'student_name' => 'Test Foydalanuvchi',
            'course_title' => 'Namuna Kurs Nomi',
            'completion_date' => now()->format('d F Y'),
            'certificate_number' => 'CERT-2024-SAMPLE',
            'qr_code_url' => 'https://via.placeholder.com/200x200?text=QR',
            'verification_url' => url('/verify/SAMPLE'),
        ];

        // Return preview HTML or PDF
        return response()->json([
            'preview_url' => route('admin.certificate-templates.show', $template),
            'data' => $sampleData,
        ]);
    }
}
