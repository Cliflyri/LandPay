<?php
namespace Tests\Unit;
use App\Support\FormattedText;
use PHPUnit\Framework\TestCase;
class ImprovementNoteLinksTest extends TestCase {
 public function test_urls_open_new_tabs_and_preserve_query_strings(): void {
  $html=FormattedText::linkedPlainText('See https://example.com/?a=1&b=2 and www.example.org.');
  $this->assertStringContainsString('href="https://example.com/?a=1&amp;b=2" target="_blank" rel="noopener noreferrer"',$html);
  $this->assertStringContainsString('href="https://www.example.org"',$html);
  $this->assertStringContainsString('www.example.org</a>.',$html);
  $this->assertStringNotContainsString('&amp;amp;',$html);
 }
 public function test_note_html_and_unsafe_schemes_are_not_executable(): void {
  $html=FormattedText::linkedPlainText('<script>alert(1)</script> javascript:alert(1) https://example.com/" onclick="alert(1)');
  $this->assertStringNotContainsString('<script>',$html);
  $this->assertStringNotContainsString('href="javascript:',$html);
  $this->assertStringNotContainsString('" onclick="',$html);
 }
}
