<?php

namespace Tests\Unit;

use App\Services\RichTextSanitizer;
use Tests\TestCase;

class RichTextSanitizerTest extends TestCase
{
    public function test_it_removes_ckeditor_chrome_without_removing_product_content(): void
    {
        $html = '<p>Visible product copy.</p>'
            .'<div class="ck ck-balloon-panel ck-balloon-panel_visible ck-powered-by-balloon">'
            .'<div class="ck ck-powered-by">'
            .'<a href="https://ckeditor.com/">Powered by CKEditor</a>'
            .'</div>'
            .'</div>';

        $cleaned = app(RichTextSanitizer::class)->clean($html);

        $this->assertStringContainsString('Visible product copy.', $cleaned);
        $this->assertStringNotContainsString('ck-balloon-panel', $cleaned);
        $this->assertStringNotContainsString('ck-powered-by', $cleaned);
        $this->assertStringNotContainsString('Powered by CKEditor', $cleaned);
    }
}
