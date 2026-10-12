@extends('layouts.app')

@section('title', 'Edit Karyawan')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 md:p-8">
        <div class="mb-6 pb-4 border-b border-slate-100">
            <h2 class="text-xl font-bold text-slate-800">Edit Karyawan: {{ $employee->name }}</h2>
            <p class="text-xs text-slate-500">Perbarui profil, skill level, dan status keaktifan operator.</p>
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

        <form action="{{ route('hrd.employees.update', $employee->id) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            
            <div>
                <label for="nik" class="block text-xs font-bold uppercase text-slate-600 mb-1.5">NIK</label>
                <input type="text" name="nik" id="nik" value="{{ old('nik', $employee->nik) }}" class="w-full py-2.5 px-3.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none" required>
            </div>

            <div>
                <label for="name" class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Nama Lengkap</label>
                <input type="text" name="name" id="name" value="{{ old('name', $employee->name) }}" class="w-full py-2.5 px-3.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none" required>
            </div>

            <div>
                <label for="category" class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Kategori Karyawan</label>
                <select name="category" id="category" class="w-full py-2.5 px-3.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-white">
                    <option value="operator" {{ old('category', $employee->category) == 'operator' ? 'selected' : '' }}>Operator (bisa diplot ke line)</option>
                    <option value="mekanik" {{ old('category', $employee->category) == 'mekanik' ? 'selected' : '' }}>Mekanik (tidak bisa diplot ke line)</option>
                </select>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="skill_level" class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Skill Level</label>
                    <select name="skill_level" id="skill_level" class="w-full py-2.5 px-3.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-white" required>
                        <option value="junior" {{ old('skill_level', $employee->skill_level) == 'junior' ? 'selected' : '' }}>Junior</option>
                        <option value="intermediate" {{ old('skill_level', $employee->skill_level) == 'intermediate' ? 'selected' : '' }}>Intermediate</option>
                        <option value="senior" {{ old('skill_level', $employee->skill_level) == 'senior' ? 'selected' : '' }}>Senior</option>
                    </select>
                </div>

                <div>
                    <label for="is_active" class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Status Keaktifan</label>
                    <select name="is_active" id="is_active" class="w-full py-2.5 px-3.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-white">
                        <option value="1" {{ old('is_active', $employee->is_active) ? 'selected' : '' }}>Aktif</option>
                        <option value="0" {{ !old('is_active', $employee->is_active) ? 'selected' : '' }}>Non-Aktif</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <a href="{{ route('hrd.employees.index') }}" class="px-5 py-2.5 text-sm font-semibold rounded-xl text-slate-600 bg-slate-100 hover:bg-slate-200 transition">Batal</a>
                <button type="submit" class="px-5 py-2.5 text-sm font-semibold rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm shadow-indigo-600/20 transition">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        function toggleSkillRequirement() {
            let isMekanik = $('#category').val() === 'mekanik';
            $('#skill_level').prop('required', !isMekanik);
        }

        $('#category').change(toggleSkillRequirement);
        toggleSkillRequirement();
    });
</script>
@endsection
