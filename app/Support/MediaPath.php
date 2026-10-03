<?php

namespace App\Support;

class MediaPath
{
    /**
     * Persist media as a host-independent path when it lives under /storage.
     * External absolute URLs are kept as-is.
     */
    public static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (preg_match('#(?:https?:)?//[^/]+(/storage/.+)$#i', $value, $matches)) {
            return $matches[1];
        }

        if (str_starts_with($value, '/storage/')) {
            return $value;
        }

        if (str_starts_with($value, 'storage/')) {
            return '/'.$value;
        }

        if (str_starts_with($value, 'uploads/')) {
            return '/storage/'.$value;
        }

        return $value;
    }
}
