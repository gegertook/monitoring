<?php

namespace Tests\Feature;

use App\Models\BreakdownProcess;
use App\Models\ProductionLine;
use App\Models\StyleOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpvTaskAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_spv_can_load_processes_for_a_style_order_on_their_line(): void
    {
        [$spv, $line, $styleOrder] = $this->createSpvStyleOrder();
        $process = BreakdownProcess::create([
            'style_order_id' => $styleOrder->id,
            'sequence_number' => 1,
            'process_name' => 'Sewing',
            'smv' => 1.25,
            'target_per_hour' => 48,
            'created_by' => $spv->id,
        ]);

        $this->actingAs($spv)
            ->getJson(route('spv.api.style-order-processes', $styleOrder))
            ->assertOk()
            ->assertJsonPath('0.id', $process->id)
            ->assertJsonPath('0.process_name', 'Sewing');
    }

    public function test_spv_cannot_load_processes_for_a_style_order_on_another_line(): void
    {
        [$spv, , $styleOrder] = $this->createSpvStyleOrder();
        $otherLine = ProductionLine::create(['name' => 'Line 2', 'capacity' => 20]);
        $styleOrder->update(['assigned_line_id' => $otherLine->id]);

        $this->actingAs($spv)
            ->getJson(route('spv.api.style-order-processes', $styleOrder))
            ->assertNotFound();
    }

    private function createSpvStyleOrder(): array
    {
        $line = ProductionLine::create(['name' => 'Line 1', 'capacity' => 20]);
        $spv = User::create([
            'nik' => 'SPV001',
            'name' => 'Supervisor',
            'email' => 'spv@example.test',
            'password' => 'password',
            'role' => 'spv',
            'assigned_line_id' => $line->id,
        ]);
        $styleOrder = StyleOrder::create([
            'code_style' => 'STYLE001',
            'buyer' => 'Buyer',
            'total_qty_target' => 100,
            'deadline' => '2026-12-31',
            'status' => 'pending',
            'assigned_line_id' => $line->id,
            'created_by' => $spv->id,
        ]);

        return [$spv, $line, $styleOrder];
    }
}
