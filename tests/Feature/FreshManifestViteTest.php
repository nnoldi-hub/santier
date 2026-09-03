<?php

namespace Tests\Feature;

use App\Support\FreshManifestVite;
use Illuminate\Foundation\Vite;
use Illuminate\Support\Facades\File;
use ReflectionMethod;
use Tests\TestCase;

class FreshManifestViteTest extends TestCase
{
    private const FIXTURE_BUILD_DIR = 'fresh-manifest-vite-test-fixture';

    protected function tearDown(): void
    {
        File::deleteDirectory(public_path(self::FIXTURE_BUILD_DIR));

        parent::tearDown();
    }

    public function test_the_container_resolves_the_fresh_manifest_subclass(): void
    {
        $this->assertInstanceOf(FreshManifestVite::class, app(Vite::class));
    }

    public function test_manifest_changes_on_disk_are_picked_up_without_a_new_process(): void
    {
        $manifestPath = public_path(self::FIXTURE_BUILD_DIR . '/manifest.json');
        File::ensureDirectoryExists(dirname($manifestPath));

        File::put($manifestPath, json_encode([
            'resources/js/app.js' => ['file' => 'assets/app-OLD123.js'],
        ]));

        $vite = app(Vite::class);
        $manifestMethod = new ReflectionMethod($vite, 'manifest');
        $manifestMethod->setAccessible(true);

        $first = $manifestMethod->invoke($vite, self::FIXTURE_BUILD_DIR);
        $this->assertSame('assets/app-OLD123.js', $first['resources/js/app.js']['file']);

        // Simulates a deploy replacing the manifest while this same PHP
        // process/worker stays alive - the whole point of the subclass.
        File::put($manifestPath, json_encode([
            'resources/js/app.js' => ['file' => 'assets/app-NEW456.js'],
        ]));

        $second = $manifestMethod->invoke($vite, self::FIXTURE_BUILD_DIR);
        $this->assertSame('assets/app-NEW456.js', $second['resources/js/app.js']['file']);
    }
}
