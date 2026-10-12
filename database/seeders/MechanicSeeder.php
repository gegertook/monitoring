<?php

namespace Database\Seeders;

use App\Models\Machine;
use App\Models\ProductionLine;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MechanicSeeder extends Seeder
{
    /**
     * Akun Admin Mekanik (otomatis tersedia untuk modul downtime)
     * + beberapa mesin contoh yang sudah diplot ke line.
     */
    public function run(): void
    {
        $mechanic = User::firstOrCreate(
            ['email' => 'mekanik@mlmps.com'],
            [
                'nik' => 'MEK001',
                'name' => 'Eko Mekanik',
                'password' => Hash::make('password123'),
                'role' => 'admin_mekanik',
                'is_active' => true,
            ]
        );

        $lines = ProductionLine::where('is_active', true)->orderBy('id')->get();

        $machines = [
            ['code' => 'OVR-001', 'name' => 'Overdeck Juki DDL-8700', 'machine_type' => 'Sewing', 'line' => 0],
            ['code' => 'SN-001', 'name' => 'Seikou OB-755', 'machine_type' => 'Sewing', 'line' => 0],
            ['code' => 'HEM-001', 'name' => 'Hemming Brother S-7300A', 'machine_type' => 'Sewing', 'line' => 1],
            ['code' => 'IRN-001', 'name' => 'Setrika Uap Gravity Feed', 'machine_type' => 'Iron', 'line' => 1],
            ['code' => 'CUT-001', 'name' => 'Meja Cutting Lay', 'machine_type' => 'Cutting', 'line' => null],
            ['code' => 'IRN-002', 'name' => 'Setrika Uap Star', 'machine_type' => 'Iron', 'line' => null],
        ];

        foreach ($machines as $machine) {
            Machine::firstOrCreate(
                ['code' => $machine['code']],
                [
                    'name' => $machine['name'],
                    'machine_type' => $machine['machine_type'],
                    'is_active' => true,
                    'current_line_id' => isset($lines[$machine['line']]) ? $lines[$machine['line']]->id : null,
                    'created_by' => $mechanic->id,
                ]
            );
        }
    }
}
