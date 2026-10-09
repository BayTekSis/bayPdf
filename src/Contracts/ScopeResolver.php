<?php

namespace BayPdf\Contracts;

interface ScopeResolver
{
    public function resolve(): ?string;
}
