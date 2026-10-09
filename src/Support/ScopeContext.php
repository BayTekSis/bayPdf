<?php

namespace BayPdf\Support;

use BayPdf\Contracts\ScopeResolver;
use BayPdf\Models\Template;
use BayPdf\Models\TemplateVersion;
use Illuminate\Database\Eloquent\Builder;
use LogicException;

/** @internal */
final class ScopeContext
{
    public function __construct(private ScopeResolver $resolver) {}

    public function enabled(): bool
    {
        return (bool) config('baypdf.scoping.enabled', false);
    }

    public function key(): ?string
    {
        if (! $this->enabled()) {
            return null;
        }

        $key = $this->resolver->resolve();
        if ($key === null || $key === '' || strlen($key) > 191 || preg_match('/[\x00-\x1F\x7F]/', $key) === 1) {
            throw new LogicException('BayPdf scoping is enabled, but the scope resolver did not return a valid opaque scope identifier.');
        }

        return $key;
    }

    /**
     * @param  Builder<Template>  $query
     * @return Builder<Template>
     */
    public function templates(Builder $query): Builder
    {
        if ($this->enabled()) {
            $query->where('scope_key', $this->key());
        }

        return $query;
    }

    /**
     * @param  Builder<TemplateVersion>  $query
     * @return Builder<TemplateVersion>
     */
    public function versions(Builder $query): Builder
    {
        if ($this->enabled()) {
            $scope = $this->key();
            $query->whereHas('template', fn (Builder $templates): Builder => $templates->where('scope_key', $scope));
        }

        return $query;
    }
}
