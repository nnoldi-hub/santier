<?php

namespace Tests\Unit;

use App\Support\ExportFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ExportFilterTest extends TestCase
{
    public function test_quick_range_last_7d_sets_default_from_and_to(): void
    {
        Carbon::setTestNow('2026-07-09 10:00:00');

        $request = Request::create('/exports/projects', 'GET', [
            'quick_range' => 'last_7d',
        ]);

        $filters = ExportFilter::fromRequest($request);

        $this->assertSame('last_7d', $filters['quick_range']);
        $this->assertSame('2026-07-03', $filters['from']);
        $this->assertSame('2026-07-09', $filters['to']);

        Carbon::setTestNow();
    }

    public function test_explicit_from_and_to_override_quick_range_defaults(): void
    {
        Carbon::setTestNow('2026-07-09 10:00:00');

        $request = Request::create('/exports/projects', 'GET', [
            'quick_range' => 'last_30d',
            'from' => '2026-07-01',
            'to' => '2026-07-05',
        ]);

        $filters = ExportFilter::fromRequest($request);

        $this->assertSame('last_30d', $filters['quick_range']);
        $this->assertSame('2026-07-01', $filters['from']);
        $this->assertSame('2026-07-05', $filters['to']);

        Carbon::setTestNow();
    }

    public function test_invalid_quick_range_is_ignored(): void
    {
        $request = Request::create('/exports/projects', 'GET', [
            'quick_range' => 'last_365d',
        ]);

        $filters = ExportFilter::fromRequest($request);

        $this->assertNull($filters['quick_range']);
        $this->assertNull($filters['from']);
        $this->assertNull($filters['to']);
    }

    public function test_global_search_is_used_and_mirrored_to_q_for_compatibility(): void
    {
        $request = Request::create('/exports/projects', 'GET', [
            'global_search' => 'defecte high',
            'q' => 'legacy value',
        ]);

        $filters = ExportFilter::fromRequest($request);

        $this->assertSame('defecte high', $filters['global_search']);
        $this->assertSame('defecte high', $filters['q']);
    }

    public function test_legacy_q_is_promoted_to_global_search_when_new_key_is_missing(): void
    {
        $request = Request::create('/exports/projects', 'GET', [
            'q' => 'proiect demo',
        ]);

        $filters = ExportFilter::fromRequest($request);

        $this->assertSame('proiect demo', $filters['global_search']);
        $this->assertSame('proiect demo', $filters['q']);
    }
}
