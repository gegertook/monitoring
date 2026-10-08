<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\ProductionLine;
use App\Models\Employee;
use App\Models\StyleOrder;
use App\Models\BreakdownProcess;
use App\Models\EmployeeLineAssignment;
use App\Models\OperatorTask;
use App\Models\HourlyOutput;
use App\Models\Notification;
use App\Models\QcDefect;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Core Staff Users (Managed by HRD)
        $users = [
            ['nik' => 'PPIC001', 'name' => 'Ahmad PPIC', 'email' => 'ppic@mlmps.com', 'role' => 'ppic'],
            ['nik' => 'IE001', 'name' => 'Budi IE', 'email' => 'ie@mlmps.com', 'role' => 'ie'],
            ['nik' => 'HRD001', 'name' => 'Citra HRD', 'email' => 'hrd@mlmps.com', 'role' => 'hrd'],
            ['nik' => 'SPV001', 'name' => 'Dedi Supervisor', 'email' => 'spv@mlmps.com', 'role' => 'spv'],
            ['nik' => 'SPV002', 'name' => 'Gita Supervisor', 'email' => 'spv2@mlmps.com', 'role' => 'spv'],
            ['nik' => 'QC001', 'name' => 'Hendra Admin QC', 'email' => 'qc@mlmps.com', 'role' => 'admin_qc'],
            ['nik' => 'ADM001', 'name' => 'Eka Admin Produksi', 'email' => 'admin@mlmps.com', 'role' => 'admin_produksi'],
            ['nik' => 'MGR001', 'name' => 'Fajar Plant Manager', 'email' => 'manager@mlmps.com', 'role' => 'manager'],
        ];

        foreach ($users as $user) {
            User::create([
                'nik' => $user['nik'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
                'password' => Hash::make('password123'),
                'is_active' => true,
            ]);
        }

        $ppicUser = User::where('role', 'ppic')->first();
        $ieUser   = User::where('role', 'ie')->first();
        $hrdUser  = User::where('role', 'hrd')->first();
        $spvUser  = User::where('role', 'spv')->first();
        $qcUser   = User::where('role', 'admin_qc')->first();
        $admUser  = User::where('role', 'admin_produksi')->first();
        $mgrUser  = User::where('role', 'manager')->first();

        // 2. Create 30 Production Lines (Authority of Manager to Add/Edit/Toggle Active)
        $createdLines = [];
        for ($i = 1; $i <= 30; $i++) {
            // Line 1 to Line 24 are active, Line 25 to 30 are temporarily inactive (maintenance / standby)
            $isActive = ($i <= 24);
            $capacity = 25 + ($i % 3) * 5; // 25, 30, or 35 operators
            
            $createdLines[] = ProductionLine::create([
                'name' => 'Line ' . $i,
                'capacity' => $capacity,
                'is_active' => $isActive,
            ]);
        }

        // 3. Create 25 Employees (Operators)
        $skillLevels = ['junior', 'intermediate', 'senior'];
        $createdEmployees = [];
        $sampleNames = [
            'Budi Santoso', 'Siti Aminah', 'Agus Setiawan', 'Dewi Lestari', 'Rian Pratama',
            'Maya Indah', 'Eko Prasetyo', 'Fitri Handayani', 'Hadi Wijaya', 'Lina Marlina',
            'Doni Kusuma', 'Rina Wulandari', 'Andi Saputra', 'Tari Anggraini', 'Joko Susilo',
            'Surya Saputra', 'Nia Ramadhani', 'Bambang Irawan', 'Tri Wahyuni', 'Dian Permata',
            'Ilham Maulana', 'Nurul Hidayah', 'Arief Budiman', 'Yuliana Sari', 'Wahyu Hidayat'
        ];

        for ($i = 1; $i <= 25; $i++) {
            $createdEmployees[] = Employee::create([
                'nik' => 'EMP' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'name' => $sampleNames[$i - 1],
                'skill_level' => $skillLevels[$i % 3],
                'created_by' => $hrdUser->id,
                'is_active' => true,
            ]);
        }

        // 4. Sample Style Orders
        $so1 = StyleOrder::create([
            'code_style' => 'STY-TSHIRT-01',
            'buyer' => 'Uniqlo Global Corp',
            'total_qty_target' => 2500,
            'deadline' => Carbon::today()->addDays(7),
            'status' => 'in_progress',
            'assigned_line_id' => $createdLines[0]->id, // Line 1
            'created_by' => $ppicUser->id,
        ]);

        $so2 = StyleOrder::create([
            'code_style' => 'STY-HOODIE-02',
            'buyer' => 'H&M Logistics BV',
            'total_qty_target' => 1500,
            'deadline' => Carbon::today()->addDays(12),
            'status' => 'in_progress',
            'assigned_line_id' => $createdLines[1]->id, // Line 2
            'created_by' => $ppicUser->id,
        ]);

        $so3 = StyleOrder::create([
            'code_style' => 'STY-CARGO-03',
            'buyer' => 'Zara International',
            'total_qty_target' => 1000,
            'deadline' => Carbon::today()->addDays(15),
            'status' => 'in_progress',
            'assigned_line_id' => $createdLines[2]->id, // Line 3
            'created_by' => $ppicUser->id,
        ]);

        // 5. Breakdown Processes
        $processesData = [
            ['sequence_number' => 1, 'process_name' => 'Potong Pola Badan (Cutting)', 'smv' => 0.3500, 'target_per_hour' => 170],
            ['sequence_number' => 2, 'process_name' => 'Jahit Sambung Bahu & Rib Leher', 'smv' => 0.5200, 'target_per_hour' => 115],
            ['sequence_number' => 3, 'process_name' => 'Pasang Lengan & Obras Ketiak', 'smv' => 0.6500, 'target_per_hour' => 92],
            ['sequence_number' => 4, 'process_name' => 'Hemming Bawah & Ujung Lengan', 'smv' => 0.4000, 'target_per_hour' => 150],
            ['sequence_number' => 5, 'process_name' => 'Quality Control (QC) & Packing', 'smv' => 0.3000, 'target_per_hour' => 200],
        ];

        $createdProcesses = [];
        foreach ($processesData as $pd) {
            $createdProcesses[] = BreakdownProcess::create([
                'style_order_id' => $so1->id,
                'sequence_number' => $pd['sequence_number'],
                'process_name' => $pd['process_name'],
                'smv' => $pd['smv'],
                'target_per_hour' => $pd['target_per_hour'],
                'created_by' => $ieUser->id,
            ]);
        }

        // Breakdown Processes for SO2
        BreakdownProcess::create([
            'style_order_id' => $so2->id,
            'sequence_number' => 1,
            'process_name' => 'Jahit Kantong Kangaroo',
            'smv' => 0.7500,
            'target_per_hour' => 80,
            'created_by' => $ieUser->id,
        ]);
        BreakdownProcess::create([
            'style_order_id' => $so2->id,
            'sequence_number' => 2,
            'process_name' => 'Pasang Hood & Tali Serut',
            'smv' => 0.8500,
            'target_per_hour' => 70,
            'created_by' => $ieUser->id,
        ]);

        // 6. Plotting Employees to Line 1 & Line 2
        for ($i = 0; $i < 8; $i++) {
            EmployeeLineAssignment::create([
                'employee_id' => $createdEmployees[$i]->id,
                'production_line_id' => $createdLines[0]->id,
                'shift' => 'shift_1',
                'assigned_date' => Carbon::today(),
                'assigned_by' => $hrdUser->id,
            ]);
        }
        for ($i = 8; $i < 15; $i++) {
            EmployeeLineAssignment::create([
                'employee_id' => $createdEmployees[$i]->id,
                'production_line_id' => $createdLines[1]->id,
                'shift' => 'shift_1',
                'assigned_date' => Carbon::today(),
                'assigned_by' => $hrdUser->id,
            ]);
        }

        // 7. Tasks Assignment
        for ($i = 0; $i < count($createdProcesses); $i++) {
            OperatorTask::create([
                'employee_id' => $createdEmployees[$i]->id,
                'breakdown_process_id' => $createdProcesses[$i]->id,
                'style_order_id' => $so1->id,
                'assigned_date' => Carbon::today(),
                'assigned_by' => $spvUser->id,
            ]);
        }

        // 8. Hourly Outputs for Line 1 (Jam 1..5)
        $hourlyData = [
            ['hour' => 1, 'output' => 110, 'defect' => 2, 'validated' => true],
            ['hour' => 2, 'output' => 115, 'defect' => 3, 'validated' => true],
            ['hour' => 3, 'output' => 108, 'defect' => 2, 'validated' => true],
            ['hour' => 4, 'output' => 118, 'defect' => 1, 'validated' => false],
            ['hour' => 5, 'output' => 112, 'defect' => 4, 'validated' => false],
        ];

        foreach ($hourlyData as $hd) {
            HourlyOutput::create([
                'production_line_id' => $createdLines[0]->id,
                'style_order_id' => $so1->id,
                'hour_number' => $hd['hour'],
                'output_qty' => $hd['output'],
                'defect_qty' => $hd['defect'],
                'production_date' => Carbon::today(),
                'recorded_by' => $spvUser->id,
                'is_validated' => $hd['validated'],
                'validated_by' => $hd['validated'] ? $admUser->id : null,
                'validated_at' => $hd['validated'] ? Carbon::now()->subHours(2) : null,
            ]);
        }

        // Hourly Outputs for Line 2
        HourlyOutput::create([
            'production_line_id' => $createdLines[1]->id,
            'style_order_id' => $so2->id,
            'hour_number' => 1,
            'output_qty' => 65,
            'defect_qty' => 4,
            'production_date' => Carbon::today(),
            'recorded_by' => $spvUser->id,
            'is_validated' => false,
        ]);
        HourlyOutput::create([
            'production_line_id' => $createdLines[1]->id,
            'style_order_id' => $so2->id,
            'hour_number' => 2,
            'output_qty' => 70,
            'defect_qty' => 5,
            'production_date' => Carbon::today(),
            'recorded_by' => $spvUser->id,
            'is_validated' => false,
        ]);

        // 9. Initial Notifications
        Notification::create([
            'user_id' => $spvUser->id,
            'type' => 'alert_tidak_sesuai',
            'title' => 'ALERT: Evaluasi Tidak Sesuai pada Line 2',
            'message' => 'Manager memberikan catatan revisi: Defect rate jam ke-2 mencapai 6.6%. Segera periksa pemasangan hood dan tension benang obras.',
            'is_read' => false,
        ]);

        Notification::create([
            'user_id' => $admUser->id,
            'type' => 'new_output',
            'title' => 'Output Baru Siap Divalidasi',
            'message' => 'SPV Line 1 telah menginput output jam ke-4 dan jam ke-5. Mohon verifikasi data.',
            'is_read' => false,
        ]);

        // 10. Initial QC Defect Records (Recorded by Admin QC)
        $sampleDefects = [
            ['line_idx' => 0, 'style' => $so1->id, 'hour' => 1, 'type' => 'Jahitan Kerut (Puckering)', 'qty' => 2, 'action' => 'repair', 'notes' => 'Tension benang jarum terlalu kencang pada sambungan lengan'],
            ['line_idx' => 0, 'style' => $so1->id, 'hour' => 2, 'type' => 'Jahitan Loncat (Skip Stitch)', 'qty' => 1, 'action' => 'repair', 'notes' => 'Jarum tumpul di mesin obras 04'],
            ['line_idx' => 0, 'style' => $so1->id, 'hour' => 3, 'type' => 'Noda Minyak / Kotor (Oil/Dirt Stain)', 'qty' => 1, 'action' => 'repair', 'notes' => 'Kena rembesan pelumas meja jahit'],
            ['line_idx' => 0, 'style' => $so1->id, 'hour' => 4, 'type' => 'Benang Putus (Broken Thread)', 'qty' => 3, 'action' => 'repair', 'notes' => 'Periksa jalur benang looper'],
            ['line_idx' => 0, 'style' => $so1->id, 'hour' => 5, 'type' => 'Ukuran Tidak Sesuai (Measurement Off)', 'qty' => 2, 'action' => 'reject', 'notes' => 'Pola potongan leher melar lebih dari 1.5 cm'],
            ['line_idx' => 1, 'style' => $so2->id, 'hour' => 1, 'type' => 'Jahitan Terbuka (Open Seam)', 'qty' => 3, 'action' => 'repair', 'notes' => 'Kampuh jahitan sisi lepas'],
            ['line_idx' => 1, 'style' => $so2->id, 'hour' => 1, 'type' => 'Jahitan Loncat (Skip Stitch)', 'qty' => 1, 'action' => 'repair', 'notes' => 'Pemasangan zipper'],
            ['line_idx' => 1, 'style' => $so2->id, 'hour' => 2, 'type' => 'Kain Cacat / Bolong (Fabric Defect)', 'qty' => 2, 'action' => 'reject', 'notes' => 'Cacat serat kain bawaan supplier'],
            ['line_idx' => 1, 'style' => $so2->id, 'hour' => 2, 'type' => 'Simetris / Pola Miring (Asymmetric)', 'qty' => 3, 'action' => 'repair', 'notes' => 'Pemasangan kantong kanan lebih tinggi 1 cm'],
            ['line_idx' => 2, 'style' => $so1->id, 'hour' => 1, 'type' => 'Ujung Benang Panjang (Untrimmed Thread)', 'qty' => 4, 'action' => 'repair', 'notes' => 'Proses trimming kurang teliti'],
            ['line_idx' => 2, 'style' => $so1->id, 'hour' => 2, 'type' => 'Jahitan Meleset (Run-off Stitch)', 'qty' => 2, 'action' => 'repair', 'notes' => 'Jahitan topstitch kerah'],
        ];

        foreach ($sampleDefects as $def) {
            QcDefect::create([
                'production_line_id' => $createdLines[$def['line_idx']]->id,
                'style_order_id' => $def['style'],
                'defect_date' => Carbon::today(),
                'hour_number' => $def['hour'],
                'defect_type' => $def['type'],
                'defect_qty' => $def['qty'],
                'action_taken' => $def['action'],
                'notes' => $def['notes'],
                'recorded_by' => $qcUser->id,
            ]);
        }
    }
}
