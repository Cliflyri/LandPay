<?php

namespace App\Support;

use Illuminate\Support\Str;

class FormattedText
{
    public static function admin(?string $text): string
    {
        $tokens = [
            '<u>' => 'LPUNDERLINEOPEN',
            '</u>' => 'LPUNDERLINECLOSE',
            '<span class="text-large">' => 'LPLARGETEXTOPEN',
            '</span>' => 'LPLARGETEXTCLOSE',
        ];
        $text = str_replace(array_keys($tokens), array_values($tokens), (string) $text);
        $html = Str::markdown($text, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'renderer' => ['soft_break' => "<br>\n"],
        ]);

        return str_replace(array_values($tokens), array_keys($tokens), $html);
    }
}