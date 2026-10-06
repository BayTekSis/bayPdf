<?php

namespace BayPdf;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class Assets
{
    public function store(UploadedFile $file): string
    {
        if (! $file->isValid() || $file->getSize() > config('baypdf.max_upload_kb') * 1024) {
            $this->invalid();
        }
        $this->inspect($file->getRealPath());
        $extension = $file->getMimeType() === 'image/png' ? 'png' : 'jpg';
        $key = trim(config('baypdf.asset_prefix'), '/').'/'.Str::uuid().'.'.$extension;
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
        $prefix = trim(config('baypdf.asset_prefix'), '/').'/';
        if (! str_starts_with($key, $prefix) || str_contains($key, '..') || str_contains($key, '\\') || str_contains($key, ':') || str_contains($key, "\0")) {
            $this->invalid();
        }
        $disk = Storage::disk(config('baypdf.disk'));
        if (! $disk->exists($key) || $disk->size($key) > config('baypdf.max_upload_kb') * 1024) {
            $this->invalid();
        }
        $bytes = $disk->get($key);
        $size = @getimagesizefromstring($bytes);
        $this->assertImage($size);

        return $bytes;
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
