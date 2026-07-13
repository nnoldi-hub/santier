<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_terms_page_renders(): void
    {
        $this->get('/termeni')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Legal/Terms'));
    }

    public function test_privacy_page_renders(): void
    {
        $this->get('/confidentialitate')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Legal/Privacy'));
    }
}
