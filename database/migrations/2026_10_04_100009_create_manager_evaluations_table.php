<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manager_evaluations', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('production_line_id')->constrained('production_lines')->onDelete('cascade');
            $table->foreignId('style_order_id')->constrained('style_orders')->onDelete('cascade');
            $table->date('evaluation_date');
            $table->enum('result', ['good', 'not_good']);
            $table->text('notes')->nullable();
            $table->foreignId('evaluated_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manager_evaluations');
    }
};
