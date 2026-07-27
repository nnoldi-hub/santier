<?php

namespace Tests\Feature;

use App\Mail\BrochureRequestMail;
use App\Models\BrochureRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BrochureRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_brochure_request_creates_record_and_sends_pdf_email(): void
    {
        Mail::fake();

        $response = $this->post('/brochure-request', [
            'name' => 'Andrei Pop',
            'email' => 'andrei@firma.ro',
            'company' => 'Firma SRL',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('brochure_requests', [
            'name' => 'Andrei Pop',
            'email' => 'andrei@firma.ro',
            'company' => 'Firma SRL',
        ]);

        $brochureRequest = BrochureRequest::where('email', 'andrei@firma.ro')->firstOrFail();
        $this->assertNotNull($brochureRequest->sent_at);

        Mail::assertSent(BrochureRequestMail::class, function (BrochureRequestMail $mail) {
            return $mail->hasTo('andrei@firma.ro')
                && $mail->hasBcc('vanzari@modulia.ro')
                && $mail->fileName === 'brosura-modulia.pdf';
        });
    }

    public function test_brochure_request_requires_name_and_email(): void
    {
        Mail::fake();

        $response = $this->from('/')->post('/brochure-request', []);

        $response->assertSessionHasErrors(['name', 'email']);
        $this->assertDatabaseCount('brochure_requests', 0);
        Mail::assertNothingSent();
    }

    public function test_brochure_request_rejects_invalid_email(): void
    {
        Mail::fake();

        $response = $this->from('/')->post('/brochure-request', [
            'name' => 'Andrei Pop',
            'email' => 'not-an-email',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseCount('brochure_requests', 0);
        Mail::assertNothingSent();
    }
}
