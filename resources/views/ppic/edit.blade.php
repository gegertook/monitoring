@extends('layouts.app')

@section('title', 'Edit Style Order')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 md:p-8">
        <div class="mb-6 pb-4 border-b border-slate-100">
            <h2 class="text-xl font-bold text-slate-800">Edit Style Order: {{ $styleOrder->code_style }}</h2>
            <p class="text-xs text-slate-500">Perbarui informasi style order dan target produksi.</p>
        </div>

        @if($errors->any())
            <div class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-xl mb-6 text-sm">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('ppic.style-orders.update', $styleOrder->id) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            
            <div>
                <label for="code_style" class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Code Style</label>
                <input type="text" name="code_style" id="code_style" value="{{ old('code_style', $styleOrder->code_style) }}" class="w-full py-2.5 px-3.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none" required>
            </div>

            <div>
                <label for="buyer" class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Nama Buyer</label>
                <input type="text" name="buyer" id="buyer" value="{{ old('buyer', $styleOrder->buyer) }}" class="w-full py-2.5 px-3.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none" required>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="total_qty_target" class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Total Qty Target (Pcs)</label>
                    <input type="number" name="total_qty_target" id="total_qty_target" value="{{ old('total_qty_target', $styleOrder->total_qty_target) }}" class="w-full py-2.5 px-3.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none" required min="1">
                </div>

                <div>
                    <label for="deadline" class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Deadline</label>
                    <input type="date" name="deadline" id="deadline" value="{{ old('deadline', $styleOrder->deadline ? $styleOrder->deadline->format('Y-m-d') : '') }}" class="w-full py-2.5 px-3.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none" required>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="assigned_line_id" class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Production Line Tujuan</label>
                    <select name="assigned_line_id" id="assigned_line_id" class="w-full py-2.5 px-3.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-white">
                        <option value="">-- Belum Ditugaskan --</option>
                        @foreach($lines as $line)
                            <option value="{{ $line->id }}" {{ old('assigned_line_id', $styleOrder->assigned_line_id) == $line->id ? 'selected' : '' }}>
                                {{ $line->name }} (Kapasitas: {{ $line->capacity }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="status" class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Status Order</label>
                    <select name="status" id="status" class="w-full py-2.5 px-3.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-white">
                        <option value="pending" {{ old('status', $styleOrder->status) == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="in_progress" {{ old('status', $styleOrder->status) == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="completed" {{ old('status', $styleOrder->status) == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ old('status', $styleOrder->status) == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <a href="{{ route('ppic.style-orders.index') }}" class="px-5 py-2.5 text-sm font-semibold rounded-xl text-slate-600 bg-slate-100 hover:bg-slate-200 transition">Batal</a>
                <button type="submit" class="px-5 py-2.5 text-sm font-semibold rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm shadow-indigo-600/20 transition">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
@endsection
