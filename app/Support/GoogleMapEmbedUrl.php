<?php

namespace App\Support;

class GoogleMapEmbedUrl
{
    public static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        if (preg_match('/<iframe\b[^>]*\bsrc=(["\'])(.*?)\1/is', $value, $matches)) {
            $value = html_entity_decode($matches[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return trim($value);
    }
}
