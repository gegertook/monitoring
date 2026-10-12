<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Isi employees.current_line_id dari plotting terbaru tiap karyawan,
     * supaya aturan "satu karyawan satu line" langsung berlaku pada data lama.
     */
    public function up(): void
    {
        $assignments = DB::table('employee_line_assignments')
            ->orderByDesc('assigned_date')
            ->orderByDesc('id')
            ->get(['employee_id', 'production_line_id']);

        $latest = [];
        foreach ($assignments as $row) {
            if (!isset($latest[$row->employee_id])) {
                $latest[$row->employee_id] = $row->production_line_id;
            }
        }

        foreach ($latest as $employeeId => $lineId) {
            DB::table('employees')->where('id', $employeeId)->update(['current_line_id' => $lineId]);
        }
    }

    public function down(): void
    {
        DB::table('employees')->update(['current_line_id' => null]);
    }
};
