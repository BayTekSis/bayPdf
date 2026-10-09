<?php

namespace BayPdf;

use BayPdf\Support\ScopeContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class Assets
{
    public function __construct(private ScopeContext $scope) {}

    public function store(UploadedFile $file): string
    {
        if (! $file->isValid() || $file->getSize() > config('baypdf.max_upload_kb') * 1024) {
            $this->invalid();
        }
        $this->inspect($file->getRealPath());
        $extension = $file->getMimeType() === 'image/png' ? 'png' : 'jpg';
        $key = $this->writePrefix().'/'.Str::uuid().'.'.$extension;
        $stream = fopen($file->getRealPath(), 'rb');
        try {
            if (! Storage::disk(config('baypdf.disk'))->put($key, $stream, ['visibility' => 'private'])) {
                $this->invalid();
            }
        } finally {
            fclose($stream);
        }

        return $key;
    }

    public function bytes(string $key): string
    {
        $this->assertSafeKey($key);
        $key = $this->readPath($key);

        return $this->readBytes($key);
    }

    /** @return list<string> */
    public function all(): array
    {
        $disk = Storage::disk(config('baypdf.disk'));
        if (! $this->scope->enabled()) {
            $assets = $disk->files($this->basePrefix());
            $assets = array_values(array_filter($assets, fn (string $key): bool => $this->isLegacyKey($key)));
            sort($assets);

            return $assets;
        }

        $prefix = $this->writePrefix();
        $assets = array_values(array_filter(
            $disk->files($prefix),
            fn (string $key): bool => $this->isImagePath($key),
        ));
        foreach ($disk->files($prefix.'/legacy') as $mirror) {
            if ($this->isImagePath($mirror)) {
                $assets[] = $this->basePrefix().'/'.basename($mirror);
            }
        }
        $assets = array_values(array_unique($assets));
        sort($assets);

        return $assets;
    }

    public function adoptLegacy(string $key): string
    {
        if (! $this->scope->enabled()) {
            throw new \LogicException('Legacy assets can be adopted only while BayPdf scoping is enabled.');
        }
        $this->assertSafeKey($key);
        if (! $this->isLegacyKey($key)) {
            $this->invalid();
        }

        $bytes = $this->readBytes($key);
        $mirror = $this->legacyMirror($key);
        if (! Storage::disk(config('baypdf.disk'))->put($mirror, $bytes, ['visibility' => 'private'])) {
            $this->invalid();
        }

        return $key;
    }

    private function readBytes(string $key): string
    {
        $disk = Storage::disk(config('baypdf.disk'));
        if (! $disk->exists($key) || $disk->size($key) > config('baypdf.max_upload_kb') * 1024) {
            $this->invalid();
        }
        $bytes = $disk->get($key);
        $size = @getimagesizefromstring($bytes);
        $this->assertImage($size);

        return $bytes;
    }

    private function readPath(string $key): string
    {
        if (! $this->scope->enabled()) {
            return $key;
        }
        $prefix = $this->writePrefix().'/';
        if (str_starts_with($key, $prefix)) {
            return $key;
        }
        if ($this->isLegacyKey($key)) {
            return $this->legacyMirror($key);
        }

        $this->invalid();
    }

    private function legacyMirror(string $key): string
    {
        return $this->writePrefix().'/legacy/'.basename($key);
    }

    private function writePrefix(): string
    {
        if (! $this->scope->enabled()) {
            return $this->basePrefix();
        }

        return $this->basePrefix().'/scopes/'.hash('sha256', (string) $this->scope->key());
    }

    private function basePrefix(): string
    {
        return trim((string) config('baypdf.asset_prefix'), '/');
    }

    private function assertSafeKey(string $key): void
    {
        $prefix = $this->basePrefix().'/';
        if (! str_starts_with($key, $prefix) || str_contains($key, '..') || str_contains($key, '\\') || str_contains($key, ':') || str_contains($key, "\0")) {
            $this->invalid();
        }
    }

    private function isLegacyKey(string $key): bool
    {
        $relative = substr($key, strlen($this->basePrefix()) + 1);

        return $relative !== '' && ! str_contains($relative, '/') && $this->isImagePath($key);
    }

    private function isImagePath(string $key): bool
    {
        return preg_match('/\.(png|jpe?g)$/i', $key) === 1;
    }

    private function inspect(string $path): void
    {
        $this->assertImage(@getimagesize($path));
    }

    private function assertImage(array|false $size): void
    {
        if (! $size || ! in_array($size[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG], true) || config('baypdf.max_image_pixels') < $size[0] * $size[1]) {
            $this->invalid();
        }
    }

    private function invalid(): never
    {
        throw ValidationException::withMessages(['asset' => 'A permitted PNG or JPEG asset within the configured limits is required.']);
    }
}
