<?php

namespace Tests\Feature;

use App\Models\Defect;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\QualityCheck;
use App\Models\StageTask;
use App\Models\User;
use Database\Seeders\IamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QualityCheckAutomationTest extends TestCase
{
    use RefreshDatabase;

    public function test_quality_check_is_automatically_marked_passed_when_stage_tasks_are_closed(): void
    {
        $this->seed(IamSeeder::class);

        $user = $this->createTenantUser('quality.auto@santier.local');
        $project = $this->createProject($user, 'Proiect Calitate Auto');
        $phase = $this->createPhase($project, 'Etapa finisaje');

        StageTask::create([
            'stage_id' => $phase->id,
            'title' => 'Task 1',
            'status' => 'done',
        ]);

        StageTask::create([
            'stage_id' => $phase->id,
            'title' => 'Task 2',
            'status' => 'done',
        ]);

        $check = QualityCheck::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'title' => 'Verificare receptie',
            'check_type' => 'handover',
            'reception_type' => 'partial',
            'status' => 'pending',
        ]);

        $this->actingAs($user)
            ->put(route('quality-checks.update', $check), [
                'project_id' => $project->id,
                'phase_id' => $phase->id,
                'title' => 'Verificare receptie',
                'description' => 'Control final',
                'check_type' => 'handover',
                'reception_type' => 'final',
                'status' => 'in_progress',
                'planned_at' => now()->addDay()->toDateTimeString(),
                'checklist' => [
                    ['text' => 'Conformitate finisaj', 'done' => true],
                ],
            ])
            ->assertRedirect(route('quality-checks.index'));

        $this->assertDatabaseHas('quality_checks', [
            'id' => $check->id,
            'status' => 'passed',
            'reception_type' => 'final',
        ]);
    }

    public function test_defect_photo_can_be_uploaded_from_form(): void
    {
        $this->seed(IamSeeder::class);
        Storage::fake('public');

        $user = $this->createTenantUser('defect.photo@santier.local');
        $project = $this->createProject($user, 'Proiect Defect Foto');
        $phase = $this->createPhase($project, 'Etapa tencuieli');

        $photo = UploadedFile::fake()->image('defect.jpg', 1200, 900);

        $this->actingAs($user)
            ->post(route('defects.store'), [
                'project_id' => $project->id,
                'phase_id' => $phase->id,
                'title' => 'Fisura perete',
                'description' => 'Fisura verticala',
                'location' => 'Camera 2',
                'status' => 'open',
                'priority' => 'high',
                'due_date' => now()->addDays(2)->toDateString(),
                'photo' => $photo,
            ])
            ->assertRedirect(route('defects.index'));

        $defect = Defect::query()->where('title', 'Fisura perete')->first();

        $this->assertNotNull($defect);
        $this->assertNotNull($defect->photo_path);
        Storage::disk('public')->assertExists($defect->photo_path);
    }

    private function createTenantUser(string $email): User
    {
        return User::factory()->create([
            'email' => $email,
            'tenant_id' => 1,
            'current_tenant_id' => 1,
            'onboarding_step' => 3,
            'onboarding_completed_at' => now(),
        ]);
    }

    private function createProject(User $user, string $name): Project
    {
        return Project::create([
            'tenant_id' => 1,
            'created_by' => $user->id,
            'name' => $name,
            'status' => 'active',
        ]);
    }

    private function createPhase(Project $project, string $name): ProjectPhase
    {
        return ProjectPhase::create([
            'project_id' => $project->id,
            'name' => $name,
            'type' => 'custom',
            'status' => 'in_progress',
            'progress_pct' => 0,
        ]);
    }
}
