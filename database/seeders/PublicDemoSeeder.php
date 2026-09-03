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
use App\Models\Tenant;
use App\Models\TenantUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class PublicDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(IamSeeder::class);

        $marker = config('demo.seed_marker', '[demo_seed]');
        $email = config('demo.email', 'demo@modulia.ro');

        $demoTenant = Tenant::updateOrCreate(
            ['slug' => 'demo-public'],
            [
                'name' => 'Modulia Demo',
                'billing_plan' => 'enterprise',
                'status' => 'active',
                'module_flags' => [],
            ]
        );

        $demoUser = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => config('demo.name', 'Demo Public Santier'),
                'password' => Hash::make(config('demo.password', 'Demo1234!')),
                'email_verified_at' => now(),
                'onboarding_step' => 3,
                'onboarding_completed_at' => now(),
                'billing_plan' => 'enterprise',
                'billing_trial_ends_at' => now()->addDays(14),
                'tenant_id' => $demoTenant->id,
                'current_tenant_id' => $demoTenant->id,
                'is_superadmin' => false,
            ]
        );

        $demoUser->tenant_id = $demoTenant->id;
        $demoUser->current_tenant_id = $demoTenant->id;
        $demoUser->save();

        TenantUser::updateOrCreate(
            ['tenant_id' => $demoTenant->id, 'user_id' => $demoUser->id],
            ['status' => 'active', 'joined_at' => now()]
        );

        $tenantAdminRole = Role::query()
            ->whereNull('tenant_id')
            ->where('guard_name', 'web')
            ->firstWhere('name', 'tenant_admin');
        if ($tenantAdminRole) {
            $demoUser->syncRoles([$tenantAdminRole]);
        }

        $demoTenantId = $demoTenant->id;

        $this->cleanupDemoData($demoUser->id, $marker, $demoTenantId);

        $client = Client::create([
            'tenant_id' => $demoTenantId,
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
            'tenant_id' => $demoTenantId,
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
            'tenant_id' => $demoTenantId,
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
            'tenant_id' => $demoTenantId,
            'name' => 'Electro Demo Systems',
            'type' => Contractor::TYPE_SUBCONTRACTOR,
            'contact_name' => 'Mihai Radu',
            'phone' => '0722000002',
            'email' => 'electro.demo@santier.local',
            'notes' => $marker,
            'active' => true,
        ]);

        $finishesContractor = Contractor::create([
            'tenant_id' => $demoTenantId,
            'name' => 'Finisaje Premium Demo',
            'type' => Contractor::TYPE_SUBCONTRACTOR,
            'contact_name' => 'Bianca Stan',
            'phone' => '0722000003',
            'email' => 'finisaje.demo@santier.local',
            'notes' => $marker,
            'active' => true,
        ]);

        $supplierContractor = Contractor::create([
            'tenant_id' => $demoTenantId,
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
            'tenant_id' => $demoTenantId,
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
            'tenant_id' => $demoTenantId,
            'name' => 'Generator 60kVA Demo',
            'type' => 'generator',
            'supplier_name' => $supplierContractor->name,
            'cost_per_hour' => 85,
            'availability_status' => Equipment::STATUS_AVAILABLE,
            'active' => true,
            'notes' => $marker,
        ]);

        $equipmentB = Equipment::create([
            'tenant_id' => $demoTenantId,
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
            'tenant_id' => $demoTenantId,
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
            'tenant_id' => $demoTenantId,
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
            'tenant_id' => $demoTenantId,
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
            'tenant_id' => $demoTenantId,
            'project_id' => $projectA->id,
            'version' => 1,
            'title' => 'Deviz lucrari etapa instalatii',
            'status' => 'accepted',
            'valid_until' => now()->addDays(20)->toDateString(),
            'notes' => $marker,
            'total_net' => 120000,
            'total_tva' => 25200,
            'total_gross' => 145200,
            'created_by' => $demoUser->id,
            'sent_at' => now()->subDays(5),
            'accepted_at' => now()->subDays(3),
        ]);

        Quote::create([
            'tenant_id' => $demoTenantId,
            'project_id' => $projectA->id,
            'version' => 2,
            'title' => 'Oferta finisaje si mobilier fix',
            'status' => 'sent',
            'valid_until' => now()->addDays(14)->toDateString(),
            'notes' => $marker,
            'total_net' => 68000,
            'total_tva' => 14280,
            'total_gross' => 82280,
            'created_by' => $demoUser->id,
            'sent_at' => now()->subDay(),
        ]);

        Material::create([
            'tenant_id' => $demoTenantId,
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
            'tenant_id' => $demoTenantId,
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
            'tenant_id' => $demoTenantId,
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
            'tenant_id' => $demoTenantId,
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
            'tenant_id' => $demoTenantId,
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
            'tenant_id' => $demoTenantId,
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
            'tenant_id' => $demoTenantId,
            'contractor_id' => $finishesContractor->id,
            'project_id' => $projectA->id,
            'stage_id' => $phaseA2->id,
            'type' => 'offer',
            'amount' => 82280,
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
            'tenant_id' => $demoTenantId,
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
            'tenant_id' => $demoTenantId,
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
            'tenant_id' => $demoTenantId,
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
            'tenant_id' => $demoTenantId,
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
            'tenant_id' => $demoTenantId,
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

        $this->seedAdditionalDemoProjects($demoTenantId, $demoUser, $marker, $supplierContractor, $finishesContractor);
    }

    /**
     * Portofoliu extins pentru filmari/promovare: proiecte distincte de
     * constructii, instalatii si finisaje, plus un proiect finalizat si unul
     * suspendat, ca dashboard-urile si rapoartele sa arate un portofoliu real.
     */
    private function seedAdditionalDemoProjects(int $demoTenantId, User $demoUser, string $marker, Contractor $supplierContractor, Contractor $finishesContractor): void
    {
        $clientResidential = Client::create([
            'tenant_id' => $demoTenantId,
            'name' => 'Rezidential Grup Ilfov',
            'type' => 'company',
            'email' => 'client.rezidential.demo@santier.local',
            'phone' => '0722000005',
            'address' => 'Sos. Bucuresti-Ploiesti 45, Voluntari',
            'contact_person' => 'Ioana Marinescu',
            'active' => true,
            'notes' => $marker,
        ]);

        $clientIndustrial = Client::create([
            'tenant_id' => $demoTenantId,
            'name' => 'Logistic Depo Industrial SRL',
            'type' => 'company',
            'email' => 'client.industrial.demo@santier.local',
            'phone' => '0722000006',
            'address' => 'DN1 Km 22, Ploiesti',
            'contact_person' => 'Radu Constantin',
            'active' => true,
            'notes' => $marker,
        ]);

        $structuralContractor = Contractor::create([
            'tenant_id' => $demoTenantId,
            'name' => 'Structuri & Fundatii Demo',
            'type' => Contractor::TYPE_SUBCONTRACTOR,
            'contact_name' => 'Vasile Toma',
            'phone' => '0722000007',
            'email' => 'structuri.demo@santier.local',
            'notes' => $marker,
            'active' => true,
        ]);

        $sanitaryContractor = Contractor::create([
            'tenant_id' => $demoTenantId,
            'name' => 'Sanitare & Termice Demo',
            'type' => Contractor::TYPE_SUBCONTRACTOR,
            'contact_name' => 'Elena Dumitrescu',
            'phone' => '0722000008',
            'email' => 'sanitare.demo@santier.local',
            'notes' => $marker,
            'active' => true,
        ]);

        $materialCiment = Material::create([
            'tenant_id' => $demoTenantId,
            'code' => 'DEMO-CIMENT-04',
            'name' => 'Ciment Portland CEM II',
            'category' => 'Constructii',
            'unit' => 'sac',
            'unit_price' => 38,
            'supplier' => 'Furnizor Demo Constructii',
            'notes' => $marker,
            'active' => true,
        ]);

        $materialTeava = Material::create([
            'tenant_id' => $demoTenantId,
            'code' => 'DEMO-TEAVA-05',
            'name' => 'Teava PPR 25mm',
            'category' => 'Instalatii sanitare',
            'unit' => 'bucata',
            'unit_price' => 18,
            'supplier' => 'Furnizor Demo Instalatii',
            'notes' => $marker,
            'active' => true,
        ]);

        $equipmentExcavator = Equipment::create([
            'tenant_id' => $demoTenantId,
            'name' => 'Excavator pe senile Demo',
            'type' => 'excavator',
            'supplier_name' => $supplierContractor->name,
            'cost_per_hour' => 180,
            'availability_status' => Equipment::STATUS_RESERVED,
            'active' => true,
            'notes' => $marker,
        ]);

        $equipmentPompa = Equipment::create([
            'tenant_id' => $demoTenantId,
            'name' => 'Pompa presiune instalatii Demo',
            'type' => 'pump',
            'supplier_name' => $supplierContractor->name,
            'cost_per_hour' => 35,
            'availability_status' => Equipment::STATUS_AVAILABLE,
            'active' => true,
            'notes' => $marker,
        ]);

        $teamConstructii = Team::create([
            'tenant_id' => $demoTenantId,
            'name' => 'Echipa Demo Constructii Civile',
            'specialty' => 'Structuri si fundatii',
            'leader_id' => $demoUser->id,
            'active' => true,
            'notes' => $marker,
        ]);

        TeamMember::create([
            'team_id' => $teamConstructii->id,
            'user_id' => $demoUser->id,
            'role' => 'Sef de santier',
            'hourly_rate' => 140,
            'joined_at' => now()->subDays(60)->toDateString(),
        ]);

        // --- Proiect Constructii: hala industriala, activ ---
        $projectConstructii = Project::create([
            'tenant_id' => $demoTenantId,
            'client_id' => $clientIndustrial->id,
            'created_by' => $demoUser->id,
            'name' => 'Constructie Hala Industriala - Faza 1',
            'description' => 'Proiect demo de constructii civile: demolare, fundatie si structura de rezistenta.',
            'address' => 'DN1 Km 22, Ploiesti',
            'status' => 'active',
            'start_date' => now()->subDays(35)->toDateString(),
            'end_date' => now()->addDays(90)->toDateString(),
            'total_budget' => 850000,
            'notes' => $marker,
        ]);

        $phaseDemolare = ProjectPhase::create([
            'project_id' => $projectConstructii->id,
            'name' => 'Demolare structuri existente',
            'type' => 'demolare',
            'order' => 1,
            'start_date' => now()->subDays(35)->toDateString(),
            'end_date' => now()->subDays(20)->toDateString(),
            'status' => 'completed',
            'progress_pct' => 100,
            'contractor_id' => $structuralContractor->id,
            'notes' => $marker,
        ]);

        $phaseStructura = ProjectPhase::create([
            'project_id' => $projectConstructii->id,
            'name' => 'Structura de rezistenta si fundatie',
            'type' => 'structura',
            'order' => 2,
            'start_date' => now()->subDays(19)->toDateString(),
            'end_date' => now()->addDays(35)->toDateString(),
            'status' => 'in_progress',
            'progress_pct' => 45,
            'contractor_id' => $structuralContractor->id,
            'notes' => $marker,
        ]);

        PhaseTeamAssignment::create([
            'phase_id' => $phaseStructura->id,
            'team_id' => $teamConstructii->id,
            'workers_needed' => 10,
            'workers_assigned' => 8,
            'start_date' => now()->subDays(10)->toDateString(),
            'end_date' => now()->addDays(9)->toDateString(),
            'notes' => $marker,
        ]);

        StageEquipment::create([
            'stage_id' => $phaseStructura->id,
            'equipment_id' => $equipmentExcavator->id,
            'quantity' => 1,
            'usage_start' => now()->subDays(3)->toDateString(),
            'usage_end' => now()->addDays(4)->toDateString(),
            'notes' => $marker,
        ]);

        Task::create([
            'tenant_id' => $demoTenantId,
            'project_id' => $projectConstructii->id,
            'phase_id' => $phaseStructura->id,
            'assigned_to' => $demoUser->id,
            'created_by' => $demoUser->id,
            'title' => 'Turnare fundatie corp productie',
            'description' => 'Coordonare turnare beton si verificare armaturi.',
            'status' => 'in_progress',
            'priority' => 'high',
            'deadline' => now()->addDays(5),
        ]);

        Task::create([
            'tenant_id' => $demoTenantId,
            'project_id' => $projectConstructii->id,
            'phase_id' => $phaseStructura->id,
            'assigned_to' => $demoUser->id,
            'created_by' => $demoUser->id,
            'title' => 'Montaj structura metalica hala',
            'description' => 'Pregatire echipa si utilaje pentru ridicarea structurii.',
            'status' => 'todo',
            'priority' => 'high',
            'deadline' => now()->addDays(15),
        ]);

        Defect::create([
            'tenant_id' => $demoTenantId,
            'project_id' => $projectConstructii->id,
            'phase_id' => $phaseStructura->id,
            'reported_by' => $demoUser->id,
            'assigned_to' => $demoUser->id,
            'title' => 'Fisura minora in placa turnata',
            'description' => 'Necesita evaluare structurala inainte de continuarea lucrarilor.',
            'location' => 'Zona productie - ax B3',
            'status' => 'open',
            'priority' => 'high',
            'due_date' => now()->addDays(3)->toDateString(),
        ]);

        Quote::create([
            'tenant_id' => $demoTenantId,
            'project_id' => $projectConstructii->id,
            'version' => 1,
            'title' => 'Deviz structura de rezistenta',
            'status' => 'accepted',
            'valid_until' => now()->addDays(30)->toDateString(),
            'notes' => $marker,
            'total_net' => 520000,
            'total_tva' => 109200,
            'total_gross' => 629200,
            'created_by' => $demoUser->id,
            'sent_at' => now()->subDays(30),
            'accepted_at' => now()->subDays(28),
        ]);

        Document::create([
            'tenant_id' => $demoTenantId,
            'contractor_id' => $structuralContractor->id,
            'project_id' => $projectConstructii->id,
            'stage_id' => $phaseStructura->id,
            'type' => 'invoice',
            'amount' => 210000,
            'issued_at' => now()->subDays(12)->toDateString(),
            'payment_status' => 'partial',
            'title' => 'Factura progres structura - transa 1',
            'file_path' => 'demo/invoices/factura-structura-transa1.pdf',
            'file_name' => 'factura-structura-transa1.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 189000,
            'notes' => $marker,
        ]);

        QualityCheck::create([
            'tenant_id' => $demoTenantId,
            'project_id' => $projectConstructii->id,
            'phase_id' => $phaseStructura->id,
            'assigned_to' => $demoUser->id,
            'title' => 'Verificare rezistenta beton fundatie',
            'description' => 'Testare cuburi de beton conform normativ, inainte de continuarea structurii.',
            'check_type' => 'materials',
            'status' => 'in_progress',
            'planned_at' => now()->addDays(2),
            'notes' => $marker,
        ]);

        StageReport::create([
            'stage_id' => $phaseStructura->id,
            'contractor_id' => $structuralContractor->id,
            'report_date' => now()->subDay()->toDateString(),
            'progress_pct' => 45,
            'activities' => 'Cofrare si armare stalpi zona B, pregatire turnare pentru saptamana urmatoare.',
            'issues' => 'Intarziere livrare armatura suplimentara, impact minor asupra graficului.',
            'materials_used' => [['name' => 'Ciment Portland CEM II', 'qty' => 80, 'unit' => 'sac']],
            'equipment_used' => [['name' => 'Excavator pe senile Demo', 'hours' => 18]],
            'images' => ['demo/reports/constructii-1.jpg'],
            'created_by' => $demoUser->id,
        ]);

        MaterialInvoice::create([
            'tenant_id' => $demoTenantId,
            'project_id' => $projectConstructii->id,
            'phase_id' => $phaseStructura->id,
            'material_id' => $materialCiment->id,
            'supplier_name' => 'Furnizor Demo Constructii',
            'invoice_no' => 'DEMO-MAT-003',
            'issue_date' => now()->subDays(6)->toDateString(),
            'due_date' => now()->addDays(9)->toDateString(),
            'amount_net' => 15200,
            'amount_vat' => 2888,
            'amount_total' => 18088,
            'payment_status' => 'unpaid',
            'notes' => $marker,
        ]);

        // --- Proiect Instalatii: modernizare sanitare/termice, activ ---
        $projectInstalatii = Project::create([
            'tenant_id' => $demoTenantId,
            'client_id' => $clientResidential->id,
            'created_by' => $demoUser->id,
            'name' => 'Modernizare Instalatii Sanitare si Termice - Bloc Rezidential',
            'description' => 'Proiect demo dedicat exclusiv instalatiilor sanitare si termice.',
            'address' => 'Sos. Bucuresti-Ploiesti 45, Voluntari',
            'status' => 'active',
            'start_date' => now()->subDays(14)->toDateString(),
            'end_date' => now()->addDays(25)->toDateString(),
            'total_budget' => 210000,
            'notes' => $marker,
        ]);

        $phaseInstalatiiParter = ProjectPhase::create([
            'project_id' => $projectInstalatii->id,
            'name' => 'Instalatii sanitare parter',
            'type' => 'instalatii_brute',
            'order' => 1,
            'start_date' => now()->subDays(14)->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'status' => 'in_progress',
            'progress_pct' => 70,
            'contractor_id' => $sanitaryContractor->id,
            'notes' => $marker,
        ]);

        $phaseInstalatiiEtaj = ProjectPhase::create([
            'project_id' => $projectInstalatii->id,
            'name' => 'Instalatii termice etaj 1',
            'type' => 'instalatii_brute',
            'order' => 2,
            'start_date' => now()->addDays(4)->toDateString(),
            'end_date' => now()->addDays(25)->toDateString(),
            'status' => 'pending',
            'progress_pct' => 0,
            'contractor_id' => $sanitaryContractor->id,
            'notes' => $marker,
        ]);

        StageEquipment::create([
            'stage_id' => $phaseInstalatiiParter->id,
            'equipment_id' => $equipmentPompa->id,
            'quantity' => 1,
            'usage_start' => now()->subDays(2)->toDateString(),
            'usage_end' => now()->addDays(5)->toDateString(),
            'notes' => $marker,
        ]);

        Task::create([
            'tenant_id' => $demoTenantId,
            'project_id' => $projectInstalatii->id,
            'phase_id' => $phaseInstalatiiParter->id,
            'assigned_to' => $demoUser->id,
            'created_by' => $demoUser->id,
            'title' => 'Inlocuire coloane sanitare parter',
            'description' => 'Coordonare cu locatarii pentru accesul in apartamente.',
            'status' => 'in_progress',
            'priority' => 'medium',
            'deadline' => now()->addDays(4),
        ]);

        Task::create([
            'tenant_id' => $demoTenantId,
            'project_id' => $projectInstalatii->id,
            'phase_id' => $phaseInstalatiiEtaj->id,
            'assigned_to' => $demoUser->id,
            'created_by' => $demoUser->id,
            'title' => 'Comanda materiale instalatii termice etaj 1',
            'description' => 'Finalizare lista de materiale si confirmare furnizor.',
            'status' => 'todo',
            'priority' => 'medium',
            'deadline' => now()->addDays(6),
        ]);

        Defect::create([
            'tenant_id' => $demoTenantId,
            'project_id' => $projectInstalatii->id,
            'phase_id' => $phaseInstalatiiParter->id,
            'reported_by' => $demoUser->id,
            'assigned_to' => $demoUser->id,
            'title' => 'Scurgere minora la imbinare teava',
            'description' => 'Reparata pe loc, necesita confirmare finala la receptie.',
            'location' => 'Parter - apartament 2',
            'status' => 'in_progress',
            'priority' => 'low',
            'due_date' => now()->addDays(2)->toDateString(),
        ]);

        Quote::create([
            'tenant_id' => $demoTenantId,
            'project_id' => $projectInstalatii->id,
            'version' => 1,
            'title' => 'Deviz modernizare instalatii sanitare si termice',
            'status' => 'sent',
            'valid_until' => now()->addDays(15)->toDateString(),
            'notes' => $marker,
            'total_net' => 176000,
            'total_tva' => 36960,
            'total_gross' => 212960,
            'created_by' => $demoUser->id,
            'sent_at' => now()->subDays(4),
        ]);

        Document::create([
            'tenant_id' => $demoTenantId,
            'contractor_id' => $sanitaryContractor->id,
            'project_id' => $projectInstalatii->id,
            'stage_id' => $phaseInstalatiiParter->id,
            'type' => 'contract',
            'amount' => 210000,
            'issued_at' => now()->subDays(14)->toDateString(),
            'payment_status' => 'partial',
            'title' => 'Contract modernizare instalatii sanitare/termice',
            'file_path' => 'demo/contracts/contract-instalatii-sanitare.pdf',
            'file_name' => 'contract-instalatii-sanitare.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 201000,
            'notes' => $marker,
        ]);

        StageTask::create([
            'stage_id' => $phaseInstalatiiParter->id,
            'title' => 'Programare receptie partiala sanitare parter',
            'description' => 'Confirmare disponibilitate diriginte de santier.',
            'assignee_type' => 'user',
            'assignee_id' => $demoUser->id,
            'deadline' => now()->addDays(3),
            'status' => 'todo',
        ]);

        MaterialInvoice::create([
            'tenant_id' => $demoTenantId,
            'project_id' => $projectInstalatii->id,
            'phase_id' => $phaseInstalatiiParter->id,
            'material_id' => $materialTeava->id,
            'supplier_name' => 'Furnizor Demo Instalatii',
            'invoice_no' => 'DEMO-MAT-004',
            'issue_date' => now()->subDays(5)->toDateString(),
            'due_date' => now()->addDays(10)->toDateString(),
            'amount_net' => 3200,
            'amount_vat' => 608,
            'amount_total' => 3808,
            'payment_status' => 'paid',
            'notes' => $marker,
        ]);

        // --- Proiect Finisaje: apartamente bloc nou, finalizat ---
        $projectFinisaje = Project::create([
            'tenant_id' => $demoTenantId,
            'client_id' => $clientResidential->id,
            'created_by' => $demoUser->id,
            'name' => 'Finisaje Apartamente Bloc Nou - Etapa 2',
            'description' => 'Proiect demo finalizat, pentru portofoliu si istoric de referinte.',
            'address' => 'Str. Narciselor 8, Voluntari',
            'status' => 'completed',
            'start_date' => now()->subDays(90)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
            'total_budget' => 175000,
            'notes' => $marker,
        ]);

        $phaseFinisajeGleturi = ProjectPhase::create([
            'project_id' => $projectFinisaje->id,
            'name' => 'Gleturi si zugraveli apartamente',
            'type' => 'zugraveli',
            'order' => 1,
            'start_date' => now()->subDays(90)->toDateString(),
            'end_date' => now()->subDays(40)->toDateString(),
            'status' => 'completed',
            'progress_pct' => 100,
            'contractor_id' => $finishesContractor->id,
            'notes' => $marker,
        ]);

        $phaseFinisajePardoseli = ProjectPhase::create([
            'project_id' => $projectFinisaje->id,
            'name' => 'Montaj pardoseli si tamplarie interioara',
            'type' => 'pardoseli',
            'order' => 2,
            'start_date' => now()->subDays(39)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
            'status' => 'completed',
            'progress_pct' => 100,
            'contractor_id' => $finishesContractor->id,
            'notes' => $marker,
        ]);

        Task::create([
            'tenant_id' => $demoTenantId,
            'project_id' => $projectFinisaje->id,
            'phase_id' => $phaseFinisajePardoseli->id,
            'assigned_to' => $demoUser->id,
            'created_by' => $demoUser->id,
            'title' => 'Predare finala apartamente etapa 2',
            'description' => 'Semnare proces verbal de predare-primire cu clientul.',
            'status' => 'done',
            'priority' => 'medium',
            'deadline' => now()->subDays(5),
            'completed_at' => now()->subDays(5),
        ]);

        Defect::create([
            'tenant_id' => $demoTenantId,
            'project_id' => $projectFinisaje->id,
            'phase_id' => $phaseFinisajePardoseli->id,
            'reported_by' => $demoUser->id,
            'assigned_to' => $demoUser->id,
            'title' => 'Zgarietura parchet apartament 12',
            'description' => 'Remediata inainte de predarea finala catre client.',
            'location' => 'Apartament 12',
            'status' => 'resolved',
            'priority' => 'low',
            'due_date' => now()->subDays(6)->toDateString(),
        ]);

        Quote::create([
            'tenant_id' => $demoTenantId,
            'project_id' => $projectFinisaje->id,
            'version' => 1,
            'title' => 'Deviz finisaje apartamente etapa 2',
            'status' => 'accepted',
            'valid_until' => now()->subDays(60)->toDateString(),
            'notes' => $marker,
            'total_net' => 147000,
            'total_tva' => 30870,
            'total_gross' => 177870,
            'created_by' => $demoUser->id,
            'sent_at' => now()->subDays(92),
            'accepted_at' => now()->subDays(90),
        ]);

        Document::create([
            'tenant_id' => $demoTenantId,
            'contractor_id' => $finishesContractor->id,
            'project_id' => $projectFinisaje->id,
            'stage_id' => $phaseFinisajePardoseli->id,
            'type' => 'invoice',
            'amount' => 177870,
            'issued_at' => now()->subDays(5)->toDateString(),
            'payment_status' => 'paid',
            'title' => 'Factura finala finisaje etapa 2',
            'file_path' => 'demo/invoices/factura-finisaje-etapa2.pdf',
            'file_name' => 'factura-finisaje-etapa2.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 156000,
            'notes' => $marker,
        ]);

        QualityCheck::create([
            'tenant_id' => $demoTenantId,
            'project_id' => $projectFinisaje->id,
            'phase_id' => $phaseFinisajePardoseli->id,
            'assigned_to' => $demoUser->id,
            'title' => 'Verificare finala calitate finisaje',
            'description' => 'Control vizual si masuratori inainte de predare.',
            'check_type' => 'execution',
            'status' => 'completed',
            'planned_at' => now()->subDays(6),
            'notes' => $marker,
        ]);

        // --- Proiect Constructii suspendat, pentru diversitate de status ---
        Project::create([
            'tenant_id' => $demoTenantId,
            'client_id' => $clientIndustrial->id,
            'created_by' => $demoUser->id,
            'name' => 'Extindere Depozit Logistic Nord',
            'description' => 'Proiect demo suspendat temporar, in asteptarea avizelor.',
            'address' => 'DN1 Km 30, Ploiesti',
            'status' => 'paused',
            'start_date' => now()->subDays(50)->toDateString(),
            'end_date' => now()->addDays(120)->toDateString(),
            'total_budget' => 480000,
            'notes' => $marker,
        ]);
    }

    private function cleanupDemoData(int $demoUserId, string $marker, int $demoTenantId): void
    {
        $projectIds = Project::query()
            ->where('tenant_id', $demoTenantId)
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
            ->where('tenant_id', $demoTenantId)
            ->where('leader_id', $demoUserId)
            ->where('notes', 'like', '%' . $marker . '%')
            ->pluck('id');

        if ($teamIds->isNotEmpty()) {
            TeamMember::query()->whereIn('team_id', $teamIds)->delete();
        }

        Team::query()
            ->where('tenant_id', $demoTenantId)
            ->where('leader_id', $demoUserId)
            ->where('notes', 'like', '%' . $marker . '%')
            ->forceDelete();

        Contractor::query()
            ->where('tenant_id', $demoTenantId)
            ->where('notes', 'like', '%' . $marker . '%')
            ->forceDelete();

        Equipment::query()
            ->where('tenant_id', $demoTenantId)
            ->where('notes', 'like', '%' . $marker . '%')
            ->forceDelete();

        Material::query()
            ->where('tenant_id', $demoTenantId)
            ->where('code', 'like', 'DEMO-%')
            ->forceDelete();

        Client::query()
            ->where('tenant_id', $demoTenantId)
            ->where('notes', 'like', '%' . $marker . '%')
            ->forceDelete();
    }
}
