<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Nullable FK so existing data is preserved
            $table->foreignId('assigned_line_id')
                  ->nullable()
                  ->after('is_active')
                  ->constrained('production_lines')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['assigned_line_id']);
            $table->dropColumn('assigned_line_id');
        });
    }
};
