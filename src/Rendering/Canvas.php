<?php

namespace BayPdf\Rendering;

use Illuminate\Support\Facades\File;
use ReflectionClass;

/** @internal */
final class Canvas extends \tFPDF
{
    public function __construct(array $page, string $cache)
    {
        $size = match ($page['size']) {
            'A5' => [148, 210],
            'Letter' => [215.9, 279.4],
            default => [210, 297],
        };
        parent::__construct($page['orientation'] === 'landscape' ? 'L' : 'P', 'mm', $size);
        $this->fontpath = str_replace('\\', '/', $cache).'/';
        $this->cMargin = 0;
        $fontSource = dirname((new ReflectionClass(\tFPDF::class))->getFileName()).'/font/unifont/';
        File::ensureDirectoryExists($this->fontpath.'unifont');
        foreach (['' => '', 'B' => '-Bold', 'I' => '-Oblique', 'BI' => '-BoldOblique'] as $style => $suffix) {
            $name = 'DejaVuSans'.$suffix.'.ttf';
            if (! is_file($this->fontpath.'unifont/'.$name)) {
                File::copy($fontSource.$name, $this->fontpath.'unifont/'.$name);
            }
            $this->AddFont('DejaVu', $style, $name, true);
        }
        $this->SetMargins(0, 0, 0);
        $this->SetAutoPageBreak(false);
        $this->SetCompression((bool) config('baypdf.pdf_compression', true));
        $this->AddPage();
    }

    public function imageBytes(string $bytes, float $x, float $y, float $width, float $height): void
    {
        // Decode only previously validated bytes; temporary files never live on a public disk.
        $path = tempnam(dirname($this->fontpath), 'baypdf-');
        try {
            file_put_contents($path, $bytes);
            $size = getimagesize($path);
            $ratio = min($width / $size[0], $height / $size[1]);
            $w = $size[0] * $ratio;
            $h = $size[1] * $ratio;
            $this->Image($path, $x + ($width - $w) / 2, $y + ($height - $h) / 2, $w, $h, $size[2] === IMAGETYPE_PNG ? 'PNG' : 'JPG');
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}
