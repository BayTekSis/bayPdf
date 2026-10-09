<?php

namespace BayPdf\Tests;

use BayPdf\Assets;
use BayPdf\Contracts\ScopeResolver;
use BayPdf\DocumentTypes;
use BayPdf\PdfRenderer;
use BayPdf\TemplateManager;
use Illuminate\Auth\GenericUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

final class AssetScopeTest extends TestCase
{
    use RefreshDatabase;

    private AssetScopeResolver $scopeResolver;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('baypdf.enabled', true);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        app(DocumentTypes::class)->register('report', 'Report', []);
        Gate::define('manage-baypdf', fn ($user): bool => $user->getAuthIdentifier() === 1);
        $this->actingAs(new GenericUser(['id' => 1]));
        $this->scopeResolver = new AssetScopeResolver;
        $this->app->instance(ScopeResolver::class, $this->scopeResolver);
    }

    public function test_disabled_scoping_preserves_legacy_asset_keys_and_reads(): void
    {
        $key = app(Assets::class)->store(UploadedFile::fake()->image('shared.png', 20, 20));

        $this->assertMatchesRegularExpression('#^baypdf/assets/[0-9a-f-]+\.png$#', $key);
        $this->assertSame([$key], app(Assets::class)->all());
        $this->assertNotSame('', app(Assets::class)->bytes($key));
    }

    public function test_each_scope_lists_and_reads_only_its_own_assets(): void
    {
        $this->useScope('organization:a');
        $scopeA = app(Assets::class)->store(UploadedFile::fake()->image('a.png', 20, 20));
        $this->useScope('organization:b');
        $scopeB = app(Assets::class)->store(UploadedFile::fake()->image('b.png', 20, 20));

        $this->assertStringNotContainsString('organization:', $scopeA);
        $this->assertNotSame($scopeA, $scopeB);

        $this->useScope('organization:a');
        $this->assertSame([$scopeA], app(Assets::class)->all());
        $this->assertNotSame('', app(Assets::class)->bytes($scopeA));

        $this->expectException(ValidationException::class);
        app(Assets::class)->bytes($scopeB);
    }

    public function test_asset_catalog_and_read_endpoint_do_not_expose_another_scope(): void
    {
        $this->useScope('organization:a');
        $scopeA = app(Assets::class)->store(UploadedFile::fake()->image('a.png', 20, 20));
        $this->useScope('organization:b');
        $scopeB = app(Assets::class)->store(UploadedFile::fake()->image('b.png', 20, 20));

        $this->useScope('organization:a');
        $this->getJson('/baypdf/api/assets/catalog')->assertOk()->assertExactJson(['assets' => [$scopeA]]);
        $this->getJson('/baypdf/api/assets?key='.urlencode($scopeB))->assertUnprocessable();
    }

    public function test_static_template_asset_from_another_scope_is_rejected_on_save(): void
    {
        $this->useScope('organization:b');
        $foreignAsset = app(Assets::class)->store(UploadedFile::fake()->image('foreign.png', 20, 20));
        $this->useScope('organization:a');
        $draft = app(TemplateManager::class)->create('Scope A report', 'report')->versions->first();

        $this->expectException(ValidationException::class);
        app(TemplateManager::class)->save($draft, $this->imageDocument(['asset' => $foreignAsset]), 1);
    }

    public function test_image_variable_from_another_scope_is_rejected_during_render(): void
    {
        $this->useScope('organization:b');
        $foreignAsset = app(Assets::class)->store(UploadedFile::fake()->image('foreign.png', 20, 20));
        $this->useScope('organization:a');

        $this->expectException(ValidationException::class);
        app(PdfRenderer::class)->render(
            $this->imageDocument(['variable' => 'logo']),
            ['logo' => $foreignAsset],
            [['key' => 'logo', 'label' => 'Logo', 'type' => 'image']],
        );
    }

    public function test_legacy_asset_adoption_is_explicit_scope_safe_and_preserves_the_key(): void
    {
        config()->set('baypdf.scoping.enabled', false);
        $legacyKey = app(Assets::class)->store(UploadedFile::fake()->image('legacy.png', 20, 20));

        $this->useScope('organization:a');
        $this->expectException(ValidationException::class);
        app(Assets::class)->bytes($legacyKey);
    }

    public function test_adopted_legacy_asset_is_available_only_in_the_adopting_scope(): void
    {
        config()->set('baypdf.scoping.enabled', false);
        $legacyKey = app(Assets::class)->store(UploadedFile::fake()->image('legacy.png', 20, 20));

        $this->useScope('organization:a');
        $this->assertSame($legacyKey, app(Assets::class)->adoptLegacy($legacyKey));
        $this->assertSame([$legacyKey], app(Assets::class)->all());
        $this->assertNotSame('', app(Assets::class)->bytes($legacyKey));

        $this->useScope('organization:b');
        $this->expectException(ValidationException::class);
        app(Assets::class)->bytes($legacyKey);
    }

    private function useScope(string $key): void
    {
        config()->set('baypdf.scoping.enabled', true);
        $this->scopeResolver->key = $key;
    }

    private function imageDocument(array $source): array
    {
        return [
            'page' => ['size' => 'A4', 'orientation' => 'portrait'],
            'elements' => [[
                'id' => 'logo',
                'type' => 'image',
                'x' => 10,
                'y' => 10,
                'width' => 30,
                'height' => 20,
                ...$source,
            ]],
        ];
    }
}

final class AssetScopeResolver implements ScopeResolver
{
    public ?string $key = null;

    public function resolve(): ?string
    {
        return $this->key;
    }
}
