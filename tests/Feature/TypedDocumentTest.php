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

        $this->actingAs($user)->get("/documents/{$pvReceptie->id}/pdf")->assertStatus(200);
        $this->actingAs($user)->get("/documents/{$pvAscunse->id}/pdf")->assertStatus(200);
    }

    public function test_existing_document_type_is_unaffected(): void
    {
        $user = $this->createOnboardedUser('pro');
        $project = $this->createProject($user);

        $response = $this->actingAs($user)->post('/documents', $this->basePayload($project, 'invoice'));

        $response->assertRedirect(route('documents.index'));
        $document = Document::firstOrFail();
        $this->assertSame('invoice', $document->type);

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
