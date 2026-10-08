<?php

namespace App\Http\Controllers;

use App\Models\QcDefect;
use App\Models\ProductionLine;
use App\Models\StyleOrder;
use App\Models\HourlyOutput;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QcController extends Controller
{
    /**
     * Standard list of manufacturing defect categories
     */
    public static function getDefectTypes(): array
    {
        return [
            'Jahitan Loncat (Skip Stitch)',
            'Jahitan Kerut (Puckering)',
            'Jahitan Terbuka (Open Seam)',
            'Jahitan Meleset (Run-off Stitch)',
            'Benang Putus (Broken Thread)',
            'Ujung Benang Panjang (Untrimmed Thread)',
            'Noda Minyak / Kotor (Oil/Dirt Stain)',
            'Kain Cacat / Bolong (Fabric Defect)',
            'Ukuran Tidak Sesuai (Measurement Off)',
            'Simetris / Pola Miring (Asymmetric)',
            'Aksesoris / Kancing Rusak (Broken Accessory)',
            'Lain-lain (Other)',
        ];
    }

    /**
     * QC Defect Entry & Monitoring per Line
     */
    public function index(Request $request)
    {
        $date = $request->input('date', Carbon::today()->toDateString());
        $lineId = $request->input('line_id');

        $lines = ProductionLine::where('is_active', true)
            ->orderByRaw('CAST(SUBSTRING_INDEX(name, " ", -1) AS UNSIGNED) ASC, name ASC')
            ->get();

        $styleOrders = StyleOrder::whereIn('status', ['in_progress', 'pending'])->get();
        if ($styleOrders->isEmpty()) {
            $styleOrders = StyleOrder::all();
        }

        $defectTypes = self::getDefectTypes();

        $query = QcDefect::with(['productionLine', 'styleOrder', 'recorder'])
            ->whereDate('defect_date', $date);

        if ($lineId) {
            $query->where('production_line_id', $lineId);
        }

        $defects = $query->latest()->paginate(15);

        // Daily KPI stats
        $todayQuery = QcDefect::whereDate('defect_date', $date);
        if ($lineId) {
            $todayQuery->where('production_line_id', $lineId);
        }

        $stats = [
            'total_defect' => (clone $todayQuery)->sum('defect_qty'),
            'repaired' => (clone $todayQuery)->where('action_taken', 'repair')->sum('defect_qty'),
            'rejected' => (clone $todayQuery)->where('action_taken', 'reject')->sum('defect_qty'),
            'active_lines_inspected' => (clone $todayQuery)->distinct('production_line_id')->count('production_line_id'),
        ];

        return view('qc.defects.index', compact('lines', 'styleOrders', 'defects', 'stats', 'defectTypes', 'date', 'lineId'));
    }

    /**
     * Store a new QC defect entry and sync with HourlyOutput
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'production_line_id' => 'required|exists:production_lines,id',
            'style_order_id' => 'required|exists:style_orders,id',
            'defect_date' => 'required|date',
            'hour_number' => 'required|integer|min:1|max:8',
            'defect_type' => 'required|string|max:150',
            'defect_qty' => 'required|integer|min:1',
            'action_taken' => 'required|in:repair,reject',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $validated['recorded_by'] = auth()->id();

            $defect = QcDefect::create($validated);

            // Sync with hourly_outputs.defect_qty
            $this->syncHourlyOutputDefect(
                $validated['production_line_id'],
                $validated['style_order_id'],
                $validated['defect_date'],
                $validated['hour_number']
            );

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Data defect berhasil dicatat dan disinkronkan.',
                    'data' => $defect
                ]);
            }

            return back()->with('success', 'Data defect berhasil disimpan.');
        } catch (\Exception $e) {
            Log::error('Error saving QC defect: ' . $e->getMessage());
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'Gagal menyimpan defect: ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Gagal menyimpan data defect.');
        }
    }

    /**
     * Update an existing QC defect entry
     */
    public function update(Request $request, QcDefect $defect)
    {
        $validated = $request->validate([
            'production_line_id' => 'required|exists:production_lines,id',
            'style_order_id' => 'required|exists:style_orders,id',
            'defect_date' => 'required|date',
            'hour_number' => 'required|integer|min:1|max:8',
            'defect_type' => 'required|string|max:150',
            'defect_qty' => 'required|integer|min:1',
            'action_taken' => 'required|in:repair,reject',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $oldLine = $defect->production_line_id;
            $oldStyle = $defect->style_order_id;
            $oldDate = $defect->defect_date->toDateString();
            $oldHour = $defect->hour_number;

            $defect->update($validated);

            // Sync old hourly record and new record
            $this->syncHourlyOutputDefect($oldLine, $oldStyle, $oldDate, $oldHour);
            $this->syncHourlyOutputDefect(
                $validated['production_line_id'],
                $validated['style_order_id'],
                $validated['defect_date'],
                $validated['hour_number']
            );

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Data defect berhasil diperbarui.',
                    'data' => $defect
                ]);
            }

            return back()->with('success', 'Data defect berhasil diperbarui.');
        } catch (\Exception $e) {
            Log::error('Error updating QC defect: ' . $e->getMessage());
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'Gagal memperbarui defect.'], 500);
            }
            return back()->with('error', 'Gagal memperbarui data defect.');
        }
    }

    /**
     * Delete a QC defect entry
     */
    public function destroy(Request $request, QcDefect $defect)
    {
        try {
            $line = $defect->production_line_id;
            $style = $defect->style_order_id;
            $date = $defect->defect_date->toDateString();
            $hour = $defect->hour_number;

            $defect->delete();

            // Re-sync with HourlyOutput
            $this->syncHourlyOutputDefect($line, $style, $date, $hour);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Data defect berhasil dihapus.'
                ]);
            }

            return back()->with('success', 'Data defect berhasil dihapus.');
        } catch (\Exception $e) {
            Log::error('Error deleting QC defect: ' . $e->getMessage());
            return response()->json(['error' => 'Gagal menghapus defect.'], 500);
        }
    }

    /**
     * Master Defect Recap (Viewable by Admin QC, Admin Produksi, and Manager)
     */
    public function recap(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::today()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::today()->toDateString());
        $lineId = $request->input('line_id');
        $defectType = $request->input('defect_type');

        $lines = ProductionLine::where('is_active', true)
            ->orderByRaw('CAST(SUBSTRING_INDEX(name, " ", -1) AS UNSIGNED) ASC, name ASC')
            ->get();

        $defectTypes = self::getDefectTypes();

        $query = QcDefect::with(['productionLine', 'styleOrder', 'recorder'])
            ->whereBetween('defect_date', [$startDate, $endDate]);

        if ($lineId) {
            $query->where('production_line_id', $lineId);
        }

        if ($defectType) {
            $query->where('defect_type', $defectType);
        }

        $defects = $query->latest('defect_date')->latest()->paginate(20);

        // Aggregation Stats
        $statsBase = QcDefect::whereBetween('defect_date', [$startDate, $endDate]);
        if ($lineId) {
            $statsBase->where('production_line_id', $lineId);
        }
        if ($defectType) {
            $statsBase->where('defect_type', $defectType);
        }

        $totalDefect = (clone $statsBase)->sum('defect_qty');
        $totalRepair = (clone $statsBase)->where('action_taken', 'repair')->sum('defect_qty');
        $totalReject = (clone $statsBase)->where('action_taken', 'reject')->sum('defect_qty');

        // Total Production Output in range to calculate defect rate %
        $hourlyOutputs = HourlyOutput::whereBetween('production_date', [$startDate, $endDate]);
        if ($lineId) {
            $hourlyOutputs->where('production_line_id', $lineId);
        }
        $goodOutput = $hourlyOutputs->sum('output_qty');
        $totalProduction = $goodOutput + $totalDefect;
        $overallDefectRate = $totalProduction > 0 ? round(($totalDefect / $totalProduction) * 100, 2) : 0;

        // Pareto: Top defect types
        $paretoDefects = (clone $statsBase)
            ->select('defect_type', DB::raw('SUM(defect_qty) as total_qty'))
            ->groupBy('defect_type')
            ->orderByDesc('total_qty')
            ->limit(8)
            ->get();

        // Defects per Line
        $defectsByLine = (clone $statsBase)
            ->join('production_lines', 'qc_defects.production_line_id', '=', 'production_lines.id')
            ->select('production_lines.name as line_name', DB::raw('SUM(qc_defects.defect_qty) as total_qty'))
            ->groupBy('production_lines.name', 'production_lines.id')
            ->orderByRaw('CAST(SUBSTRING_INDEX(production_lines.name, " ", -1) AS UNSIGNED) ASC')
            ->get();

        // Top Worst Line
        $worstLine = $defectsByLine->sortByDesc('total_qty')->first();

        // Defects per Hour (1 to 8)
        $defectsByHour = (clone $statsBase)
            ->select('hour_number', DB::raw('SUM(defect_qty) as total_qty'))
            ->groupBy('hour_number')
            ->orderBy('hour_number')
            ->pluck('total_qty', 'hour_number')
            ->toArray();

        $hourData = [];
        for ($h = 1; $h <= 8; $h++) {
            $hourData[] = [
                'hour' => 'Jam ' . $h,
                'qty' => $defectsByHour[$h] ?? 0
            ];
        }

        $stats = [
            'total_defect' => $totalDefect,
            'total_repair' => $totalRepair,
            'total_reject' => $totalReject,
            'defect_rate' => $overallDefectRate,
            'worst_line' => $worstLine ? $worstLine->line_name : '-',
            'worst_line_qty' => $worstLine ? $worstLine->total_qty : 0,
        ];

        return view('qc.recap.index', compact(
            'lines',
            'defectTypes',
            'defects',
            'stats',
            'paretoDefects',
            'defectsByLine',
            'hourData',
            'startDate',
            'endDate',
            'lineId',
            'defectType'
        ));
    }

    /**
     * AJAX endpoint to fetch JSON data for recap charts
     */
    public function getRecapData(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::today()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::today()->toDateString());
        $lineId = $request->input('line_id');

        $query = QcDefect::whereBetween('defect_date', [$startDate, $endDate]);
        if ($lineId) {
            $query->where('production_line_id', $lineId);
        }

        $pareto = (clone $query)
            ->select('defect_type', DB::raw('SUM(defect_qty) as total'))
            ->groupBy('defect_type')
            ->orderByDesc('total')
            ->get();

        $byLine = (clone $query)
            ->join('production_lines', 'qc_defects.production_line_id', '=', 'production_lines.id')
            ->select('production_lines.name', DB::raw('SUM(qc_defects.defect_qty) as total'))
            ->groupBy('production_lines.name', 'production_lines.id')
            ->orderByRaw('CAST(SUBSTRING_INDEX(production_lines.name, " ", -1) AS UNSIGNED) ASC')
            ->get();

        return response()->json([
            'pareto' => $pareto,
            'by_line' => $byLine
        ]);
    }

    /**
     * Export Defect Recap to CSV
     */
    public function exportRecap(Request $request): StreamedResponse
    {
        $startDate = $request->input('start_date', Carbon::today()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::today()->toDateString());
        $lineId = $request->input('line_id');
        $defectType = $request->input('defect_type');

        $query = QcDefect::with(['productionLine', 'styleOrder', 'recorder'])
            ->whereBetween('defect_date', [$startDate, $endDate]);

        if ($lineId) {
            $query->where('production_line_id', $lineId);
        }
        if ($defectType) {
            $query->where('defect_type', $defectType);
        }

        $records = $query->orderBy('defect_date', 'desc')->get();

        $filename = "rekap_defect_qc_{$startDate}_sampai_{$endDate}.csv";

        return response()->streamDownload(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['No', 'Tanggal', 'Line Produksi', 'Kode Style', 'Jam Ke', 'Jenis Defect', 'Jumlah Defect (pcs)', 'Tindakan (Action)', 'Catatan / Remark', 'Petugas QC']);

            $no = 1;
            foreach ($records as $item) {
                fputcsv($handle, [
                    $no++,
                    $item->defect_date->format('Y-m-d'),
                    $item->productionLine->name ?? '-',
                    $item->styleOrder->code_style ?? '-',
                    'Jam ' . $item->hour_number,
                    $item->defect_type,
                    $item->defect_qty,
                    strtoupper($item->action_taken),
                    $item->notes ?? '-',
                    $item->recorder->name ?? '-'
                ]);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Helper to keep HourlyOutput.defect_qty synchronized with QC records
     */
    private function syncHourlyOutputDefect($lineId, $styleId, $date, $hour): void
    {
        try {
            $totalDefect = QcDefect::where('production_line_id', $lineId)
                ->where('style_order_id', $styleId)
                ->whereDate('defect_date', $date)
                ->where('hour_number', $hour)
                ->sum('defect_qty');

            $hourly = HourlyOutput::where('production_line_id', $lineId)
                ->where('style_order_id', $styleId)
                ->whereDate('production_date', $date)
                ->where('hour_number', $hour)
                ->first();

            if ($hourly) {
                $hourly->defect_qty = $totalDefect;
                $hourly->save();
            }
        } catch (\Exception $e) {
            Log::warning('Could not sync HourlyOutput defect_qty: ' . $e->getMessage());
        }
    }
}
