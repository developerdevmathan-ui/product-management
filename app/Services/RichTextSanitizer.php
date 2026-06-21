<?php

namespace App\Services;

use HTMLPurifier;
use HTMLPurifier_Config;
use RuntimeException;

class RichTextSanitizer
{
    public function clean(string $html): string
    {
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
}
