<?php

namespace Tests\Unit;

use App\Support\FormattedText;
use PHPUnit\Framework\TestCase;

class FormattedTextTest extends TestCase
{
    public function test_it_renders_supported_formatting_and_strips_unsafe_html(): void
    {
        $html = FormattedText::admin('**Bold** <u>underlined</u> <span class="text-large">large</span> <script>alert(1)</script> [bad](javascript:alert(1))');

        $this->assertStringContainsString('<strong>Bold</strong>', $html);
        $this->assertStringContainsString('<u>underlined</u>', $html);
        $this->assertStringContainsString('<span class="text-large">large</span>', $html);
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('href="javascript:', $html);
    }
}