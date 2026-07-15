<?php

namespace Tests\Feature;

use App\Mail\QuoteSentMail;
use App\Models\Client;
use App\Models\Project;
use App\Models\Quote;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\IamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PublicQuoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_quote_page_requires_a_valid_signature(): void
    {
        $user = $this->createOnboardedUser('pro');
        $quote = $this->createQuote($user);

        $this->get("/oferte/{$quote->id}/vizualizare")->assertStatus(403);
    }

    public function test_public_quote_page_renders_with_a_valid_signature(): void
    {
        $user = $this->createOnboardedUser('pro');
        $quote = $this->createQuote($user);

        $url = URL::signedRoute('public.quotes.show', ['quote' => $quote->id]);

        $this->get($url)->assertStatus(200);
    }

    public function test_signature_does_not_validate_for_a_different_quote(): void
    {
        $user = $this->createOnboardedUser('pro');
        $quoteA = $this->createQuote($user);
        $quoteB = $this->createQuote($user);

        $url = URL::signedRoute('public.quotes.show', ['quote' => $quoteA->id]);
        $tamperedUrl = str_replace("/oferte/{$quoteA->id}/", "/oferte/{$quoteB->id}/", $url);

        $this->get($tamperedUrl)->assertStatus(403);
    }

    public function test_sending_a_quote_includes_the_public_url_in_the_mail(): void
    {
        Mail::fake();
        $user = $this->createOnboardedUser('pro');
        $quote = $this->createQuote($user);

        $this->actingAs($user)->patch("/quotes/{$quote->id}/send");

        Mail::assertSent(QuoteSentMail::class, function (QuoteSentMail $mail) {
            return str_contains($mail->publicUrl, '/oferte/') && str_contains($mail->publicUrl, 'signature=');
        });
    }

    private function createOnboardedUser(string $plan): User
    {
        $user = User::factory()->create([
            'onboarding_step' => 3,
            'onboarding_completed_at' => now(),
            'billing_plan' => $plan,
        ]);

        $this->seed(IamSeeder::class);
        Tenant::find(1)?->update(['billing_plan' => $plan]);

        return $user->fresh();
    }

    private function createQuote(User $user): Quote
    {
        $client = Client::create([
            'tenant_id' => 1,
            'name' => 'Client Test SRL',
            'type' => 'company',
            'email' => 'client@test.ro',
            'active' => true,
        ]);

        $project = Project::create([
            'tenant_id' => 1,
            'client_id' => $client->id,
            'created_by' => $user->id,
            'name' => 'Proiect Oferta Publica',
            'status' => 'active',
            'total_budget' => 10000,
            'start_date' => now()->toDateString(),
        ]);

        return Quote::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'version' => 1,
            'title' => 'Oferta Test',
            'status' => 'draft',
            'total_net' => 1000,
            'total_tva' => 190,
            'total_gross' => 1190,
            'created_by' => $user->id,
        ]);
    }
}
