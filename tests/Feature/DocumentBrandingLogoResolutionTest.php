<?php

namespace Tests\Feature;

use App\Support\DocumentBranding;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentBrandingLogoResolutionTest extends TestCase
{
    public function test_empty_url_resolves_to_null(): void
    {
        $this->assertNull(DocumentBranding::resolveLogoPath(null));
        $this->assertNull(DocumentBranding::resolveLogoPath(''));
    }

    public function test_default_site_relative_path_resolves_to_a_real_public_file(): void
    {
        $resolved = DocumentBranding::resolveLogoPath('/brand/logo_modulia.png');

        $this->assertNotNull($resolved);
        $this->assertFileExists($resolved);
    }

    public function test_own_storage_url_resolves_to_the_local_file_instead_of_a_remote_fetch(): void
    {
        Storage::disk('public')->put('branding/logo-test.png', 'fake-image-bytes');

        try {
            $uploadedUrl = rtrim(config('app.url'), '/') . '/storage/branding/logo-test.png';

            $resolved = DocumentBranding::resolveLogoPath($uploadedUrl);

            $this->assertNotNull($resolved);
            $this->assertStringNotContainsString('http://', $resolved);
            $this->assertStringNotContainsString('https://', $resolved);
            $this->assertFileExists($resolved);
        } finally {
            Storage::disk('public')->delete('branding/logo-test.png');
        }
    }

    public function test_genuinely_external_url_is_passed_through_unchanged(): void
    {
        $externalUrl = 'https://cdn.example.com/logo.png';

        $this->assertSame($externalUrl, DocumentBranding::resolveLogoPath($externalUrl));
    }

    public function test_missing_local_file_resolves_to_null(): void
    {
        $this->assertNull(DocumentBranding::resolveLogoPath('/brand/does-not-exist.png'));
    }
}
