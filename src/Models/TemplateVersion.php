<?php

namespace BayPdf\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * @property int $id
 * @property int $template_id
 * @property int $number
 * @property int $lock_version
 * @property array $document
 * @property array $variables
 * @property CarbonImmutable|null $published_at
 */
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
            if ($version->getRawOriginal('published_at') !== null || static::query()->whereKey($version->id)->whereNotNull('published_at')->exists()) {
                throw new LogicException('Published versions are immutable; clone a new draft.');
            }
        });
        self::deleting(function (self $version): void {
            if ($version->published_at !== null || static::query()->whereKey($version->id)->whereNotNull('published_at')->exists()) {
                throw new LogicException('Published versions cannot be deleted.');
            }
        });
    }

    /** @return BelongsTo<Template, $this> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class, 'template_id');
    }
}
