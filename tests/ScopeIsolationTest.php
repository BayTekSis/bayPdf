<?php

namespace BayPdf\Tests;

use BayPdf\Contracts\ScopeResolver;
use BayPdf\DocumentTypes;
use BayPdf\Models\Template;
use BayPdf\TemplateManager;
use Illuminate\Auth\GenericUser;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use LogicException;

final class ScopeIsolationTest extends TestCase
{
    use RefreshDatabase;

    private TestScopeResolver $scopeResolver;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('baypdf.enabled', true);
    }

    protected function setUp(): void
    {
        parent::setUp();
        app(DocumentTypes::class)->register('report', 'Report', []);
        Gate::define('manage-baypdf', fn ($user): bool => $user->getAuthIdentifier() === 1);
        $this->actingAs(new GenericUser(['id' => 1]));
        $this->scopeResolver = new TestScopeResolver;
        $this->app->instance(ScopeResolver::class, $this->scopeResolver);
    }

    public function test_disabled_scoping_preserves_shared_template_behavior(): void
    {
        $template = app(TemplateManager::class)->create('Shared report', 'report');

        $this->assertNull($template->scope_key);
        $this->getJson('/baypdf/api/templates')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.name', 'Shared report');
    }

    public function test_enabled_scoping_requires_a_server_side_scope(): void
    {
        config()->set('baypdf.scoping.enabled', true);

        $this->expectException(LogicException::class);
        app(TemplateManager::class)->create('Unavailable report', 'report');
    }

    public function test_each_scope_lists_only_its_own_templates(): void
    {
        $this->useScope('organization:a');
        app(TemplateManager::class)->create('Scope A report', 'report');
        $this->useScope('organization:b');
        app(TemplateManager::class)->create('Scope B report', 'report');

        $this->useScope('organization:a');
        $this->getJson('/baypdf/api/templates')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.name', 'Scope A report');

        $this->useScope('organization:b');
        $this->getJson('/baypdf/api/templates')
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.name', 'Scope B report');
    }

    public function test_cross_scope_template_and_version_routes_are_unavailable(): void
    {
        $this->useScope('organization:a');
        $template = app(TemplateManager::class)->create('Scope A report', 'report');
        $version = $template->versions->first();
        $document = $this->document();

        $this->useScope('organization:b');
        $this->getJson("/baypdf/api/templates/{$template->id}")->assertNotFound();
        $this->putJson("/baypdf/api/versions/{$version->id}", ['lock_version' => 1, 'document' => $document])->assertNotFound();
        $this->postJson("/baypdf/api/versions/{$version->id}/preview", ['document' => $document])->assertNotFound();
        $this->postJson("/baypdf/api/versions/{$version->id}/publish", ['lock_version' => 1])->assertNotFound();
        $this->postJson("/baypdf/api/versions/{$version->id}/clone")->assertNotFound();
    }

    public function test_safe_programmatic_api_rejects_a_version_from_another_scope(): void
    {
        $this->useScope('organization:a');
        $version = app(TemplateManager::class)->create('Scope A report', 'report')->versions->first();

        $this->useScope('organization:b');
        $this->expectException(ModelNotFoundException::class);
        app(TemplateManager::class)->preview($version);
    }

    public function test_version_cannot_be_reassigned_to_a_template_in_another_scope(): void
    {
        $this->useScope('organization:a');
        $version = app(TemplateManager::class)->create('Scope A report', 'report')->versions->first();
        $this->useScope('organization:b');
        $otherTemplate = app(TemplateManager::class)->create('Scope B report', 'report');

        $version->template_id = $otherTemplate->id;

        $this->expectException(LogicException::class);
        $version->save();
    }

    public function test_legacy_unscoped_templates_are_hidden_when_scoping_is_enabled(): void
    {
        config()->set('baypdf.scoping.enabled', false);
        $legacy = app(TemplateManager::class)->create('Legacy shared report', 'report');

        $this->useScope('organization:a');
        $this->getJson('/baypdf/api/templates')->assertOk()->assertJsonPath('total', 0);
        $this->getJson("/baypdf/api/templates/{$legacy->id}")->assertNotFound();

        config()->set('baypdf.scoping.enabled', false);
        $this->getJson("/baypdf/api/templates/{$legacy->id}")->assertOk();
    }

    public function test_scope_is_taken_from_the_resolver_instead_of_request_input(): void
    {
        $this->useScope('organization:a');

        $response = $this->postJson('/baypdf/api/templates', [
            'name' => 'Server scoped report',
            'document_type' => 'report',
            'scope_key' => 'organization:b',
        ])->assertCreated();

        $this->assertSame('organization:a', Template::query()->findOrFail($response->json('id'))->scope_key);
    }

    private function useScope(string $key): void
    {
        config()->set('baypdf.scoping.enabled', true);
        $this->scopeResolver->key = $key;
    }

    private function document(): array
    {
        return [
            'page' => ['size' => 'A4', 'orientation' => 'portrait'],
            'elements' => [[
                'id' => 'title',
                'type' => 'text',
                'x' => 10,
                'y' => 10,
                'width' => 100,
                'height' => 20,
                'content' => 'Scoped report',
            ]],
        ];
    }
}

final class TestScopeResolver implements ScopeResolver
{
    public ?string $key = null;

    public function resolve(): ?string
    {
        return $this->key;
    }
}
