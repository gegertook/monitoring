<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operator_tasks', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('breakdown_process_id')->constrained('breakdown_processes')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('style_order_id')->constrained('style_orders')->onDelete('cascade');
            $table->foreignId('assigned_by')->constrained('users')->onDelete('restrict');
            $table->date('assigned_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operator_tasks');
    }
};
