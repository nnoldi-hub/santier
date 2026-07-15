<?php

namespace App\Support;

use App\Models\AppSetting;

class DocumentBranding
{
    /**
     * Assembles the full branding array for a tenant's PDFs/emails: platform
     * defaults layered with tenant overrides (AppSetting::allForTenant), the
     * logo resolved to a renderer-loadable path, and white-label applied - on
     * an enterprise-plan tenant that hasn't customized its own branding yet,
     * the Modulia name/logo defaults are blanked instead of leaking through.
     */
    public static function resolve(int $tenantId): array
    {
        $defaults = config('platform.defaults', []);
        $branding = AppSetting::allForTenant($defaults, $tenantId);
        $whiteLabel = PricingPlan::tenantHasFeature($tenantId, 'white_label');

        if ($whiteLabel) {
            foreach (['company_name', 'app_name', 'document_logo_url'] as $key) {
                if (($branding[$key] ?? null) === ($defaults[$key] ?? null)) {
                    $branding[$key] = '';
                }
            }
        }

        $branding['document_logo_url'] = self::resolveLogoPath($branding['document_logo_url'] ?? null) ?? '';
        $branding['white_label'] = $whiteLabel;

        $template = (string) ($branding['document_template'] ?? 'classic');
        $templatesAllowed = PricingPlan::tenantHasFeature($tenantId, 'document_templates');
        $branding['document_template'] = ($templatesAllowed && in_array($template, ['classic', 'modern'], true))
            ? $template
            : 'classic';

        return $branding;
    }

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
