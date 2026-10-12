<?php

namespace Tests\Feature;

use App\Models\Machine;
use App\Models\MachineBreakdown;
use App\Models\Notification;
use App\Models\ProductionLine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MechanicDowntimeTest extends TestCase
{
    use RefreshDatabase;

    private ProductionLine $line;
    private User $spv;
    private User $mekanik;
    private User $adminProduksi;
    private Machine $machine;
    private Machine $spare;

    protected function setUp(): void
    {
        parent::setUp();

        $this->line = ProductionLine::create(['name' => 'Line 1', 'capacity' => 20]);
        $otherLine = ProductionLine::create(['name' => 'Line 2', 'capacity' => 20]);

        $this->spv = User::create([
            'nik' => 'SPV-M',
            'name' => 'SPV Line 1',
            'email' => 'spv.mekanik@example.test',
            'password' => 'password',
            'role' => 'spv',
            'assigned_line_id' => $this->line->id,
            'is_active' => true,
        ]);

        $this->mekanik = User::create([
            'nik' => 'MEK-M',
            'name' => 'Admin Mekanik',
            'email' => 'mekanik@example.test',
            'password' => 'password',
            'role' => 'admin_mekanik',
            'is_active' => true,
        ]);

        $this->adminProduksi = User::create([
            'nik' => 'ADM-M',
            'name' => 'Admin Produksi',
            'email' => 'admin.produksi.mek@example.test',
            'password' => 'password',
            'role' => 'admin_produksi',
            'is_active' => true,
        ]);

        $this->machine = Machine::create([
            'code' => 'MCH-001',
            'name' => 'Overdeck Juki',
            'machine_type' => 'Sewing',
            'current_line_id' => $this->line->id,
            'created_by' => $this->mekanik->id,
        ]);

        $this->spare = Machine::create([
            'code' => 'MCH-SPARE',
            'name' => 'Mesin Cadangan',
            'machine_type' => 'Sewing',
            'current_line_id' => null,
            'created_by' => $this->mekanik->id,
        ]);

        // Mesin milik line lain (dipakai untuk uji validasi line)
        Machine::create([
            'code' => 'MCH-L2',
            'name' => 'Mesin Line 2',
            'current_line_id' => $otherLine->id,
            'created_by' => $this->mekanik->id,
        ]);
    }

    private function reportBreakdown(): MachineBreakdown
    {
        $response = $this->actingAs($this->spv)->postJson(route('spv.downtime.store'), [
            'machine_id' => $this->machine->id,
            'notes' => 'Jarum patah',
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        return MachineBreakdown::firstOrFail();
    }

    public function test_spv_can_report_a_broken_machine_and_mekanik_is_notified(): void
    {
        $this->reportBreakdown();

        $this->assertDatabaseHas('machine_breakdowns', [
            'machine_id' => $this->machine->id,
            'production_line_id' => $this->line->id,
            'status' => 'reported',
            'notes' => 'Jarum patah',
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->mekanik->id,
            'type' => 'mesin_rusak',
            'is_read' => false,
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->adminProduksi->id,
            'type' => 'mesin_rusak',
        ]);
    }

    public function test_spv_cannot_report_a_machine_on_another_line(): void
    {
        $otherLineMachine = Machine::where('code', 'MCH-L2')->firstOrFail();

        $this->actingAs($this->spv)
            ->postJson(route('spv.downtime.store'), ['machine_id' => $otherLineMachine->id])
            ->assertStatus(422)
            ->assertJsonPath('error', 'Mesin tidak terdaftar di line Anda.');

        $this->assertSame(0, MachineBreakdown::count());
    }

    public function test_machine_cannot_be_reported_twice_while_still_open(): void
    {
        $this->reportBreakdown();

        $this->actingAs($this->spv)
            ->postJson(route('spv.downtime.store'), ['machine_id' => $this->machine->id])
            ->assertStatus(422);

        $this->assertSame(1, MachineBreakdown::count());
    }

    public function test_admin_produksi_dispatches_the_mechanic(): void
    {
        $breakdown = $this->reportBreakdown();

        $this->actingAs($this->adminProduksi)
            ->postJson(route('admin.downtime.dispatch', $breakdown), ['mechanic_user_id' => $this->mekanik->id])
            ->assertOk()
            ->assertJsonPath('success', true);

        $breakdown->refresh();

        $this->assertSame('mechanic_sent', $breakdown->status);
        $this->assertSame($this->mekanik->id, $breakdown->mechanic_user_id);
        $this->assertNotNull($breakdown->mechanic_sent_at);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->mekanik->id,
            'type' => 'penugasan_mekanik',
        ]);
    }

    public function test_spv_cannot_request_a_replacement_before_one_hour(): void
    {
        $breakdown = $this->reportBreakdown();

        $this->travel(59)->minutes();

        $this->actingAs($this->spv)
            ->postJson(route('spv.downtime.request-replacement', $breakdown))
            ->assertStatus(403);

        $this->assertSame('reported', $breakdown->fresh()->status);
    }

    public function test_spv_can_request_a_replacement_after_one_hour(): void
    {
        $breakdown = $this->reportBreakdown();

        $this->travel(61)->minutes();

        $this->actingAs($this->spv)
            ->postJson(route('spv.downtime.request-replacement', $breakdown))
            ->assertOk();

        $this->assertSame('replacement_requested', $breakdown->fresh()->status);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->mekanik->id,
            'type' => 'permintaan_ganti_mesin',
        ]);
    }

    public function test_spv_cannot_request_replacement_for_another_lines_breakdown(): void
    {
        $otherLine = ProductionLine::where('name', 'Line 2')->firstOrFail();
        $otherMachine = Machine::where('code', 'MCH-L2')->firstOrFail();

        $breakdown = MachineBreakdown::create([
            'machine_id' => $otherMachine->id,
            'production_line_id' => $otherLine->id,
            'status' => 'reported',
            'reported_by' => $this->adminProduksi->id,
            'reported_at' => now()->subHours(2),
        ]);

        $this->actingAs($this->spv)
            ->postJson(route('spv.downtime.request-replacement', $breakdown))
            ->assertStatus(403);
    }

    public function test_mekanik_can_mark_a_breakdown_as_repaired(): void
    {
        $breakdown = $this->reportBreakdown();

        $this->actingAs($this->mekanik)
            ->postJson(route('mekanik.downtime.resolve', $breakdown), ['resolution' => 'repaired'])
            ->assertOk();

        $breakdown->refresh();

        $this->assertSame('repaired', $breakdown->status);
        $this->assertSame('repaired', $breakdown->resolution);
        $this->assertNotNull($breakdown->resolved_at);
        $this->assertSame($this->line->id, $this->machine->fresh()->current_line_id);

        // SPV pelapor diberi tahu
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->spv->id,
            'type' => 'downtime_selesai',
        ]);
    }

    public function test_mekanik_can_replace_a_broken_machine(): void
    {
        $breakdown = $this->reportBreakdown();

        $this->actingAs($this->mekanik)
            ->postJson(route('mekanik.downtime.resolve', $breakdown), [
                'resolution' => 'replaced',
                'replacement_machine_id' => $this->spare->id,
            ])
            ->assertOk();

        $breakdown->refresh();

        $this->assertSame('replaced', $breakdown->status);
        $this->assertSame($this->spare->id, $breakdown->replacement_machine_id);
        $this->assertNull($this->machine->fresh()->current_line_id);
        $this->assertSame($this->line->id, $this->spare->fresh()->current_line_id);
    }

    public function test_a_machine_cannot_be_used_as_its_own_replacement(): void
    {
        $breakdown = $this->reportBreakdown();

        $this->actingAs($this->mekanik)
            ->postJson(route('mekanik.downtime.resolve', $breakdown), [
                'resolution' => 'replaced',
                'replacement_machine_id' => $this->machine->id,
            ])
            ->assertStatus(500);

        $this->assertSame('reported', $breakdown->fresh()->status);
    }

    public function test_a_broken_machine_cannot_be_moved_to_another_line(): void
    {
        $this->reportBreakdown();

        $this->actingAs($this->mekanik)
            ->postJson(route('mekanik.machines.assign-line', $this->machine), ['line_id' => null])
            ->assertStatus(422);

        $this->assertSame($this->line->id, $this->machine->fresh()->current_line_id);
    }

    public function test_a_machine_belongs_to_exactly_one_line_and_can_be_moved(): void
    {
        $otherLine = ProductionLine::where('name', 'Line 2')->firstOrFail();

        $this->actingAs($this->mekanik)
            ->postJson(route('mekanik.machines.assign-line', $this->machine), ['line_id' => $otherLine->id])
            ->assertOk();

        $this->assertSame($otherLine->id, $this->machine->fresh()->current_line_id);
        // MCH-L2 sudah menempel di Line 2 sejak awal, jadi sekarang ada 2 mesin di sana
        // dan tidak ada lagi mesin yang tersisa di Line 1.
        $this->assertSame(2, Machine::onLine($otherLine->id)->count());
        $this->assertSame(0, Machine::onLine($this->line->id)->count());
    }

    public function test_only_admin_mekanik_can_open_the_mechanic_module(): void
    {
        $this->actingAs($this->spv)->get(route('mekanik.machines.index'))->assertForbidden();
        $this->actingAs($this->adminProduksi)->get(route('mekanik.downtime.index'))->assertForbidden();
        $this->actingAs($this->mekanik)->get(route('mekanik.machines.index'))->assertOk();
        $this->actingAs($this->mekanik)->get(route('mekanik.downtime.index'))->assertOk();
    }

    public function test_downtime_page_lists_breakdowns_for_admin_produksi(): void
    {
        $this->reportBreakdown();

        $this->actingAs($this->adminProduksi)
            ->get(route('admin.downtime.index'))
            ->assertOk()
            ->assertSee('MCH-001')
            ->assertSee('Line 1');
    }
}
