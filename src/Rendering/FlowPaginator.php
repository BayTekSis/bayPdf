<?php

namespace BayPdf\Rendering;

use Illuminate\Validation\ValidationException;

/** @internal */
final class FlowPaginator
{
    public function __construct(private TextLayout $text) {}

    public function plan(Canvas $pdf, array $document, array $values): array
    {
        $flow = $document['flow'];
        $table = $flow['table'];
        $rows = $values[$table['source']];
        $headerHeight = $this->tableHeight($pdf, $table, array_column($table['columns'], 'label'), $table['header'], 'flow.table.columns');
        $rowPlans = [];
        foreach ($rows as $index => $row) {
            $rowPlans[] = [
                'values' => $row,
                'height' => $this->tableHeight(
                    $pdf,
                    $table,
                    array_map(fn (array $column): string => $row[$column['field']], $table['columns']),
                    $table['row'],
                    "data.{$table['source']}.{$index}",
                ),
                'index' => $index,
            ];
        }

        $pages = [['header' => null, 'rows' => [], 'trailing' => []]];
        $page = 0;
        $y = (float) $flow['first_top'];
        $this->placeHeader($pages[$page], $y, $headerHeight, (float) $flow['bottom']);

        foreach ($rowPlans as $rowPlan) {
            if ($y + $rowPlan['height'] > (float) $flow['bottom'] + 0.001) {
                $page = $this->newPage($pages);
                $y = (float) $flow['continuation_top'];
                if ($table['repeat_header']) {
                    $this->placeHeader($pages[$page], $y, $headerHeight, (float) $flow['bottom']);
                }
            }
            if ($y + $rowPlan['height'] > (float) $flow['bottom'] + 0.001) {
                throw ValidationException::withMessages([
                    "data.{$table['source']}.{$rowPlan['index']}" => 'The collection row is taller than the usable page area. Reduce its content or font size.',
                ]);
            }
            $pages[$page]['rows'][] = [...$rowPlan, 'y' => $y];
            $y += $rowPlan['height'];
        }

        foreach ($flow['trailing'] as $index => $element) {
            $gap = (float) ($element['gap_before'] ?? $flow['gap']);
            $height = (float) $element['height'];
            $candidate = $y + $gap;
            if ($candidate + $height > (float) $flow['bottom'] + 0.001) {
                $page = $this->newPage($pages);
                $candidate = (float) $flow['continuation_top'];
            }
            if ($candidate + $height > (float) $flow['bottom'] + 0.001) {
                throw ValidationException::withMessages([
                    "flow.trailing.{$index}" => 'The trailing block is taller than the usable page area.',
                ]);
            }
            $pages[$page]['trailing'][] = ['element' => $element, 'y' => $candidate, 'index' => $index];
            $y = $candidate + $height;
        }

        return $pages;
    }

    private function placeHeader(array &$page, float &$y, float $height, float $bottom): void
    {
        if ($y + $height > $bottom + 0.001) {
            throw ValidationException::withMessages(['flow.table' => 'The table header does not fit in the usable page area.']);
        }
        $page['header'] = ['y' => $y, 'height' => $height];
        $y += $height;
    }

    private function newPage(array &$pages): int
    {
        if (count($pages) >= config('baypdf.limits.max_generated_pages')) {
            throw ValidationException::withMessages(['document' => 'The generated page limit was exceeded.']);
        }
        $pages[] = ['header' => null, 'rows' => [], 'trailing' => []];

        return array_key_last($pages);
    }

    private function tableHeight(Canvas $pdf, array $table, array $values, array $style, string $errorKey): float
    {
        $fontSize = (float) $style['font_size'];
        $padding = (float) $style['padding'];
        $pdf->SetFont('DejaVu', $style['font_style'], $fontSize);
        $lineHeight = $this->text->lineHeight($fontSize);
        $height = $lineHeight + 2 * $padding;
        foreach ($table['columns'] as $index => $column) {
            $lines = $this->text->lines($pdf, (string) $values[$index], (float) $column['width'] - 2 * $padding, $errorKey);
            $height = max($height, count($lines) * $lineHeight + 2 * $padding);
        }

        return $height;
    }
}
