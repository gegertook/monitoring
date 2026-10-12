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
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-50 border border-indigo-200 text-indigo-700 rounded-xl text-sm font-bold">
                    <i class="fa-solid fa-industry"></i>
                    {{ $line->name }}
                </span>
                <button type="button" id="load_operators" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-xl shadow-sm transition text-xs">
                    <i class="fa-solid fa-arrows-rotate"></i>
                    <span>Muat Data Operator</span>
                </button>
            </div>
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

    {{-- Panel Downtime Mesin: SPV melaporkan mesin rusak, Admin Mekanik menindaklanjuti --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
            <div>
                <h3 class="text-base font-bold text-slate-800">Mesin di Line &amp; Status Downtime</h3>
                <p class="text-xs text-slate-500">Jika mesin rusak, tandai di sini. Admin mekanik &amp; admin produksi otomatis diberi tahu.</p>
            </div>
            <span class="text-xs text-slate-400">Bisa minta ganti mesin setelah downtime &gt; 1 jam</span>
        </div>

        <div class="p-5">
            <div id="machine_list" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <div class="text-center py-6 text-slate-400 text-xs"><i class="fa-solid fa-spinner fa-spin mr-1"></i> Memuat mesin...</div>
            </div>

            <div id="downtime_list" class="mt-4 space-y-2"></div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex justify-between items-center">
            <div>
                <h3 class="text-base font-bold text-slate-800">Form Pencatatan Output Operator</h3>
                <p class="text-xs text-slate-500 mt-0.5">SPV hanya mencatat <b>output</b>. Defect dicatat Admin QC dan tampil di sini sebagai informasi.</p>
            </div>
            <span class="text-xs text-slate-400">Tekan tombol "Simpan" pada baris operator</span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-slate-600 font-semibold">
                    <tr>
                        <th class="px-5 py-3.5 text-left">Nama Operator</th>
                        <th class="px-5 py-3.5 text-left">Elemen Proses</th>
                        <th class="px-5 py-3.5 text-left w-48">Output Qty (Pcs)</th>
                        <th class="px-5 py-3.5 text-left w-32">Defect (QC)</th>
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

    function showTableMessage(message, isError) {
        let td = $('<td>')
            .attr('colspan', 5)
            .addClass('text-center py-8 ' + (isError ? 'text-rose-500 font-semibold' : 'text-slate-400'))
            .text(message);
        $('#operator_table_body').empty().append($('<tr>').append(td));
    }

    function showTableLoading() {
        let td = $('<td>').attr('colspan', 5).addClass('text-center py-8 text-slate-400');
        // (colspan tetap 5 karena kolom defect masih ditampilkan read-only)
        td.append($('<i>').addClass('fa-solid fa-spinner fa-spin mr-1')).append(document.createTextNode(' Memuat data pengerjaan...'));
        $('#operator_table_body').empty().append($('<tr>').append(td));
    }

    function loadOperatorTable() {
        let styleId = $('#filter_style').val();
        let date = $('#filter_date').val();

        if(!styleId) {
            activeProcesses = [];
            showTableMessage('Pilih style order terlebih dahulu.', false);
            return;
        }

        showTableLoading();

        // Fetch processes for this style order first (SPV endpoint, scoped to this line)
        $.get(`/spv/api/style-orders/${styleId}/processes`)
        .fail(function(xhr) {
            let msg = xhr.responseJSON?.error || 'Gagal memuat data pengerjaan (HTTP ' + (xhr.status || '-') + ').';
            showTableMessage(msg, true);
            showNotification('error', msg);
        })
        .done(function(processes) {
            activeProcesses = Array.isArray(processes) ? processes : [];

            // Fetch already recorded operator outputs
            $.get("{{ route('spv.api.operator-data') }}", { style_order_id: styleId, date: date })
            .fail(function(xhr) {
                let msg = xhr.responseJSON?.error || 'Gagal memuat data output yang sudah direkam.';
                showTableMessage(msg, true);
                showNotification('error', msg);
            })
            .done(function(records) {
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
                                <span class="op-defect-view text-xs font-semibold text-rose-600 bg-rose-50 border border-rose-100 rounded-lg px-2.5 py-1.5 inline-block">0 pcs (QC)</span>
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

                // Populate recorded data if exists (defect hanya tampil, diisi Admin QC)
                if(records && records.length > 0) {
                    records.forEach(r => {
                        let row = $(`#operator_table_body tr[data-emp-id="${r.employee_id}"]`);
                        if(row.length) {
                            row.find('.op-process-select').val(r.breakdown_process_id);
                            row.find('.op-output-input').val(r.output_qty);
                            row.find('.op-defect-view').text((r.defect_qty || 0) + ' pcs (QC)');
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

    // -------------------------------------------------------------------------
    // Mesin di Line & Downtime
    // -------------------------------------------------------------------------
    function machineBadge(broken) {
        return broken
            ? '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-100 text-rose-700"><i class="fa-solid fa-triangle-exclamation"></i> Rusak</span>'
            : '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700"><i class="fa-solid fa-circle-check"></i> Normal</span>';
    }

    function loadMachines() {
        $.get("{{ route('spv.api.line-machines') }}")
        .fail(function() {
            $('#machine_list').html('<div class="col-span-full text-center py-6 text-rose-500 text-xs">Gagal memuat mesin line.</div>');
        })
        .done(function(machines) {
            if(!machines || machines.length === 0) {
                $('#machine_list').html('<div class="col-span-full text-center py-6 text-slate-400 text-xs border border-dashed border-slate-200 rounded-xl">Belum ada mesin yang diplot ke line ini. Hubungi admin mekanik.</div>');
                return;
            }

            let html = '';
            machines.forEach(m => {
                let broken = !!m.open_breakdown;
                html += `
                <div class="p-3.5 rounded-xl border ${broken ? 'border-rose-200 bg-rose-50' : 'border-slate-200 bg-slate-50'} hover:shadow-xs transition">
                    <div class="flex justify-between items-start gap-2">
                        <div>
                            <p class="text-xs font-bold text-slate-800">${m.code}</p>
                            <p class="text-[11px] text-slate-500">${m.name}${m.machine_type ? ' &bull; ' + m.machine_type : ''}</p>
                        </div>
                        ${machineBadge(broken)}
                    </div>
                    <div class="mt-2.5">
                        ${broken
                            ? `<button type="button" class="w-full px-3 py-1.5 rounded-lg text-[11px] font-semibold bg-white text-rose-600 border border-rose-200" disabled><i class="fa-solid fa-screwdriver-wrench mr-1"></i> Sedang ditangani</button>`
                            : `<button type="button" onclick="reportBreakdown(${m.id}, '${m.code}')" class="w-full px-3 py-1.5 rounded-lg text-[11px] font-semibold bg-rose-600 text-white hover:bg-rose-700 transition"><i class="fa-solid fa-triangle-exclamation mr-1"></i> Laporkan Rusak</button>`}
                    </div>
                </div>`;
            });

            $('#machine_list').html(html);
        });
    }

    function loadDowntime() {
        $.get("{{ route('spv.api.downtime') }}")
        .fail(function() {
            $('#downtime_list').html('<div class="text-center py-4 text-rose-500 text-xs">Gagal memuat status downtime.</div>');
        })
        .done(function(res) {
            let list = (res && res.breakdowns) ? res.breakdowns : [];

            if(list.length === 0) {
                $('#downtime_list').html('<div class="text-center py-3 text-xs text-emerald-600 font-semibold"><i class="fa-solid fa-circle-check mr-1"></i> Tidak ada downtime aktif di line ini.</div>');
                return;
            }

            let html = '';
            list.forEach(b => {
                let statusLabel = {
                    reported: 'Dilaporkan - menunggu pengiriman mekanik',
                    mechanic_sent: 'Mekanik sudah dikirim ke line',
                    replacement_requested: 'Permintaan ganti mesin sudah dikirim'
                }[b.status] || b.status;

                let over = b.minutes_since_reported >= 60;

                html += `
                <div class="p-3.5 rounded-xl border ${over ? 'border-purple-200 bg-purple-50' : 'border-amber-200 bg-amber-50'} flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold text-slate-800"><i class="fa-solid fa-gears mr-1"></i> ${b.machine_code} &mdash; ${b.machine_name}</p>
                        <p class="text-[11px] text-slate-500 mt-0.5">${statusLabel}${b.notes ? ' &bull; Keluhan: ' + b.notes : ''}</p>
                        <p class="text-[11px] ${over ? 'text-purple-700 font-bold' : 'text-slate-400'} mt-0.5">
                            <i class="fa-regular fa-clock mr-1"></i> ${b.minutes_since_reported} menit sejak laporan
                        </p>
                    </div>
                    <div>
                        ${b.status === 'replacement_requested'
                            ? '<span class="text-[11px] font-bold text-purple-700"><i class="fa-solid fa-hourglass-half mr-1"></i> Menunggu admin mekanik mengganti mesin</span>'
                            : (b.can_request_replacement
                                ? `<button type="button" onclick="requestReplacement(${b.id})" class="px-3 py-2 rounded-lg text-[11px] font-bold bg-purple-600 text-white hover:bg-purple-700 transition"><i class="fa-solid fa-right-left mr-1"></i> Minta Ganti Mesin</button>`
                                : `<span class="text-[11px] text-slate-500">Minta ganti mesin: bisa setelah 1 jam (${b.minutes_since_reported}/60 menit)</span>`)}
                    </div>
                </div>`;
            });

            $('#downtime_list').html(html);
        });
    }

    function reportBreakdown(machineId, machineCode) {
        Swal.fire({
            title: `Laporkan mesin ${machineCode} rusak?`,
            input: 'textarea',
            inputPlaceholder: 'Keluhan (mis. jarum patah, motor tidak jalan, bunyi kasar)...',
            inputAttributes: { 'maxlength': 500 },
            showCancelButton: true,
            confirmButtonText: 'Laporkan Rusak',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#e11d48'
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: "{{ route('spv.downtime.store') }}",
                type: "POST",
                data: {
                    machine_id: machineId,
                    notes: result.value || '',
                    style_order_id: $('#filter_style').val() || ''
                },
                success: function(res) {
                    showNotification('success', res.message || 'Mesin dilaporkan rusak.');
                    loadMachines();
                    loadDowntime();
                },
                error: function(err) {
                    showNotification('error', err.responseJSON?.error || 'Gagal melaporkan mesin rusak.');
                }
            });
        });
    }

    function requestReplacement(breakdownId) {
        Swal.fire({
            title: 'Minta Ganti Mesin?',
            text: 'Permintaan akan dikirim ke admin mekanik. Tersedia setelah downtime lebih dari 1 jam.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Kirim Permintaan',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#7c3aed'
        }).then((result) => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: `/spv/downtime/${breakdownId}/request-replacement`,
                type: "POST",
                success: function(res) {
                    showNotification('success', res.message || 'Permintaan ganti mesin dikirim.');
                    loadDowntime();
                },
                error: function(err) {
                    showNotification('error', err.responseJSON?.error || 'Gagal mengirim permintaan.');
                }
            });
        });
    }

    $(document).ready(function() {
        loadOperatorTable();
        loadMachines();
        loadDowntime();

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
