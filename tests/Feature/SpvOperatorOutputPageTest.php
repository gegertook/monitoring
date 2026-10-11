<?php

namespace Tests\Feature;

use App\Models\ProductionLine;
use App\Models\StyleOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpvOperatorOutputPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_operator_output_page_loads_processes_from_spv_endpoint(): void
    {
        $spv = $this->createSpvWithLine();

        $response = $this->actingAs($spv)->get(route('spv.operator-output.index'));

        $response->assertOk();
        // The table fetches breakdown processes through the SPV endpoint (scoped to the SPV's line).
        $response->assertSee('/spv/api/style-orders/', false);
        // Regression: the IE endpoint is behind role:ie, so an SPV call got 403 and the
        // "Memuat data pengerjaan..." spinner never resolved.
        $response->assertDontSee('/ie/api/style-orders/', false);
    }

    public function test_ie_processes_endpoint_rejects_spv_users(): void
    {
        $spv = $this->createSpvWithLine();
        $styleOrder = StyleOrder::create([
            'code_style' => 'STYLE001',
            'buyer' => 'Buyer',
            'total_qty_target' => 100,
            'deadline' => '2026-12-31',
            'status' => 'pending',
            'assigned_line_id' => $spv->assigned_line_id,
            'created_by' => $spv->id,
        ]);

        $this->actingAs($spv)
            ->getJson("/ie/api/style-orders/{$styleOrder->id}/processes")
            ->assertForbidden();
    }

    private function createSpvWithLine(): User
    {
        $line = ProductionLine::create(['name' => 'Line 1', 'capacity' => 20]);

        return User::create([
            'nik' => 'SPV010',
            'name' => 'Supervisor Output',
            'email' => 'spv.output@example.test',
            'password' => 'password',
            'role' => 'spv',
            'assigned_line_id' => $line->id,
        ]);
    }
}
