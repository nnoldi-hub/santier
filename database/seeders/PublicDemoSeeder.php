<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Contractor;
use App\Models\Defect;
use App\Models\Document;
use App\Models\Equipment;
use App\Models\Material;
use App\Models\MaterialInvoice;
use App\Models\PhaseTeamAssignment;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\QualityCheck;
use App\Models\Quote;
use App\Models\StageEquipment;
use App\Models\StageReport;
use App\Models\StageTask;
use App\Models\Task;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PublicDemoSeeder extends Seeder
{
    public function run(): void
    {
        $marker = config('demo.seed_marker', '[demo_seed]');
        $email = config('demo.email', 'demo@santier.local');

        $demoUser = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => config('demo.name', 'Demo Public Santier'),
                'password' => Hash::make(config('demo.password', 'Demo1234!')),
                'email_verified_at' => now(),
                'onboarding_step' => 3,
                'onboarding_completed_at' => now(),
                'billing_plan' => 'pro',
                'billing_trial_ends_at' => now()->addDays(14),
            ]
        );

        $this->cleanupDemoData($demoUser->id, $marker);

        $client = Client::create([
            'tenant_id' => 1,
            'name' => 'Client Demo Office Park',
            'type' => 'company',
            'email' => 'client.demo@santier.local',
            'phone' => '0722000001',
            'address' => 'Str. Constructorilor 24, Bucuresti',
            'contact_person' => 'Andrei Pop',
            'active' => true,
            'notes' => $marker,
        ]);

        $projectA = Project::create([
            'tenant_id' => 1,
            'client_id' => $client->id,
            'created_by' => $demoUser->id,
            'name' => 'Renovare Office Park - Corp A',
            'description' => 'Proiect demo cu etape, taskuri, defecte, devize si exporturi.',
            'address' => 'Bd. Timisoara 12, Bucuresti',
            'status' => 'active',
            'start_date' => now()->subDays(20)->toDateString(),
            'end_date' => now()->addDays(45)->toDateString(),
            'total_budget' => 350000,
            'notes' => $marker,
        ]);

        $projectB = Project::create([
            'tenant_id' => 1,
            'client_id' => $client->id,
            'created_by' => $demoUser->id,
            'name' => 'Amenajare Showroom - Corp B',
            'description' => 'Al doilea proiect demo pentru vizualizare portofoliu.',
            'address' => 'Str. Fabricii 3, Bucuresti',
            'status' => 'active',
            'start_date' => now()->subDays(8)->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
            'total_budget' => 125000,
            'notes' => $marker,
        ]);

        $electricalContractor = Contractor::create([
            'tenant_id' => 1,
            'name' => 'Electro Demo Systems',
            'type' => Contractor::TYPE_SUBCONTRACTOR,
            'contact_name' => 'Mihai Radu',
            'phone' => '0722000002',
            'email' => 'electro.demo@santier.local',
            'notes' => $marker,
            'active' => true,
        ]);

        $finishesContractor = Contractor::create([
            'tenant_id' => 1,
            'name' => 'Finisaje Premium Demo',
            'type' => Contractor::TYPE_SUBCONTRACTOR,
            'contact_name' => 'Bianca Stan',
            'phone' => '0722000003',
            'email' => 'finisaje.demo@santier.local',
            'notes' => $marker,
            'active' => true,
        ]);

        $supplierContractor = Contractor::create([
            'tenant_id' => 1,
            'name' => 'Utilaje & Materiale Demo',
            'type' => Contractor::TYPE_EQUIPMENT_SUPPLIER,
            'contact_name' => 'Sorin Pavel',
            'phone' => '0722000004',
            'email' => 'supplier.demo@santier.local',
            'notes' => $marker,
            'active' => true,
        ]);

        $phaseAParent = ProjectPhase::create([
            'project_id' => $projectA->id,
            'name' => 'Executie Corp A',
            'type' => 'custom',
            'order' => 1,
            'start_date' => now()->subDays(20)->toDateString(),
            'end_date' => now()->addDays(40)->toDateString(),
            'status' => 'in_progress',
            'progress_pct' => 58,
            'notes' => $marker,
        ]);

        $phaseA1 = ProjectPhase::create([
            'project_id' => $projectA->id,
            'name' => 'Instalatii electrice si HVAC',
            'type' => 'instalatii_brute',
            'order' => 1,
            'parent_id' => $phaseAParent->id,
            'start_date' => now()->subDays(18)->toDateString(),
            'end_date' => now()->addDays(8)->toDateString(),
            'status' => 'in_progress',
            'progress_pct' => 62,
            'contractor_id' => $electricalContractor->id,
            'notes' => $marker,
        ]);

        $phaseA2 = ProjectPhase::create([
            'project_id' => $projectA->id,
            'name' => 'Finisaje receptie si sali meeting',
            'type' => 'finisaje_fine',
            'order' => 2,
            'parent_id' => $phaseAParent->id,
            'start_date' => now()->addDays(9)->toDateString(),
            'end_date' => now()->addDays(40)->toDateString(),
            'status' => 'pending',
            'progress_pct' => 0,
            'contractor_id' => $finishesContractor->id,
            'notes' => $marker,
        ]);

        $phaseB1 = ProjectPhase::create([
            'project_id' => $projectB->id,
            'name' => 'Amenajare showroom parter',
            'type' => 'custom',
            'order' => 1,
            'start_date' => now()->subDays(7)->toDateString(),
            'end_date' => now()->addDays(18)->toDateString(),
            'status' => 'in_progress',
            'progress_pct' => 35,
            'contractor_id' => $finishesContractor->id,
            'notes' => $marker,
        ]);

        $team = Team::create([
            'tenant_id' => 1,
            'name' => 'Echipa Demo Finisaje',
            'specialty' => 'Renovari interioare',
            'leader_id' => $demoUser->id,
            'active' => true,
            'notes' => $marker,
        ]);

        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $demoUser->id,
            'role' => 'Coordonator',
            'hourly_rate' => 120,
            'joined_at' => now()->subDays(30)->toDateString(),
        ]);

        PhaseTeamAssignment::create([
            'phase_id' => $phaseA1->id,
            'team_id' => $team->id,
            'workers_needed' => 6,
            'workers_assigned' => 5,
            'start_date' => now()->subDays(15)->toDateString(),
            'end_date' => now()->addDays(6)->toDateString(),
            'notes' => $marker,
        ]);

        PhaseTeamAssignment::create([
            'phase_id' => $phaseA2->id,
            'team_id' => $team->id,
            'workers_needed' => 4,
            'workers_assigned' => 2,
            'start_date' => now()->addDays(9)->toDateString(),
            'end_date' => now()->addDays(28)->toDateString(),
            'notes' => $marker,
        ]);

        $equipmentA = Equipment::create([
            'tenant_id' => 1,
            'name' => 'Generator 60kVA Demo',
            'type' => 'generator',
            'supplier_name' => $supplierContractor->name,
            'cost_per_hour' => 85,
            'availability_status' => Equipment::STATUS_AVAILABLE,
            'active' => true,
            'notes' => $marker,
        ]);

        $equipmentB = Equipment::create([
            'tenant_id' => 1,
            'name' => 'Schela mobila aluminiu Demo',
            'type' => 'scaffold',
            'supplier_name' => $supplierContractor->name,
            'cost_per_hour' => 22,
            'availability_status' => Equipment::STATUS_RESERVED,
            'active' => true,
            'notes' => $marker,
        ]);

        StageEquipment::create([
            'stage_id' => $phaseA1->id,
            'equipment_id' => $equipmentA->id,
            'quantity' => 1,
            'usage_start' => now()->subDays(4)->toDateString(),
            'usage_end' => now()->addDays(2)->toDateString(),
            'notes' => $marker,
        ]);

        StageEquipment::create([
            'stage_id' => $phaseA2->id,
            'equipment_id' => $equipmentB->id,
            'quantity' => 2,
            'usage_start' => now()->addDays(10)->toDateString(),
            'usage_end' => now()->addDays(18)->toDateString(),
            'notes' => $marker,
        ]);

        Task::create([
            'tenant_id' => 1,
            'project_id' => $projectA->id,
            'phase_id' => $phaseA1->id,
            'assigned_to' => $demoUser->id,
            'created_by' => $demoUser->id,
            'title' => 'Montaj tablouri electrice etaj 1',
            'description' => 'Verificare finala si etichetare circuite.',
            'status' => 'in_progress',
            'priority' => 'high',
            'deadline' => now()->addDays(3),
        ]);

        Task::create([
            'tenant_id' => 1,
            'project_id' => $projectA->id,
            'phase_id' => $phaseA2->id,
            'assigned_to' => $demoUser->id,
            'created_by' => $demoUser->id,
            'title' => 'Planificare finisaje zona receptie',
            'description' => 'Pregatire deviz materiale si secventa executie.',
            'status' => 'todo',
            'priority' => 'medium',
            'deadline' => now()->addDays(10),
        ]);

        Defect::create([
            'tenant_id' => 1,
            'project_id' => $projectA->id,
            'phase_id' => $phaseA1->id,
            'reported_by' => $demoUser->id,
            'assigned_to' => $demoUser->id,
            'title' => 'Fisura usoara perete nord',
            'description' => 'Necesita reparatie si retus inainte de receptie partiala.',
            'location' => 'Nivel 1 - zona birouri',
            'status' => 'open',
            'priority' => 'medium',
            'due_date' => now()->addDays(5)->toDateString(),
        ]);

        Quote::create([
            'tenant_id' => 1,
            'project_id' => $projectA->id,
            'version' => 1,
            'title' => 'Deviz lucrari etapa instalatii',
            'status' => 'accepted',
            'valid_until' => now()->addDays(20)->toDateString(),
            'notes' => $marker,
            'total_net' => 120000,
            'total_tva' => 22800,
            'total_gross' => 142800,
            'created_by' => $demoUser->id,
            'sent_at' => now()->subDays(5),
            'accepted_at' => now()->subDays(3),
        ]);

        Quote::create([
            'tenant_id' => 1,
            'project_id' => $projectA->id,
            'version' => 2,
            'title' => 'Oferta finisaje si mobilier fix',
            'status' => 'sent',
            'valid_until' => now()->addDays(14)->toDateString(),
            'notes' => $marker,
            'total_net' => 68000,
            'total_tva' => 12920,
            'total_gross' => 80920,
            'created_by' => $demoUser->id,
            'sent_at' => now()->subDay(),
        ]);

        Material::create([
            'tenant_id' => 1,
            'code' => 'DEMO-CABLU-01',
            'name' => 'Cablu electric FY 2.5',
            'category' => 'Instalatii electrice',
            'unit' => 'rola',
            'unit_price' => 320,
            'supplier' => 'Furnizor Demo Electric',
            'notes' => $marker,
            'active' => true,
        ]);

        Material::create([
            'tenant_id' => 1,
            'code' => 'DEMO-GLET-02',
            'name' => 'Glet finisaj premium',
            'category' => 'Finisaje',
            'unit' => 'sac',
            'unit_price' => 56,
            'supplier' => 'Furnizor Demo Finisaje',
            'notes' => $marker,
            'active' => true,
        ]);

        $materialA = Material::create([
            'tenant_id' => 1,
            'code' => 'DEMO-VOPSEA-03',
            'name' => 'Vopsea lavabila trafic intens',
            'category' => 'Finisaje',
            'unit' => 'galeata',
            'unit_price' => 245,
            'supplier' => 'Furnizor Demo Finisaje',
            'notes' => $marker,
            'active' => true,
        ]);

        Document::create([
            'tenant_id' => 1,
            'contractor_id' => $electricalContractor->id,
            'project_id' => $projectA->id,
            'stage_id' => $phaseA1->id,
            'type' => 'contract',
            'amount' => 95000,
            'issued_at' => now()->subDays(19)->toDateString(),
            'payment_status' => 'paid',
            'title' => 'Contract executie instalatii Corp A',
            'file_path' => 'demo/contracts/contract-instalatii-corp-a.pdf',
            'file_name' => 'contract-instalatii-corp-a.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 248000,
            'notes' => $marker,
        ]);

        Document::create([
            'tenant_id' => 1,
            'contractor_id' => $electricalContractor->id,
            'project_id' => $projectA->id,
            'stage_id' => $phaseA1->id,
            'type' => 'invoice',
            'amount' => 28500,
            'issued_at' => now()->subDays(9)->toDateString(),
            'payment_status' => 'partial',
            'title' => 'Factura progres instalatii iunie',
            'file_path' => 'demo/invoices/factura-instalatii-iunie.pdf',
            'file_name' => 'factura-instalatii-iunie.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 133000,
            'notes' => $marker,
        ]);

        Document::create([
            'tenant_id' => 1,
            'contractor_id' => $finishesContractor->id,
            'project_id' => $projectA->id,
            'stage_id' => $phaseA2->id,
            'type' => 'estimate',
            'amount' => 18800,
            'issued_at' => now()->subDays(2)->toDateString(),
            'payment_status' => 'unpaid',
            'title' => 'Deviz materiale finisaje receptie',
            'file_path' => 'demo/estimates/deviz-finisaje-receptie.xlsx',
            'file_name' => 'deviz-finisaje-receptie.xlsx',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'file_size' => 92000,
            'notes' => $marker,
        ]);

        Document::create([
            'tenant_id' => 1,
            'contractor_id' => $finishesContractor->id,
            'project_id' => $projectA->id,
            'stage_id' => $phaseA2->id,
            'type' => 'offer',
            'amount' => 80920,
            'issued_at' => now()->subDay()->toDateString(),
            'payment_status' => 'unpaid',
            'title' => 'Oferta amenajare receptie si meeting',
            'file_path' => 'demo/offers/oferta-receptie.pdf',
            'file_name' => 'oferta-receptie.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 121000,
            'notes' => $marker,
        ]);

        StageReport::create([
            'stage_id' => $phaseA1->id,
            'contractor_id' => $electricalContractor->id,
            'report_date' => now()->subDays(2)->toDateString(),
            'progress_pct' => 55,
            'activities' => 'Montaj trasee electrice principale si testare generator de santier.',
            'issues' => 'Necesara confirmarea zonei de pozitionare pentru tabloul secundar.',
            'materials_used' => [['name' => 'Cablu electric FY 2.5', 'qty' => 12, 'unit' => 'rola']],
            'equipment_used' => [['name' => 'Generator 60kVA Demo', 'hours' => 14]],
            'images' => ['demo/reports/instalatii-1.jpg'],
            'created_by' => $demoUser->id,
        ]);

        StageReport::create([
            'stage_id' => $phaseA1->id,
            'contractor_id' => $electricalContractor->id,
            'report_date' => now()->toDateString(),
            'progress_pct' => 62,
            'activities' => 'Etichetare circuite etaj 1 si verificare trasee HVAC pentru zona open-space.',
            'issues' => 'Lipsa unei aprobari finale pe pozitia a doua corpuri de iluminat decorative.',
            'materials_used' => [['name' => 'Cablu electric FY 2.5', 'qty' => 4, 'unit' => 'rola']],
            'equipment_used' => [['name' => 'Schela mobila aluminiu Demo', 'hours' => 6]],
            'images' => ['demo/reports/instalatii-2.jpg'],
            'created_by' => $demoUser->id,
        ]);

        StageTask::create([
            'stage_id' => $phaseA1->id,
            'title' => 'Confirmare traseu final HVAC receptie',
            'description' => 'Necesita validare cu arhitectul si PM inainte de inchidere tavane.',
            'assignee_type' => 'user',
            'assignee_id' => $demoUser->id,
            'deadline' => now()->addDays(2),
            'status' => 'in_progress',
        ]);

        StageTask::create([
            'stage_id' => $phaseA2->id,
            'title' => 'Programare echipa glet si vopsitorie',
            'description' => 'Bloc de start pentru etapa de finisaje dupa predarea instalatiilor.',
            'assignee_type' => 'team',
            'assignee_id' => $team->id,
            'deadline' => now()->addDays(11),
            'status' => 'todo',
        ]);

        StageTask::create([
            'stage_id' => $phaseA2->id,
            'title' => 'Confirmare oferta mobilier fix',
            'description' => 'Asteptam raspuns final din partea subcontractorului de finisaje.',
            'assignee_type' => 'contractor',
            'assignee_id' => $finishesContractor->id,
            'deadline' => now()->addDays(6),
            'status' => 'blocked',
        ]);

        QualityCheck::create([
            'tenant_id' => 1,
            'project_id' => $projectA->id,
            'phase_id' => $phaseA1->id,
            'assigned_to' => $demoUser->id,
            'title' => 'Verificare executie trasee electrice',
            'description' => 'Control de conformitate inainte de inchiderea peretilor usori.',
            'check_type' => 'execution',
            'status' => 'in_progress',
            'planned_at' => now()->addDay(),
            'notes' => $marker,
        ]);

        QualityCheck::create([
            'tenant_id' => 1,
            'project_id' => $projectA->id,
            'phase_id' => $phaseA2->id,
            'assigned_to' => $demoUser->id,
            'title' => 'Verificare lot vopsea receptie',
            'description' => 'Validare materiale conform fisa tehnica si paletar aprobat.',
            'check_type' => 'materials',
            'status' => 'pending',
            'planned_at' => now()->addDays(12),
            'notes' => $marker,
        ]);

        MaterialInvoice::create([
            'tenant_id' => 1,
            'project_id' => $projectA->id,
            'phase_id' => $phaseA1->id,
            'material_id' => $materialA->id,
            'supplier_name' => 'Furnizor Demo Finisaje',
            'invoice_no' => 'DEMO-MAT-001',
            'issue_date' => now()->subDays(3)->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'amount_net' => 4200,
            'amount_vat' => 798,
            'amount_total' => 4998,
            'payment_status' => 'unpaid',
            'notes' => $marker,
        ]);

        MaterialInvoice::create([
            'tenant_id' => 1,
            'project_id' => $projectA->id,
            'phase_id' => $phaseA2->id,
            'material_id' => $materialA->id,
            'supplier_name' => 'Furnizor Demo Finisaje',
            'invoice_no' => 'DEMO-MAT-002',
            'issue_date' => now()->subDay()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'amount_net' => 6100,
            'amount_vat' => 1159,
            'amount_total' => 7259,
            'payment_status' => 'partial',
            'notes' => $marker,
        ]);

        Task::create([
            'tenant_id' => 1,
            'project_id' => $projectB->id,
            'phase_id' => $phaseB1->id,
            'assigned_to' => $demoUser->id,
            'created_by' => $demoUser->id,
            'title' => 'Stabilire layout final showroom',
            'description' => 'Aprobarea traseului de circulatie si mobilierului de expunere.',
            'status' => 'todo',
            'priority' => 'medium',
            'deadline' => now()->addDays(4),
        ]);
    }

    private function cleanupDemoData(int $demoUserId, string $marker): void
    {
        $projectIds = Project::query()
            ->where('created_by', $demoUserId)
            ->where('notes', 'like', '%' . $marker . '%')
            ->pluck('id');

        if ($projectIds->isNotEmpty()) {
            $phaseIds = ProjectPhase::query()->whereIn('project_id', $projectIds)->pluck('id');

            if ($phaseIds->isNotEmpty()) {
                StageEquipment::query()->whereIn('stage_id', $phaseIds)->delete();
                PhaseTeamAssignment::query()->whereIn('phase_id', $phaseIds)->delete();
                StageReport::query()->whereIn('stage_id', $phaseIds)->delete();
                StageTask::query()->whereIn('stage_id', $phaseIds)->delete();
                QualityCheck::query()->whereIn('phase_id', $phaseIds)->delete();
                Defect::query()->whereIn('phase_id', $phaseIds)->delete();
                Document::query()->whereIn('stage_id', $phaseIds)->delete();
                MaterialInvoice::query()->whereIn('phase_id', $phaseIds)->delete();
            }

            Quote::query()->whereIn('project_id', $projectIds)->delete();
            Task::query()->whereIn('project_id', $projectIds)->delete();
            Defect::query()->whereIn('project_id', $projectIds)->delete();
            Document::query()->whereIn('project_id', $projectIds)->delete();
            QualityCheck::query()->whereIn('project_id', $projectIds)->delete();
            MaterialInvoice::query()->whereIn('project_id', $projectIds)->delete();
            ProjectPhase::query()->whereIn('project_id', $projectIds)->delete();
            Project::query()->whereIn('id', $projectIds)->forceDelete();
        }

        $teamIds = Team::query()
            ->where('leader_id', $demoUserId)
            ->where('notes', 'like', '%' . $marker . '%')
            ->pluck('id');

        if ($teamIds->isNotEmpty()) {
            TeamMember::query()->whereIn('team_id', $teamIds)->delete();
        }

        Team::query()
            ->where('leader_id', $demoUserId)
            ->where('notes', 'like', '%' . $marker . '%')
            ->forceDelete();

        Contractor::query()
            ->where('notes', 'like', '%' . $marker . '%')
            ->forceDelete();

        Equipment::query()
            ->where('notes', 'like', '%' . $marker . '%')
            ->forceDelete();

        Material::query()
            ->where('code', 'like', 'DEMO-%')
            ->forceDelete();

        Client::query()
            ->where('notes', 'like', '%' . $marker . '%')
            ->forceDelete();
    }
}
