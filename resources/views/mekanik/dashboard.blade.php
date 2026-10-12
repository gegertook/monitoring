@extends('layouts.app')

@section('title', 'Dashboard Admin Mekanik')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Dashboard Admin Mekanik</h2>
            <p class="text-sm text-slate-500">Kelola master mesin, plotting mesin ke line, dan penanganan downtime produksi.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('mekanik.machines.index') }}" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-xl shadow-sm transition text-xs">
                <i class="fa-solid fa-gears"></i> <span>Master Mesin</span>
            </a>
            <a href="{{ route('mekanik.downtime.index') }}" class="inline-flex items-center gap-2 bg-rose-600 hover:bg-rose-700 text-white font-semibold py-2 px-4 rounded-xl shadow-sm transition text-xs">
                <i class="fa-solid fa-triangle-exclamation"></i> <span>Papan Downtime</span>
            </a>
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl shadow-sm border-l-4 border-indigo-500 border border-slate-200 p-4">
            <p class="text-xs text-slate-400 uppercase font-bold tracking-wider">Total Mesin</p>
            <p class="text-2xl font-black text-indigo-600 mt-1">{{ $stats['total'] }}</p>
            <span class="text-[10px] text-slate-400">unit terdaftar</span>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border-l-4 border-emerald-500 border border-slate-200 p-4">
            <p class="text-xs text-slate-400 uppercase font-bold tracking-wider">Dipakai Line</p>
            <p class="text-2xl font-black text-emerald-600 mt-1">{{ $stats['dipakai'] }}</p>
            <span class="text-[10px] text-slate-400">terplot ke line</span>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border-l-4 border-slate-400 border border-slate-200 p-4">
            <p class="text-xs text-slate-400 uppercase font-bold tracking-wider">Ready (Pool)</p>
            <p class="text-2xl font-black text-slate-600 mt-1">{{ $stats['ready'] }}</p>
            <span class="text-[10px] text-slate-400">siap dipasang</span>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border-l-4 border-rose-500 border border-slate-200 p-4">
            <p class="text-xs text-slate-400 uppercase font-bold tracking-wider">Sedang Rusak</p>
            <p class="text-2xl font-black text-rose-600 mt-1">{{ $stats['rusak'] }}</p>
            <span class="text-[10px] text-slate-400">downtime aktif</span>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex justify-between items-center">
            <h3 class="text-base font-bold text-slate-800">Downtime Aktif</h3>
            <a href="{{ route('mekanik.downtime.index') }}" class="text-xs font-semibold text-indigo-600 hover:underline">Lihat semua</a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-slate-600 font-semibold">
                    <tr>
                        <th class="px-5 py-3.5 text-left">Mesin</th>
                        <th class="px-5 py-3.5 text-left">Line</th>
                        <th class="px-5 py-3.5 text-left">Status</th>
                        <th class="px-5 py-3.5 text-left">Keluhan</th>
                        <th class="px-5 py-3.5 text-left">Sejak Laporan</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-100">
                    @forelse($openBreakdowns as $b)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-5 py-3.5">
                                <span class="font-bold text-slate-800 block">{{ $b->machine->code ?? '-' }}</span>
                                <span class="text-xs text-slate-400">{{ $b->machine->name ?? '-' }}</span>
                            </td>
                            <td class="px-5 py-3.5 font-semibold text-slate-700">{{ $b->productionLine->name ?? '-' }}</td>
                            <td class="px-5 py-3.5">{!! \App\Http\Controllers\MekanikController::statusBadge($b->status) !!}</td>
                            <td class="px-5 py-3.5 text-xs text-slate-500">{{ $b->notes ?: '-' }}</td>
                            <td class="px-5 py-3.5 text-xs font-semibold {{ $b->minutesSinceReported() >= 60 ? 'text-rose-600' : 'text-slate-500' }}">
                                {{ $b->minutesSinceReported() }} menit
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-slate-400">Tidak ada downtime aktif. Semua mesin beroperasi normal.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
        <div class="flex justify-between items-center mb-4 pb-3 border-b border-slate-100">
            <h3 class="text-base font-bold text-slate-800">Status Mesin</h3>
            <a href="{{ route('mekanik.machines.index') }}" class="text-xs font-semibold text-indigo-600 hover:underline">Kelola mesin</a>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
            @forelse($machines as $machine)
                <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50 hover:bg-white hover:shadow-xs transition flex justify-between items-center">
                    <div>
                        <p class="text-xs font-bold text-slate-800">{{ $machine->code }} &mdash; {{ $machine->name }}</p>
                        <p class="text-[10px] text-slate-400">{{ $machine->currentLine->name ?? 'Belum di line (pool)' }}</p>
                    </div>
                    {!! \App\Http\Controllers\MekanikController::statusBadge($machine->status) !!}
                </div>
            @empty
                <div class="col-span-full py-8 text-center text-sm text-slate-400 border border-dashed border-slate-200 rounded-xl">
                    Belum ada mesin terdaftar. Tambahkan lewat menu <b>Master Mesin</b>.
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
