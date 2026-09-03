<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Contractor;
use App\Models\Equipment;
use App\Models\Material;
use App\Models\Project;
use App\Models\Team;
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
}
