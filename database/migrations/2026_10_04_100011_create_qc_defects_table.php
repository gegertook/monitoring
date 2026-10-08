<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qc_defects', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('production_line_id')->constrained('production_lines')->onDelete('cascade');
            $table->foreignId('style_order_id')->constrained('style_orders')->onDelete('cascade');
            $table->date('defect_date');
            $table->integer('hour_number')->default(1);
            $table->string('defect_type');
            $table->integer('defect_qty')->default(1);
            $table->enum('action_taken', ['repair', 'reject'])->default('repair');
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();

            $table->index(['production_line_id', 'defect_date']);
            $table->index(['defect_date']);
            $table->index(['defect_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qc_defects');
    }
};
