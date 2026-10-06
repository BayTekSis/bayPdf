<?php

namespace BayPdf\Tests;

use BayPdf\DocumentTypes;
use BayPdf\TemplateManager;
use Illuminate\Auth\GenericUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

final class DesignerApiTest extends TestCase
{
    use RefreshDatabase;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('baypdf.enabled', true);
    }

    protected function setUp(): void
    {
        parent::setUp();
        app(DocumentTypes::class)->register('certificate', 'Certificate', []);
        Gate::define('manage-baypdf', fn ($user): bool => $user->getAuthIdentifier() === 1);
    }

    private function login(int $id = 1): void
    {
        $this->actingAs(new GenericUser(['id' => $id]));
    }

    public function test_unauthenticated_requests_are_denied(): void
    {
        $this->getJson('/baypdf/api/catalog')->assertUnauthorized();
        $this->postJson('/baypdf/api/templates', ['name' => 'Test', 'document_type' => 'certificate'])->assertUnauthorized();
        $this->getJson('/baypdf/api/assets?key=baypdf/assets/test.png')->assertUnauthorized();
    }

    public function test_host_gate_protects_every_designer_resource(): void
    {
        $template = app(TemplateManager::class)->create('Protected', 'certificate');
        $id = $template->versions->first()->id;
        $this->login(2);
        $this->getJson('/baypdf')->assertForbidden();
        $this->getJson('/baypdf/api/templates')->assertForbidden();
        $this->postJson("/baypdf/api/versions/{$id}/preview")->assertForbidden();
        $this->postJson('/baypdf/api/assets')->assertForbidden();
    }

    public function test_localized_shell_has_no_inline_executable_bootstrap(): void
    {
        $this->login();
        $this->get('/baypdf?locale=tr')->assertOk()->assertSee('Şablon stüdyosu')->assertSee('noindex, nofollow')->assertSee('vendor/baypdf/designer.js');
        $this->get('/baypdf?locale=de')->assertOk()->assertSee('Vorlagenstudio');
    }

    public function test_unknown_document_type_is_validation_error(): void
    {
        $this->login();
        $this->postJson('/baypdf/api/templates', ['name' => 'Test', 'document_type' => 'camp'])->assertUnprocessable()->assertJsonValidationErrors('document_type');
    }

    public function test_full_version_lifecycle_and_conflicting_save(): void
    {
        $this->login();
        $created = $this->postJson('/baypdf/api/templates', ['name' => 'My template', 'document_type' => 'certificate'])->assertCreated();
        $id = $created->json('versions.0.id');
        $document = ['page' => ['size' => 'A4', 'orientation' => 'portrait'], 'elements' => [
            ['id' => 'text', 'type' => 'text', 'x' => 10, 'y' => 10, 'width' => 100, 'height' => 20, 'content' => 'Hello PDF'],
        ]];
        $this->putJson("/baypdf/api/versions/{$id}", ['lock_version' => 1, 'document' => $document])->assertOk()->assertJsonPath('lock_version', 2);
        $this->putJson("/baypdf/api/versions/{$id}", ['lock_version' => 1, 'document' => $document])->assertConflict();
        $this->postJson("/baypdf/api/versions/{$id}/preview", ['document' => $document])->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->postJson("/baypdf/api/versions/{$id}/publish", ['lock_version' => 2])->assertOk();
        $this->putJson("/baypdf/api/versions/{$id}", ['lock_version' => 3, 'document' => $document])->assertConflict();
        $this->postJson("/baypdf/api/versions/{$id}/clone")->assertCreated()->assertJsonPath('number', 2)->assertJsonPath('published_at', null);
        $this->getJson('/baypdf/api/templates')->assertOk()->assertJsonPath('total', 1);
    }

    public function test_upload_download_and_invalid_file_protection(): void
    {
        $this->login();
        Storage::fake('local');
        $key = $this->post('/baypdf/api/assets', ['file' => UploadedFile::fake()->image('image.png')], ['Accept' => 'application/json'])->assertCreated()->json('key');
        $this->getJson('/baypdf/api/assets?key='.urlencode($key))->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->post('/baypdf/api/assets', ['file' => UploadedFile::fake()->create('script.svg', 2, 'image/svg+xml')], ['Accept' => 'application/json'])->assertUnprocessable();
        $this->getJson('/baypdf/api/assets?key=baypdf/assets/../../secret')->assertUnprocessable();
    }

    public function test_csrf_middleware_rejects_mutations_without_token(): void
    {
        $this->login();
        $this->app['env'] = 'production';
        $this->postJson('/baypdf/api/templates', ['name' => 'Test', 'document_type' => 'certificate'])->assertStatus(419);
    }

    public function test_route_cache_can_be_built(): void
    {
        $this->artisan('route:cache')->assertSuccessful();
        $this->artisan('route:clear')->assertSuccessful();
    }
}
