<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('style_orders', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('code_style')->unique();
            $table->string('buyer');
            $table->integer('total_qty_target');
            $table->date('deadline');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->foreignId('assigned_line_id')->nullable()->constrained('production_lines')->onDelete('restrict');
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('style_orders');
    }
};
