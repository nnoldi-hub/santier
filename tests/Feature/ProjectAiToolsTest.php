<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Document;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjectAiToolsTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_invoice_flow_extracts_and_commits_document(): void
    {
        Storage::fake('local');

        $user = $this->createOnboardedUser();
        [$project, $phase] = $this->seedProjectContext($user);

        $extractResponse = $this->actingAs($user)->post(route('projects.ai.invoice.extract', $project), [
            'stage_id' => $phase->id,
            'attachment' => UploadedFile::fake()->image('factura-furnizor-demo-1250.jpg'),
        ]);

        $extractResponse->assertOk();
        $extractResponse->assertJsonStructure([
            'message',
            'draft' => [
                'temp_file_path',
                'file_name',
                'stage_id',
                'supplier_name',
                'amount',
                'vat_amount',
            ],
        ]);

        $tempFilePath = $extractResponse->json('draft.temp_file_path');
        $this->assertNotNull($tempFilePath);
        Storage::disk('local')->assertExists($tempFilePath);

        $commitResponse = $this->actingAs($user)->post(route('projects.ai.invoice.commit', $project), [
            'stage_id' => $phase->id,
            'temp_file_path' => $tempFilePath,
            'supplier_name' => 'Furnizor Demo AI',
            'amount' => 1250,
            'vat_amount' => 237.5,
            'issued_at' => now()->toDateString(),
            'payment_status' => 'unpaid',
            'title' => 'Factura test AI',
            'notes' => 'Confirmare manuala pentru test.',
        ]);

        $commitResponse->assertOk();
        $commitResponse->assertJsonStructure(['message', 'document_id', 'contractor_id']);

        $document = Document::query()->find($commitResponse->json('document_id'));

        $this->assertNotNull($document);
        $this->assertSame('Factura test AI', $document->title);
        $this->assertSame($project->id, $document->project_id);
        $this->assertSame($phase->id, $document->stage_id);
        $this->assertSame('invoice', $document->type);
        $this->assertSame('unpaid', $document->payment_status);
        $this->assertStringContainsString('TVA estimat: 237.50', (string) $document->notes);

        Storage::disk('local')->assertMissing($tempFilePath);
        Storage::disk('local')->assertExists($document->file_path);
    }

    private function seedProjectContext(User $user): array
    {
        $client = Client::create([
            'tenant_id' => 1,
            'name' => 'Client AI Tools',
            'type' => 'company',
            'active' => true,
        ]);

        $project = Project::create([
            'tenant_id' => 1,
            'client_id' => $client->id,
            'created_by' => $user->id,
            'name' => 'Proiect AI Tools',
            'status' => 'active',
        ]);

        $phase = ProjectPhase::create([
            'project_id' => $project->id,
            'name' => 'Etapa AI',
            'type' => 'custom',
            'status' => 'in_progress',
            'progress_pct' => 15,
        ]);

        return [$project, $phase];
    }

    private function createOnboardedUser(): User
    {
        return User::factory()->create([
            'onboarding_step' => 3,
            'onboarding_completed_at' => now(),
            'billing_plan' => 'pro',
        ]);
    }
}
