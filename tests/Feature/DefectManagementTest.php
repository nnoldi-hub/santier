<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Defect;
use App\Models\DefectPhoto;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DefectManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_defect_can_be_created_with_multiple_photos(): void
    {
        Storage::fake('public');
        $user = $this->createOnboardedUser();
        [$project, $phase] = $this->seedProjectContext($user);

        $response = $this->actingAs($user)->post('/defects', [
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'title' => 'Fisura perete',
            'status' => 'open',
            'priority' => 'high',
            'photos' => [
                UploadedFile::fake()->image('fisura1.jpg'),
                UploadedFile::fake()->image('fisura2.jpg'),
            ],
        ]);

        $response->assertRedirect('/defects');
        $defect = Defect::where('title', 'Fisura perete')->firstOrFail();
        $this->assertDatabaseCount('defect_photos', 2);
        $this->assertSame($user->id, $defect->reported_by);
    }

    public function test_marking_defect_as_resolved_without_photo_is_rejected(): void
    {
        Storage::fake('public');
        $user = $this->createOnboardedUser();
        [$project, $phase] = $this->seedProjectContext($user);

        $response = $this->actingAs($user)->post('/defects', [
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'title' => 'Defect fara poza',
            'status' => 'resolved',
            'priority' => 'medium',
        ]);

        $response->assertSessionHasErrors('photos');
        $this->assertDatabaseMissing('defects', ['title' => 'Defect fara poza']);
    }

    public function test_marking_defect_as_resolved_with_photo_sets_resolved_by(): void
    {
        Storage::fake('public');
        $user = $this->createOnboardedUser();
        [$project, $phase] = $this->seedProjectContext($user);

        $response = $this->actingAs($user)->post('/defects', [
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'title' => 'Defect rezolvat',
            'status' => 'resolved',
            'priority' => 'medium',
            'photos' => [UploadedFile::fake()->image('remediat.jpg')],
        ]);

        $response->assertRedirect('/defects');
        $defect = Defect::where('title', 'Defect rezolvat')->firstOrFail();
        $this->assertSame($user->id, $defect->resolved_by);
        $this->assertNotNull($defect->resolved_at);
    }

    public function test_marking_defect_as_rejected_without_photo_is_accepted(): void
    {
        Storage::fake('public');
        $user = $this->createOnboardedUser();
        [$project, $phase] = $this->seedProjectContext($user);

        $response = $this->actingAs($user)->post('/defects', [
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'title' => 'Defect respins',
            'status' => 'rejected',
            'priority' => 'low',
        ]);

        $response->assertRedirect('/defects');
        $this->assertDatabaseHas('defects', ['title' => 'Defect respins', 'status' => 'rejected']);
    }

    public function test_photo_can_be_deleted(): void
    {
        Storage::fake('public');
        $user = $this->createOnboardedUser();
        [$project, $phase] = $this->seedProjectContext($user);

        $defect = Defect::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'reported_by' => $user->id,
            'title' => 'Defect cu poza',
            'status' => 'open',
            'priority' => 'medium',
        ]);

        $photo = DefectPhoto::create([
            'tenant_id' => 1,
            'defect_id' => $defect->id,
            'path' => 'defects/photos/existing.jpg',
            'name' => 'existing.jpg',
        ]);
        Storage::disk('public')->put($photo->path, 'fake-content');

        $response = $this->actingAs($user)->delete("/defects/{$defect->id}/photos/{$photo->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('defect_photos', ['id' => $photo->id]);
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

        $response = $this->actingAs($user)->post('/defects', [
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'title' => 'Defect semnat',
            'status' => 'open',
            'priority' => 'medium',
            'signature_data_url' => $pngDataUrl,
            'signed_by_name' => 'Ion Popescu',
        ]);

        $response->assertRedirect('/defects');
        $defect = Defect::where('title', 'Defect semnat')->firstOrFail();
        $this->assertNotNull($defect->signature_path);
        $this->assertSame('Ion Popescu', $defect->signed_by_name);
        Storage::disk('public')->assertExists($defect->signature_path);
    }

    private function seedProjectContext(User $user): array
    {
        $client = Client::create([
            'tenant_id' => 1,
            'name' => 'Client Defecte',
            'type' => 'company',
            'active' => true,
        ]);

        $project = Project::create([
            'tenant_id' => 1,
            'client_id' => $client->id,
            'created_by' => $user->id,
            'name' => 'Proiect Defecte',
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
