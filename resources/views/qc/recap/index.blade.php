@extends('layouts.app')

@section('title', 'Rekapitulasi Defect Produksi - Quality Control')

@section('content')
<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Rekapitulasi Defect Keseluruhan</h1>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800 border border-indigo-200">
                    <i class="fa-solid fa-chart-pie text-[10px]"></i> Laporan Mutu & Kualitas
                </span>
            </div>
            <p class="text-sm text-slate-500 mt-1">Analisis temuan cacat produksi lintas 30 line, Pareto kategori defect, dan evaluasi tindakan penanganan.</p>
        </div>
        <div class="flex items-center gap-2">
            @php
                $exportRoute = match(auth()->user()->role) {
                    'admin_produksi' => route('admin.defects.export', request()->query()),
                    'manager' => route('manager.defects.export', request()->query()),
                    default => route('qc.export', request()->query()),
                };
            @endphp
            <a href="{{ $exportRoute }}" class="inline-flex items-center gap-2 px-3.5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium text-sm shadow-xs transition">
                <i class="fa-solid fa-file-excel"></i>
                <span>Download Rekap CSV</span>
            </a>
            @if(auth()->user()->role === 'admin_qc')
                <a href="{{ route('qc.defects.index') }}" class="inline-flex items-center gap-2 px-3.5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white rounded-lg font-medium text-sm shadow-xs transition">
                    <i class="fa-solid fa-plus"></i>
                    <span>Input Defect Line</span>
                </a>
            @endif
        </div>
    </div>

    <!-- Filter Toolbar Card -->
    <div class="bg-white p-4 sm:p-5 rounded-xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ url()->current() }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Dari Tanggal</label>
                <input type="date" name="start_date" value="{{ $startDate }}" 
                    class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-hidden bg-white">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Sampai Tanggal</label>
                <input type="date" name="end_date" value="{{ $endDate }}" 
                    class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-hidden bg-white">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Line Produksi</label>
                <select name="line_id" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-hidden bg-white">
                    <option value="">Semua Line Produksi</option>
                    @foreach($lines as $l)
                        <option value="{{ $l->id }}" {{ $lineId == $l->id ? 'selected' : '' }}>{{ $l->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Kategori Defect</label>
                <select name="defect_type" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-hidden bg-white">
                    <option value="">Semua Kategori</option>
                    @foreach($defectTypes as $dt)
                        <option value="{{ $dt }}" {{ $defectType == $dt ? 'selected' : '' }}>{{ $dt }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 py-2 px-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold transition flex items-center justify-center gap-1.5 shadow-xs">
                    <i class="fa-solid fa-filter text-xs"></i>
                    <span>Terapkan</span>
                </button>
                <a href="{{ url()->current() }}" class="py-2 px-3 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg text-sm transition" title="Reset">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Summary KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Defect</p>
                <span class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center text-sm font-bold">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </span>
            </div>
            <h3 class="text-2xl font-bold text-slate-900 mt-2">{{ number_format($stats['total_defect']) }} <span class="text-xs font-normal text-slate-500">pcs</span></h3>
            <p class="text-[11px] text-slate-400 mt-1">Periode terpilih</p>
        </div>

        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Defect Rate</p>
                <span class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm font-bold">
                    <i class="fa-solid fa-percent"></i>
                </span>
            </div>
            <h3 class="text-2xl font-bold {{ $stats['defect_rate'] > 5 ? 'text-rose-600' : 'text-emerald-600' }} mt-2">
                {{ $stats['defect_rate'] }}%
            </h3>
            <p class="text-[11px] text-slate-400 mt-1">Standar target &lt; 3.0%</p>
        </div>

        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Repair (Dapat Diperbaiki)</p>
                <span class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-sm font-bold">
                    <i class="fa-solid fa-wrench"></i>
                </span>
            </div>
            <h3 class="text-2xl font-bold text-amber-600 mt-2">{{ number_format($stats['total_repair']) }} <span class="text-xs font-normal text-slate-500">pcs</span></h3>
            <p class="text-[11px] text-slate-400 mt-1">{{ $stats['total_defect'] > 0 ? round(($stats['total_repair'] / $stats['total_defect']) * 100, 1) : 0 }}% dari total defect</p>
        </div>

        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Reject (Afkir)</p>
                <span class="w-8 h-8 rounded-lg bg-red-50 text-red-600 flex items-center justify-center text-sm font-bold">
                    <i class="fa-solid fa-ban"></i>
                </span>
            </div>
            <h3 class="text-2xl font-bold text-red-600 mt-2">{{ number_format($stats['total_reject']) }} <span class="text-xs font-normal text-slate-500">pcs</span></h3>
            <p class="text-[11px] text-slate-400 mt-1">{{ $stats['total_defect'] > 0 ? round(($stats['total_reject'] / $stats['total_defect']) * 100, 1) : 0 }}% dari total defect</p>
        </div>

        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Line Defect Tertinggi</p>
                <span class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center text-sm font-bold">
                    <i class="fa-solid fa-arrow-trend-up"></i>
                </span>
            </div>
            <h3 class="text-xl font-bold text-purple-700 mt-2 truncate">{{ $stats['worst_line'] }}</h3>
            <p class="text-[11px] text-slate-400 mt-1">{{ number_format($stats['worst_line_qty']) }} pcs temuan</p>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Pareto Chart: Defect Types -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-bold text-slate-900 text-sm">Pareto Analisis: Jenis Cacat Terbanyak</h3>
                    <p class="text-xs text-slate-400">Peringkat defect utama yang membutuhkan perbaikan proses.</p>
                </div>
                <span class="text-xs px-2.5 py-1 bg-slate-100 rounded-md font-semibold text-slate-600">Top 8 Cacat</span>
            </div>
            <div class="relative h-64">
                <canvas id="paretoChart"></canvas>
            </div>
        </div>

        <!-- Defect by Hour -->
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-bold text-slate-900 text-sm">Tren Defect per Jam Kerja (Jam 1 s/d Jam 8)</h3>
                    <p class="text-xs text-slate-400">Fluktuasi jumlah temuan defect sepanjang jam operasional.</p>
                </div>
                <span class="text-xs px-2.5 py-1 bg-emerald-50 text-emerald-700 rounded-md font-semibold">1 Shift Kerja</span>
            </div>
            <div class="relative h-64">
                <canvas id="hourlyDefectChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Full Width Chart: Defect per Line -->
    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="font-bold text-slate-900 text-sm">Distribusi Temuan Defect Lintas Line Produksi</h3>
                <p class="text-xs text-slate-400">Perbandingan jumlah defect pada masing-masing line produksi.</p>
            </div>
            <span class="text-xs px-2.5 py-1 bg-indigo-50 text-indigo-700 rounded-md font-semibold">Line Comparison</span>
        </div>
        <div class="relative h-72">
            <canvas id="lineDefectChart"></canvas>
        </div>
    </div>

    <!-- Detailed Table Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
            <h3 class="font-bold text-slate-900 text-sm">Daftar Detail Temuan Defect</h3>
            <span class="text-xs text-slate-500 font-medium">Total {{ $defects->total() }} Catatan Ditemukan</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <th class="py-3 px-4">Tanggal</th>
                        <th class="py-3 px-4">Line Produksi</th>
                        <th class="py-3 px-4">Style Order</th>
                        <th class="py-3 px-4">Jam Kerja</th>
                        <th class="py-3 px-4">Jenis Defect</th>
                        <th class="py-3 px-4 text-center">Jumlah</th>
                        <th class="py-3 px-4 text-center">Tindakan</th>
                        <th class="py-3 px-4">Catatan / Area Cacat</th>
                        <th class="py-3 px-4">Petugas QC</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-sm">
                    @forelse($defects as $item)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="py-3 px-4 text-xs font-mono text-slate-600">
                                {{ $item->defect_date->format('d/m/Y') }}
                            </td>
                            <td class="py-3 px-4 font-semibold text-slate-900">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-xs font-bold bg-slate-100 text-slate-800">
                                    <i class="fa-solid fa-industry text-indigo-500"></i> {{ $item->productionLine->name ?? '-' }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-xs font-semibold text-indigo-600 font-mono">
                                {{ $item->styleOrder->code_style ?? '-' }}
                            </td>
                            <td class="py-3 px-4 text-xs text-slate-600">
                                Jam {{ $item->hour_number }}
                            </td>
                            <td class="py-3 px-4 font-medium text-slate-800">
                                {{ $item->defect_type }}
                            </td>
                            <td class="py-3 px-4 text-center">
                                <span class="font-bold text-rose-600">{{ $item->defect_qty }}</span> <span class="text-xs text-slate-400">pcs</span>
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if($item->action_taken === 'repair')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-100 text-amber-800">
                                        Repair
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-red-100 text-red-800">
                                        Reject
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-xs text-slate-500 max-w-xs truncate">
                                {{ $item->notes ?: '-' }}
                            </td>
                            <td class="py-3 px-4 text-xs text-slate-600">
                                {{ $item->recorder->name ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-12 text-center text-slate-500">
                                <i class="fa-solid fa-circle-check text-3xl text-emerald-400 mb-2 block"></i>
                                Tidak ada catatan defect yang cocok dengan filter yang dipilih.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($defects->hasPages())
            <div class="p-4 border-t border-slate-200 bg-slate-50/50">
                {{ $defects->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
    // 1. Pareto Chart
    const paretoLabels = {!! json_encode($paretoDefects->pluck('defect_type')) !!};
    const paretoData = {!! json_encode($paretoDefects->pluck('total_qty')) !!};

    const ctxPareto = document.getElementById('paretoChart').getContext('2d');
    new Chart(ctxPareto, {
        type: 'bar',
        data: {
            labels: paretoLabels,
            datasets: [{
                label: 'Jumlah Defect (pcs)',
                data: paretoData,
                backgroundColor: '#f43f5e',
                borderRadius: 6,
                maxBarThickness: 32
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: {
                    ticks: {
                        callback: function(val, index) {
                            const lbl = this.getLabelForValue(val);
                            return lbl.length > 15 ? lbl.substr(0, 15) + '...' : lbl;
                        },
                        font: { size: 10 }
                    }
                },
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0 }
                }
            }
        }
    });

    // 2. Hourly Defect Trend
    const hourLabels = {!! json_encode(array_column($hourData, 'hour')) !!};
    const hourValues = {!! json_encode(array_column($hourData, 'qty')) !!};

    const ctxHour = document.getElementById('hourlyDefectChart').getContext('2d');
    new Chart(ctxHour, {
        type: 'line',
        data: {
            labels: hourLabels,
            datasets: [{
                label: 'Defect per Jam',
                data: hourValues,
                borderColor: '#e11d48',
                backgroundColor: 'rgba(225, 29, 72, 0.1)',
                tension: 0.3,
                fill: true,
                pointBackgroundColor: '#e11d48',
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0 }
                }
            }
        }
    });

    // 3. Line Defect Comparison
    const lineLabels = {!! json_encode($defectsByLine->pluck('line_name')) !!};
    const lineValues = {!! json_encode($defectsByLine->pluck('total_qty')) !!};

    const ctxLine = document.getElementById('lineDefectChart').getContext('2d');
    new Chart(ctxLine, {
        type: 'bar',
        data: {
            labels: lineLabels,
            datasets: [{
                label: 'Total Defect (pcs)',
                data: lineValues,
                backgroundColor: '#6366f1',
                borderRadius: 6,
                maxBarThickness: 24
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { precision: 0 }
                }
            }
        }
    });
</script>
@endsection
