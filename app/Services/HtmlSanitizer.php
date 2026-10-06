<?php

namespace App\Services;

use HTMLPurifier;
use HTMLPurifier_Config;

/** Cleans admin-authored rich text (CMS pages) before it is stored. */
class HtmlSanitizer
{
    private ?HTMLPurifier $purifier = null;

    public function clean(?string $html): string
    {
        if (blank($html)) {
            return '';
        }

        return $this->purifier()->purify($html);
    }

    private function purifier(): HTMLPurifier
    {
        if ($this->purifier) {
            return $this->purifier;
        }

        $config = HTMLPurifier_Config::createDefault();
        $cache = storage_path('framework/cache/htmlpurifier');
        if (! is_dir($cache)) {
            @mkdir($cache, 0755, true);
        }
        $config->set('Cache.SerializerPath', $cache);
        $config->set('HTML.Allowed', 'h2,h3,h4,p,br,strong,b,em,i,u,s,blockquote,ul,ol,li,a[href|title|target|rel],img[src|alt|width|height],hr,table,thead,tbody,tr,th,td,span,div');
        $config->set('Attr.AllowedFrameTargets', ['_blank']);
        $config->set('HTML.TargetBlank', true);
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true, 'tel' => true]);
        $config->set('AutoFormat.RemoveEmpty', true);

        return $this->purifier = new HTMLPurifier($config);
    }
}
