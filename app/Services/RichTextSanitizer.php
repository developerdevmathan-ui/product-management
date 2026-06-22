<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMXPath;
use HTMLPurifier;
use HTMLPurifier_Config;
use RuntimeException;

class RichTextSanitizer
{
    public function clean(string $html): string
    {
        $html = $this->stripEditorChrome($html);

        $cachePath = storage_path('framework/cache/htmlpurifier');

        if (! is_dir($cachePath) && ! mkdir($cachePath, 0755, true) && ! is_dir($cachePath)) {
            throw new RuntimeException('Unable to create the HTML purifier cache directory.');
        }

        $config = HTMLPurifier_Config::createDefault();
        $config->set('Cache.SerializerPath', $cachePath);
        $config->set('HTML.Allowed', implode(',', [
            'p',
            'br',
            'strong',
            'b',
            'em',
            'i',
            'u',
            's',
            'blockquote',
            'ul',
            'ol',
            'li',
            'a[href|title|target|rel]',
            'h2',
            'h3',
            'h4',
            'pre',
            'code',
        ]));
        $config->set('Attr.AllowedFrameTargets', ['_blank']);
        $config->set('HTML.TargetBlank', true);
        $config->set('HTML.Nofollow', true);
        $config->set('URI.AllowedSchemes', [
            'http' => true,
            'https' => true,
            'mailto' => true,
        ]);

        return trim((new HTMLPurifier($config))->purify($html));
    }

    /**
     * Remove CKEditor UI chrome that can be pasted or persisted with rich text.
     */
    private function stripEditorChrome(string $html): string
    {
        if ($html === '' || (! str_contains($html, 'ck') && ! str_contains($html, 'data-cke'))) {
            return $html;
        }

        $previous = libxml_use_internal_errors(true);

        $document = new DOMDocument('1.0', 'UTF-8');
        $loaded = $document->loadHTML(
            '<?xml encoding="UTF-8"><div id="rich-text-root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        if (! $loaded) {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);

            return $html;
        }

        $xpath = new DOMXPath($document);
        $nodes = $xpath->query(
            '//*[@class and (contains(concat(" ", normalize-space(@class), " "), " ck ") or contains(concat(" ", normalize-space(@class), " "), " ck-"))]'
            .' | //*[@data-cke-filler or @data-cke-widget-wrapper or @data-cke-widget-id or @data-cke-expando]'
        );

        if ($nodes !== false) {
            foreach (iterator_to_array($nodes) as $node) {
                $node->parentNode?->removeChild($node);
            }
        }

        $root = $document->getElementById('rich-text-root');
        $cleaned = $root instanceof DOMElement ? $this->innerHtml($root) : $html;

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $cleaned;
    }

    private function innerHtml(DOMElement $element): string
    {
        $html = '';

        foreach ($element->childNodes as $child) {
            $html .= $element->ownerDocument->saveHTML($child);
        }

        return $html;
    }
}
