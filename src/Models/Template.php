<?php

namespace BayPdf\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $document_type
 * @property Collection<int, TemplateVersion> $versions
 */
final class Template extends Model
{
    protected $table = 'baypdf_templates';

    protected $fillable = ['name', 'document_type'];

    /** @return HasMany<TemplateVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(TemplateVersion::class, 'template_id')->orderByDesc('number');
    }
}
