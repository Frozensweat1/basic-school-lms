<?php

namespace Tests\Unit;

use App\Support\ContentSanitizer;
use Tests\TestCase;

class ContentSanitizerTest extends TestCase
{
    public function test_dangerous_markup_and_urls_are_removed(): void
    {
        $html = app(ContentSanitizer::class)->clean('<p onclick="alert(1)">Hello <script>alert(1)</script><a href="javascript:alert(1)">link</a></p>');

        $this->assertSame('<p>Hello <a>link</a></p>', $html);
        $this->assertStringNotContainsString('script', strtolower($html));
        $this->assertStringNotContainsString('onclick', strtolower($html));
        $this->assertStringNotContainsString('javascript:', strtolower($html));
    }
}
