<?php

namespace App\Support;

class DocumentBranding
{
    /**
     * Resolves a stored document_logo_url (which may be a site-relative path like
     * /storage/branding/xxx.png, meant for browser rendering) into something PDF/XLSX
     * renderers can actually load: an absolute local filesystem path for relative
     * values, or the URL as-is when it's already a full http(s) address.
     */
    public static function resolveLogoPath(?string $url): ?string
    {
        $value = trim((string) $url);

        if ($value === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $value)) {
            return $value;
        }

        $relative = ltrim($value, '/');

        if (str_starts_with($relative, 'storage/')) {
            $storagePath = storage_path('app/public/' . substr($relative, strlen('storage/')));

            if (file_exists($storagePath)) {
                return $storagePath;
            }
        }

        $publicPath = public_path($relative);

        return file_exists($publicPath) ? $publicPath : null;
    }
}
