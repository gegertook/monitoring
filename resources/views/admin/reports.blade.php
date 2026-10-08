@extends('layouts.app')

@section('title', 'Laporan & Rekapitulasi Produksi (Admin Produksi)')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Laporan & Rekapitulasi Produksi</h2>
            <p class="text-sm text-slate-500">Analisis tren output per line, akumulasi defect rate, dan ekspor data resmi pabrik.</p>
        </div>
        <div>
            <button type="button" id="export_csv_btn" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2.5 px-4 rounded-xl shadow-sm shadow-emerald-600/20 transition text-sm">
                <i class="fa-solid fa-file-csv text-base"></i>
                <span>Download Rekap CSV / Excel</span>
            </button>
        </div>
    </div>

    <!-- Filters Panel -->
    <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
        <form id="report_filter_form" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Dari Tanggal</label>
                <input type="date" id="start_date" name="start_date" value="{{ date('Y-m-d', strtotime('-6 days')) }}" class="w-full py-2 px-3 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-white font-medium">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Sampai Tanggal</label>
                <input type="date" id="end_date" name="end_date" value="{{ date('Y-m-d') }}" class="w-full py-2 px-3 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-white font-medium">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Production Line</label>
                <select id="filter_line" name="production_line_id" class="w-full py-2 px-3 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-white">
                    <option value="">Semua Production Line</option>
                    @foreach($lines as $line)
                        <option value="{{ $line->id }}">{{ $line->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <button type="submit" class="w-full inline-flex items-center justify-center gap-2 bg-slate-800 hover:bg-slate-900 text-white font-semibold py-2 px-4 rounded-xl text-sm transition shadow-sm">
                    <i class="fa-solid fa-chart-pie"></i>
                    <span>Terapkan Filter</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl shadow-sm p-4 border border-slate-200">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Total Output Bersih</span>
            <div class="flex items-baseline gap-1 mt-1">
                <span class="text-2xl font-black text-indigo-700" id="stat_total_output">0</span>
                <span class="text-xs text-slate-400 font-semibold">pcs</span>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm p-4 border border-slate-200">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Total Barang Defect</span>
            <div class="flex items-baseline gap-1 mt-1">
                <span class="text-2xl font-black text-rose-600" id="stat_total_defect">0</span>
                <span class="text-xs text-slate-400 font-semibold">pcs</span>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm p-4 border border-slate-200">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Defect Rate Rata-rata</span>
            <div class="flex items-baseline gap-1 mt-1">
                <span class="text-2xl font-black text-slate-800" id="stat_defect_rate">0.00%</span>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm p-4 border border-slate-200">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Target Line Efficiency</span>
            <div class="flex items-baseline gap-1 mt-1">
                <span class="text-2xl font-black text-emerald-600" id="stat_efficiency">88.5%</span>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
            <h3 class="text-sm font-bold text-slate-700 mb-4">Tren Output Harian (Pcs)</h3>
            <div class="h-64">
                <canvas id="trendChart"></canvas>
            </div>
        </div>
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
            <h3 class="text-sm font-bold text-slate-700 mb-4">Perbandingan Output per Production Line</h3>
            <div class="h-64">
                <canvas id="barChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Aggregated Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden p-6">
        <div class="mb-4">
            <h3 class="text-base font-bold text-slate-800">Tabel Rekapitulasi Data Produksi</h3>
            <p class="text-xs text-slate-500">Rincian catatan per jam yang telah terekam di sistem.</p>
        </div>

        <div class="overflow-x-auto rounded-xl border border-slate-100">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-slate-600 font-semibold">
                    <tr>
                        <th class="px-5 py-3.5 text-left">Tanggal</th>
                        <th class="px-5 py-3.5 text-left">Line</th>
                        <th class="px-5 py-3.5 text-left">Style Order</th>
                        <th class="px-5 py-3.5 text-center">Jam Ke</th>
                        <th class="px-5 py-3.5 text-right">Output (Pcs)</th>
                        <th class="px-5 py-3.5 text-right">Defect (Pcs)</th>
                        <th class="px-5 py-3.5 text-right">Defect Rate</th>
                        <th class="px-5 py-3.5 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white" id="report_table_body">
                    <tr>
                        <td colspan="8" class="px-5 py-8 text-center text-slate-400">Memuat data laporan...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let trendChartInstance = null;
    let barChartInstance = null;

    function loadReportData() {
        let startDate = $('#start_date').val();
        let endDate = $('#end_date').val();
        let lineId = $('#filter_line').val();

        $('#report_table_body').html('<tr><td colspan="8" class="text-center py-8 text-slate-400"><i class="fa-solid fa-spinner fa-spin mr-1"></i> Memuat laporan...</td></tr>');

        $.ajax({
            url: "{{ route('admin.api.report-data') }}",
            type: "GET",
            data: {
                start_date: startDate,
                end_date: endDate,
                production_line_id: lineId
            },
            success: function(res) {
                $('#stat_total_output').text(Number(res.total_output).toLocaleString());
                $('#stat_total_defect').text(Number(res.total_defect).toLocaleString());
                $('#stat_defect_rate').text(res.defect_rate + '%');
                $('#stat_efficiency').text((res.efficiency || 88.5) + '%');

                // Render Chart 1: Trend
                let trendLabels = [];
                let trendData = [];
                let defectData = [];

                if(res.daily_trend) {
                    $.each(res.daily_trend, function(dateLabel, values) {
                        trendLabels.push(dateLabel);
                        trendData.push(values.output);
                        defectData.push(values.defect);
                    });
                }

                if(trendChartInstance) trendChartInstance.destroy();
                const trendCtx = document.getElementById('trendChart').getContext('2d');
                trendChartInstance = new Chart(trendCtx, {
                    type: 'line',
                    data: {
                        labels: trendLabels.length > 0 ? trendLabels : ['Hari 1', 'Hari 2', 'Hari 3', 'Hari 4'],
                        datasets: [
                            {
                                label: 'Output Bagus',
                                data: trendData.length > 0 ? trendData : [0, 0, 0, 0],
                                borderColor: '#4f46e5',
                                backgroundColor: 'rgba(79, 70, 229, 0.1)',
                                tension: 0.3,
                                fill: true
                            },
                            {
                                label: 'Defect',
                                data: defectData.length > 0 ? defectData : [0, 0, 0, 0],
                                borderColor: '#e11d48',
                                backgroundColor: 'transparent',
                                tension: 0.3
                            }
                        ]
                    },
                    options: { responsive: true, maintainAspectRatio: false }
                });

                // Render Chart 2: Bar per Line
                let lineLabels = [];
                let lineOutputs = [];

                if(res.line_stats && res.line_stats.length > 0) {
                    res.line_stats.forEach(ls => {
                        lineLabels.push(ls.line_name);
                        lineOutputs.push(ls.output);
                    });
                }

                if(barChartInstance) barChartInstance.destroy();
                const barCtx = document.getElementById('barChart').getContext('2d');
                barChartInstance = new Chart(barCtx, {
                    type: 'bar',
                    data: {
                        labels: lineLabels.length > 0 ? lineLabels : ['Line 1', 'Line 2', 'Line 3'],
                        datasets: [{
                            label: 'Total Output per Line',
                            data: lineOutputs.length > 0 ? lineOutputs : [0, 0, 0],
                            backgroundColor: ['#4f46e5', '#06b6d4', '#10b981', '#f59e0b']
                        }]
                    },
                    options: { responsive: true, maintainAspectRatio: false }
                });

                // Render Table rows
                let rows = '';
                if(res.data && res.data.length > 0) {
                    res.data.forEach(item => {
                        let totalUnits = item.output_qty + item.defect_qty;
                        let rate = totalUnits > 0 ? (item.defect_qty / totalUnits * 100).toFixed(2) : 0;
                        let lineName = item.production_line ? item.production_line.name : '-';
                        let styleName = item.style_order ? item.style_order.code_style : '-';

                        rows += `
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-5 py-3.5 text-slate-600">${item.production_date ? item.production_date.substring(0, 10) : '-'}</td>
                            <td class="px-5 py-3.5 font-bold text-slate-800">${lineName}</td>
                            <td class="px-5 py-3.5 font-medium text-indigo-600">${styleName}</td>
                            <td class="px-5 py-3.5 text-center font-bold text-slate-700">Jam ${item.hour_number}</td>
                            <td class="px-5 py-3.5 text-right font-bold text-slate-800">${Number(item.output_qty).toLocaleString()} pcs</td>
                            <td class="px-5 py-3.5 text-right font-bold text-rose-600">${Number(item.defect_qty).toLocaleString()} pcs</td>
                            <td class="px-5 py-3.5 text-right font-mono font-bold text-slate-700">${rate}%</td>
                            <td class="px-5 py-3.5 text-center">
                                <span class="px-2 py-0.5 text-xs font-bold rounded-full ${item.is_validated ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'}">
                                    ${item.is_validated ? 'Validated' : 'Pending'}
                                </span>
                            </td>
                        </tr>`;
                    });
                } else {
                    rows = '<tr><td colspan="8" class="text-center py-8 text-slate-400">Tidak ada data yang memenuhi kriteria filter.</td></tr>';
                }

                $('#report_table_body').html(rows);
            },
            error: function() {
                $('#report_table_body').html('<tr><td colspan="8" class="text-center py-8 text-rose-500">Gagal mengambil data laporan.</td></tr>');
            }
        });
    }

    $(document).ready(function() {
        loadReportData();

        $('#report_filter_form').submit(function(e) {
            e.preventDefault();
            loadReportData();
        });

        $('#export_csv_btn').click(function() {
            let startDate = $('#start_date').val();
            let endDate = $('#end_date').val();
            let lineId = $('#filter_line').val();

            let url = `{{ route('admin.reports.export') }}?start_date=${startDate}&end_date=${endDate}`;
            if(lineId) url += `&production_line_id=${lineId}`;
            window.location.href = url;
        });
    });
</script>
@endsection
