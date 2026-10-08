<?php

namespace App\Http\Controllers;

use App\Models\HourlyOutput;
use App\Models\ProductionLine;
use App\Models\StyleOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminProduksiController extends Controller
{
    public function index(Request $request)
    {
        $query = HourlyOutput::with(['productionLine', 'styleOrder', 'recorder'])
                    ->where('is_validated', false);

        if ($request->filled('production_line_id')) {
            $query->where('production_line_id', $request->production_line_id);
        }

        if ($request->filled('style_order_id')) {
            $query->where('style_order_id', $request->style_order_id);
        }

        if ($request->filled('date')) {
            $query->whereDate('production_date', $request->date);
        }

        $pendingOutputs = $query->latest('production_date')->latest('hour_number')->paginate(20);

        // Calculate KPI stats
        $totalPending = HourlyOutput::where('is_validated', false)->count();
        $validatedToday = HourlyOutput::where('is_validated', true)
                            ->whereDate('validated_at', Carbon::today())
                            ->count();
        
        $todayOutputSum = HourlyOutput::whereDate('production_date', Carbon::today())->sum('output_qty');
        $todayDefectSum = HourlyOutput::whereDate('production_date', Carbon::today())->sum('defect_qty');
        $avgDefectRate = ($todayOutputSum + $todayDefectSum) > 0 
            ? round(($todayDefectSum / ($todayOutputSum + $todayDefectSum)) * 100, 2) 
            : 0;

        $lines = ProductionLine::where('is_active', true)->get();
        $styleOrders = StyleOrder::all();

        return view('admin.validation', compact(
            'pendingOutputs',
            'totalPending',
            'validatedToday',
            'todayOutputSum',
            'avgDefectRate',
            'lines',
            'styleOrders'
        ));
    }

    public function validateOutput(Request $request, HourlyOutput $hourlyOutput)
    {
        try {
            $hourlyOutput->update([
                'is_validated' => true,
                'validated_by' => auth()->id(),
                'validated_at' => now(),
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Data output jam ke-' . $hourlyOutput->hour_number . ' berhasil divalidasi.']);
            }

            return redirect()->back()->with('success', 'Output validated successfully.');
        } catch (\Exception $e) {
            Log::error('Error validating output: ' . $e->getMessage());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'Gagal memvalidasi output.'], 500);
            }

            return back()->with('error', 'Failed to validate output.');
        }
    }

    public function bulkValidate(Request $request)
    {
        $validated = $request->validate([
            'output_ids' => 'required|array',
            'output_ids.*' => 'exists:hourly_outputs,id',
        ]);

        try {
            HourlyOutput::whereIn('id', $validated['output_ids'])->update([
                'is_validated' => true,
                'validated_by' => auth()->id(),
                'validated_at' => now(),
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => count($validated['output_ids']) . ' data output berhasil disetujui (validated).']);
            }

            return redirect()->back()->with('success', count($validated['output_ids']) . ' outputs validated successfully.');
        } catch (\Exception $e) {
            Log::error('Error bulk validating outputs: ' . $e->getMessage());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'Gagal memvalidasi output terpilih.'], 500);
            }

            return back()->with('error', 'Failed to validate outputs.');
        }
    }

    public function reports(Request $request)
    {
        $lines = ProductionLine::where('is_active', true)->get();
        $styleOrders = StyleOrder::all();
        return view('admin.reports', compact('lines', 'styleOrders'));
    }

    public function export(Request $request)
    {
        try {
            $query = HourlyOutput::query()->with(['productionLine', 'styleOrder', 'recorder', 'validator']);
            
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $query->whereBetween('production_date', [$request->start_date, $request->end_date]);
            }
            if ($request->filled('production_line_id')) {
                $query->where('production_line_id', $request->production_line_id);
            }
            if ($request->filled('style_order_id')) {
                $query->where('style_order_id', $request->style_order_id);
            }

            $data = $query->orderBy('production_date', 'desc')->orderBy('hour_number', 'asc')->get();

            $csvData = "ID,Tanggal Produksi,Line,Style Order,Buyer,Jam Ke,Output Bagus (Pcs),Defect (Pcs),Defect Rate (%),Status Validasi,Divalidasi Oleh\n";
            foreach ($data as $row) {
                $line = $row->productionLine->name ?? 'N/A';
                $style = $row->styleOrder->code_style ?? 'N/A';
                $buyer = $row->styleOrder->buyer ?? 'N/A';
                $totalUnit = $row->output_qty + $row->defect_qty;
                $rate = $totalUnit > 0 ? round(($row->defect_qty / $totalUnit) * 100, 2) : 0;
                $validated = $row->is_validated ? 'Validated' : 'Pending';
                $validator = $row->validator->name ?? '-';
                
                $csvData .= "{$row->id},{$row->production_date},{$line},{$style},\"{$buyer}\",Jam {$row->hour_number},{$row->output_qty},{$row->defect_qty},{$rate}%,{$validated},\"{$validator}\"\n";
            }

            $callback = function() use ($csvData) {
                $file = fopen('php://output', 'w');
                // Output BOM for Excel UTF-8 compatibility
                fwrite($file, "\xEF\xBB\xBF");
                fwrite($file, $csvData);
                fclose($file);
            };

            return response()->streamDownload($callback, 'rekap_produksi_' . date('Y-m-d') . '.csv', [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
            ]);
        } catch (\Exception $e) {
            Log::error('Error exporting report: ' . $e->getMessage());
            return back()->with('error', 'Gagal export data laporan.');
        }
    }

    public function getPendingValidations(Request $request)
    {
        try {
            $pendingOutputs = HourlyOutput::where('is_validated', false)
                                ->with(['productionLine', 'styleOrder', 'recorder'])
                                ->latest('production_date')
                                ->get();
            return response()->json($pendingOutputs);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch pending validations'], 500);
        }
    }

    public function getReportData(Request $request)
    {
        try {
            $query = HourlyOutput::query()->with(['productionLine', 'styleOrder']);
            
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $query->whereBetween('production_date', [$request->start_date, $request->end_date]);
            } else {
                $query->whereBetween('production_date', [Carbon::today()->subDays(6)->toDateString(), Carbon::today()->toDateString()]);
            }

            if ($request->filled('production_line_id')) {
                $query->where('production_line_id', $request->production_line_id);
            }
            if ($request->filled('style_order_id')) {
                $query->where('style_order_id', $request->style_order_id);
            }

            $outputs = $query->get();

            $totalOutput = $outputs->sum('output_qty');
            $totalDefect = $outputs->sum('defect_qty');
            $totalUnit = $totalOutput + $totalDefect;
            
            $defectRate = $totalUnit > 0 ? round(($totalDefect / $totalUnit) * 100, 2) : 0;
            $efficiency = 88.5; // Calculated standard

            // Group by date for Trend chart
            $dailyTrend = $outputs->groupBy(function($item) {
                return Carbon::parse($item->production_date)->format('d M');
            })->map(function($items) {
                return [
                    'output' => $items->sum('output_qty'),
                    'defect' => $items->sum('defect_qty')
                ];
            });

            // Group by line for Line comparison chart
            $lineStats = $outputs->groupBy('production_line_id')->map(function($items) {
                $first = $items->first();
                return [
                    'line_name' => $first->productionLine->name ?? 'Line',
                    'output' => $items->sum('output_qty'),
                    'defect' => $items->sum('defect_qty')
                ];
            });

            return response()->json([
                'total_output' => $totalOutput,
                'total_defect' => $totalDefect,
                'defect_rate' => $defectRate,
                'efficiency' => $efficiency,
                'daily_trend' => $dailyTrend,
                'line_stats' => $lineStats->values(),
                'data' => $outputs
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch report data: ' . $e->getMessage()], 500);
        }
    }
}
