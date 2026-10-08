<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hourly_outputs', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('production_line_id')->constrained('production_lines')->onDelete('cascade');
            $table->foreignId('style_order_id')->constrained('style_orders')->onDelete('cascade');
            $table->integer('hour_number');
            $table->integer('output_qty');
            $table->integer('defect_qty')->default(0);
            $table->date('production_date');
            $table->foreignId('recorded_by')->constrained('users')->onDelete('restrict');
            $table->boolean('is_validated')->default(false);
            $table->foreignId('validated_by')->nullable()->constrained('users')->onDelete('restrict');
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();
            
            $table->unique(['production_line_id', 'style_order_id', 'hour_number', 'production_date'], 'hourly_outputs_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hourly_outputs');
    }
};
