<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Contractor;
use App\Models\Equipment;
use App\Models\Material;
use App\Models\Project;
use App\Models\Team;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PublicDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_a_varied_project_portfolio(): void
    {
        $this->seed(PublicDemoSeeder::class);

        $this->assertSame(6, Project::query()->count());
        $this->assertSame(3, Client::query()->count());
        $this->assertSame(5, Contractor::query()->count());
        $this->assertSame(5, Material::query()->count());
        $this->assertSame(4, Equipment::query()->count());
        $this->assertSame(2, Team::query()->count());

        $this->assertDatabaseHas('projects', [
            'name' => 'Constructie Hala Industriala - Faza 1',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('projects', [
            'name' => 'Modernizare Instalatii Sanitare si Termice - Bloc Rezidential',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('projects', [
            'name' => 'Finisaje Apartamente Bloc Nou - Etapa 2',
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('projects', [
            'name' => 'Extindere Depozit Logistic Nord',
            'status' => 'paused',
        ]);
    }

    public function test_seeder_is_idempotent_and_does_not_duplicate_on_rerun(): void
    {
        $this->seed(PublicDemoSeeder::class);
        $this->seed(PublicDemoSeeder::class);

        $this->assertSame(6, Project::query()->count());
        $this->assertSame(3, Client::query()->count());
        $this->assertSame(5, Contractor::query()->count());
        $this->assertSame(5, Material::query()->count());
        $this->assertSame(4, Equipment::query()->count());
        $this->assertSame(2, Team::query()->count());
        $this->assertSame(1, User::query()->where('email', config('demo.email'))->count());
    }

    public function test_seeder_never_touches_records_belonging_to_another_tenant(): void
    {
        $marker = config('demo.seed_marker', '[demo_seed]');

        $otherTenant = Tenant::create([
            'name' => 'Alta Firma SRL',
            'slug' => 'alta-firma',
            'billing_plan' => 'pro',
            'status' => 'active',
            'module_flags' => [],
        ]);

        $otherClient = Client::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Client Real Coincidental',
            'type' => 'company',
            'notes' => $marker,
            'active' => true,
        ]);

        $otherContractor = Contractor::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Contractor Real Coincidental',
            'type' => Contractor::TYPE_SUBCONTRACTOR,
            'notes' => $marker,
            'active' => true,
        ]);

        $otherEquipment = Equipment::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Utilaj Real Coincidental',
            'type' => 'excavator',
            'availability_status' => Equipment::STATUS_AVAILABLE,
            'notes' => $marker,
            'active' => true,
        ]);

        $otherMaterial = Material::create([
            'tenant_id' => $otherTenant->id,
            'code' => 'DEMO-DAR-REAL-01',
            'name' => 'Material Real Coincidental',
            'category' => 'Test',
            'unit' => 'buc',
            'unit_price' => 10,
            'active' => true,
        ]);

        // Ruleaza (si re-ruleaza, ca la refresh-ul nocturn) seederul demo-ului public.
        $this->seed(PublicDemoSeeder::class);
        $this->seed(PublicDemoSeeder::class);

        $this->assertModelExists($otherClient);
        $this->assertModelExists($otherContractor);
        $this->assertModelExists($otherEquipment);
        $this->assertModelExists($otherMaterial);
    }
}
