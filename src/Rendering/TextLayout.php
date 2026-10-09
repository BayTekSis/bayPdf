<?php

namespace BayPdf\Rendering;

use Illuminate\Validation\ValidationException;

/** @internal */
final class TextLayout
{
    /** @return list<string> */
    public function lines(Canvas $pdf, string $text, float $width, string $errorKey): array
    {
        $lines = [];
        foreach (explode("\n", str_replace("\r", '', $text)) as $paragraph) {
            $line = '';
            foreach (preg_split('/(\s+)/u', $paragraph, -1, PREG_SPLIT_DELIM_CAPTURE) as $word) {
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
                        throw ValidationException::withMessages([$errorKey => 'Text is too wide for the configured font size and available width.']);
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

    public function lineHeight(float $fontSize): float
    {
        return $fontSize * 25.4 / 72 * 1.25;
    }
}
