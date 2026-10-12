<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('machine_breakdowns', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('machine_id')->constrained('machines')->onDelete('cascade');
            $table->foreignId('production_line_id')->constrained('production_lines')->onDelete('cascade');
            $table->foreignId('style_order_id')->nullable()->constrained('style_orders')->onDelete('set null');
            $table->text('notes')->nullable();

            // reported -> mechanic_sent -> (replacement_requested) -> repaired / replaced
            $table->enum('status', ['reported', 'mechanic_sent', 'replacement_requested', 'repaired', 'replaced'])->default('reported');
            $table->enum('resolution', ['repaired', 'replaced'])->nullable();

            $table->foreignId('reported_by')->constrained('users')->onDelete('restrict');
            $table->timestamp('reported_at');

            $table->foreignId('mechanic_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('mechanic_sent_at')->nullable();

            $table->foreignId('replacement_requested_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('replacement_requested_at')->nullable();
            $table->foreignId('replacement_machine_id')->nullable()->constrained('machines')->onDelete('set null');

            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();

            $table->index(['machine_id', 'status']);
            $table->index(['production_line_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('machine_breakdowns');
    }
};
