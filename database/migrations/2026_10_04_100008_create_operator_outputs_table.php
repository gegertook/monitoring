<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operator_outputs', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('style_order_id')->constrained('style_orders')->onDelete('cascade');
            $table->foreignId('breakdown_process_id')->constrained('breakdown_processes')->onDelete('cascade');
            $table->date('production_date');
            $table->integer('output_qty');
            $table->integer('defect_qty')->default(0);
            $table->foreignId('recorded_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();
            
            $table->unique(['employee_id', 'breakdown_process_id', 'production_date'], 'op_outputs_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operator_outputs');
    }
};
