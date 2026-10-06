<?php

namespace BayPdf\Tests;

use BayPdf\DocumentTypes;
use BayPdf\Models\TemplateVersion;
use BayPdf\TemplateManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use LogicException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class TemplateManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(DocumentTypes::class)->register('certificate', 'Certificate', [
            ['key' => 'recipient.name', 'label' => 'Recipient', 'type' => 'text', 'required' => true, 'example' => 'Sample Recipient'],
        ]);
    }

    private function draft(): TemplateVersion
    {
        return app(TemplateManager::class)->create('Certificate', 'certificate')->versions->first();
    }

    private function document(): array
    {
        return ['page' => ['size' => 'A4', 'orientation' => 'portrait'], 'elements' => [
            ['id' => 'name', 'type' => 'variable', 'variable' => 'recipient.name', 'x' => 20, 'y' => 20, 'width' => 150, 'height' => 20],
        ]];
    }

    public function test_draft_save_publish_and_render_work_with_schema_snapshot(): void
    {
        $manager = app(TemplateManager::class);
        $version = $manager->save($this->draft(), $this->document(), 1);
        $version = $manager->publish($version, 2);
        $this->assertNotNull($version->published_at);
        $this->assertSame(3, $version->lock_version);
        $this->assertStringStartsWith('%PDF-', $manager->render($version, ['recipient' => ['name' => 'Ayşe']]));
        $this->assertSame('recipient.name', $version->variables[0]['key']);
    }

    public function test_stale_save_is_rejected(): void
    {
        $manager = app(TemplateManager::class);
        $version = $this->draft();
        $manager->save($version, $this->document(), 1);
        $this->expectException(ConflictHttpException::class);
        $manager->save($version, $this->document(), 1);
    }

    public function test_published_version_cannot_be_edited_via_service(): void
    {
        $manager = app(TemplateManager::class);
        $version = $manager->publish($manager->save($this->draft(), $this->document(), 1), 2);
        $this->expectException(ConflictHttpException::class);
        $manager->save($version, $this->document(), 3);
    }

    public function test_published_version_cannot_be_mutated_via_model(): void
    {
        $manager = app(TemplateManager::class);
        $version = $manager->publish($manager->save($this->draft(), $this->document(), 1), 2);
        $this->expectException(LogicException::class);
        $version->update(['document' => []]);
    }

    public function test_stale_model_cannot_overwrite_a_subsequently_published_version(): void
    {
        $manager = app(TemplateManager::class);
        $version = $manager->save($this->draft(), $this->document(), 1);
        $stale = $version->fresh();
        $manager->publish($version, 2);
        $this->expectException(LogicException::class);
        $stale->update(['document' => []]);
    }

    public function test_clone_preserves_document_and_schema_and_gets_new_version(): void
    {
        $manager = app(TemplateManager::class);
        $version = $manager->save($this->draft(), $this->document(), 1);
        $clone = $manager->cloneDraft($version);
        $this->assertSame(2, $clone->number);
        $this->assertNull($clone->published_at);
        $this->assertSame($version->document, $clone->document);
        $this->assertSame($version->variables, $clone->variables);
    }

    public function test_draft_is_not_used_for_production_generation(): void
    {
        $this->expectException(ValidationException::class);
        app(TemplateManager::class)->render($this->draft(), ['recipient.name' => 'Name']);
    }

    public function test_empty_template_is_not_published(): void
    {
        $this->expectException(ValidationException::class);
        app(TemplateManager::class)->publish($this->draft(), 1);
    }
}
