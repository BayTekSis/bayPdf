<?php

namespace BayPdf\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

final class TemplateVersion extends Model
{
    protected $table = 'baypdf_versions';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['document' => 'array', 'variables' => 'array', 'published_at' => 'immutable_datetime', 'number' => 'integer', 'lock_version' => 'integer'];
    }

    protected static function booted(): void
    {
        self::updating(function (self $version): void {
            if ($version->getRawOriginal('published_at') !== null) {
                throw new LogicException('Published versions are immutable; clone a new draft.');
            }
        });
        self::deleting(function (self $version): void {
            if ($version->published_at !== null) {
                throw new LogicException('Published versions cannot be deleted.');
            }
        });
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class, 'template_id');
    }
}
