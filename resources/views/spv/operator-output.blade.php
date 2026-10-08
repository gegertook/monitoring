@extends('layouts.app')

@section('title', 'Input Output Operator Harian (SPV)')

@section('content')
<div class="space-y-6">
    <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4 pb-3 border-b border-slate-100">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Pencatatan Hasil Output Individu / Operator</h2>
                <p class="text-xs text-slate-500">SPV Line merekam pencapaian unit harian dan defect individu operator untuk penilaian kinerja.</p>
            </div>
            <button type="button" id="load_operators" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-xl shadow-sm transition text-xs">
                <i class="fa-solid fa-arrows-rotate"></i>
                <span>Muat Data Operator</span>
            </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Style Order</label>
                <select id="filter_style" class="w-full py-2.5 px-3 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-white">
                    <option value="">-- Pilih Style Order --</option>
                    @foreach($styleOrders as $order)
                        <option value="{{ $order->id }}" {{ $loop->first ? 'selected' : '' }}>
                            {{ $order->code_style }} ({{ $order->buyer }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Tanggal Produksi</label>
                <input type="date" id="filter_date" value="{{ $date ?? date('Y-m-d') }}" class="w-full py-2 px-3 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-white font-medium">
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Total Akumulasi Output</span>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-2xl font-black text-indigo-600" id="stat_total_output">0</span>
                <span class="text-xs text-slate-400 font-semibold">pcs</span>
            </div>
        </div>
        <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Rata-rata per Operator</span>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-2xl font-black text-emerald-600" id="stat_avg_output">0</span>
                <span class="text-xs text-slate-400 font-semibold">pcs / operator</span>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex justify-between items-center">
            <h3 class="text-base font-bold text-slate-800">Form Pencatatan Output Operator</h3>
            <span class="text-xs text-slate-400">Tekan "Simpan Tiap Baris" atau "Simpan Semua"</span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-slate-600 font-semibold">
                    <tr>
                        <th class="px-5 py-3.5 text-left">Nama Operator</th>
                        <th class="px-5 py-3.5 text-left">Elemen Proses</th>
                        <th class="px-5 py-3.5 text-left w-48">Output Qty (Pcs)</th>
                        <th class="px-5 py-3.5 text-left w-48">Defect Qty (Pcs)</th>
                        <th class="px-5 py-3.5 text-center w-28">Aksi</th>
                    </tr>
                </thead>
                <tbody id="operator_table_body" class="bg-white divide-y divide-slate-100">
                    <tr>
                        <td colspan="5" class="px-5 py-8 text-center text-slate-400">Memuat operator...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let activeProcesses = [];

    function calculateStats() {
        let totalOutput = 0;
        let count = 0;
        $('.op-output-input').each(function() {
            let val = parseInt($(this).val()) || 0;
            totalOutput += val;
            if(val > 0) count++;
        });
        
        $('#stat_total_output').text(totalOutput.toLocaleString());
        $('#stat_avg_output').text(count > 0 ? (totalOutput / count).toFixed(1) : 0);
    }

    function loadOperatorTable() {
        let styleId = $('#filter_style').val();
        let date = $('#filter_date').val();

        if(!styleId) return;

        $('#operator_table_body').html('<tr><td colspan="5" class="text-center py-8 text-slate-400"><i class="fa-solid fa-spinner fa-spin mr-1"></i> Memuat data pengerjaan...</td></tr>');

        // Fetch processes for this style order first
        $.get(`/ie/api/style-orders/${styleId}/processes`, function(processes) {
            activeProcesses = processes;

            // Fetch already recorded operator outputs
            $.get("{{ route('spv.api.operator-data') }}", { style_order_id: styleId, date: date }, function(records) {
                let recordedMap = {};
                if(records && records.length > 0) {
                    records.forEach(r => {
                        recordedMap[`${r.employee_id}_${r.breakdown_process_id}`] = r;
                    });
                }

                // Render all operators
                let employees = @json($employees);
                let rows = '';

                if(employees && employees.length > 0) {
                    employees.forEach(emp => {
                        let procOptions = '';
                        if(activeProcesses.length > 0) {
                            activeProcesses.forEach(p => {
                                procOptions += `<option value="${p.id}">${p.process_name} (#${p.sequence_number})</option>`;
                            });
                        } else {
                            procOptions = '<option value="">(Belum ada proses di style ini)</option>';
                        }

                        rows += `
                        <tr class="hover:bg-slate-50 transition" data-emp-id="${emp.id}">
                            <td class="px-5 py-3.5">
                                <span class="font-bold text-slate-800 block">${emp.name}</span>
                                <span class="text-xs text-slate-400 font-mono">${emp.nik} &bull; ${emp.skill_level}</span>
                            </td>
                            <td class="px-5 py-3.5">
                                <select class="op-process-select w-full py-1.5 px-2 text-xs border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 bg-white">
                                    ${procOptions}
                                </select>
                            </td>
                            <td class="px-5 py-3.5">
                                <input type="number" class="op-output-input w-full py-1.5 px-3 text-sm font-semibold border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500" value="0" min="0">
                            </td>
                            <td class="px-5 py-3.5">
                                <input type="number" class="op-defect-input w-full py-1.5 px-3 text-sm font-semibold border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 text-rose-600" value="0" min="0">
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <button type="button" onclick="saveOperatorRow(${emp.id}, this)" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-indigo-50 text-indigo-600 hover:bg-indigo-600 hover:text-white transition">
                                    Simpan
                                </button>
                            </td>
                        </tr>`;
                    });
                } else {
                    rows = '<tr><td colspan="5" class="text-center py-8 text-slate-400">Tidak ada operator aktif.</td></tr>';
                }

                $('#operator_table_body').html(rows);

                // Populate recorded data if exists
                if(records && records.length > 0) {
                    records.forEach(r => {
                        let row = $(`#operator_table_body tr[data-emp-id="${r.employee_id}"]`);
                        if(row.length) {
                            row.find('.op-process-select').val(r.breakdown_process_id);
                            row.find('.op-output-input').val(r.output_qty);
                            row.find('.op-defect-input').val(r.defect_qty);
                        }
                    });
                }

                calculateStats();
            });
        });
    }

    function saveOperatorRow(empId, btn) {
        let row = $(btn).closest('tr');
        let styleId = $('#filter_style').val();
        let date = $('#filter_date').val();
        let processId = row.find('.op-process-select').val();
        let outputQty = row.find('.op-output-input').val();
        let defectQty = row.find('.op-defect-input').val();

        if(!processId) {
            showNotification('warning', 'Pilih proses pengerjaan terlebih dahulu!');
            return;
        }

        $.ajax({
            url: "{{ route('spv.operator-output.store') }}",
            type: "POST",
            data: {
                employee_id: empId,
                style_order_id: styleId,
                breakdown_process_id: processId,
                output_qty: outputQty,
                defect_qty: defectQty,
                production_date: date
            },
            success: function(res) {
                showNotification('success', 'Output operator tersimpan!');
                calculateStats();
            },
            error: function(err) {
                showNotification('error', err.responseJSON?.error || 'Gagal menyimpan output.');
            }
        });
    }

    $(document).ready(function() {
        loadOperatorTable();

        $('#filter_style, #filter_date').change(function() {
            loadOperatorTable();
        });

        $('#load_operators').click(function() {
            loadOperatorTable();
        });

        $(document).on('input', '.op-output-input', calculateStats);
    });
</script>
@endsection
