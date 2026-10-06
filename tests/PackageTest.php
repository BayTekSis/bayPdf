<?php

namespace BayPdf\Tests;

final class PackageTest extends TestCase
{
    public function test_package_boots_with_designer_disabled_and_private_local_storage(): void
    {
        $this->assertFalse(config('baypdf.enabled'));
        $this->assertSame('local', config('baypdf.disk'));
        $this->assertSame(['web', 'auth'], config('baypdf.middleware'));
    }
}
