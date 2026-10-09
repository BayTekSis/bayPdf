<?php

namespace BayPdf\Http;

use BayPdf\Assets;
use BayPdf\DocumentTypes;
use BayPdf\Models\Template;
use BayPdf\Models\TemplateVersion;
use BayPdf\PdfRenderer;
use BayPdf\Support\ScopeContext;
use BayPdf\TemplateManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

final class DesignerController
{
    public function __construct(private TemplateManager $templates, private ScopeContext $scope) {}

    public function index(): View
    {
        return view('baypdf::designer');
    }

    public function catalog(): JsonResponse
    {
        return response()->json(['types' => app(DocumentTypes::class)->all()]);
    }

    public function templates(): JsonResponse
    {
        $templates = $this->scope->templates(Template::query());

        return response()->json($templates->withCount('versions')->latest('id')->paginate(30));
    }

    public function show(Template $template): JsonResponse
    {
        return response()->json($template->load('versions'));
    }

    public function store(DesignerRequest $request): JsonResponse
    {
        return response()->json($this->templates->create($request->validated('name'), $request->validated('document_type')), 201);
    }

    public function update(DesignerRequest $request, TemplateVersion $version): JsonResponse
    {
        return response()->json($this->templates->save($version, $request->validated('document'), $request->integer('lock_version')));
    }

    public function publish(DesignerRequest $request, TemplateVersion $version): JsonResponse
    {
        return response()->json($this->templates->publish($version, $request->integer('lock_version')));
    }

    public function clone(TemplateVersion $version): JsonResponse
    {
        return response()->json($this->templates->cloneDraft($version), 201);
    }

    public function preview(DesignerRequest $request, TemplateVersion $version): Response
    {
        $pdf = $request->has('document')
            ? app(PdfRenderer::class)->render($request->validated('document'), app(DocumentTypes::class)->examples($version->variables), $version->variables)
            : $this->templates->preview($version);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="baypdf-preview.pdf"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function upload(DesignerRequest $request): JsonResponse
    {
        return response()->json(['key' => app(Assets::class)->store($request->file('file'))], 201);
    }

    public function asset(Request $request): Response
    {
        $validated = $request->validate(['key' => ['required', 'string', 'max:255']]);
        $bytes = app(Assets::class)->bytes($validated['key']);
        $info = getimagesizefromstring($bytes);

        return response($bytes, 200, ['Content-Type' => $info['mime'], 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
