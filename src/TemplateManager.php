<?php

namespace BayPdf;

use BayPdf\Models\Template;
use BayPdf\Models\TemplateVersion;
use BayPdf\Support\ScopeContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class TemplateManager
{
    public function __construct(
        private DocumentTypes $types,
        private DocumentValidator $validator,
        private PdfRenderer $renderer,
        private ScopeContext $scope,
    ) {}

    public function create(string $name, string $documentType): Template
    {
        Validator::make(['name' => $name], ['name' => ['required', 'string', 'max:120']])->validate();
        $type = $this->types->get($documentType);

        $scopeKey = $this->scope->key();

        return DB::transaction(function () use ($name, $documentType, $type, $scopeKey): Template {
            $template = Template::create(['name' => $name, 'document_type' => $documentType, 'scope_key' => $scopeKey]);
            $template->versions()->create(['number' => 1, 'document' => $this->validator->blank(), 'variables' => $type['variables']]);

            return $template->load('versions');
        });
    }

    public function save(TemplateVersion $version, array $document, int $expectedLock): TemplateVersion
    {
        return DB::transaction(function () use ($version, $document, $expectedLock): TemplateVersion {
            $current = $this->findVersion($version->id, true);
            $this->assertDraft($current, $expectedLock);
            $current->document = $this->validator->validate($document, $current->variables);
            $current->lock_version++;
            $current->save();

            return $current;
        });
    }

    public function publish(TemplateVersion $version, int $expectedLock): TemplateVersion
    {
        return DB::transaction(function () use ($version, $expectedLock): TemplateVersion {
            $current = $this->findVersion($version->id, true);
            $this->assertDraft($current, $expectedLock);
            if (($current->document['schema_version'] ?? 1) !== 2 && count($current->document['elements']) === 0) {
                throw ValidationException::withMessages(['document' => 'Add at least one element before publishing.']);
            }
            $this->renderPreview($current);
            $current->published_at = CarbonImmutable::now();
            $current->lock_version++;
            $current->save();

            return $current;
        });
    }

    public function cloneDraft(TemplateVersion $source): TemplateVersion
    {
        return DB::transaction(function () use ($source): TemplateVersion {
            $source = $this->findVersion($source->id, true);
            $template = $this->scope->templates(Template::query())->lockForUpdate()->findOrFail($source->template_id);

            return $template->versions()->create([
                'number' => $template->versions()->max('number') + 1,
                'document' => $source->document,
                'variables' => $source->variables,
            ]);
        });
    }

    public function preview(TemplateVersion $version): string
    {
        return $this->renderPreview($this->findVersion($version->id));
    }

    public function render(TemplateVersion $version, array $data): string
    {
        $version = $this->findVersion($version->id);
        if ($version->published_at === null) {
            throw ValidationException::withMessages(['version' => 'Publish the version before generating documents.']);
        }

        return $this->renderer->render($version->document, $data, $version->variables);
    }

    private function renderPreview(TemplateVersion $version): string
    {
        return $this->renderer->render($version->document, $this->types->examples($version->variables), $version->variables);
    }

    private function findVersion(int $id, bool $lock = false): TemplateVersion
    {
        $query = $this->scope->versions(TemplateVersion::query());
        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->findOrFail($id);
    }

    private function assertDraft(TemplateVersion $version, int $expectedLock): void
    {
        if ($version->published_at !== null || $version->lock_version !== $expectedLock) {
            throw new ConflictHttpException('The version changed or was published. Reload before editing.');
        }
    }
}
