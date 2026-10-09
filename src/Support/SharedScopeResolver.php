<?php

namespace BayPdf\Support;

use BayPdf\Contracts\ScopeResolver;

/** @internal */
final class SharedScopeResolver implements ScopeResolver
{
    public function resolve(): ?string
    {
        return null;
    }
}
