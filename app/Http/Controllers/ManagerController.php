<?php

namespace App\Http\Controllers;

use App\Models\ManagerEvaluation;
use App\Models\Notification;
use App\Models\HourlyOutput;
use App\Models\ProductionLine;
use App\Models\StyleOrder;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ManagerController extends Controller
{
    public function dashboard()
    {
        $lines = ProductionLine::where('is_active', true)->with('styleOrders')->get();
        return view('manager.dashboard', compact('lines'));
    }

    public function evaluation()
    {
        $lines = ProductionLine::where('is_active', true)->get();
        $styleOrders = StyleOrder::all();
        $evaluations = ManagerEvaluation::with(['productionLine', 'styleOrder', 'evaluator'])
            ->latest('evaluation_date')
            ->latest()
            ->paginate(15);
            
        return view('manager.evaluation', compact('lines', 'styleOrders', 'evaluations'));
    }

    public function storeEvaluation(Request $request)
    {
        $validated = $request->validate([
            'production_line_id' => 'required|exists:production_lines,id',
            'style_order_id' => 'required|exists:style_orders,id',
            'result' => 'required|in:good,not_good',
            'notes' => 'nullable|string',
        ]);

        if ($validated['result'] === 'not_good' && empty($validated['notes'])) {
            return response()->json(['error' => 'Catatan revisi wajib diisi jika hasil tidak sesuai.'], 422);
        }

        try {
            $validated['evaluated_by'] = auth()->id();
            $validated['evaluation_date'] = now()->toDateString();
            
            $evaluation = ManagerEvaluation::create($validated);

            // If not good, dispatch notification feedback loop to all SPVs and IEs
            if ($validated['result'] === 'not_good') {
                $line = ProductionLine::find($validated['production_line_id']);
                $style = StyleOrder::find($validated['style_order_id']);
                $lineName = $line ? $line->name : 'Line';
                $styleCode = $style ? $style->code_style : 'Order';

                $recipients = User::whereIn('role', ['spv', 'ie'])->get();
                foreach ($recipients as $recipient) {
                    Notification::create([
                        'user_id' => $recipient->id,
                        'type' => 'alert_tidak_sesuai',
                        'title' => "ALERT: Evaluasi Tidak Sesuai pada {$lineName}",
                        'message' => "Manager memberikan evaluasi 'Hasil Tidak Sesuai' pada {$lineName} ({$styleCode}). Catatan: " . ($validated['notes'] ?? 'Segera lakukan perbaikan proses dan kualitas.'),
                        'data' => [
                            'line_id' => $line->id ?? null,
                            'style_id' => $style->id ?? null,
                            'result' => 'not_good'
                        ],
                        'is_read' => false,
                    ]);
                }
            }

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $validated['result'] === 'not_good'
                        ? 'Evaluasi disimpan dan notifikasi peringatan telah dikirim ke SPV & IE.'
                        : 'Evaluasi hasil baik berhasil disimpan.',
                    'data' => $evaluation
                ]);
            }

            return redirect()->back()->with('success', 'Evaluasi berhasil disimpan.');
        } catch (\Exception $e) {
            Log::error('Error submitting evaluation: ' . $e->getMessage());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'Gagal menyimpan evaluasi: ' . $e->getMessage()], 500);
            }

            return back()->with('error', 'Failed to submit evaluation.');
        }
    }

    public function getRealtimeKpi(Request $request)
    {
        try {
            $today = Carbon::today()->toDateString();
            
            $todayOutputs = HourlyOutput::whereDate('production_date', $today)->get();
            $totalOutput = $todayOutputs->sum('output_qty');
            $totalDefect = $todayOutputs->sum('defect_qty');
            $totalUnits = $totalOutput + $totalDefect;
            
            $defectRate = $totalUnits > 0 ? round(($totalDefect / $totalUnits) * 100, 2) : 0;
            
            $activeLinesCount = ProductionLine::where('is_active', true)->count();
            
            // Hourly outputs for today (hours 1..8)
            $hourlyTrends = [];
            for ($h = 1; $h <= 8; $h++) {
                $hOutputs = $todayOutputs->where('hour_number', $h);
                $hourlyTrends[$h] = [
                    'hour' => 'Jam ' . $h,
                    'output' => $hOutputs->sum('output_qty'),
                    'defect' => $hOutputs->sum('defect_qty'),
                ];
            }

            return response()->json([
                'total_output' => $totalOutput,
                'total_defect' => $totalDefect,
                'defect_rate' => $defectRate,
                'overall_efficiency' => 91.2,
                'active_lines' => $activeLinesCount,
                'hourly_trend' => array_values($hourlyTrends)
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch KPI data: ' . $e->getMessage()], 500);
        }
    }

    public function getLinePerformance(Request $request)
    {
        try {
            $today = Carbon::today()->toDateString();
            
            $lines = ProductionLine::where('is_active', true)
                ->with(['styleOrders' => function($q) {
                    $q->whereIn('status', ['in_progress', 'pending']);
                }])
                ->get()
                ->map(function($line) use ($today) {
                    $outputs = HourlyOutput::where('production_line_id', $line->id)
                                ->whereDate('production_date', $today)
                                ->get();
                                
                    $actual = $outputs->sum('output_qty');
                    $defect = $outputs->sum('defect_qty');
                    $total = $actual + $defect;
                    $defectRate = $total > 0 ? round(($defect / $total) * 100, 2) : 0;
                    
                    $activeStyle = $line->styleOrders->first();
                    $target = $activeStyle ? round($activeStyle->total_qty_target / 8) : 500;
                    $efficiency = $target > 0 ? min(115, round(($actual / $target) * 100, 1)) : 85.0;

                    return [
                        'id' => $line->id,
                        'name' => $line->name,
                        'style_name' => $activeStyle ? $activeStyle->code_style : 'No Style',
                        'target' => $target,
                        'actual' => $actual,
                        'efficiency' => $efficiency,
                        'defect_rate' => $defectRate,
                        'status' => $defectRate > 5.0 ? 'Attention Needed' : ($efficiency >= 90 ? 'Excellent' : 'Normal')
                    ];
                });
            
            return response()->json($lines);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch line performance: ' . $e->getMessage()], 500);
        }
    }

    public function getDefectAnalysis(Request $request)
    {
        try {
            $today = Carbon::today()->toDateString();
            $data = ProductionLine::where('is_active', true)->get()->map(function($l) use ($today) {
                $outputs = HourlyOutput::where('production_line_id', $l->id)
                    ->whereDate('production_date', $today)
                    ->get();
                $actual = $outputs->sum('output_qty');
                $defect = $outputs->sum('defect_qty');
                $total = $actual + $defect;
                return [
                    'line' => $l->name,
                    'defect_rate' => $total > 0 ? round(($defect / $total) * 100, 2) : 0
                ];
            });
            
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch defect analysis'], 500);
        }
    }

    /**
     * Manajemen Line Produksi (Otoritas Manager)
     */
    public function lines(Request $request)
    {
        $query = ProductionLine::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $lines = $query->orderByRaw('CAST(SUBSTRING_INDEX(name, " ", -1) AS UNSIGNED) ASC, name ASC')
            ->paginate(15);

        // Stats summary
        $stats = [
            'total' => ProductionLine::count(),
            'active' => ProductionLine::where('is_active', true)->count(),
            'inactive' => ProductionLine::where('is_active', false)->count(),
            'total_capacity' => ProductionLine::where('is_active', true)->sum('capacity'),
        ];

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'lines' => $lines,
                'stats' => $stats
            ]);
        }

        return view('manager.lines', compact('lines', 'stats'));
    }

    public function storeLine(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:production_lines,name',
            'capacity' => 'required|integer|min:1|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            $validated['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;
            $line = ProductionLine::create($validated);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Line {$line->name} berhasil ditambahkan.",
                    'data' => $line
                ]);
            }

            return redirect()->route('manager.lines.index')->with('success', "Line {$line->name} berhasil ditambahkan.");
        } catch (\Exception $e) {
            Log::error('Error creating line: ' . $e->getMessage());
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'Gagal menambahkan line: ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Gagal menambahkan line: ' . $e->getMessage());
        }
    }

    public function updateLine(Request $request, ProductionLine $line)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:production_lines,name,' . $line->id,
            'capacity' => 'required|integer|min:1|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            $validated['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : $line->is_active;
            $line->update($validated);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "Line {$line->name} berhasil diperbarui.",
                    'data' => $line
                ]);
            }

            return redirect()->route('manager.lines.index')->with('success', "Line {$line->name} berhasil diperbarui.");
        } catch (\Exception $e) {
            Log::error('Error updating line: ' . $e->getMessage());
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'Gagal memperbarui line: ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Gagal memperbarui line.');
        }
    }

    public function toggleLine(Request $request, ProductionLine $line)
    {
        try {
            $line->is_active = !$line->is_active;
            $line->save();

            $statusText = $line->is_active ? 'diaktifkan' : 'dinonaktifkan';

            return response()->json([
                'success' => true,
                'is_active' => $line->is_active,
                'message' => "Status {$line->name} berhasil diubah menjadi {$statusText}."
            ]);
        } catch (\Exception $e) {
            Log::error('Error toggling line: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal mengubah status line.'], 500);
        }
    }

    public function destroyLine(Request $request, ProductionLine $line)
    {
        try {
            // Check if line has any associations
            $hasOrders = $line->styleOrders()->exists();
            $hasOutputs = $line->hourlyOutputs()->exists();
            $hasAssignments = $line->employeeAssignments()->exists();

            if ($hasOrders || $hasOutputs || $hasAssignments) {
                // If it has history, deactivate instead of hard delete
                $line->is_active = false;
                $line->save();

                return response()->json([
                    'success' => true,
                    'is_deactivated' => true,
                    'message' => "Line {$line->name} memiliki riwayat produksi dan tidak dapat dihapus permanen. Status telah diubah menjadi Non-Aktif."
                ]);
            }

            $lineName = $line->name;
            $line->delete();

            return response()->json([
                'success' => true,
                'message' => "Line {$lineName} berhasil dihapus dari sistem."
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting line: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal menghapus line: ' . $e->getMessage()], 500);
        }
    }
}

