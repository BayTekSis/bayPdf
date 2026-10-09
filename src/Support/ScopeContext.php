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
            $this->whereScope($query, (string) $this->key());
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
            $query->whereHas('template', fn (Builder $templates): Builder => $this->whereScope($templates, (string) $scope));
        }

        return $query;
    }

    /**
     * @param  Builder<Template>  $query
     * @return Builder<Template>
     */
    private function whereScope(Builder $query, string $key): Builder
    {
        $comparison = match ($query->getModel()->getConnection()->getDriverName()) {
            'mysql', 'mariadb' => 'CAST(scope_key AS BINARY) = CAST(? AS BINARY)',
            'sqlite' => 'scope_key COLLATE BINARY = ?',
            'pgsql' => "convert_to(scope_key, 'UTF8') = convert_to(CAST(? AS text), 'UTF8')",
            'sqlsrv' => 'CONVERT(varbinary(max), scope_key) = CONVERT(varbinary(max), CAST(? AS nvarchar(191)))',
            default => throw new LogicException('The database driver does not support exact BayPdf scope comparison.'),
        };

        return $query->where('scope_key', $key)->whereRaw($comparison, [$key]);
    }
}
