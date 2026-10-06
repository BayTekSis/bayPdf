<?php

namespace BayPdf;

use BayPdf\Rendering\Canvas;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;

final class PdfRenderer
{
    public function __construct(
        private DocumentValidator $validator,
        private VariableResolver $resolver,
        private Assets $assets,
    ) {}

    public function render(array $document, array $data = [], array $variables = []): string
    {
        $document = $this->validator->validate($document, $variables);
        $values = $this->resolver->resolve($variables, $data);
        $cache = config('baypdf.font_cache');
        File::ensureDirectoryExists($cache);
        $lock = fopen($cache.'/render.lock', 'c');
        if ($lock === false || ! flock($lock, LOCK_EX)) {
            throw new \RuntimeException('Unable to lock the BayPdf font cache.');
        }
        try {
            $pdf = new Canvas($document['page'], $cache);
            foreach ($document['elements'] as $i => $element) {
                if ($element['hidden'] ?? false) {
                    continue;
                }
                $this->draw($pdf, $element, $values, $i);
            }

            return $pdf->Output('S');
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function draw(Canvas $pdf, array $element, array $values, int $index): void
    {
        $x = (float) $element['x'];
        $y = (float) $element['y'];
        $w = (float) $element['width'];
        $h = (float) $element['height'];
        $value = ! empty($element['variable']) ? $values[$element['variable']] : ($element['content'] ?? '');
        $color = sscanf($element['color'] ?? '#172b29', '#%02x%02x%02x');
        $pdf->SetDrawColor(...$color);
        $pdf->SetTextColor(...$color);
        $pdf->SetLineWidth(0.3);
        switch ($element['type']) {
            case 'text':
            case 'variable':
                $pdf->SetFont('DejaVu', $element['font_style'] ?? '', $element['font_size'] ?? 12);
                $lines = $this->wrap($pdf, $value, $w);
                $lineHeight = ($element['font_size'] ?? 12) * 25.4 / 72 * 1.25;
                if (count($lines) * $lineHeight > $h + 0.01) {
                    throw ValidationException::withMessages(["elements.{$index}" => 'Text does not fit. Increase the element height or reduce its font size.']);
                }
                foreach ($lines as $n => $line) {
                    $pdf->SetXY($x, $y + $n * $lineHeight);
                    $pdf->Cell($w, $lineHeight, $line, 0, 0, $element['align'] ?? 'L');
                }
                break;
            case 'image':
                $key = ! empty($element['variable']) ? $value : ($element['asset'] ?? '');
                if ($key !== '') {
                    $pdf->imageBytes($this->assets->bytes($key), $x, $y, $w, $h);
                }
                break;
            case 'qr':
                if ($value !== '') {
                    if (strlen($value) > 1000) {
                        throw ValidationException::withMessages(["elements.{$index}" => 'QR content must not exceed 1000 bytes.']);
                    }
                    $bytes = (new PngWriter)->write(QrCode::create($value)->setSize(400)->setMargin(16))->getString();
                    $pdf->imageBytes($bytes, $x, $y, $w, $h);
                }
                break;
            case 'line':
                $pdf->Line($x, $y, $x + $w, $y + $h);
                break;
            case 'rectangle':
                if (! empty($element['fill'])) {
                    $pdf->SetFillColor(...sscanf($element['fill'], '#%02x%02x%02x'));
                }
                $pdf->Rect($x, $y, $w, $h, empty($element['fill']) ? 'D' : 'DF');
        }
    }

    private function wrap(Canvas $pdf, string $text, float $width): array
    {
        $lines = [];
        foreach (explode("\n", str_replace("\r", '', $text)) as $paragraph) {
            $line = '';
            foreach (preg_split('/(\\s+)/u', $paragraph, -1, PREG_SPLIT_DELIM_CAPTURE) as $word) {
                if ($pdf->GetStringWidth($line.$word) <= $width) {
                    $line .= $word;

                    continue;
                }
                if (trim($line) !== '') {
                    $lines[] = rtrim($line);
                    $line = '';
                }
                foreach (mb_str_split(ltrim($word)) as $char) {
                    if ($pdf->GetStringWidth($char) > $width) {
                        throw ValidationException::withMessages(['elements' => 'Text element is too narrow for its font size.']);
                    }
                    if ($pdf->GetStringWidth($line.$char) > $width) {
                        $lines[] = $line;
                        $line = '';
                    }
                    $line .= $char;
                }
            }
            $lines[] = rtrim($line);
        }

        return $lines;
    }
}
