<?php

namespace BayPdf\Tests;

use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Gate;

final class DesignerCustomPathTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('baypdf.enabled', true);
        $app['config']->set('baypdf.path', 'finance/companies/{company}/baypdf');
    }

    public function test_shell_uses_the_resolved_company_route_as_api_base(): void
    {
        Gate::define('manage-baypdf', fn ($user): bool => true);
        $this->actingAs(new GenericUser(['id' => 1]));

        $this->get('/finance/companies/7/baypdf')->assertOk()
            ->assertSee('data-base="http://localhost/finance/companies/7/baypdf"', false);
    }
}
