<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PublicDemoRefreshTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_refresh_creates_demo_user_and_seed_data(): void
    {
        Artisan::call('demo:refresh');

        $demoEmail = config('demo.email');
        $demoPassword = config('demo.password');
        $marker = config('demo.seed_marker');

        $demoUser = User::where('email', $demoEmail)->first();

        $this->assertNotNull($demoUser);
        $this->assertTrue(Hash::check($demoPassword, $demoUser->password));
        $this->assertNotNull($demoUser->onboarding_completed_at);

        $this->assertTrue(Project::where('created_by', $demoUser->id)->where('notes', 'like', '%' . $marker . '%')->count() >= 2);
        $this->assertTrue(Client::where('notes', 'like', '%' . $marker . '%')->count() >= 1);
    }

    public function test_demo_refresh_is_idempotent_and_does_not_duplicate_seeded_projects(): void
    {
        Artisan::call('demo:refresh');
        Artisan::call('demo:refresh');

        $demoUser = User::where('email', config('demo.email'))->firstOrFail();
        $marker = config('demo.seed_marker');

        $count = Project::where('created_by', $demoUser->id)
            ->where('notes', 'like', '%' . $marker . '%')
            ->count();

        $this->assertSame(2, $count);
    }
}
