<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_line_assignments', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('production_line_id')->constrained('production_lines')->onDelete('cascade');
            $table->string('shift')->default('shift_1');
            $table->date('assigned_date');
            $table->foreignId('assigned_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();
            
            $table->unique(['employee_id', 'assigned_date', 'shift'], 'emp_line_assign_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_line_assignments');
    }
};
