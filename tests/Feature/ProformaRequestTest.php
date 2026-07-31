<?php

namespace Tests\Feature;

use App\Mail\ProformaRequestMail;
use App\Models\CommercialAction;
use App\Models\PilotInvite;
use App\Models\ProformaRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ProformaRequestTest extends TestCase
{
    use RefreshDatabase;

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'company_name' => 'Constructii Andrei SRL',
            'company_cui' => 'RO12345678',
            'company_address' => 'Str. Exemplu nr. 1, Cluj-Napoca',
            'contact_name' => 'Andrei Pop',
            'contact_email' => 'andrei@firma.ro',
            'contact_phone' => '0722123456',
            'plan' => 'pro',
            'interval' => 'monthly',
        ], $overrides);
    }

    public function test_valid_request_creates_record_sends_pdf_and_tracks_pilot_invite(): void
    {
        Mail::fake();

        $response = $this->post('/proforma-request', $this->validPayload());

        $response->assertRedirect();

        $this->assertDatabaseHas('proforma_requests', [
            'company_name' => 'Constructii Andrei SRL',
            'company_cui' => 'RO12345678',
            'contact_email' => 'andrei@firma.ro',
            'plan' => 'pro',
            'interval' => 'monthly',
            'discount_pct' => 20,
        ]);

        $proformaRequest = ProformaRequest::where('contact_email', 'andrei@firma.ro')->firstOrFail();
        $this->assertNotNull($proformaRequest->sent_at);

        Mail::assertSent(ProformaRequestMail::class, function (ProformaRequestMail $mail) {
            return $mail->hasTo('andrei@firma.ro');
        });
        Mail::assertSent(ProformaRequestMail::class, function (ProformaRequestMail $mail) {
            return $mail->hasTo('vanzari@modulia.ro');
        });
        Mail::assertSent(ProformaRequestMail::class, 2);

        $this->assertDatabaseHas('pilot_invites', [
            'contact_email' => 'andrei@firma.ro',
            'company_name' => 'Constructii Andrei SRL',
            'status' => 'contacted',
            'commercial_stage' => 'negotiation',
        ]);

        $invite = PilotInvite::where('contact_email', 'andrei@firma.ro')->firstOrFail();
        $this->assertDatabaseHas('commercial_actions', [
            'pilot_invite_id' => $invite->id,
            'action_type' => 'oferta',
        ]);
    }

    public function test_existing_pilot_invite_is_updated_instead_of_duplicated(): void
    {
        Mail::fake();

        $invite = PilotInvite::create([
            'tenant_id' => 1,
            'company_name' => 'Constructii Andrei SRL',
            'contact_name' => 'Andrei Pop',
            'contact_email' => 'Andrei@Firma.ro',
            'status' => 'invited',
            'commercial_stage' => 'prospecting',
            'invited_at' => now(),
        ]);

        $this->post('/proforma-request', $this->validPayload());

        $this->assertDatabaseCount('pilot_invites', 1);

        $invite->refresh();
        $this->assertNotNull($invite->last_contacted_at);

        $this->assertDatabaseHas('commercial_actions', [
            'pilot_invite_id' => $invite->id,
            'action_type' => 'oferta',
        ]);
    }

    public function test_request_requires_company_cui(): void
    {
        Mail::fake();

        $response = $this->from('/')->post('/proforma-request', $this->validPayload(['company_cui' => '']));

        $response->assertSessionHasErrors('company_cui');
        $this->assertDatabaseCount('proforma_requests', 0);
        Mail::assertNothingSent();
    }

    public function test_request_requires_contact_phone(): void
    {
        Mail::fake();

        $response = $this->from('/')->post('/proforma-request', $this->validPayload(['contact_phone' => '']));

        $response->assertSessionHasErrors('contact_phone');
        $this->assertDatabaseCount('proforma_requests', 0);
        Mail::assertNothingSent();
    }

    public function test_request_rejects_plan_outside_self_serve_whitelist(): void
    {
        Mail::fake();

        $response = $this->from('/')->post('/proforma-request', $this->validPayload(['plan' => 'enterprise']));

        $response->assertSessionHasErrors('plan');
        $this->assertDatabaseCount('proforma_requests', 0);
        Mail::assertNothingSent();
    }
}
