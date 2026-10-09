<?php

namespace BayPdf;

use BayPdf\Rendering\Canvas;
use BayPdf\Rendering\FlowPaginator;
use BayPdf\Rendering\TextLayout;
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
        private TextLayout $text,
        private FlowPaginator $paginator,
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
            if (($document['schema_version'] ?? 1) === 2) {
                $this->drawFlowDocument($pdf, $document, $values);
            } else {
                foreach ($document['elements'] as $index => $element) {
                    if (! ($element['hidden'] ?? false)) {
                        $this->draw($pdf, $element, $values, "elements.{$index}", 1, 1);
                    }
                }
            }

            return $pdf->Output('S');
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function drawFlowDocument(Canvas $pdf, array $document, array $values): void
    {
        $pages = $this->paginator->plan($pdf, $document, $values);
        $total = count($pages);
        $table = $document['flow']['table'];
        foreach ($pages as $pageIndex => $plan) {
            $current = $pageIndex + 1;
            if ($current > 1) {
                $pdf->AddPage();
            }
            foreach ($document['elements'] as $index => $element) {
                if (! ($element['hidden'] ?? false) && $this->visibleOnPage($element['repeat'], $current, $total)) {
                    $this->draw($pdf, $element, $values, "elements.{$index}", $current, $total);
                }
            }
            if ($plan['header'] !== null) {
                $this->drawTableRow(
                    $pdf,
                    $table,
                    array_column($table['columns'], 'label'),
                    $table['header'],
                    $plan['header']['y'],
                    $plan['header']['height'],
                    'flow.table.columns',
                );
            }
            foreach ($plan['rows'] as $row) {
                $this->drawTableRow(
                    $pdf,
                    $table,
                    array_map(fn (array $column): string => $row['values'][$column['field']], $table['columns']),
                    $table['row'],
                    $row['y'],
                    $row['height'],
                    "data.{$table['source']}.{$row['index']}",
                );
            }
            foreach ($plan['trailing'] as $trailing) {
                if (! ($trailing['element']['hidden'] ?? false)) {
                    $this->draw(
                        $pdf,
                        [...$trailing['element'], 'y' => $trailing['y']],
                        $values,
                        "flow.trailing.{$trailing['index']}",
                        $current,
                        $total,
                    );
                }
            }
        }
    }

    private function visibleOnPage(string $repeat, int $current, int $total): bool
    {
        return match ($repeat) {
            'all' => true,
            'continuation' => $current > 1,
            'last' => $current === $total,
            default => $current === 1,
        };
    }

    private function draw(Canvas $pdf, array $element, array $values, string $errorKey, int $currentPage, int $totalPages): void
    {
        $x = (float) $element['x'];
        $y = (float) $element['y'];
        $width = (float) $element['width'];
        $height = (float) $element['height'];
        $value = ! empty($element['variable']) ? $values[$element['variable']] : ($element['content'] ?? '');
        if ($element['type'] === 'page_number') {
            $value = str_replace(['{current}', '{total}'], [(string) $currentPage, (string) $totalPages], $element['content'] ?: 'Page {current} / {total}');
        }
        $color = sscanf($element['color'] ?? '#172b29', '#%02x%02x%02x');
        $pdf->SetDrawColor(...$color);
        $pdf->SetTextColor(...$color);
        $pdf->SetLineWidth(0.3);
        switch ($element['type']) {
            case 'text':
            case 'variable':
            case 'page_number':
                $fontSize = (float) ($element['font_size'] ?? 12);
                $pdf->SetFont('DejaVu', $element['font_style'] ?? '', $fontSize);
                $lines = $this->text->lines($pdf, (string) $value, $width, $errorKey);
                $lineHeight = $this->text->lineHeight($fontSize);
                if (count($lines) * $lineHeight > $height + 0.01) {
                    throw ValidationException::withMessages([$errorKey => 'Text does not fit. Increase the element height or reduce its font size.']);
                }
                foreach ($lines as $lineIndex => $line) {
                    $pdf->SetXY($x, $y + $lineIndex * $lineHeight);
                    $pdf->Cell($width, $lineHeight, $line, 0, 0, $element['align'] ?? 'L');
                }
                break;
            case 'image':
                $key = ! empty($element['variable']) ? $value : ($element['asset'] ?? '');
                if ($key !== '') {
                    $pdf->imageBytes($this->assets->bytes((string) $key), $x, $y, $width, $height);
                }
                break;
            case 'qr':
                if ($value !== '') {
                    if (strlen((string) $value) > 1000) {
                        throw ValidationException::withMessages([$errorKey => 'QR content must not exceed 1000 bytes.']);
                    }
                    $bytes = (new PngWriter)->write(QrCode::create((string) $value)->setSize(400)->setMargin(16))->getString();
                    $pdf->imageBytes($bytes, $x, $y, $width, $height);
                }
                break;
            case 'line':
                $pdf->Line($x, $y, $x + $width, $y + $height);
                break;
            case 'rectangle':
                if (! empty($element['fill'])) {
                    $pdf->SetFillColor(...sscanf($element['fill'], '#%02x%02x%02x'));
                }
                $pdf->Rect($x, $y, $width, $height, empty($element['fill']) ? 'D' : 'DF');
        }
    }

    private function drawTableRow(Canvas $pdf, array $table, array $values, array $style, float $y, float $height, string $errorKey): void
    {
        $fontSize = (float) $style['font_size'];
        $padding = (float) $style['padding'];
        $lineHeight = $this->text->lineHeight($fontSize);
        $color = sscanf($style['color'], '#%02x%02x%02x');
        $pdf->SetFont('DejaVu', $style['font_style'], $fontSize);
        $pdf->SetTextColor(...$color);
        $pdf->SetDrawColor(...$color);
        $pdf->SetLineWidth(0.3);
        if ($style['fill'] !== null) {
            $pdf->SetFillColor(...sscanf($style['fill'], '#%02x%02x%02x'));
        }

        $x = (float) $table['x'];
        foreach ($table['columns'] as $index => $column) {
            $width = (float) $column['width'];
            $mode = $style['border'] ? ($style['fill'] !== null ? 'DF' : 'D') : ($style['fill'] !== null ? 'F' : '');
            if ($mode !== '') {
                $pdf->Rect($x, $y, $width, $height, $mode);
            }
            $lines = $this->text->lines($pdf, (string) $values[$index], $width - 2 * $padding, $errorKey);
            foreach ($lines as $lineIndex => $line) {
                $pdf->SetXY($x + $padding, $y + $padding + $lineIndex * $lineHeight);
                $pdf->Cell($width - 2 * $padding, $lineHeight, $line, 0, 0, $column['align']);
            }
            $x += $width;
        }
    }
}
