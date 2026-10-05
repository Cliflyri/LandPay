<?php

namespace App\Support;

use Illuminate\Support\Str;

class FormattedText
{

    public static function linkedPlainText(?string $text): string
    {
        $parts = preg_split('~(https?://[^\s<>"\']+|www\.[^\s<>"\']+)~iu', (string) $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        return implode('', array_map(function (string $part, int $index): string {
            if ($index % 2 === 0) return e($part);
            $url = rtrim($part, '.,!?;:');
            $trailing = substr($part, strlen($url));
            $href = str_starts_with(strtolower($url), 'www.') ? 'https://'.$url : $url;
            return '<a href="'.e($href).'" target="_blank" rel="noopener noreferrer">'.e($url).'</a>'.e($trailing);
        }, $parts, array_keys($parts)));
    }

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