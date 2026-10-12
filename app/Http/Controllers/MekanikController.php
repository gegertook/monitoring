<?php

namespace App\Http\Controllers;

use App\Models\Machine;
use App\Models\MachineBreakdown;
use App\Models\ProductionLine;
use App\Support\Notifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MekanikController extends Controller
{
    /**
     * Badge HTML untuk status mesin / downtime (dipakai view).
     */
    public static function statusBadge(string $status): string
    {
        $map = [
            'ready' => ['bg-slate-100 text-slate-700', 'fa-circle-check', 'Ready'],
            'dipakai' => ['bg-emerald-100 text-emerald-700', 'fa-industry', 'Dipakai'],
            'rusak' => ['bg-rose-100 text-rose-700', 'fa-triangle-exclamation', 'Rusak'],
            'nonaktif' => ['bg-slate-200 text-slate-500', 'fa-power-off', 'Nonaktif'],
            MachineBreakdown::STATUS_REPORTED => ['bg-amber-100 text-amber-700', 'fa-bell', 'Dilaporkan'],
            MachineBreakdown::STATUS_MECHANIC_SENT => ['bg-blue-100 text-blue-700', 'fa-screwdriver-wrench', 'Mekanik Dikirim'],
            MachineBreakdown::STATUS_REPLACEMENT_REQUESTED => ['bg-purple-100 text-purple-700', 'fa-arrows-rotate', 'Minta Ganti Mesin'],
            MachineBreakdown::STATUS_REPAIRED => ['bg-emerald-100 text-emerald-700', 'fa-check', 'Diperbaiki'],
            MachineBreakdown::STATUS_REPLACED => ['bg-indigo-100 text-indigo-700', 'fa-right-left', 'Diganti'],
        ];

        [$class, $icon, $label] = $map[$status] ?? ['bg-slate-100 text-slate-700', 'fa-circle-info', ucfirst($status)];

        return "<span class=\"inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold {$class}\">"
            . "<i class=\"fa-solid {$icon}\"></i> {$label}</span>";
    }

    /**
     * Dashboard Admin Mekanik: ringkasan mesin + downtime aktif.
     */
    public function index()
    {
        $machines = Machine::with('currentLine')->orderBy('code')->get();

        $stats = [
            'total' => $machines->count(),
            'dipakai' => $machines->whereNotNull('current_line_id')->count(),
            'ready' => $machines->filter(fn ($m) => !$m->current_line_id && $m->is_active)->count(),
            'rusak' => MachineBreakdown::open()->count(),
        ];

        $openBreakdowns = MachineBreakdown::open()
            ->with(['machine', 'productionLine', 'styleOrder', 'reporter', 'mechanic'])
            ->latest('reported_at')
            ->get();

        $lines = ProductionLine::where('is_active', true)->orderBy('name')->get();

        return view('mekanik.dashboard', compact('machines', 'stats', 'openBreakdowns', 'lines'));
    }

    // -------------------------------------------------------------------------
    // Master Mesin
    // -------------------------------------------------------------------------
    public function machinesIndex(Request $request)
    {
        $query = Machine::with('currentLine')->orderBy('code');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('machine_type', 'like', "%{$search}%");
            });
        }

        if ($request->filled('line_id')) {
            $query->onLine($request->line_id);
        }

        $machines = $query->get();
        $lines = ProductionLine::where('is_active', true)->orderBy('name')->get();

        return view('mekanik.machines', compact('machines', 'lines'));
    }

    public function storeMachine(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:machines,code',
            'name' => 'required|string|max:255',
            'machine_type' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
        ]);

        try {
            $validated['created_by'] = auth()->id();
            $machine = Machine::create($validated);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Mesin berhasil ditambahkan.', 'data' => $machine]);
            }

            return redirect()->back()->with('success', 'Mesin berhasil ditambahkan.');
        } catch (\Exception $e) {
            Log::error('Error creating machine: ' . $e->getMessage());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'Gagal menambahkan mesin: ' . $e->getMessage()], 500);
            }

            return back()->withInput()->with('error', 'Gagal menambahkan mesin.');
        }
    }

    public function updateMachine(Request $request, Machine $machine)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:machines,code,' . $machine->id,
            'name' => 'required|string|max:255',
            'machine_type' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
        ]);

        try {
            $machine->update($validated);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Data mesin berhasil diperbarui.']);
            }

            return redirect()->back()->with('success', 'Data mesin berhasil diperbarui.');
        } catch (\Exception $e) {
            Log::error('Error updating machine: ' . $e->getMessage());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'Gagal memperbarui mesin: ' . $e->getMessage()], 500);
            }

            return back()->withInput()->with('error', 'Gagal memperbarui mesin.');
        }
    }

    public function toggleMachine(Request $request, Machine $machine)
    {
        try {
            $machine->update(['is_active' => !$machine->is_active]);
            $status = $machine->is_active ? 'diaktifkan' : 'dinonaktifkan';

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => "Mesin berhasil {$status}."]);
            }

            return redirect()->back()->with('success', "Mesin berhasil {$status}.");
        } catch (\Exception $e) {
            Log::error('Error toggling machine: ' . $e->getMessage());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'Gagal mengubah status mesin.'], 500);
            }

            return back()->with('error', 'Gagal mengubah status mesin.');
        }
    }

    /**
     * Plotting mesin ke line. Satu mesin hanya boleh berada di satu line;
     * line_id kosong = melepas mesin dari semua line (kembali ke pool).
     */
    public function assignLine(Request $request, Machine $machine)
    {
        $validated = $request->validate([
            'line_id' => 'nullable|exists:production_lines,id',
        ]);

        try {
            $lineId = $validated['line_id'] ?? null;

            // Mesin dengan laporan downtime terbuka tidak boleh dipindah sama sekali
            // (termasuk dilepas ke pool) sampai laporan diselesaikan.
            if ($machine->isBroken()) {
                $message = 'Mesin sedang rusak/dalam perbaikan. Selesaikan laporan downtime sebelum memindahkan mesin.';
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['error' => $message], 422);
                }
                return back()->with('error', $message);
            }

            $machine->update(['current_line_id' => $lineId]);

            $lineName = $lineId ? (ProductionLine::find($lineId)->name ?? '-') : 'pool (tidak di line)';
            $message = "Mesin {$machine->code} sekarang berada di {$lineName}.";

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $message]);
            }

            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Error assigning machine to line: ' . $e->getMessage());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => 'Gagal memindahkan mesin: ' . $e->getMessage()], 500);
            }

            return back()->with('error', 'Gagal memindahkan mesin.');
        }
    }

    // -------------------------------------------------------------------------
    // Downtime
    // -------------------------------------------------------------------------
    public function downtimeIndex(Request $request)
    {
        $status = $request->input('status');
        $lineId = $request->input('line_id');

        $query = MachineBreakdown::with(['machine', 'productionLine', 'styleOrder', 'reporter', 'mechanic', 'replacementMachine'])
            ->latest('reported_at');

        if ($status === 'open') {
            $query->open();
        } elseif ($status) {
            $query->where('status', $status);
        }

        if ($lineId) {
            $query->forLine($lineId);
        }

        $breakdowns = $query->get();

        $stats = [
            'open' => MachineBreakdown::open()->count(),
            'waiting_mechanic' => MachineBreakdown::open()->where('status', 'reported')->count(),
            'in_progress' => MachineBreakdown::open()->where('status', 'mechanic_sent')->count(),
            'replacement_requested' => MachineBreakdown::open()->where('status', 'replacement_requested')->count(),
            'resolved_today' => MachineBreakdown::where('resolution', '!=', null)
                ->whereDate('resolved_at', now()->toDateString())
                ->count(),
        ];

        $lines = ProductionLine::where('is_active', true)->orderBy('name')->get();
        $replacementCandidates = Machine::active()->whereNull('current_line_id')->orderBy('code')->get();

        return view('mekanik.downtime', compact('breakdowns', 'stats', 'lines', 'replacementCandidates'));
    }

    /**
     * Admin Mekanik menyelesaikan downtime:
     *  - repaired: mesin diperbaiki, tetap di line yang sama
     *  - replaced: mesin lama dilepas, mesin pengganti masuk ke line tersebut
     */
    public function resolveBreakdown(Request $request, MachineBreakdown $breakdown)
    {
        $validated = $request->validate([
            'resolution' => 'required|in:repaired,replaced',
            'replacement_machine_id' => 'required_if:resolution,replaced|nullable|exists:machines,id',
        ]);

        if ($breakdown->isResolved()) {
            $message = 'Laporan downtime ini sudah diselesaikan sebelumnya.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => $message], 422);
            }
            return back()->with('error', $message);
        }

        try {
            $breakdown->load('machine');

            if ($validated['resolution'] === 'replaced') {
                $replacement = Machine::find($validated['replacement_machine_id']);

                if (!$replacement) {
                    throw new \RuntimeException('Mesin pengganti wajib dipilih.');
                }

                if ($replacement->id === $breakdown->machine_id) {
                    throw new \RuntimeException('Mesin pengganti tidak boleh sama dengan mesin yang rusak.');
                }

                if (!$replacement->is_active) {
                    throw new \RuntimeException("Mesin {$replacement->code} dalam status nonaktif.");
                }

                if ($replacement->current_line_id || $replacement->isBroken()) {
                    throw new \RuntimeException("Mesin {$replacement->code} sudah dipakai line lain / sedang rusak.");
                }

                // Mesin lama keluar dari line, mesin pengganti masuk ke line yang sama
                $breakdown->machine->update(['current_line_id' => null]);
                $replacement->update(['current_line_id' => $breakdown->production_line_id]);
                $breakdown->replacement_machine_id = $replacement->id;
            }

            $breakdown->fill([
                'status' => $validated['resolution'] === 'repaired'
                    ? MachineBreakdown::STATUS_REPAIRED
                    : MachineBreakdown::STATUS_REPLACED,
                'resolution' => $validated['resolution'],
                'resolved_by' => auth()->id(),
                'resolved_at' => now(),
            ])->save();

            $lineName = $breakdown->productionLine->name ?? 'Line';
            $machineCode = $breakdown->machine->code ?? '-';
            $resolvedText = $validated['resolution'] === 'repaired'
                ? "mesin {$machineCode} sudah diperbaiki"
                : "mesin {$machineCode} diganti dengan mesin {$breakdown->replacementMachine->code}";

            Notifier::toUser($breakdown->reported_by, 'downtime_selesai', "Downtime Selesai - {$lineName}", "Mesin di {$lineName} telah {$resolvedText} dan siap dipakai kembali.", [
                'breakdown_id' => $breakdown->id,
                'line_id' => $breakdown->production_line_id,
                'machine_id' => $breakdown->machine_id,
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => "Downtime diselesaikan: {$resolvedText}."]);
            }

            return redirect()->back()->with('success', "Downtime diselesaikan: {$resolvedText}.");
        } catch (\Exception $e) {
            Log::error('Error resolving machine breakdown: ' . $e->getMessage());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 500);
            }

            return back()->with('error', $e->getMessage());
        }
    }
}
