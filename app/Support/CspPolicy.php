<?php

namespace App\Support;

class CspPolicy
{
    public static function headerValue(): string
    {
        return implode('; ', self::directives(includeFrameAncestors: true));
    }

    public static function metaContent(): string
    {
        return implode('; ', self::directives(includeFrameAncestors: false));
    }

    /**
     * @return list<string>
     */
    private static function directives(bool $includeFrameAncestors): array
    {
        $directives = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://static.cloudflareinsights.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "style-src-elem 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "img-src 'self' data: blob: https:",
            "font-src 'self' data: https://fonts.gstatic.com",
            "connect-src 'self' https:",
            "media-src 'self' https: blob:",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ];

        if ($includeFrameAncestors) {
            $directives[] = "frame-ancestors 'self'";
        }

        return $directives;
    }
}
