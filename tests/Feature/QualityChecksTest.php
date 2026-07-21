<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\QualityCheck;
use App\Models\QualityCheckPhoto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class QualityChecksTest extends TestCase
{
    use RefreshDatabase;

    public function test_quality_check_can_be_created(): void
    {
        $user = $this->createOnboardedUser();
        [$project, $phase] = $this->seedProjectContext($user);

        $response = $this->actingAs($user)->post('/quality-checks', [
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'title' => 'Verificare finisaj pereti',
            'description' => 'Control planeitate pe toate camerele.',
            'check_type' => 'execution',
            'reception_type' => 'partial',
            'status' => 'pending',
            'planned_at' => now()->addDay()->format('Y-m-d H:i:s'),
        ]);

        $response->assertRedirect('/quality-checks');

        $this->assertDatabaseHas('quality_checks', [
            'tenant_id' => 1,
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'title' => 'Verificare finisaj pereti',
            'check_type' => 'execution',
            'status' => 'pending',
        ]);
    }

    public function test_quality_checks_index_can_filter_by_status(): void
    {
        $user = $this->createOnboardedUser();
        [$project, $phase] = $this->seedProjectContext($user);

        QualityCheck::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'title' => 'Verificare conforma',
            'check_type' => 'materials',
            'status' => 'passed',
        ]);

        QualityCheck::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'title' => 'Verificare neconforma',
            'check_type' => 'execution',
            'status' => 'failed',
        ]);

        $response = $this->actingAs($user)->get('/quality-checks?status=failed');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('QualityChecks/Index')
            ->where('checks.data.0.title', 'Verificare neconforma')
        );
    }

    public function test_quality_check_rejects_phase_not_in_selected_project(): void
    {
        $user = $this->createOnboardedUser();
        [$projectA, $phaseA] = $this->seedProjectContext($user);

        $clientB = Client::create([
            'tenant_id' => 1,
            'name' => 'Client B',
            'type' => 'company',
            'active' => true,
        ]);

        $projectB = Project::create([
            'tenant_id' => 1,
            'client_id' => $clientB->id,
            'created_by' => $user->id,
            'name' => 'Proiect B',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)
            ->from('/quality-checks/create')
            ->post('/quality-checks', [
                'project_id' => $projectB->id,
                'phase_id' => $phaseA->id,
                'title' => 'Verificare invalida',
                'check_type' => 'execution',
                'status' => 'pending',
            ]);

        $response->assertRedirect('/quality-checks/create');
        $response->assertSessionHasErrors('phase_id');

        $this->assertDatabaseMissing('quality_checks', [
            'title' => 'Verificare invalida',
        ]);
    }

    public function test_marking_check_as_passed_without_photo_is_rejected(): void
    {
        Storage::fake('public');
        $user = $this->createOnboardedUser();
        [$project, $phase] = $this->seedProjectContext($user);

        $response = $this->actingAs($user)->post('/quality-checks', [
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'title' => 'Verificare fara poza',
            'check_type' => 'execution',
            'status' => 'passed',
        ]);

        $response->assertSessionHasErrors('photos');
        $this->assertDatabaseMissing('quality_checks', ['title' => 'Verificare fara poza']);
    }

    public function test_marking_check_as_passed_with_photo_is_accepted(): void
    {
        Storage::fake('public');
        $user = $this->createOnboardedUser();
        [$project, $phase] = $this->seedProjectContext($user);

        $response = $this->actingAs($user)->post('/quality-checks', [
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'title' => 'Verificare cu poza',
            'check_type' => 'execution',
            'reception_type' => 'partial',
            'status' => 'passed',
            'photos' => [UploadedFile::fake()->image('finisaj.jpg')],
        ]);

        $response->assertRedirect('/quality-checks');
        $qualityCheck = QualityCheck::where('title', 'Verificare cu poza')->firstOrFail();
        $this->assertDatabaseHas('quality_check_photos', [
            'quality_check_id' => $qualityCheck->id,
            'name' => 'finisaj.jpg',
        ]);
        Storage::disk('public')->assertExists($qualityCheck->photos->first()->path);
    }

    public function test_photo_can_be_deleted(): void
    {
        Storage::fake('public');
        $user = $this->createOnboardedUser();
        [$project, $phase] = $this->seedProjectContext($user);

        $qualityCheck = QualityCheck::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'title' => 'Verificare cu poza',
            'check_type' => 'execution',
            'status' => 'pending',
        ]);

        $photo = QualityCheckPhoto::create([
            'tenant_id' => 1,
            'quality_check_id' => $qualityCheck->id,
            'path' => 'quality-checks/photos/existing.jpg',
            'name' => 'existing.jpg',
        ]);
        Storage::disk('public')->put($photo->path, 'fake-content');

        $response = $this->actingAs($user)->delete("/quality-checks/{$qualityCheck->id}/photos/{$photo->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('quality_check_photos', ['id' => $photo->id]);
        Storage::disk('public')->assertMissing($photo->path);
    }

    public function test_signature_data_url_is_decoded_and_stored(): void
    {
        Storage::fake('public');
        $user = $this->createOnboardedUser();
        [$project, $phase] = $this->seedProjectContext($user);

        $pngDataUrl = 'data:image/png;base64,' . base64_encode(base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='
        ));

        $response = $this->actingAs($user)->post('/quality-checks', [
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'title' => 'Verificare semnata',
            'check_type' => 'execution',
            'reception_type' => 'partial',
            'status' => 'pending',
            'signature_data_url' => $pngDataUrl,
            'signed_by_name' => 'Ion Popescu',
        ]);

        $response->assertRedirect('/quality-checks');
        $qualityCheck = QualityCheck::where('title', 'Verificare semnata')->firstOrFail();
        $this->assertNotNull($qualityCheck->signature_path);
        $this->assertSame('Ion Popescu', $qualityCheck->signed_by_name);
        $this->assertNotNull($qualityCheck->signed_at);
        Storage::disk('public')->assertExists($qualityCheck->signature_path);
    }

    private function seedProjectContext(User $user): array
    {
        $client = Client::create([
            'tenant_id' => 1,
            'name' => 'Client Verificari',
            'type' => 'company',
            'active' => true,
        ]);

        $project = Project::create([
            'tenant_id' => 1,
            'client_id' => $client->id,
            'created_by' => $user->id,
            'name' => 'Proiect Verificari',
            'status' => 'active',
        ]);

        $phase = ProjectPhase::create([
            'project_id' => $project->id,
            'name' => 'Etapa control',
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
