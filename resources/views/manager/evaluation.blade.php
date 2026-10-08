@extends('layouts.app')

@section('title', 'Evaluasi Kinerja Line Produksi (Manager)')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Evaluasi & Pengambilan Keputusan Kinerja Line</h2>
            <p class="text-sm text-slate-500">Berdasarkan data output, efisiensi, dan defect rate, Manager menentukan status "Hasil Baik" atau "Hasil Tidak Sesuai" (Feedback Loop ke SPV/IE).</p>
        </div>
    </div>

    <!-- Line & Style Selection Section -->
    <div class="bg-white rounded-2xl shadow-sm p-5 border border-slate-200">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Production Line</label>
                <select id="eval_line_id" class="w-full py-2.5 px-3 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-white">
                    <option value="">-- Pilih Production Line --</option>
                    @foreach($lines as $line)
                        <option value="{{ $line->id }}" {{ $loop->first ? 'selected' : '' }}>{{ $line->name }} (Kapasitas: {{ $line->capacity }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Style Order</label>
                <select id="eval_style_id" class="w-full py-2.5 px-3 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-white">
                    <option value="">-- Pilih Style Order --</option>
                    @foreach($styleOrders as $so)
                        <option value="{{ $so->id }}" {{ $loop->first ? 'selected' : '' }}>{{ $so->code_style }} ({{ $so->buyer }})</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Evaluation Decision Form -->
        <div class="lg:col-span-5 bg-white rounded-2xl shadow-sm p-6 border border-slate-200">
            <h3 class="text-base font-bold text-slate-800 mb-4 pb-2 border-b border-slate-100">Form Keputusan Evaluasi Manager</h3>
            
            <form id="evaluation_form" class="space-y-5">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-2">Hasil Evaluasi Kinerja Line</label>
                    <div class="space-y-3">
                        <label class="flex items-center p-3.5 border-2 rounded-xl cursor-pointer hover:bg-emerald-50/50 transition border-slate-200 has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50/30">
                            <input type="radio" name="result" value="good" class="h-4 w-4 text-emerald-600 focus:ring-emerald-500" checked>
                            <div class="ml-3">
                                <span class="block text-sm font-bold text-slate-800">Hasil Baik (Completed / On Track)</span>
                                <span class="block text-xs text-slate-500">Output, efisiensi & kualitas memenuhi standar pabrik.</span>
                            </div>
                        </label>

                        <label class="flex items-center p-3.5 border-2 rounded-xl cursor-pointer hover:bg-rose-50/50 transition border-slate-200 has-[:checked]:border-rose-500 has-[:checked]:bg-rose-50/30">
                            <input type="radio" name="result" value="not_good" class="h-4 w-4 text-rose-600 focus:ring-rose-500">
                            <div class="ml-3">
                                <span class="block text-sm font-bold text-rose-700">Hasil Tidak Sesuai (Alert Feedback Loop)</span>
                                <span class="block text-xs text-slate-500">Sistem otomatis mengirim notifikasi peringatan ke SPV Line & IE.</span>
                            </div>
                        </label>
                    </div>
                </div>
                
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1.5">
                        Catatan Evaluasi / Instruksi Koreksi <span class="text-rose-500 hidden" id="notes_req">*</span>
                    </label>
                    <textarea id="eval_notes" name="notes" rows="4" class="w-full py-2.5 px-3.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none" placeholder="Tulis catatan evaluasi atau tindakan koreksi yang wajib dilakukan SPV..."></textarea>
                </div>

                <button type="submit" class="w-full inline-flex items-center justify-center gap-2 bg-slate-900 hover:bg-indigo-600 text-white font-bold py-3 px-4 rounded-xl shadow-sm transition">
                    <i class="fa-solid fa-paper-plane text-sm"></i>
                    <span>Simpan Keputusan Evaluasi</span>
                </button>
            </form>
        </div>

        <!-- History Table -->
        <div class="lg:col-span-7 bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden p-6">
            <h3 class="text-base font-bold text-slate-800 mb-4 pb-2 border-b border-slate-100">Riwayat Evaluasi Line Produksi</h3>
            
            <div class="overflow-x-auto rounded-xl border border-slate-100">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-slate-600 font-semibold">
                        <tr>
                            <th class="px-4 py-3 text-left">Tanggal</th>
                            <th class="px-4 py-3 text-left">Line & Style</th>
                            <th class="px-4 py-3 text-center">Keputusan</th>
                            <th class="px-4 py-3 text-left">Catatan Manager</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white" id="eval_history_body">
                        @forelse($evaluations as $eval)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-4 py-3 text-xs text-slate-500 whitespace-nowrap">
                                    {{ $eval->evaluation_date ? $eval->evaluation_date->format('d M Y') : $eval->created_at->format('d M Y') }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="font-bold text-slate-800 text-xs block">{{ $eval->productionLine->name ?? '-' }}</span>
                                    <span class="text-[11px] text-indigo-600">{{ $eval->styleOrder->code_style ?? '-' }}</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($eval->result === 'good')
                                        <span class="px-2.5 py-0.5 text-xs font-bold rounded-full bg-emerald-100 text-emerald-800">
                                            <i class="fa-solid fa-check text-[10px] mr-1"></i>Baik
                                        </span>
                                    @else
                                        <span class="px-2.5 py-0.5 text-xs font-bold rounded-full bg-rose-100 text-rose-800">
                                            <i class="fa-solid fa-triangle-exclamation text-[10px] mr-1"></i>Tidak Sesuai
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-600">
                                    {{ $eval->notes ?? '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-slate-400 text-xs">Belum ada riwayat evaluasi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $evaluations->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $('input[name="result"]').change(function() {
        if($(this).val() === 'not_good') {
            $('#notes_req').show();
            $('#eval_notes').prop('required', true).attr('placeholder', 'Wajib diisi: Berikan instruksi revisi pengerjaan ke Supervisor Line...');
        } else {
            $('#notes_req').hide();
            $('#eval_notes').prop('required', false).attr('placeholder', 'Catatan evaluasi...');
        }
    });

    $('#evaluation_form').submit(function(e) {
        e.preventDefault();
        
        let lineId = $('#eval_line_id').val();
        let styleId = $('#eval_style_id').val();
        let result = $('input[name="result"]:checked').val();
        let notes = $('#eval_notes').val();

        if(!lineId || !styleId) {
            showNotification('warning', 'Pilih Production Line dan Style Order terlebih dahulu!');
            return;
        }

        if(result === 'not_good' && !notes.trim()) {
            showNotification('warning', 'Catatan instruksi perbaikan wajib diisi jika hasil tidak sesuai!');
            return;
        }

        let confirmTitle = result === 'not_good' ? 'Kirim Peringatan ke SPV Line?' : 'Simpan Evaluasi Hasil Baik?';
        let confirmText = result === 'not_good' 
            ? 'Keputusan Hasil Tidak Sesuai akan langsung mengirimkan ALERT ke notifikasi Supervisor Line dan IE untuk perbaikan.'
            : 'Kinerja line dinyatakan memenuhi standar dan selesai.';

        Swal.fire({
            title: confirmTitle,
            text: confirmText,
            icon: result === 'not_good' ? 'warning' : 'question',
            showCancelButton: true,
            confirmButtonColor: result === 'not_good' ? '#e11d48' : '#059669',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Simpan Keputusan',
            cancelButtonText: 'Batal'
        }).then((res) => {
            if (res.isConfirmed) {
                $.ajax({
                    url: "{{ route('manager.evaluation.store') }}",
                    type: "POST",
                    data: {
                        production_line_id: lineId,
                        style_order_id: styleId,
                        result: result,
                        notes: notes
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Evaluasi Tersimpan!',
                            text: response.message,
                            confirmButtonColor: '#4f46e5'
                        }).then(() => {
                            window.location.reload();
                        });
                    },
                    error: function(err) {
                        Swal.fire('Gagal!', err.responseJSON?.error || 'Gagal menyimpan evaluasi.', 'error');
                    }
                });
            }
        });
    });
</script>
@endsection
