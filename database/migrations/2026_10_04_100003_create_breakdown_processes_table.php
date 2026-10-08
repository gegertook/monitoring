<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('breakdown_processes', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('style_order_id')->constrained('style_orders')->onDelete('cascade');
            $table->integer('sequence_number');
            $table->string('process_name');
            $table->decimal('smv', 8, 4);
            $table->integer('target_per_hour');
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();
            
            $table->unique(['style_order_id', 'sequence_number'], 'breakdown_processes_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('breakdown_processes');
    }
};
