<?php

namespace App\Http\Controllers;

use App\Models\OperatorTask;
use App\Models\HourlyOutput;
use App\Models\OperatorOutput;
use App\Models\BreakdownProcess;
use App\Models\ProductionLine;
use App\Models\StyleOrder;
use App\Models\Employee;
use App\Models\EmployeeLineAssignment;
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

        $tasks = OperatorTask::with(['employee', 'breakdownProcess', 'styleOrder'])
            ->whereDate('assigned_date', $date)
            ->whereHas('breakdownProcess', function ($q) use ($line) {
                $q->whereHas('styleOrder', function ($sq) use ($line) {
                    $sq->where('assigned_line_id', $line->id);
                });
            })
            ->latest()
            ->get();

        return view('spv.task-assignment', compact('line', 'styleOrders', 'employees', 'tasks', 'date'));
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
            'assigned_date' => 'required|date',
        ]);

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
                    $defectQty = intval($data['defect_qty'] ?? 0);

                    HourlyOutput::updateOrCreate(
                        [
                            'production_line_id' => $lineId,
                            'style_order_id' => $styleId,
                            'hour_number' => intval($h),
                            'production_date' => $date,
                        ],
                        [
                            'output_qty' => $outputQty,
                            'defect_qty' => $defectQty,
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

        // Single row
        $validated = $request->validate([
            'style_order_id' => 'required|exists:style_orders,id',
            'hour_number' => 'required|integer|min:1|max:24',
            'output_qty' => 'required|integer|min:0',
            'defect_qty' => 'required|integer|min:0',
            'production_date' => 'required|date',
        ]);

        try {
            $record = HourlyOutput::updateOrCreate(
                [
                    'production_line_id' => $line->id,
                    'style_order_id' => $validated['style_order_id'],
                    'hour_number' => $validated['hour_number'],
                    'production_date' => $validated['production_date'],
                ],
                [
                    'output_qty' => $validated['output_qty'],
                    'defect_qty' => $validated['defect_qty'],
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

        return view('spv.operator-output', compact('line', 'styleOrders', 'date', 'employees'));
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
            'defect_qty' => 'required|integer|min:0',
            'production_date' => 'required|date',
        ]);

        try {
            $record = OperatorOutput::updateOrCreate(
                [
                    'employee_id' => $validated['employee_id'],
                    'breakdown_process_id' => $validated['breakdown_process_id'],
                    'production_date' => $validated['production_date'],
                ],
                [
                    'style_order_id' => $validated['style_order_id'],
                    'output_qty' => $validated['output_qty'],
                    'defect_qty' => $validated['defect_qty'],
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
}
