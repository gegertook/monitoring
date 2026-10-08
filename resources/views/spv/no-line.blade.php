@extends('layouts.app')

@section('title', 'Line Belum Ditetapkan')

@section('content')
<div class="flex flex-col items-center justify-center min-h-[60vh] text-center px-4">
    <div class="w-20 h-20 rounded-full bg-amber-100 border-4 border-amber-200 flex items-center justify-center mb-6">
        <i class="fa-solid fa-triangle-exclamation text-amber-500 text-3xl"></i>
    </div>
    <h1 class="text-2xl font-bold text-slate-900 mb-2">Line Produksi Belum Ditetapkan</h1>
    <p class="text-slate-500 max-w-md mb-4">
        Akun SPV Anda belum dihubungkan ke <strong>Line Produksi</strong> manapun.
        Silakan hubungi <strong>HRD</strong> untuk menetapkan line yang sesuai ke akun Anda.
    </p>
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 max-w-sm w-full text-left text-sm text-amber-800">
        <p class="font-semibold mb-1"><i class="fa-solid fa-circle-info mr-1"></i> Yang perlu dilakukan:</p>
        <ul class="list-disc list-inside space-y-1 text-amber-700">
            <li>Login sebagai HRD</li>
            <li>Buka menu <strong>Akun Staff</strong></li>
            <li>Edit akun SPV ini, pilih <strong>Line Tugas</strong> yang sesuai</li>
            <li>Simpan perubahan dan login ulang</li>
        </ul>
    </div>
</div>
@endsection
