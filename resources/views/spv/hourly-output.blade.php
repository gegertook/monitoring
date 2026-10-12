@extends('layouts.app')

@section('title', 'Input Output Per Jam & Defect (SPV)')

@section('content')
<div class="space-y-6">
    <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4 pb-3 border-b border-slate-100">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Pencatatan Output Line Per Jam & Analisis Defect</h2>
                <p class="text-xs text-slate-500">SPV Line mencatat <b>output barang bagus</b> setiap jam kerja (8 Jam). Defect dicatat <b>Admin QC</b> dan hanya tampil sebagai informasi di sini.</p>
            </div>
            <div class="flex items-center gap-2">
                {{-- Fixed line badge --}}
                <span class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-50 border border-indigo-200 text-indigo-700 rounded-xl text-sm font-bold">
                    <i class="fa-solid fa-industry"></i>
                    {{ $line->name }}
                </span>
                <button type="button" id="load_data_btn" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-xl shadow-sm transition text-xs">
                    <i class="fa-solid fa-arrows-rotate"></i>
                    <span>Muat Data</span>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Style Order Aktif di {{ $line->name }}</label>
                <select id="style_order_id" class="w-full py-2.5 px-3 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-white">
                    <option value="">-- Pilih Style Order --</option>
                    @foreach($styleOrders as $order)
                        <option value="{{ $order->id }}" {{ $loop->first ? 'selected' : '' }}>
                            {{ $order->code_style }} ({{ $order->buyer }} - Target: {{ number_format($order->total_qty_target) }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Tanggal Produksi</label>
                <input type="date" id="production_date" value="{{ $date ?? date('Y-m-d') }}" class="w-full py-2 px-3 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-white font-medium">
            </div>
        </div>
    </div>

    <!-- Live Performance Metric Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Total Output Bagus</span>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-2xl font-black text-slate-800" id="actual_output_total">0</span>
                <span class="text-xs text-slate-400 font-semibold">pcs</span>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200">
            <span class="text-xs font-bold text-rose-500 uppercase tracking-wider block">Total Defect (Cacat)</span>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-2xl font-black text-rose-600" id="defect_total">0</span>
                <span class="text-xs text-rose-400 font-semibold">pcs</span>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200">
            <span class="text-xs font-bold text-indigo-500 uppercase tracking-wider block">Defect Rate Rata-rata</span>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="text-2xl font-black text-indigo-600" id="avg_defect_rate">0.00%</span>
            </div>
            <span class="text-[10px] text-slate-400 block mt-0.5">Toleransi pabrik: &lt; 3.0%</span>
        </div>

        <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Status Produksi Line</span>
            <div class="mt-2" id="line_status_container">
                <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-slate-100 text-slate-600" id="line_overall_status">Ready</span>
            </div>
        </div>
    </div>

    <!-- Hourly Table Input Form -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <form id="hourly_output_form">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-slate-600 font-semibold">
                        <tr>
                            <th class="px-5 py-3.5 text-left w-32">Jam Ke</th>
                            <th class="px-5 py-3.5 text-left">Output Barang Bagus (Pcs)</th>
                            <th class="px-5 py-3.5 text-left">Jumlah Cacat / Defect (Pcs) <span class="block text-[10px] font-normal text-slate-400">diisi Admin QC</span></th>
                            <th class="px-5 py-3.5 text-left w-36">Defect Rate (%)</th>
                            <th class="px-5 py-3.5 text-center w-36">Status Kualitas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white" id="hourly_rows">
                        @for ($i = 1; $i <= 8; $i++)
                        <tr class="hover:bg-slate-50 transition" data-hour="{{ $i }}">
                            <td class="px-5 py-3 font-bold text-slate-700">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-md bg-indigo-50 text-indigo-700 flex items-center justify-center font-bold text-xs">
                                        {{ $i }}
                                    </div>
                                    <span>Jam Ke-{{ $i }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-3">
                                <input type="number" name="hours[{{ $i }}][output_qty]" class="output-input w-full max-w-[200px] py-1.5 px-3 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none font-semibold text-slate-800" value="0" min="0">
                            </td>
                            <td class="px-5 py-3">
                                <span class="defect-view text-xs font-semibold text-rose-600 bg-rose-50 border border-rose-100 rounded-lg px-2.5 py-1.5 inline-block" data-value="0">0 pcs (QC)</span>
                            </td>
                            <td class="px-5 py-3 font-mono font-bold text-slate-700 defect-rate-val">
                                0.00%
                            </td>
                            <td class="px-5 py-3 text-center">
                                <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-slate-100 text-slate-600 status-indicator">N/A</span>
                            </td>
                        </tr>
                        @endfor
                    </tbody>
                    <tfoot class="bg-slate-50 font-bold border-t border-slate-200">
                        <tr>
                            <td class="px-5 py-4 text-slate-800 uppercase text-xs">Total Harian</td>
                            <td class="px-5 py-4 text-indigo-700 text-base font-extrabold" id="tfoot_output">0 pcs</td>
                            <td class="px-5 py-4 text-rose-600 text-base font-extrabold" id="tfoot_defect">0 pcs</td>
                            <td class="px-5 py-4 text-slate-800 font-extrabold" id="tfoot_rate">0.00%</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                <span class="text-xs text-slate-500"><i class="fa-solid fa-circle-info mr-1"></i> Data yang tersimpan otomatis masuk antrian validasi Admin Produksi.</span>
                <button type="submit" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 px-6 rounded-xl shadow-sm shadow-emerald-600/20 transition">
                    <i class="fa-solid fa-cloud-arrow-up text-sm"></i>
                    <span>Simpan Output (Save All)</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function calculateRates() {
        let totalOutput = 0;
        let totalDefect = 0;

        $('#hourly_rows tr').each(function() {
            let outVal = parseInt($(this).find('.output-input').val()) || 0;
            let defVal = parseInt($(this).find('.defect-view').data('value')) || 0;
            
            totalOutput += outVal;
            totalDefect += defVal;

            let rate = (outVal + defVal) > 0 ? (defVal / (outVal + defVal) * 100).toFixed(2) : 0.00;
            $(this).find('.defect-rate-val').text(rate + '%');
            
            let statusBadge = $(this).find('.status-indicator');
            statusBadge.removeClass('bg-emerald-100 text-emerald-800 bg-amber-100 text-amber-800 bg-rose-100 text-rose-800 bg-slate-100 text-slate-600');
            
            if(outVal === 0 && defVal === 0) {
                statusBadge.text('Belum Ada').addClass('bg-slate-100 text-slate-600');
            } else if(rate < 3) {
                statusBadge.text('Good (<3%)').addClass('bg-emerald-100 text-emerald-800');
            } else if (rate <= 5) {
                statusBadge.text('Warning (3-5%)').addClass('bg-amber-100 text-amber-800');
            } else {
                statusBadge.text('Critical (>5%)').addClass('bg-rose-100 text-rose-800');
            }
        });

        $('#tfoot_output, #actual_output_total').text(totalOutput.toLocaleString() + ' pcs');
        $('#tfoot_defect, #defect_total').text(totalDefect.toLocaleString() + ' pcs');
        
        let avgRate = (totalOutput + totalDefect) > 0 ? (totalDefect / (totalOutput + totalDefect) * 100).toFixed(2) : 0.00;
        $('#tfoot_rate, #avg_defect_rate').text(avgRate + '%');

        let overallBadge = $('#line_overall_status');
        overallBadge.removeClass('bg-emerald-100 text-emerald-800 bg-amber-100 text-amber-800 bg-rose-100 text-rose-800 bg-slate-100 text-slate-600');
        if(totalOutput === 0) {
            overallBadge.text('Standby').addClass('bg-slate-100 text-slate-600');
        } else if(avgRate < 3) {
            overallBadge.text('Normal / Good').addClass('bg-emerald-100 text-emerald-800');
        } else if(avgRate <= 5) {
            overallBadge.text('Waspada Defect').addClass('bg-amber-100 text-amber-800');
        } else {
            overallBadge.text('Kritis Defect Tinggi').addClass('bg-rose-100 text-rose-800');
        }
    }

    function loadHourlyData() {
        let styleId = $('#style_order_id').val();
        let date = $('#production_date').val();

        if(!styleId) {
            showNotification('warning', 'Pilih Style Order terlebih dahulu!');
            return;
        }

        Swal.fire({
            title: 'Memuat Data...',
            text: 'Mengambil data pencatatan jam kerja...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        $.ajax({
            url: "{{ route('spv.api.hourly-data') }}",
            type: "GET",
            data: {
                style_order_id: styleId,
                date: date
            },
            success: function(response) {
                Swal.close();
                if(response.success && response.hours) {
                    $.each(response.hours, function(hourNum, data) {
                        let row = $(`#hourly_rows tr[data-hour="${hourNum}"]`);
                        let defectQty = data.defect_qty || 0;
                        row.find('.output-input').val(data.output_qty || 0);
                        row.find('.defect-view').text(defectQty + ' pcs (QC)').data('value', defectQty);
                    });
                    calculateRates();
                    showNotification('success', 'Data output berhasil dimuat.');
                }
            },
            error: function() {
                Swal.close();
                showNotification('error', 'Gagal memuat data output per jam.');
            }
        });
    }

    $(document).ready(function() {
        calculateRates();
        loadHourlyData();

        $('.output-input').on('input', calculateRates);

        $('#load_data_btn').click(function() {
            loadHourlyData();
        });

        $('#style_order_id, #production_date').change(function() {
            loadHourlyData();
        });

        $('#hourly_output_form').submit(function(e) {
            e.preventDefault();

            let styleId = $('#style_order_id').val();
            let date = $('#production_date').val();

            if(!styleId) {
                showNotification('warning', 'Pilih Style Order!');
                return;
            }

            let hoursData = {};
            $('#hourly_rows tr').each(function() {
                let h = $(this).data('hour');
                hoursData[h] = {
                    output_qty: $(this).find('.output-input').val()
                };
            });

            Swal.fire({
                title: 'Menyimpan Output...',
                text: 'Menyimpan data 8 jam produksi ke database...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            $.ajax({
                url: "{{ route('spv.hourly-output.store') }}",
                type: "POST",
                data: {
                    style_order_id: styleId,
                    production_date: date,
                    hours: hoursData
                },
                success: function(res) {
                    Swal.fire('Berhasil!', 'Data output per jam telah tersimpan ke database.', 'success');
                    calculateRates();
                },
                error: function(err) {
                    Swal.fire('Gagal!', err.responseJSON?.error || 'Terjadi kesalahan saat menyimpan data.', 'error');
                }
            });
        });
    });
</script>
@endsection

