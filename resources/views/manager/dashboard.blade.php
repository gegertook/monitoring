@extends('layouts.app')

@section('title', 'Executive Dashboard (Manager)')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Executive Production Dashboard</h2>
            <p class="text-sm text-slate-500">Pemantauan Real-time KPI (Target vs Actual, Efficiency %, Defect Rate %) seluruh line pabrik.</p>
        </div>
        <div class="flex items-center gap-4">
            <div class="flex items-center gap-2 text-xs font-semibold text-slate-600 bg-slate-50 px-3 py-2 rounded-xl border border-slate-200">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>Auto Refresh (10s):</span>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" id="autoRefreshToggle" class="sr-only peer" checked>
                    <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-indigo-600"></div>
                </label>
            </div>
            <button onclick="refreshDashboard()" class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 transition" title="Refresh Sekarang">
                <i class="fa-solid fa-arrows-rotate text-sm"></i>
            </button>
        </div>
    </div>

    <!-- TOP KPI Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-gradient-to-br from-indigo-600 to-indigo-700 rounded-2xl shadow-md p-5 text-white">
            <div class="flex justify-between items-center text-indigo-100">
                <p class="text-xs font-bold uppercase tracking-wider">Total Output Hari Ini</p>
                <i class="fa-solid fa-boxes-stacked text-lg opacity-60"></i>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-3xl font-black" id="kpi_total_output">0</span>
                <span class="text-xs text-indigo-200 font-semibold">pcs</span>
            </div>
            <span class="text-[11px] text-indigo-200 block mt-1"><i class="fa-solid fa-arrow-trend-up mr-1"></i> Data live dari lantai pabrik</span>
        </div>

        <div class="bg-gradient-to-br from-emerald-600 to-emerald-700 rounded-2xl shadow-md p-5 text-white">
            <div class="flex justify-between items-center text-emerald-100">
                <p class="text-xs font-bold uppercase tracking-wider">Overall Efficiency</p>
                <i class="fa-solid fa-gauge-high text-lg opacity-60"></i>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-3xl font-black" id="kpi_efficiency">91.2%</span>
            </div>
            <span class="text-[11px] text-emerald-200 block mt-1"><i class="fa-solid fa-circle-check mr-1"></i> Sesuai target standar</span>
        </div>

        <div class="bg-gradient-to-br from-rose-500 to-rose-600 rounded-2xl shadow-md p-5 text-white">
            <div class="flex justify-between items-center text-rose-100">
                <p class="text-xs font-bold uppercase tracking-wider">Defect Rate Pabrik</p>
                <i class="fa-solid fa-triangle-exclamation text-lg opacity-60"></i>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-3xl font-black" id="kpi_defect_rate">0.00%</span>
            </div>
            <span class="text-[11px] text-rose-100 block mt-1">Batas toleransi maksimal: 3.0%</span>
        </div>

        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl shadow-md p-5 text-white">
            <div class="flex justify-between items-center text-slate-300">
                <p class="text-xs font-bold uppercase tracking-wider">Line Aktif Beroperasi</p>
                <i class="fa-solid fa-layer-group text-lg opacity-60"></i>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-3xl font-black" id="kpi_active_lines">3</span>
                <span class="text-xs text-slate-400 font-semibold">Lines</span>
            </div>
            <span class="text-[11px] text-slate-400 block mt-1">Semua line dalam pemantauan</span>
        </div>
    </div>

    <!-- Charts 2x2 Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
            <h3 class="text-sm font-bold text-slate-800 mb-4">Target vs Actual Output (Per Line)</h3>
            <div class="h-64">
                <canvas id="targetActualChart"></canvas>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
            <h3 class="text-sm font-bold text-slate-800 mb-4">Tren Output Harian Jam ke Jam (Hari Ini)</h3>
            <div class="h-64">
                <canvas id="hourlyTrendChart"></canvas>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
            <h3 class="text-sm font-bold text-slate-800 mb-4">Defect Rate per Line (%)</h3>
            <div class="h-64">
                <canvas id="defectRateChart"></canvas>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
            <h3 class="text-sm font-bold text-slate-800 mb-4">Efisiensi Mingguan (%)</h3>
            <div class="h-64">
                <canvas id="weeklyPerfChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Production Lines Status Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden p-6">
        <div class="flex justify-between items-center mb-4">
            <div>
                <h3 class="text-base font-bold text-slate-800">Status & Kinerja Live Production Lines</h3>
                <p class="text-xs text-slate-500">Data live diperbarui otomatis setiap ada input dari Supervisor.</p>
            </div>
            <a href="{{ route('manager.evaluation.index') }}" class="px-3.5 py-2 rounded-xl text-xs font-semibold bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition">
                <i class="fa-solid fa-magnifying-glass-chart mr-1"></i> Buka Evaluasi Line
            </a>
        </div>

        <div class="overflow-x-auto rounded-xl border border-slate-100">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-slate-600 font-semibold">
                    <tr>
                        <th class="px-5 py-3.5 text-left">Nama Line</th>
                        <th class="px-5 py-3.5 text-left">Style Order</th>
                        <th class="px-5 py-3.5 text-right">Target Output</th>
                        <th class="px-5 py-3.5 text-right">Actual Output</th>
                        <th class="px-5 py-3.5 text-left w-48">Efficiency (%)</th>
                        <th class="px-5 py-3.5 text-right">Defect Rate</th>
                        <th class="px-5 py-3.5 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white" id="line_status_body">
                    <tr>
                        <td colspan="7" class="px-5 py-8 text-center text-slate-400">Memuat status line...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let targetActualChart = null;
    let hourlyTrendChart = null;
    let defectRateChart = null;
    let weeklyPerfChart = null;

    function refreshDashboard() {
        // Fetch Realtime KPI
        $.ajax({
            url: "{{ route('manager.api.realtime-kpi') }}",
            type: "GET",
            success: function(kpi) {
                $('#kpi_total_output').text(Number(kpi.total_output).toLocaleString());
                $('#kpi_defect_rate').text(kpi.defect_rate + '%');
                $('#kpi_efficiency').text(kpi.overall_efficiency + '%');
                $('#kpi_active_lines').text(kpi.active_lines);

                // Update Hourly trend chart
                if(kpi.hourly_trend) {
                    let hLabels = [];
                    let hOutputs = [];
                    let hDefects = [];
                    kpi.hourly_trend.forEach(item => {
                        hLabels.push(item.hour);
                        hOutputs.push(item.output);
                        hDefects.push(item.defect);
                    });

                    if(hourlyTrendChart) hourlyTrendChart.destroy();
                    const hourlyCtx = document.getElementById('hourlyTrendChart').getContext('2d');
                    hourlyTrendChart = new Chart(hourlyCtx, {
                        type: 'line',
                        data: {
                            labels: hLabels,
                            datasets: [
                                {
                                    label: 'Output Bagus',
                                    data: hOutputs,
                                    borderColor: '#10b981',
                                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                    tension: 0.3,
                                    fill: true
                                },
                                {
                                    label: 'Defect',
                                    data: hDefects,
                                    borderColor: '#ef4444',
                                    backgroundColor: 'transparent',
                                    tension: 0.3
                                }
                            ]
                        },
                        options: { responsive: true, maintainAspectRatio: false }
                    });
                }
            }
        });

        // Fetch Line Performance
        $.ajax({
            url: "{{ route('manager.api.line-performance') }}",
            type: "GET",
            success: function(lines) {
                let rows = '';
                let lineLabels = [];
                let lineTargets = [];
                let lineActuals = [];

                if(lines && lines.length > 0) {
                    lines.forEach(line => {
                        lineLabels.push(line.name);
                        lineTargets.push(line.target);
                        lineActuals.push(line.actual);

                        let statusBadge = {
                            'Excellent': 'bg-emerald-100 text-emerald-800',
                            'Normal': 'bg-blue-100 text-blue-800',
                            'Attention Needed': 'bg-rose-100 text-rose-800'
                        }[line.status] || 'bg-slate-100 text-slate-800';

                        let effColor = line.efficiency >= 90 ? 'bg-emerald-500' : (line.efficiency >= 75 ? 'bg-indigo-500' : 'bg-rose-500');

                        rows += `
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-5 py-3.5 font-bold text-slate-800">${line.name}</td>
                            <td class="px-5 py-3.5 text-slate-600 font-medium">${line.style_name}</td>
                            <td class="px-5 py-3.5 text-right font-medium text-slate-500">${Number(line.target).toLocaleString()}</td>
                            <td class="px-5 py-3.5 text-right font-extrabold text-slate-800">${Number(line.actual).toLocaleString()}</td>
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-xs w-10">${line.efficiency}%</span>
                                    <div class="flex-1 bg-slate-200 rounded-full h-2">
                                        <div class="${effColor} h-2 rounded-full" style="width: ${Math.min(100, line.efficiency)}%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-right font-mono font-bold ${line.defect_rate > 5 ? 'text-rose-600' : 'text-slate-700'}">${line.defect_rate}%</td>
                            <td class="px-5 py-3.5 text-center">
                                <span class="px-2.5 py-1 text-xs font-bold rounded-full ${statusBadge}">${line.status}</span>
                            </td>
                        </tr>`;
                    });
                } else {
                    rows = '<tr><td colspan="7" class="px-5 py-8 text-center text-slate-400">Tidak ada line produksi aktif.</td></tr>';
                }

                $('#line_status_body').html(rows);

                // Render Target vs Actual Chart
                if(targetActualChart) targetActualChart.destroy();
                const targetCtx = document.getElementById('targetActualChart').getContext('2d');
                targetActualChart = new Chart(targetCtx, {
                    type: 'bar',
                    data: {
                        labels: lineLabels,
                        datasets: [
                            {
                                label: 'Target Output',
                                data: lineTargets,
                                backgroundColor: '#cbd5e1'
                            },
                            {
                                label: 'Actual Output',
                                data: lineActuals,
                                backgroundColor: '#4f46e5'
                            }
                        ]
                    },
                    options: { responsive: true, maintainAspectRatio: false }
                });
            }
        });

        // Fetch Defect Analysis
        $.ajax({
            url: "{{ route('manager.api.defect-analysis') }}",
            type: "GET",
            success: function(data) {
                let labels = [];
                let rates = [];
                if(data && data.length > 0) {
                    data.forEach(d => {
                        labels.push(d.line);
                        rates.push(d.defect_rate);
                    });
                }

                if(defectRateChart) defectRateChart.destroy();
                const defectCtx = document.getElementById('defectRateChart').getContext('2d');
                defectRateChart = new Chart(defectCtx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Defect Rate (%)',
                            data: rates,
                            backgroundColor: rates.map(r => r > 5 ? '#e11d48' : (r > 3 ? '#f59e0b' : '#10b981'))
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            }
        });
    }

    $(document).ready(function() {
        refreshDashboard();

        // Static Weekly Chart
        const weeklyCtx = document.getElementById('weeklyPerfChart').getContext('2d');
        weeklyPerfChart = new Chart(weeklyCtx, {
            type: 'line',
            data: {
                labels: ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
                datasets: [{
                    label: 'Overall Efficiency %',
                    data: [88, 90, 89, 93, 91, 94],
                    borderColor: '#3b82f6',
                    tension: 0.2,
                    fill: false
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });

        // Auto refresh interval (10 seconds)
        let refreshInterval = setInterval(refreshDashboard, 10000);

        $('#autoRefreshToggle').change(function() {
            if($(this).is(':checked')) {
                refreshInterval = setInterval(refreshDashboard, 10000);
                showNotification('info', 'Auto-refresh 10 detik diaktifkan.');
            } else {
                clearInterval(refreshInterval);
                showNotification('info', 'Auto-refresh dinonaktifkan.');
            }
        });
    });
</script>
@endsection
