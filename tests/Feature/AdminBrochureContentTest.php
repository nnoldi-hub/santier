<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\BrochureContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBrochureContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_update_brochure_content(): void
    {
        $superadmin = $this->createSuperadmin('superadmin@santier.local');

        $response = $this->actingAs($superadmin)->patch(route('admin.settings.update'), [
            'app_name' => 'Modulia',
            'company_name' => 'Modulia SRL',
            'support_email' => 'suport@modulia.ro',
            'sales_email' => 'vanzari@modulia.ro',
            'trial_days' => 14,
            'brochure_cover_title' => 'Titlu custom',
            'brochure_cover_subtitle' => 'Subtitlu custom',
            'brochure_closing_title' => 'Inchidere custom',
            'brochure_closing_text' => 'Text de inchidere custom',
            'brochure_pain_points' => [
                ['title' => 'Problema noua', 'text' => 'Descriere problema noua'],
            ],
            'brochure_features' => [
                ['title' => 'Functie noua', 'text' => 'Descriere functie noua'],
            ],
            'brochure_how_it_works' => [
                ['title' => 'Pas nou', 'text' => 'Descriere pas nou'],
            ],
        ]);

        $response->assertRedirect();

        $content = BrochureContent::current();
        $this->assertSame('Titlu custom', $content['cover_title']);
        $this->assertSame('Subtitlu custom', $content['cover_subtitle']);
        $this->assertSame('Inchidere custom', $content['closing_title']);
        $this->assertSame('Text de inchidere custom', $content['closing_text']);
        $this->assertSame([['title' => 'Problema noua', 'text' => 'Descriere problema noua']], $content['pain_points']);
        $this->assertSame([['title' => 'Functie noua', 'text' => 'Descriere functie noua']], $content['features']);
        $this->assertSame([['title' => 'Pas nou', 'text' => 'Descriere pas nou']], $content['how_it_works']);
    }

    public function test_non_admin_cannot_update_brochure_content(): void
    {
        $user = User::factory()->create([
            'tenant_id' => 1,
            'current_tenant_id' => 1,
            'is_superadmin' => false,
            'onboarding_step' => 3,
            'onboarding_completed_at' => now(),
        ]);

        $response = $this->actingAs($user)->patch(route('admin.settings.update'), [
            'app_name' => 'Modulia',
            'company_name' => 'Modulia SRL',
            'support_email' => 'suport@modulia.ro',
            'sales_email' => 'vanzari@modulia.ro',
            'trial_days' => 14,
            'brochure_cover_title' => 'Nu ar trebui sa fie salvat',
        ]);

        $response->assertForbidden();
        $this->assertNotSame('Nu ar trebui sa fie salvat', BrochureContent::current()['cover_title']);
    }

    public function test_default_content_is_used_when_nothing_was_customized(): void
    {
        $defaults = BrochureContent::defaults();

        $this->assertSame($defaults, BrochureContent::current());
    }

    private function createSuperadmin(string $email): User
    {
        return User::factory()->create([
            'email' => $email,
            'tenant_id' => 1,
            'current_tenant_id' => 1,
            'is_superadmin' => true,
            'onboarding_step' => 3,
            'onboarding_completed_at' => now(),
        ]);
    }
}
