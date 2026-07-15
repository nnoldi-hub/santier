<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Document;
use App\Models\Project;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\IamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TypedDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_pv_receptie_requires_type_data_fields(): void
    {
        $user = $this->createOnboardedUser('pro');
        $project = $this->createProject($user);

        $response = $this->actingAs($user)->post('/documents', $this->basePayload($project, 'proc_verbal_receptie'));

        $response->assertSessionHasErrors([
            'type_data.comisie',
            'type_data.descriere_lucrari',
            'type_data.concluzie',
        ]);
        $this->assertDatabaseCount('documents', 0);
    }

    public function test_pv_receptie_stores_type_data_when_complete(): void
    {
        $user = $this->createOnboardedUser('pro');
        $project = $this->createProject($user);

        $payload = $this->basePayload($project, 'proc_verbal_receptie') + [
            'type_data' => [
                'comisie' => 'Ion Popescu - manager proiect',
                'descriere_lucrari' => 'Structura de rezistenta finalizata.',
                'defecte' => '',
                'concluzie' => 'admis',
            ],
        ];

        $response = $this->actingAs($user)->post('/documents', $payload);

        $response->assertRedirect(route('documents.index'));
        $document = Document::firstOrFail();
        $this->assertSame('admis', $document->type_data['concluzie']);
        $this->assertSame('Ion Popescu - manager proiect', $document->type_data['comisie']);
    }

    public function test_pv_lucrari_ascunse_requires_type_data_fields(): void
    {
        $user = $this->createOnboardedUser('pro');
        $project = $this->createProject($user);

        $response = $this->actingAs($user)->post('/documents', $this->basePayload($project, 'proc_verbal_lucrari_ascunse'));

        $response->assertSessionHasErrors([
            'type_data.descriere_lucrari_ascunse',
            'type_data.verificari_efectuate',
            'type_data.responsabil_tehnic',
        ]);
    }

    public function test_pv_predare_primire_requires_type_data_fields(): void
    {
        $user = $this->createOnboardedUser('pro');
        $project = $this->createProject($user);

        $response = $this->actingAs($user)->post('/documents', $this->basePayload($project, 'proc_verbal_predare_primire'));

        $response->assertSessionHasErrors([
            'type_data.predat_de',
            'type_data.primit_de',
            'type_data.obiecte',
            'type_data.stare',
        ]);
    }

    public function test_pv_remediere_defecte_requires_type_data_fields(): void
    {
        $user = $this->createOnboardedUser('pro');
        $project = $this->createProject($user);

        $response = $this->actingAs($user)->post('/documents', $this->basePayload($project, 'proc_verbal_remediere_defecte'));

        $response->assertSessionHasErrors([
            'type_data.defect_identificat',
            'type_data.responsabil_remediere',
            'type_data.termen',
            'type_data.stare_remediere',
        ]);
    }

    public function test_pv_constatare_requires_type_data_fields(): void
    {
        $user = $this->createOnboardedUser('pro');
        $project = $this->createProject($user);

        $response = $this->actingAs($user)->post('/documents', $this->basePayload($project, 'proc_verbal_constatare'));

        $response->assertSessionHasErrors([
            'type_data.situatie_constatata',
            'type_data.martori',
        ]);
    }

    public function test_contract_requires_type_data_fields(): void
    {
        $user = $this->createOnboardedUser('pro');
        $project = $this->createProject($user);

        $response = $this->actingAs($user)->post('/documents', $this->basePayload($project, 'contract'));

        $response->assertSessionHasErrors([
            'type_data.parti_contractante',
            'type_data.obiect_contract',
            'type_data.termene',
            'type_data.penalitati',
        ]);
    }

    public function test_invoice_requires_number_and_type_data_fields(): void
    {
        $user = $this->createOnboardedUser('pro');
        $project = $this->createProject($user);

        $response = $this->actingAs($user)->post('/documents', $this->basePayload($project, 'invoice'));

        $response->assertSessionHasErrors([
            'invoice_number',
            'type_data.produse_servicii',
            'type_data.tva_pct',
            'type_data.scadenta',
        ]);
    }

    public function test_delivery_note_requires_type_data_fields(): void
    {
        $user = $this->createOnboardedUser('pro');
        $project = $this->createProject($user);

        $response = $this->actingAs($user)->post('/documents', $this->basePayload($project, 'delivery_note'));

        $response->assertSessionHasErrors([
            'type_data.furnizor',
            'type_data.materiale',
            'type_data.transportator',
        ]);
    }

    public function test_invoice_stores_invoice_number_when_complete(): void
    {
        $user = $this->createOnboardedUser('pro');
        $project = $this->createProject($user);

        $payload = $this->basePayload($project, 'invoice') + [
            'invoice_number' => 'FAC-2026-0001',
            'type_data' => [
                'produse_servicii' => 'Montaj gips-carton - 40mp - 45 RON/mp',
                'tva_pct' => 19,
                'scadenta' => now()->addDays(30)->toDateString(),
            ],
        ];

        $response = $this->actingAs($user)->post('/documents', $payload);

        $response->assertRedirect(route('documents.index'));
        $document = Document::firstOrFail();
        $this->assertSame('FAC-2026-0001', $document->invoice_number);
        $this->assertSame(19.0, (float) $document->type_data['tva_pct']);
    }

    public function test_pdf_renders_for_new_types_classic_and_modern(): void
    {
        $user = $this->createOnboardedUser('pro');
        $project = $this->createProject($user);

        $pvReceptie = Document::create($this->basePayload($project, 'proc_verbal_receptie') + [
            'tenant_id' => 1,
            'type_data' => ['comisie' => 'Comisie test', 'descriere_lucrari' => 'Descriere test', 'concluzie' => 'admis'],
        ]);
        $pvAscunse = Document::create($this->basePayload($project, 'proc_verbal_lucrari_ascunse') + [
            'tenant_id' => 1,
            'type_data' => ['descriere_lucrari_ascunse' => 'Descriere', 'verificari_efectuate' => 'Verificari', 'responsabil_tehnic' => 'Ion Ionescu'],
        ]);
        $pvPredarePrimire = Document::create($this->basePayload($project, 'proc_verbal_predare_primire') + [
            'tenant_id' => 1,
            'type_data' => ['predat_de' => 'A', 'primit_de' => 'B', 'obiecte' => 'Schela', 'stare' => 'Buna'],
        ]);
        $pvRemediere = Document::create($this->basePayload($project, 'proc_verbal_remediere_defecte') + [
            'tenant_id' => 1,
            'type_data' => ['defect_identificat' => 'Fisura', 'responsabil_remediere' => 'C', 'termen' => now()->toDateString(), 'stare_remediere' => 'remediat'],
        ]);
        $pvConstatare = Document::create($this->basePayload($project, 'proc_verbal_constatare') + [
            'tenant_id' => 1,
            'type_data' => ['situatie_constatata' => 'Infiltratii', 'martori' => 'D'],
        ]);
        $contract = Document::create($this->basePayload($project, 'contract') + [
            'tenant_id' => 1,
            'type_data' => ['parti_contractante' => 'A / B', 'obiect_contract' => 'Lucrari finisaje', 'termene' => '30 zile', 'penalitati' => '0.1%/zi'],
        ]);
        $invoice = Document::create($this->basePayload($project, 'invoice') + [
            'tenant_id' => 1,
            'invoice_number' => 'FAC-2026-0002',
            'type_data' => ['produse_servicii' => 'Montaj gips-carton', 'tva_pct' => 19, 'scadenta' => now()->addDays(30)->toDateString()],
        ]);
        $deliveryNote = Document::create($this->basePayload($project, 'delivery_note') + [
            'tenant_id' => 1,
            'type_data' => ['furnizor' => 'Depozit Central SRL', 'materiale' => 'Ciment - 20 saci', 'transportator' => 'Auto Transport SRL'],
        ]);

        $this->actingAs($user)->get("/documents/{$pvReceptie->id}/pdf")->assertStatus(200);
        $this->actingAs($user)->get("/documents/{$pvAscunse->id}/pdf")->assertStatus(200);
        $this->actingAs($user)->get("/documents/{$pvPredarePrimire->id}/pdf")->assertStatus(200);
        $this->actingAs($user)->get("/documents/{$pvRemediere->id}/pdf")->assertStatus(200);
        $this->actingAs($user)->get("/documents/{$pvConstatare->id}/pdf")->assertStatus(200);
        $this->actingAs($user)->get("/documents/{$contract->id}/pdf")->assertStatus(200);
        $this->actingAs($user)->get("/documents/{$invoice->id}/pdf")->assertStatus(200);
        $this->actingAs($user)->get("/documents/{$deliveryNote->id}/pdf")->assertStatus(200);
    }

    public function test_existing_document_type_is_unaffected(): void
    {
        $user = $this->createOnboardedUser('pro');
        $project = $this->createProject($user);

        $response = $this->actingAs($user)->post('/documents', $this->basePayload($project, 'site_photo'));

        $response->assertRedirect(route('documents.index'));
        $document = Document::firstOrFail();
        $this->assertSame('site_photo', $document->type);

        $this->actingAs($user)->get("/documents/{$document->id}/pdf")->assertStatus(200);
    }

    private function basePayload(Project $project, string $type): array
    {
        return [
            'title' => 'Document Test',
            'type' => $type,
            'project_id' => $project->id,
            'amount' => 1000,
            'issued_at' => now()->toDateString(),
            'payment_status' => 'unpaid',
        ];
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

    private function createProject(User $user): Project
    {
        $client = Client::create([
            'tenant_id' => 1,
            'name' => 'Client Test SRL',
            'type' => 'company',
            'active' => true,
        ]);

        return Project::create([
            'tenant_id' => 1,
            'client_id' => $client->id,
            'created_by' => $user->id,
            'name' => 'Proiect Documente',
            'status' => 'active',
            'total_budget' => 10000,
            'start_date' => now()->toDateString(),
        ]);
    }
}
