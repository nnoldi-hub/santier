<?php

namespace Tests\Feature;

use App\Jobs\RunExportSubscriptionJob;
use App\Mail\ScheduledExportMail;
use App\Models\Client;
use App\Models\Contractor;
use App\Models\Defect;
use App\Models\Document;
use App\Models\Equipment;
use App\Models\ExportSubscription;
use App\Models\Material;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\Quote;
use App\Models\ResourceDelivery;
use App\Models\ResourceDocumentLink;
use App\Models\ResourceOrder;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Models\StageEquipment;
use App\Models\StageReport;
use App\Models\StageTask;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EnterpriseExportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_workbook_export_downloads_and_writes_audit_log(): void
    {
        $user = $this->createOnboardedUser();
        $this->seedExportData($user);

        $response = $this->actingAs($user)->get('/exports/workbook');

        $response->assertOk();
        $this->assertStringContainsString('.xlsx', $response->headers->get('content-disposition', ''));

        $this->assertDatabaseHas('export_logs', [
            'tenant_id' => 1,
            'user_id' => $user->id,
            'export_type' => 'enterprise-workbook',
            'format' => 'xlsx',
            'status' => 'success',
        ]);
    }

    public function test_managerial_pdf_downloads_and_writes_audit_log(): void
    {
        $user = $this->createOnboardedUser();
        $this->seedExportData($user);

        $response = $this->actingAs($user)->get('/exports/managerial-pdf');

        $response->assertOk();
        $this->assertStringContainsString('.pdf', $response->headers->get('content-disposition', ''));

        $this->assertDatabaseHas('export_logs', [
            'tenant_id' => 1,
            'user_id' => $user->id,
            'export_type' => 'managerial-pdf',
            'format' => 'pdf',
            'status' => 'success',
        ]);
    }

    public function test_wbs_csv_downloads_and_writes_audit_log(): void
    {
        $user = $this->createOnboardedUser();
        $this->seedExportData($user);

        $response = $this->actingAs($user)->get('/exports/wbs');

        $response->assertOk();
        $this->assertStringContainsString('wbs_etape.csv', $response->headers->get('content-disposition', ''));

        $this->assertDatabaseHas('export_logs', [
            'tenant_id' => 1,
            'user_id' => $user->id,
            'export_type' => 'wbs',
            'format' => 'csv',
            'status' => 'success',
        ]);
    }

    public function test_equipment_csv_downloads_and_writes_audit_log(): void
    {
        $user = $this->createOnboardedUser();
        $this->seedExportData($user);

        $response = $this->actingAs($user)->get('/exports/equipment');

        $response->assertOk();
        $this->assertStringContainsString('utilaje_rezervari.csv', $response->headers->get('content-disposition', ''));

        $this->assertDatabaseHas('export_logs', [
            'tenant_id' => 1,
            'user_id' => $user->id,
            'export_type' => 'equipment',
            'format' => 'csv',
            'status' => 'success',
        ]);
    }

    public function test_documents_csv_downloads_and_writes_audit_log(): void
    {
        $user = $this->createOnboardedUser();
        $this->seedExportData($user);

        $response = $this->actingAs($user)->get('/exports/documents');

        $response->assertOk();
        $this->assertStringContainsString('documente_financiare.csv', $response->headers->get('content-disposition', ''));

        $this->assertDatabaseHas('export_logs', [
            'tenant_id' => 1,
            'user_id' => $user->id,
            'export_type' => 'documents',
            'format' => 'csv',
            'status' => 'success',
        ]);
    }

    public function test_stage_reports_csv_downloads_and_writes_audit_log(): void
    {
        $user = $this->createOnboardedUser();
        $this->seedExportData($user);

        $response = $this->actingAs($user)->get('/exports/stage-reports');

        $response->assertOk();
        $this->assertStringContainsString('rapoarte_etapa.csv', $response->headers->get('content-disposition', ''));

        $this->assertDatabaseHas('export_logs', [
            'tenant_id' => 1,
            'user_id' => $user->id,
            'export_type' => 'stage-reports',
            'format' => 'csv',
            'status' => 'success',
        ]);
    }

    public function test_stage_tasks_csv_downloads_and_writes_audit_log(): void
    {
        $user = $this->createOnboardedUser();
        $this->seedExportData($user);

        $response = $this->actingAs($user)->get('/exports/stage-tasks');

        $response->assertOk();
        $this->assertStringContainsString('taskuri_etapa.csv', $response->headers->get('content-disposition', ''));

        $this->assertDatabaseHas('export_logs', [
            'tenant_id' => 1,
            'user_id' => $user->id,
            'export_type' => 'stage-tasks',
            'format' => 'csv',
            'status' => 'success',
        ]);
    }

    public function test_stage_progress_csv_downloads_and_writes_audit_log(): void
    {
        $user = $this->createOnboardedUser();
        $this->seedExportData($user);

        $response = $this->actingAs($user)->get('/exports/stage-progress');

        $response->assertOk();
        $this->assertStringContainsString('progres_etape.csv', $response->headers->get('content-disposition', ''));

        $this->assertDatabaseHas('export_logs', [
            'tenant_id' => 1,
            'user_id' => $user->id,
            'export_type' => 'stage-progress',
            'format' => 'csv',
            'status' => 'success',
        ]);
    }

    public function test_exports_preview_returns_summary_and_writes_audit_log(): void
    {
        $user = $this->createOnboardedUser();
        $this->seedExportData($user);

        $response = $this->actingAs($user)->get('/exports/preview?export_type=tasks&global_search=turnare');

        $response->assertOk();
        $response->assertJsonStructure([
            'export_type',
            'title',
            'rows_count',
            'sample',
            'active_filters',
            'generated_at',
        ]);
        $response->assertJsonFragment([
            'export_type' => 'tasks',
        ]);

        $this->assertDatabaseHas('export_logs', [
            'tenant_id' => 1,
            'user_id' => $user->id,
            'export_type' => 'preview',
            'format' => 'system',
            'status' => 'success',
        ]);
    }

    public function test_resource_comparison_preview_returns_rows_and_audit_log(): void
    {
        $user = $this->createOnboardedUser();
        $this->seedExportData($user);

        $response = $this->actingAs($user)->get('/exports/preview?export_type=resource-comparison&global_search=aviz');

        $response->assertOk();
        $response->assertJsonFragment([
            'export_type' => 'resource-comparison',
            'title' => 'Materiale & Avize comparative',
        ]);

        $this->assertDatabaseHas('export_logs', [
            'tenant_id' => 1,
            'user_id' => $user->id,
            'export_type' => 'preview',
            'format' => 'system',
            'status' => 'success',
        ]);
    }

    public function test_subscription_creation_persists_data_and_audit_log(): void
    {
        $user = $this->createOnboardedUser();

        $payload = [
            'name' => 'Raport saptamanal PM',
            'export_type' => 'projects',
            'format' => 'xlsx',
            'frequency' => 'weekly',
            'schedule_time' => '08:00',
            'schedule_weekday' => 1,
            'recipients' => ['pm@example.com', 'owner@example.com'],
            'filters' => ['project_id' => null, 'status' => ['active']],
        ];

        $response = $this->actingAs($user)
            ->from('/exports')
            ->post('/exports/subscriptions', $payload);

        $response->assertRedirect('/exports');

        $this->assertDatabaseHas('export_subscriptions', [
            'tenant_id' => 1,
            'created_by' => $user->id,
            'name' => 'Raport saptamanal PM',
            'export_type' => 'projects',
            'format' => 'xlsx',
            'frequency' => 'weekly',
            'schedule_time' => '08:00',
            'schedule_weekday' => 1,
            'active' => true,
        ]);

        $this->assertDatabaseHas('export_logs', [
            'tenant_id' => 1,
            'user_id' => $user->id,
            'export_type' => 'subscription',
            'format' => 'system',
            'status' => 'success',
            'delivery_channel' => 'email',
            'delivery_target' => 'pm@example.com,owner@example.com',
        ]);
    }

    public function test_run_subscription_endpoint_dispatches_job(): void
    {
        Queue::fake();

        $user = $this->createOnboardedUser();
        $subscription = ExportSubscription::create([
            'tenant_id' => 1,
            'created_by' => $user->id,
            'name' => 'Run now test',
            'export_type' => 'projects',
            'format' => 'csv',
            'frequency' => 'daily',
            'schedule_time' => '08:00',
            'filters' => [],
            'recipients' => ['ops@example.com'],
            'active' => true,
            'next_run_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->from('/exports')
            ->post("/exports/subscriptions/{$subscription->id}/run");

        $response->assertRedirect('/exports');

        Queue::assertPushed(RunExportSubscriptionJob::class, function (RunExportSubscriptionJob $job) use ($subscription) {
            return $job->subscriptionId === $subscription->id;
        });
    }

    public function test_scheduled_subscription_job_generates_file_sends_mail_and_logs_audit(): void
    {
        Storage::fake('local');
        Mail::fake();

        $user = $this->createOnboardedUser();
        $this->seedExportData($user);

        $subscription = ExportSubscription::create([
            'tenant_id' => 1,
            'created_by' => $user->id,
            'name' => 'CSV daily report',
            'export_type' => 'projects',
            'format' => 'csv',
            'frequency' => 'daily',
            'schedule_time' => '08:00',
            'filters' => [],
            'recipients' => ['pm@example.com'],
            'active' => true,
            'next_run_at' => now(),
        ]);

        (new RunExportSubscriptionJob($subscription->id))->handle();

        $files = Storage::disk('local')->allFiles('exports/scheduled');
        $this->assertNotEmpty($files);

        Mail::assertQueued(ScheduledExportMail::class, function (ScheduledExportMail $mail) {
            return $mail->hasTo('pm@example.com');
        });

        $this->assertDatabaseHas('export_logs', [
            'tenant_id' => 1,
            'export_type' => 'projects',
            'format' => 'csv',
            'status' => 'success',
            'delivery_channel' => 'email',
            'delivery_target' => 'pm@example.com',
        ]);

        $subscription->refresh();
        $this->assertNotNull($subscription->last_run_at);
        $this->assertNotNull($subscription->next_run_at);
    }

    private function seedExportData(User $user): void
    {
        $client = Client::create([
            'tenant_id' => 1,
            'name' => 'Client Demo SRL',
            'type' => 'company',
            'active' => true,
        ]);

        $project = Project::create([
            'tenant_id' => 1,
            'client_id' => $client->id,
            'created_by' => $user->id,
            'name' => 'Proiect Demo Export',
            'status' => 'active',
            'total_budget' => 100000,
            'start_date' => now()->toDateString(),
        ]);

        $contractor = Contractor::create([
            'tenant_id' => 1,
            'name' => 'Contractor Export',
            'type' => 'subcontractor',
            'active' => true,
        ]);

        $phase = ProjectPhase::create([
            'project_id' => $project->id,
            'name' => 'Executie',
            'type' => 'custom',
            'order' => 1,
            'status' => 'in_progress',
            'progress_pct' => 40,
            'contractor_id' => $contractor->id,
        ]);

        ProjectPhase::create([
            'project_id' => $project->id,
            'parent_id' => $phase->id,
            'name' => 'Subetapa Finisaje',
            'type' => 'custom',
            'order' => 2,
            'status' => 'pending',
            'progress_pct' => 10,
            'contractor_id' => $contractor->id,
        ]);

        Team::create([
            'tenant_id' => 1,
            'name' => 'Echipa A',
            'specialty' => 'Finisaje',
            'leader_id' => $user->id,
            'active' => true,
        ]);

        Material::create([
            'tenant_id' => 1,
            'code' => 'MAT-001',
            'name' => 'Ciment M500',
            'category' => 'Constructii',
            'unit' => 'sac',
            'unit_price' => 45.50,
            'active' => true,
        ]);

        Task::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'assigned_to' => $user->id,
            'created_by' => $user->id,
            'title' => 'Turnare sapa',
            'status' => 'in_progress',
            'priority' => 'high',
            'deadline' => now()->addDays(3),
        ]);

        Defect::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'reported_by' => $user->id,
            'assigned_to' => $user->id,
            'title' => 'Fisura perete N1',
            'status' => 'open',
            'priority' => 'high',
            'due_date' => now()->addDays(2)->toDateString(),
        ]);

        Quote::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'version' => 1,
            'title' => 'Deviz initial',
            'status' => 'accepted',
            'total_net' => 80000,
            'total_tva' => 15200,
            'total_gross' => 95200,
            'created_by' => $user->id,
        ]);

        $equipment = Equipment::create([
            'tenant_id' => 1,
            'name' => 'Excavator test',
            'type' => 'excavator',
            'supplier_name' => 'Utilaje Demo',
            'cost_per_hour' => 180,
            'availability_status' => Equipment::STATUS_AVAILABLE,
            'active' => true,
        ]);

        StageEquipment::create([
            'stage_id' => $phase->id,
            'equipment_id' => $equipment->id,
            'quantity' => 1,
            'usage_start' => now()->addDay()->toDateString(),
            'usage_end' => now()->addDays(2)->toDateString(),
        ]);

        Document::create([
            'tenant_id' => 1,
            'title' => 'Factura lucrari etapa executie',
            'type' => 'invoice',
            'project_id' => $project->id,
            'stage_id' => $phase->id,
            'contractor_id' => $contractor->id,
            'amount' => 4200,
            'issued_at' => now()->toDateString(),
            'payment_status' => 'partial',
            'file_name' => 'factura-demo.pdf',
        ]);

        $resourceOrder = ResourceOrder::create([
            'tenant_id' => 1,
            'project_id' => $project->id,
            'phase_id' => $phase->id,
            'resource_type' => 'material',
            'material_id' => Material::query()->first()->id,
            'supplier_name' => 'Furnizor Avize SRL',
            'carrier_name' => 'Transport Avize SRL',
            'ordered_quantity' => 100,
            'ordered_unit' => 'm3',
            'unit_price' => 55,
            'delivery_date' => now()->toDateString(),
            'responsible_user_id' => $user->id,
            'status' => 'delivered',
            'notes' => 'Comanda pentru avize si trasabilitate.',
        ]);

        $document = Document::create([
            'tenant_id' => 1,
            'title' => 'Aviz livrare materiale',
            'type' => 'delivery_note',
            'project_id' => $project->id,
            'stage_id' => $phase->id,
            'contractor_id' => $contractor->id,
            'amount' => 0,
            'issued_at' => now()->toDateString(),
            'payment_status' => 'unpaid',
            'file_name' => 'aviz-demo.pdf',
        ]);

        ResourceDelivery::create([
            'tenant_id' => 1,
            'resource_order_id' => $resourceOrder->id,
            'declared_quantity' => 100,
            'received_quantity' => 96,
            'equipment_reported_quantity' => 0,
            'consumed_quantity' => 82,
            'returned_quantity' => 14,
            'delivered_at' => now(),
            'notes' => 'Receptionat partial si consumat pe santier.',
        ]);

        ResourceDocumentLink::create([
            'tenant_id' => 1,
            'resource_order_id' => $resourceOrder->id,
            'document_id' => $document->id,
            'document_role' => 'delivery_note',
            'document_number' => 'AVZ-001',
            'supplier_name' => 'Furnizor Avize SRL',
            'carrier_name' => 'Transport Avize SRL',
            'declared_quantity' => 100,
            'delivered_quantity' => 96,
            'difference_quantity' => 4,
            'notes' => 'Link aviz material.',
        ]);

        StageReport::create([
            'stage_id' => $phase->id,
            'contractor_id' => $contractor->id,
            'report_date' => now()->toDateString(),
            'progress_pct' => 45,
            'activities' => 'Avans bun pe montaj.',
            'issues' => 'Asteptare aviz utilitati.',
            'created_by' => $user->id,
        ]);

        StageTask::create([
            'stage_id' => $phase->id,
            'title' => 'Verificare finisaje',
            'description' => 'Control final pe zona A.',
            'assignee_type' => 'user',
            'assignee_id' => $user->id,
            'deadline' => now()->addDay(),
            'status' => 'in_progress',
        ]);
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
