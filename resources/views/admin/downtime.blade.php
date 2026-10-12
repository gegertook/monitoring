@extends('layouts.app')

@section('title', 'Downtime Mesin & Pengiriman Mekanik (Admin Produksi)')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Downtime Mesin &amp; Pengiriman Mekanik</h2>
            <p class="text-sm text-slate-500">Kirim mekanik ke line yang melaporkan mesin rusak. Setelah lebih dari 1 jam, SPV dapat meminta penggantian mesin.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.validation.index') }}" class="inline-flex items-center gap-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold py-2 px-4 rounded-xl transition text-xs">
                <i class="fa-solid fa-check-double"></i> <span>Validasi Input SPV</span>
            </a>
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl shadow-sm border-l-4 border-rose-500 border border-slate-200 p-4">
            <p class="text-xs text-slate-400 uppercase font-bold tracking-wider">Downtime Aktif</p>
            <p class="text-2xl font-black text-rose-600 mt-1">{{ $stats['open'] }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border-l-4 border-amber-500 border border-slate-200 p-4">
            <p class="text-xs text-slate-400 uppercase font-bold tracking-wider">Belum Kirim Mekanik</p>
            <p class="text-2xl font-black text-amber-600 mt-1">{{ $stats['waiting_mechanic'] }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border-l-4 border-purple-500 border border-slate-200 p-4">
            <p class="text-xs text-slate-400 uppercase font-bold tracking-wider">Minta Ganti Mesin</p>
            <p class="text-2xl font-black text-purple-600 mt-1">{{ $stats['replacement_requested'] }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border-l-4 border-emerald-500 border border-slate-200 p-4">
            <p class="text-xs text-slate-400 uppercase font-bold tracking-wider">Selesai Hari Ini</p>
            <p class="text-2xl font-black text-emerald-600 mt-1">{{ $stats['resolved_today'] }}</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Status</label>
                <select id="filter_status" class="w-full py-2 px-3 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-white">
                    <option value="">Semua status</option>
                    <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Aktif (belum selesai)</option>
                    <option value="reported" {{ request('status') === 'reported' ? 'selected' : '' }}>Dilaporkan</option>
                    <option value="mechanic_sent" {{ request('status') === 'mechanic_sent' ? 'selected' : '' }}>Mekanik Dikirim</option>
                    <option value="replacement_requested" {{ request('status') === 'replacement_requested' ? 'selected' : '' }}>Minta Ganti Mesin</option>
                    <option value="repaired" {{ request('status') === 'repaired' ? 'selected' : '' }}>Diperbaiki</option>
                    <option value="replaced" {{ request('status') === 'replaced' ? 'selected' : '' }}>Diganti</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Line</label>
                <select id="filter_line" class="w-full py-2 px-3 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-white">
                    <option value="">Semua line</option>
                    @foreach($lines as $line)
                        <option value="{{ $line->id }}" {{ request('line_id') == $line->id ? 'selected' : '' }}>{{ $line->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button id="btn_reload" class="w-full inline-flex items-center justify-center gap-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold py-2 px-4 rounded-xl transition text-xs">
                    <i class="fa-solid fa-arrows-rotate"></i> <span>Muat Ulang</span>
                </button>
            </div>
        </div>
    </div>

    @if($mechanics->isEmpty())
        <div class="bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-2xl p-4">
            <i class="fa-solid fa-triangle-exclamation mr-1"></i>
            Belum ada akun <b>Admin Mekanik</b> yang aktif. Buat dulu lewat menu HRD &rarr; Akun Staff &amp; Supervisor (role: Admin Mekanik).
        </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-slate-600 font-semibold">
                    <tr>
                        <th class="px-5 py-3.5 text-left">Waktu Lapor</th>
                        <th class="px-5 py-3.5 text-left">Mesin</th>
                        <th class="px-5 py-3.5 text-left">Line</th>
                        <th class="px-5 py-3.5 text-left">Keluhan</th>
                        <th class="px-5 py-3.5 text-left">Status</th>
                        <th class="px-5 py-3.5 text-left w-56">Kirim Mekanik</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-100">
                    @forelse($breakdowns as $b)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-5 py-3.5 text-xs text-slate-500">{{ $b->reported_at->format('d M Y H:i') }}</td>
                            <td class="px-5 py-3.5">
                                <span class="font-bold text-slate-800 block">{{ $b->machine->code ?? '-' }}</span>
                                <span class="text-xs text-slate-400">{{ $b->machine->name ?? '-' }}</span>
                            </td>
                            <td class="px-5 py-3.5 font-semibold text-slate-700">{{ $b->productionLine->name ?? '-' }}</td>
                            <td class="px-5 py-3.5 text-xs text-slate-500">{{ $b->notes ?: '-' }}</td>
                            <td class="px-5 py-3.5">
                                {!! \App\Http\Controllers\MekanikController::statusBadge($b->status) !!}
                                @if($b->status === 'replacement_requested')
                                    <span class="block text-[10px] text-purple-600 font-semibold mt-1">SPV minta ganti mesin</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5">
                                @if($b->status === 'reported')
                                    <select class="mechanic-select w-full py-1.5 px-2 text-xs border border-slate-200 rounded-xl bg-white focus:ring-2 focus:ring-indigo-500" data-breakdown-id="{{ $b->id }}">
                                        <option value="">-- Pilih mekanik --</option>
                                        @foreach($mechanics as $m)
                                            <option value="{{ $m->id }}">{{ $m->name }}</option>
                                        @endforeach
                                    </select>
                                @elseif($b->mechanic)
                                    <span class="text-xs font-semibold text-slate-700">{{ $b->mechanic->name }}</span>
                                    <span class="block text-[10px] text-slate-400">dikirim {{ $b->mechanic_sent_at?->format('H:i') ?? '-' }}</span>
                                @else
                                    <span class="text-xs text-slate-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-slate-400">Tidak ada laporan downtime untuk filter ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function dispatchMechanic(breakdownId, mechanicId) {
        $.ajax({
            url: `/admin-produksi/downtime/${breakdownId}/dispatch`,
            type: "POST",
            data: { mechanic_user_id: mechanicId },
            success: function(res) {
                showNotification('success', res.message || 'Mekanik dikirim.');
                setTimeout(() => window.location.reload(), 700);
            },
            error: function(err) {
                showNotification('error', err.responseJSON?.error || 'Gagal mengirim mekanik.');
            }
        });
    }

    $(document).ready(function() {
        $('#btn_reload').click(() => window.location.reload());

        $(document).on('change', '.mechanic-select', function() {
            let id = $(this).data('breakdown-id');
            let mechanicId = $(this).val();
            if (mechanicId) {
                dispatchMechanic(id, mechanicId);
            }
        });

        function applyFilters() {
            let params = new URLSearchParams();
            if ($('#filter_status').val()) params.set('status', $('#filter_status').val());
            if ($('#filter_line').val()) params.set('line_id', $('#filter_line').val());
            let qs = params.toString();
            window.location.href = "{{ route('admin.downtime.index') }}" + (qs ? '?' + qs : '');
        }

        $('#filter_status').change(applyFilters);
        $('#filter_line').change(applyFilters);
    });
</script>
@endsection
