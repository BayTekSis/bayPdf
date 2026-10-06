<?php

namespace BayPdf\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Template extends Model
{
    protected $table = 'baypdf_templates';

    protected $fillable = ['name', 'document_type'];

    public function versions(): HasMany
    {
        return $this->hasMany(TemplateVersion::class, 'template_id')->orderByDesc('number');
    }
}
