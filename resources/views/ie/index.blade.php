@extends('layouts.app')

@section('title', 'Breakdown Process (IE)')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Breakdown Process & SMV (IE)</h2>
            <p class="text-sm text-slate-500">Pilih style order untuk memecah proses teknis, menetapkan SMV dan target pcs/jam.</p>
        </div>
        <div class="w-full sm:w-80 relative">
            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3.5 text-slate-400 text-sm"></i>
            <input type="text" id="searchInput" placeholder="Cari Style Order / Buyer..." class="w-full pl-10 pr-4 py-2 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
        </div>
    </div>

    <div id="gridContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($styleOrders as $order)
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 hover:shadow-md hover:border-indigo-200 transition-all p-5 flex flex-col justify-between">
                <div>
                    <div class="flex justify-between items-start mb-3">
                        <span class="text-xs font-bold text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded-lg">
                            {{ $order->productionLine ? $order->productionLine->name : 'Unassigned Line' }}
                        </span>
                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $order->status === 'in_progress' ? 'bg-blue-100 text-blue-800' : 'bg-amber-100 text-amber-800' }}">
                            {{ strtoupper($order->status) }}
                        </span>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800 mb-1">{{ $order->code_style }}</h3>
                    <p class="text-xs text-slate-500 mb-4"><i class="fa-regular fa-building mr-1"></i> {{ $order->buyer }}</p>
                    
                    <div class="grid grid-cols-2 gap-2 text-xs bg-slate-50 p-3 rounded-xl mb-4">
                        <div>
                            <span class="text-slate-400 block">Target Qty</span>
                            <span class="font-bold text-slate-700">{{ number_format($order->total_qty_target) }} pcs</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block">Total Proses</span>
                            <span class="font-bold text-indigo-600">{{ $order->breakdownProcesses->count() }} Proses</span>
                        </div>
                    </div>
                </div>

                <a href="{{ route('ie.breakdown.show', $order->id) }}" class="inline-flex items-center justify-center gap-2 w-full py-2.5 px-4 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition">
                    <span>Buka Breakdown Process</span>
                    <i class="fa-solid fa-arrow-right text-xs"></i>
                </a>
            </div>
        @empty
            <div class="col-span-full py-12 text-center bg-white rounded-2xl border border-slate-200">
                <i class="fa-solid fa-box-open text-4xl text-slate-300 mb-2"></i>
                <p class="text-slate-500 text-sm">Tidak ada Style Order yang tersedia untuk di-breakdown.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection

@section('scripts')
<script>
    let timer;
    $('#searchInput').on('keyup', function() {
        clearTimeout(timer);
        timer = setTimeout(() => {
            let search = $(this).val().toLowerCase();
            $('#gridContainer > div').each(function() {
                let text = $(this).text().toLowerCase();
                $(this).toggle(text.indexOf(search) > -1);
            });
        }, 200);
    });
</script>
@endsection
