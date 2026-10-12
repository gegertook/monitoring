<?php

namespace Tests\Feature;

use App\Models\BreakdownProcess;
use App\Models\Employee;
use App\Models\EmployeeLineAssignment;
use App\Models\HourlyOutput;
use App\Models\Machine;
use App\Models\OperatorOutput;
use App\Models\ProductionLine;
use App\Models\StyleOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlottingAndDefectTest extends TestCase
{
    use RefreshDatabase;

    private User $hrd;
    private User $spv;
    private ProductionLine $line1;
    private ProductionLine $line2;
    private StyleOrder $styleOrder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hrd = User::create([
            'nik' => 'HRD-T', 'name' => 'HRD Tester', 'email' => 'hrd.t@example.test',
            'password' => 'password', 'role' => 'hrd', 'is_active' => true,
        ]);

        $this->line1 = ProductionLine::create(['name' => 'Line 1', 'capacity' => 20]);
        $this->line2 = ProductionLine::create(['name' => 'Line 2', 'capacity' => 20]);

        $this->spv = User::create([
            'nik' => 'SPV-T', 'name' => 'SPV Tester', 'email' => 'spv.t@example.test',
            'password' => 'password', 'role' => 'spv',
            'assigned_line_id' => $this->line1->id, 'is_active' => true,
        ]);

        $this->styleOrder = StyleOrder::create([
            'code_style' => 'STYLE-T', 'buyer' => 'Buyer', 'total_qty_target' => 100,
            'deadline' => '2026-12-31', 'status' => 'in_progress',
            'assigned_line_id' => $this->line1->id, 'created_by' => $this->spv->id,
        ]);
    }

    private function makeEmployee(string $name, string $category = 'operator'): Employee
    {
        return Employee::create([
            'nik' => strtoupper(str_replace(' ', '-', $name)),
            'name' => $name,
            'skill_level' => 'junior',
            'category' => $category,
            'is_active' => true,
            'created_by' => $this->hrd->id,
        ]);
    }

    // -------------------------------------------------------------------------
    // Plotting
    // -------------------------------------------------------------------------
    public function test_hr_can_plot_several_employees_in_one_request(): void
    {
        $a = $this->makeEmployee('Operator Satu');
        $b = $this->makeEmployee('Operator Dua');
        $c = $this->makeEmployee('Operator Tiga');

        $response = $this->actingAs($this->hrd)->postJson(route('hrd.plotting.store'), [
            'employee_ids' => [$a->id, $b->id, $c->id],
            'production_line_id' => $this->line1->id,
            'assigned_date' => now()->toDateString(),
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        $this->assertCount(0, $response->json('skipped'));
        $this->assertSame(3, EmployeeLineAssignment::count());
        $this->assertSame($this->line1->id, $a->fresh()->current_line_id);
        $this->assertSame($this->line1->id, $b->fresh()->current_line_id);
        $this->assertSame($this->line1->id, $c->fresh()->current_line_id);
    }

    public function test_an_employee_on_line_one_cannot_be_plotted_to_line_two(): void
    {
        $employee = $this->makeEmployee('Operator Pindah');

        $this->actingAs($this->hrd)->postJson(route('hrd.plotting.store'), [
            'employee_ids' => [$employee->id],
            'production_line_id' => $this->line1->id,
            'assigned_date' => now()->toDateString(),
        ])->assertOk();

        $second = $this->actingAs($this->hrd)->postJson(route('hrd.plotting.store'), [
            'employee_ids' => [$employee->id],
            'production_line_id' => $this->line2->id,
            'assigned_date' => now()->toDateString(),
        ]);

        $second->assertOk()->assertJsonPath('success', false);
        $this->assertCount(1, $second->json('skipped'));

        $this->assertSame($this->line1->id, $employee->fresh()->current_line_id);
        $this->assertSame(1, EmployeeLineAssignment::count());
        $this->assertSame($this->line1->id, EmployeeLineAssignment::first()->production_line_id);
    }

    public function test_hr_can_move_an_employee_to_another_line(): void
    {
        $employee = $this->makeEmployee('Operator Muda');

        $this->actingAs($this->hrd)->postJson(route('hrd.plotting.store'), [
            'employee_ids' => [$employee->id],
            'production_line_id' => $this->line1->id,
            'assigned_date' => now()->toDateString(),
        ])->assertOk();

        $this->actingAs($this->hrd)->postJson(route('hrd.plotting.move'), [
            'employee_id' => $employee->id,
            'production_line_id' => $this->line2->id,
            'assigned_date' => now()->toDateString(),
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertSame($this->line2->id, $employee->fresh()->current_line_id);
        $this->assertSame(1, EmployeeLineAssignment::count());
        $this->assertSame($this->line2->id, EmployeeLineAssignment::first()->production_line_id);
    }

    public function test_unassign_returns_operator_to_the_pool(): void
    {
        $employee = $this->makeEmployee('Operator Lepas Pool');

        $this->actingAs($this->hrd)->postJson(route('hrd.plotting.store'), [
            'employee_ids' => [$employee->id],
            'production_line_id' => $this->line1->id,
            'assigned_date' => now()->toDateString(),
        ])->assertOk();

        $this->actingAs($this->hrd)->postJson(route('hrd.plotting.unassign'), [
            'employee_id' => $employee->id,
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertNull($employee->fresh()->current_line_id);
        $this->assertSame(0, EmployeeLineAssignment::where('employee_id', $employee->id)->count());
    }

    public function test_line_card_counts_current_members_even_without_todays_assignment_row(): void
    {
        // Menempel lewat backfill/pindah: ada di current_line_id tanpa baris assignment hari ini
        $employee = $this->makeEmployee('Operator Backfill');
        $employee->update(['current_line_id' => $this->line1->id]);

        $this->actingAs($this->hrd)->get(route('hrd.plotting.index'))
            ->assertOk()
            ->assertViewHas('membersByLine', function ($members) use ($employee) {
                return $members->get($this->line1->id)->pluck('id')->contains($employee->id)
                    && $members->get($this->line2->id, collect())->isEmpty();
            });
    }

    public function test_removing_a_plotting_puts_the_employee_back_in_the_pool(): void
    {
        $employee = $this->makeEmployee('Operator Lepas');

        $this->actingAs($this->hrd)->postJson(route('hrd.plotting.store'), [
            'employee_ids' => [$employee->id],
            'production_line_id' => $this->line1->id,
            'assigned_date' => now()->toDateString(),
        ])->assertOk();

        $assignment = EmployeeLineAssignment::firstOrFail();

        $this->actingAs($this->hrd)
            ->deleteJson(route('hrd.plotting.destroy', $assignment))
            ->assertOk();

        $this->assertNull($employee->fresh()->current_line_id);
    }

    public function test_mekanik_employees_are_not_shown_in_the_plotting_roster(): void
    {
        $this->makeEmployee('Operator Layak Plot');
        $mekanik = $this->makeEmployee('Mekanik Bengkel', 'mekanik');

        // Halaman plotting (daftar karyawan dimuat lewat AJAX, jadi yang dicek adalah
        // halaman terbuka + API daftar karyawan di bawah yang jadi sumber roster)
        $page = $this->actingAs($this->hrd)->get(route('hrd.plotting.index'));
        $page->assertOk();

        // API daftar karyawan (dipakai panel plotting)
        $api = $this->actingAs($this->hrd)->getJson(route('hrd.api.employees'));
        $api->assertOk();
        $names = collect($api->json())->pluck('name');
        $this->assertTrue($names->contains('Operator Layak Plot'));
        $this->assertFalse($names->contains('Mekanik Bengkel'));
    }

    public function test_a_mekanik_cannot_be_plotted_even_when_forced(): void
    {
        $mekanik = $this->makeEmployee('Mekanik Paksa', 'mekanik');

        $response = $this->actingAs($this->hrd)->postJson(route('hrd.plotting.store'), [
            'employee_ids' => [$mekanik->id],
            'production_line_id' => $this->line1->id,
            'assigned_date' => now()->toDateString(),
        ]);

        $response->assertOk()->assertJsonPath('success', false);
        $this->assertSame(0, EmployeeLineAssignment::count());
        $this->assertNull($mekanik->fresh()->current_line_id);
    }

    // -------------------------------------------------------------------------
    // SPV tidak mencatat defect (milik Admin QC)
    // -------------------------------------------------------------------------
    public function test_spv_hourly_output_does_not_overwrite_the_qc_defect_value(): void
    {
        $existing = HourlyOutput::create([
            'production_line_id' => $this->line1->id,
            'style_order_id' => $this->styleOrder->id,
            'hour_number' => 1,
            'output_qty' => 10,
            'defect_qty' => 5, // diisi Admin QC
            'production_date' => now()->toDateString(),
            'recorded_by' => $this->spv->id,
        ]);

        $this->actingAs($this->spv)->postJson(route('spv.hourly-output.store'), [
            'style_order_id' => $this->styleOrder->id,
            'hour_number' => 1,
            'output_qty' => 120,
            'defect_qty' => 999, // tidak boleh diterima
            'production_date' => now()->toDateString(),
        ])->assertOk();

        $existing->refresh();

        $this->assertSame(120, $existing->output_qty);
        $this->assertSame(5, $existing->defect_qty);
    }

    public function test_spv_hourly_output_bulk_save_keeps_qc_defect_values(): void
    {
        $existing = HourlyOutput::create([
            'production_line_id' => $this->line1->id,
            'style_order_id' => $this->styleOrder->id,
            'hour_number' => 2,
            'output_qty' => 10,
            'defect_qty' => 3,
            'production_date' => now()->toDateString(),
            'recorded_by' => $this->spv->id,
        ]);

        $this->actingAs($this->spv)->postJson(route('spv.hourly-output.store'), [
            'style_order_id' => $this->styleOrder->id,
            'production_date' => now()->toDateString(),
            'hours' => [
                1 => ['output_qty' => 50, 'defect_qty' => 999],
                2 => ['output_qty' => 75, 'defect_qty' => 999],
            ],
        ])->assertOk();

        $this->assertSame(50, HourlyOutput::where('hour_number', 1)->first()->output_qty);
        $this->assertSame(0, HourlyOutput::where('hour_number', 1)->first()->defect_qty);
        $this->assertSame(75, $existing->fresh()->output_qty);
        $this->assertSame(3, $existing->fresh()->defect_qty);
    }

    public function test_spv_operator_output_does_not_write_defect(): void
    {
        $employee = $this->makeEmployee('Operator Defect');
        $process = BreakdownProcess::create([
            'style_order_id' => $this->styleOrder->id,
            'sequence_number' => 1,
            'process_name' => 'Sewing',
            'smv' => 1.0,
            'target_per_hour' => 40,
            'created_by' => $this->spv->id,
        ]);

        $existing = OperatorOutput::create([
            'employee_id' => $employee->id,
            'style_order_id' => $this->styleOrder->id,
            'breakdown_process_id' => $process->id,
            'output_qty' => 5,
            'defect_qty' => 4,
            'production_date' => now()->toDateString(),
            'recorded_by' => $this->spv->id,
        ]);

        $this->actingAs($this->spv)->postJson(route('spv.operator-output.store'), [
            'employee_id' => $employee->id,
            'style_order_id' => $this->styleOrder->id,
            'breakdown_process_id' => $process->id,
            'output_qty' => 80,
            'defect_qty' => 999,
            'production_date' => now()->toDateString(),
        ])->assertOk();

        $existing->refresh();

        $this->assertSame(80, $existing->output_qty);
        $this->assertSame(4, $existing->defect_qty);
    }

    public function test_spv_line_machines_endpoint_lists_only_this_lines_machines(): void
    {
        Machine::create([
            'code' => 'OWN-001', 'name' => 'Mesin Line 1',
            'current_line_id' => $this->line1->id, 'created_by' => $this->spv->id,
        ]);

        $otherLine = ProductionLine::create(['name' => 'Line X', 'capacity' => 10]);
        Machine::create([
            'code' => 'OTHER-001', 'name' => 'Mesin Line X',
            'current_line_id' => $otherLine->id, 'created_by' => $this->spv->id,
        ]);

        $response = $this->actingAs($this->spv)->getJson(route('spv.api.line-machines'));

        $response->assertOk();
        $codes = collect($response->json())->pluck('code');
        $this->assertTrue($codes->contains('OWN-001'));
        $this->assertFalse($codes->contains('OTHER-001'));
    }
}
