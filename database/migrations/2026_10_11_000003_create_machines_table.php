<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('machines', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('machine_type')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);

            // Satu mesin hanya menempel di satu line dalam satu waktu (bisa dipindah oleh Admin Mekanik)
            $table->foreignId('current_line_id')->nullable()->constrained('production_lines')->onDelete('set null');
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machines');
    }
};
