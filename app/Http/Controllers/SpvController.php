<?php

namespace App\Http\Controllers;

use App\Models\OperatorTask;
use App\Models\HourlyOutput;
use App\Models\Machine;
use App\Models\MachineBreakdown;
use App\Models\OperatorOutput;
use App\Models\BreakdownProcess;
use App\Models\ProductionLine;
use App\Models\StyleOrder;
use App\Models\Employee;
use App\Models\EmployeeLineAssignment;
use App\Support\Notifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SpvController extends Controller
{
    /**
     * Return the authenticated SPV's assigned ProductionLine,
     * or null if none is configured.
     */
    private function getMyLine(): ?ProductionLine
    {
        $user = auth()->user();
        if ($user->assigned_line_id) {
            return ProductionLine::find($user->assigned_line_id);
        }
        return null;
    }

    /**
     * Render a friendly "no line assigned" warning page.
     */
    private function noLineView()
    {
        return view('spv.no-line');
    }

    // -------------------------------------------------------------------------
    // Task Assignment
    // -------------------------------------------------------------------------
    public function taskAssignment(Request $request)
    {
        $line = $this->getMyLine();
        if (!$line) return $this->noLineView();

        $styleOrders = StyleOrder::with('breakdownProcesses')
            ->whereIn('status', ['in_progress', 'pending'])
            ->where('assigned_line_id', $line->id)
            ->get();

        $date = $request->input('date', Carbon::today()->toDateString());

        // Operators assigned to THIS line today
        $lineEmployeeIds = EmployeeLineAssignment::where('production_line_id', $line->id)
            ->whereDate('assigned_date', $date)
            ->pluck('employee_id');

        $employees = Employee::whereIn('id', $lineEmployeeIds)->orderBy('name')->get();

        // Mesin yang menempel di line ini (untuk pemilihan mesin pada tiap tugas)
        $machines = Machine::active()->onLine($line->id)->orderBy('code')->get();

        $tasks = OperatorTask::with(['employee', 'breakdownProcess', 'styleOrder', 'machine'])
            ->whereDate('assigned_date', $date)
            ->whereHas('breakdownProcess', function ($q) use ($line) {
                $q->whereHas('styleOrder', function ($sq) use ($line) {
                    $sq->where('assigned_line_id', $line->id);
                });
            })
            ->latest()
            ->get();

        return view('spv.task-assignment', compact('line', 'styleOrders', 'employees', 'tasks', 'machines', 'date'));
    }

    public function storeTask(Request $request)
    {
        $line = $this->getMyLine();
        if (!$line) {
            return response()->json(['error' => 'Akun SPV Anda belum ditetapkan ke line manapun.'], 403);
        }

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'breakdown_process_id' => 'required|exists:breakdown_processes,id',
            'style_order_id' => 'required|exists:style_orders,id',
            'machine_id' => 'nullable|exists:machines,id',
            'assigned_date' => 'required|date',
        ]);

        // Mesin yang dipakai harus mesin yang menempel di line SPV ini
        if (!empty($validated['machine_id'])) {
            $machineOnLine = Machine::where('id', $validated['machine_id'])
                ->where('current_line_id', $line->id)
                ->exists();

            if (!$machineOnLine) {
                return response()->json(['error' => 'Mesin tidak terdaftar di line Anda.'], 422);
            }
        }

        try {
            $validated['assigned_by'] = auth()->id();
            $task = OperatorTask::create($validated);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Tugas berhasil diberikan ke operator.', 'data' => $task->load(['employee', 'breakdownProcess'])]);
            }

            return redirect()->back()->with('success', 'Tugas berhasil diberikan ke operator.');
        } catch (\Exception $e) {
            Log::error('Error assigning task: ' . $e->getMessage());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'Gagal memberikan tugas: ' . $e->getMessage()], 500);
            }

            return back()->with('error', 'Gagal memberikan tugas.');
        }
    }

    // -------------------------------------------------------------------------
    // Hourly Output
    // -------------------------------------------------------------------------
    public function hourlyOutput(Request $request)
    {
        $line = $this->getMyLine();
        if (!$line) return $this->noLineView();

        $styleOrders = StyleOrder::with('breakdownProcesses')
            ->whereIn('status', ['in_progress', 'pending'])
            ->where('assigned_line_id', $line->id)
            ->get();

        $date = $request->input('date', Carbon::today()->toDateString());

        return view('spv.hourly-output', compact('line', 'styleOrders', 'date'));
    }

    public function storeHourlyOutput(Request $request)
    {
        $line = $this->getMyLine();
        if (!$line) {
            return response()->json(['error' => 'Akun SPV Anda belum ditetapkan ke line manapun.'], 403);
        }

        // Bulk hours array
        if ($request->has('hours') && is_array($request->hours)) {
            $request->validate([
                'style_order_id' => 'required|exists:style_orders,id',
                'production_date' => 'required|date',
                'hours' => 'required|array',
            ]);

            try {
                $lineId = $line->id;
                $styleId = $request->style_order_id;
                $date = $request->production_date;

                foreach ($request->hours as $h => $data) {
                    $outputQty = intval($data['output_qty'] ?? 0);

                    // SPV hanya mencatat output bagus. Defect milik Admin QC (disinkronkan oleh QC),
                    // jadi tidak ditimpa di sini.
                    HourlyOutput::updateOrCreate(
                        [
                            'production_line_id' => $lineId,
                            'style_order_id' => $styleId,
                            'hour_number' => intval($h),
                            'production_date' => Carbon::parse($date)->startOfDay(),
                        ],
                        [
                            'output_qty' => $outputQty,
                            'recorded_by' => auth()->id(),
                            'is_validated' => false,
                        ]
                    );
                }

                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => true, 'message' => 'Data output per jam berhasil disimpan ke database.']);
                }

                return redirect()->back()->with('success', 'Data output per jam berhasil disimpan.');
            } catch (\Exception $e) {
                Log::error('Error saving bulk hourly output: ' . $e->getMessage());
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['error' => 'Gagal menyimpan output: ' . $e->getMessage()], 500);
                }
                return back()->with('error', 'Gagal menyimpan output.');
            }
        }

        // Single row (defect sengaja tidak divalidasi/diterima: diisi oleh Admin QC)
        $validated = $request->validate([
            'style_order_id' => 'required|exists:style_orders,id',
            'hour_number' => 'required|integer|min:1|max:24',
            'output_qty' => 'required|integer|min:0',
            'production_date' => 'required|date',
        ]);

        try {
            $record = HourlyOutput::updateOrCreate(
                [
                    'production_line_id' => $line->id,
                    'style_order_id' => $validated['style_order_id'],
                    'hour_number' => $validated['hour_number'],
                    'production_date' => Carbon::parse($validated['production_date'])->startOfDay(),
                ],
                [
                    'output_qty' => $validated['output_qty'],
                    'recorded_by' => auth()->id(),
                    'is_validated' => false,
                ]
            );

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Hourly output saved successfully.', 'data' => $record]);
            }

            return redirect()->back()->with('success', 'Hourly output saved successfully.');
        } catch (\Exception $e) {
            Log::error('Error saving hourly output: ' . $e->getMessage());
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'Failed to save hourly output: ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Failed to save hourly output.');
        }
    }

    // -------------------------------------------------------------------------
    // Operator Output
    // -------------------------------------------------------------------------
    public function operatorOutput(Request $request)
    {
        $line = $this->getMyLine();
        if (!$line) return $this->noLineView();

        $styleOrders = StyleOrder::with('breakdownProcesses')
            ->whereIn('status', ['in_progress', 'pending'])
            ->where('assigned_line_id', $line->id)
            ->get();

        $date = $request->input('date', Carbon::today()->toDateString());

        // Only employees assigned to this line on this date
        $lineEmployeeIds = EmployeeLineAssignment::where('production_line_id', $line->id)
            ->whereDate('assigned_date', $date)
            ->pluck('employee_id');

        $employees = Employee::whereIn('id', $lineEmployeeIds)->where('is_active', true)->orderBy('name')->get();

        // Mesin yang menempel di line ini + laporan downtime yang masih terbuka
        $machines = Machine::active()->onLine($line->id)->with('openBreakdown')->orderBy('code')->get();
        $openBreakdowns = MachineBreakdown::open()->forLine($line->id)
            ->with(['machine', 'styleOrder'])
            ->latest('reported_at')
            ->get();

        return view('spv.operator-output', compact('line', 'styleOrders', 'date', 'employees', 'machines', 'openBreakdowns'));
    }

    public function storeOperatorOutput(Request $request)
    {
        $line = $this->getMyLine();
        if (!$line) {
            return response()->json(['error' => 'Akun SPV Anda belum ditetapkan ke line manapun.'], 403);
        }

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'style_order_id' => 'required|exists:style_orders,id',
            'breakdown_process_id' => 'required|exists:breakdown_processes,id',
            'output_qty' => 'required|integer|min:0',
            'production_date' => 'required|date',
        ]);

        try {
            // SPV hanya mencatat output. Defect per operator bukan kewenangan SPV (diisi Admin QC),
            // jadi kolom defect_qty tidak lagi ditulis dari halaman ini.
            $record = OperatorOutput::updateOrCreate(
                [
                    'employee_id' => $validated['employee_id'],
                    'breakdown_process_id' => $validated['breakdown_process_id'],
                    'production_date' => Carbon::parse($validated['production_date'])->startOfDay(),
                ],
                [
                    'style_order_id' => $validated['style_order_id'],
                    'output_qty' => $validated['output_qty'],
                    'recorded_by' => auth()->id(),
                ]
            );

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Output operator harian berhasil disimpan.', 'data' => $record]);
            }

            return redirect()->back()->with('success', 'Output operator saved successfully.');
        } catch (\Exception $e) {
            Log::error('Error saving operator output: ' . $e->getMessage());
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'Gagal menyimpan output operator: ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Failed to save operator output.');
        }
    }

    // -------------------------------------------------------------------------
    // AJAX Endpoints — always scoped to SPV's assigned line
    // -------------------------------------------------------------------------
    public function getStyleOrderProcesses(StyleOrder $styleOrder)
    {
        $line = $this->getMyLine();
        if (!$line) {
            return response()->json(['error' => 'Akun SPV belum punya line.'], 403);
        }

        if ($styleOrder->assigned_line_id !== $line->id) {
            return response()->json(['error' => 'Style order tidak ditemukan pada line Anda.'], 404);
        }

        return response()->json(
            $styleOrder->breakdownProcesses()->orderBy('sequence_number')->get()
        );
    }

    public function getLineOperators(Request $request)
    {
        try {
            $line = $this->getMyLine();
            if (!$line) {
                return response()->json(['error' => 'Akun SPV belum punya line.'], 403);
            }

            $date = $request->input('date', Carbon::today()->toDateString());

            $operators = EmployeeLineAssignment::where('production_line_id', $line->id)
                ->whereDate('assigned_date', $date)
                ->with('employee')
                ->get();

            return response()->json($operators);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch operators'], 500);
        }
    }

    public function getHourlyData(Request $request)
    {
        try {
            $line = $this->getMyLine();
            if (!$line) {
                return response()->json(['error' => 'Akun SPV belum punya line.'], 403);
            }

            $styleId = $request->input('style_order_id');
            $date = $request->input('date', Carbon::today()->toDateString());

            $query = HourlyOutput::where('production_line_id', $line->id)
                ->whereDate('production_date', $date);

            if ($styleId) {
                $query->where('style_order_id', $styleId);
            }

            $records = $query->orderBy('hour_number')->get()->keyBy('hour_number');

            // Format all 8 hours
            $hours = [];
            for ($h = 1; $h <= 8; $h++) {
                $rec = $records->get($h);
                $hours[$h] = [
                    'hour_number' => $h,
                    'output_qty' => $rec ? $rec->output_qty : 0,
                    'defect_qty' => $rec ? $rec->defect_qty : 0,
                    'is_validated' => $rec ? $rec->is_validated : false,
                ];
            }

            return response()->json([
                'success' => true,
                'hours' => $hours,
                'total_output' => $records->sum('output_qty'),
                'total_defect' => $records->sum('defect_qty'),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch hourly data: ' . $e->getMessage()], 500);
        }
    }

    public function getOperatorData(Request $request)
    {
        try {
            $line = $this->getMyLine();
            if (!$line) {
                return response()->json(['error' => 'Akun SPV belum punya line.'], 403);
            }

            $styleId = $request->input('style_order_id');
            $date = $request->input('date', Carbon::today()->toDateString());

            // Only employees assigned to this line
            $lineEmployeeIds = EmployeeLineAssignment::where('production_line_id', $line->id)
                ->whereDate('assigned_date', $date)
                ->pluck('employee_id');

            $query = OperatorOutput::whereIn('employee_id', $lineEmployeeIds)
                ->whereDate('production_date', $date)
                ->with(['employee', 'breakdownProcess']);

            if ($styleId) {
                $query->where('style_order_id', $styleId);
            }

            return response()->json($query->get());
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch operator data'], 500);
        }
    }

    // -------------------------------------------------------------------------
    // Downtime Mesin — dilaporkan SPV, ditangani Admin Produksi & Admin Mekanik
    // -------------------------------------------------------------------------

    /**
     * Mesin yang menempel di line SPV (untuk dropdown pemilihan mesin pada tugas).
     */
    public function getLineMachines()
    {
        $line = $this->getMyLine();
        if (!$line) {
            return response()->json(['error' => 'Akun SPV belum punya line.'], 403);
        }

        return response()->json(
            Machine::active()->onLine($line->id)->with('openBreakdown')->orderBy('code')->get()
        );
    }

    /**
     * Status downtime di line SPV, termasuk sisa waktu sebelum boleh minta ganti mesin.
     */
    public function getDowntime()
    {
        $line = $this->getMyLine();
        if (!$line) {
            return response()->json(['error' => 'Akun SPV belum punya line.'], 403);
        }

        $breakdowns = MachineBreakdown::open()->forLine($line->id)
            ->with(['machine', 'styleOrder'])
            ->latest('reported_at')
            ->get()
            ->map(fn ($b) => [
                'id' => $b->id,
                'machine_id' => $b->machine_id,
                'machine_code' => $b->machine->code ?? '-',
                'machine_name' => $b->machine->name ?? '-',
                'status' => $b->status,
                'notes' => $b->notes,
                'reported_at' => $b->reported_at->toDateTimeString(),
                'minutes_since_reported' => $b->minutesSinceReported(),
                'mechanic_sent_at' => $b->mechanic_sent_at?->toDateTimeString(),
                'can_request_replacement' => $b->canRequestReplacement(),
            ]);

        return response()->json(['success' => true, 'breakdowns' => $breakdowns]);
    }

    /**
     * SPV melaporkan mesin di line-nya rusak (pencatatan output per operator).
     */
    public function storeDowntime(Request $request)
    {
        $line = $this->getMyLine();
        if (!$line) {
            return response()->json(['error' => 'Akun SPV Anda belum ditetapkan ke line manapun.'], 403);
        }

        $validated = $request->validate([
            'machine_id' => 'required|exists:machines,id',
            'style_order_id' => 'nullable|exists:style_orders,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $machine = Machine::find($validated['machine_id']);

        if (!$machine || (int) $machine->current_line_id !== (int) $line->id) {
            return response()->json(['error' => 'Mesin tidak terdaftar di line Anda.'], 422);
        }

        if ($machine->isBroken()) {
            return response()->json(['error' => "Mesin {$machine->code} sudah dilaporkan rusak dan sedang ditangani."], 422);
        }

        try {
            $breakdown = MachineBreakdown::create([
                'machine_id' => $machine->id,
                'production_line_id' => $line->id,
                'style_order_id' => $validated['style_order_id'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'status' => MachineBreakdown::STATUS_REPORTED,
                'reported_by' => auth()->id(),
                'reported_at' => now(),
            ]);

            Notifier::toRoles(
                ['admin_mekanik', 'admin_produksi'],
                'mesin_rusak',
                "MESIN RUSAK - {$line->name}",
                "Mesin {$machine->code} ({$machine->name}) dilaporkan rusak di {$line->name} oleh " . auth()->user()->name . '.'
                    . (!empty($validated['notes']) ? ' Keluhan: ' . $validated['notes'] : ''),
                ['breakdown_id' => $breakdown->id, 'line_id' => $line->id, 'machine_id' => $machine->id]
            );

            return response()->json([
                'success' => true,
                'message' => "Mesin {$machine->code} dilaporkan rusak. Admin mekanik & admin produksi sudah diberi tahu.",
                'data' => $breakdown,
            ]);
        } catch (\Exception $e) {
            Log::error('Error reporting machine breakdown: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal melaporkan mesin rusak: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Lewat 1 jam sejak laporan, SPV boleh meminta mesin pengganti.
     */
    public function requestReplacement(Request $request, MachineBreakdown $breakdown)
    {
        $line = $this->getMyLine();
        if (!$line) {
            return response()->json(['error' => 'Akun SPV Anda belum ditetapkan ke line manapun.'], 403);
        }

        if ((int) $breakdown->production_line_id !== (int) $line->id) {
            return response()->json(['error' => 'Laporan downtime tidak berada di line Anda.'], 403);
        }

        if (!$breakdown->isOpen()) {
            return response()->json(['error' => 'Laporan downtime ini sudah selesai.'], 422);
        }

        if ($breakdown->status === MachineBreakdown::STATUS_REPLACEMENT_REQUESTED) {
            return response()->json(['error' => 'Permintaan ganti mesin sudah dikirim sebelumnya.'], 422);
        }

        if (!$breakdown->canRequestReplacement()) {
            $remaining = max(1, MachineBreakdown::REPLACEMENT_THRESHOLD_MINUTES - $breakdown->minutesSinceReported());
            return response()->json([
                'error' => "Permintaan ganti mesin baru boleh dikirim lewat 1 jam sejak laporan. Kurang {$remaining} menit.",
            ], 403);
        }

        try {
            $breakdown->load('machine');
            $breakdown->update([
                'status' => MachineBreakdown::STATUS_REPLACEMENT_REQUESTED,
                'replacement_requested_by' => auth()->id(),
                'replacement_requested_at' => now(),
            ]);

            Notifier::toRoles(
                ['admin_mekanik', 'admin_produksi'],
                'permintaan_ganti_mesin',
                "PERMINTAAN GANTI MESIN - {$line->name}",
                "SPV {$line->name} meminta penggantian mesin {$breakdown->machine->code} karena downtime sudah lebih dari 1 jam.",
                ['breakdown_id' => $breakdown->id, 'line_id' => $line->id, 'machine_id' => $breakdown->machine_id]
            );

            return response()->json(['success' => true, 'message' => 'Permintaan ganti mesin dikirim ke admin mekanik.']);
        } catch (\Exception $e) {
            Log::error('Error requesting machine replacement: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal mengirim permintaan ganti mesin: ' . $e->getMessage()], 500);
        }
    }
}
