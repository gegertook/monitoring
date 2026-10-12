<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // operator = bisa diplot ke line; mekanik = tidak bisa diplot ke line
            $table->enum('category', ['operator', 'mekanik'])->default('operator')->after('skill_level');

            // Line "permanen" karyawan. Berubah hanya saat HRD memindah / melepas plotting.
            $table->foreignId('current_line_id')
                ->nullable()
                ->after('category')
                ->constrained('production_lines')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('current_line_id');
            $table->dropColumn('category');
        });
    }
};
