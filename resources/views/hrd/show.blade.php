@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-800">Detail Karyawan</h1>
        <a href="{{ route('hrd.index') }}" class="text-blue-600 hover:underline">&larr; Kembali</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Profile Card -->
        <div class="bg-white rounded-lg shadow p-6 md:col-span-1 h-fit">
            <div class="flex items-center justify-center mb-4">
                <div class="h-24 w-24 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 text-3xl font-bold">
                    {{ substr($employee->name, 0, 1) }}
                </div>
            </div>
            <h2 class="text-xl font-bold text-center text-gray-800">{{ $employee->name }}</h2>
            <p class="text-center text-gray-500 mb-6">{{ $employee->nik }}</p>
            
            <div class="space-y-3">
                <div class="flex justify-between border-b pb-2">
                    <span class="text-gray-600">Skill Level</span>
                    <span class="font-medium text-gray-900">{{ $employee->skill_level }}</span>
                </div>
                <div class="flex justify-between border-b pb-2">
                    <span class="text-gray-600">Status</span>
                    <span>
                        @if($employee->is_active ?? true)
                            <span class="text-green-600 font-medium">Aktif</span>
                        @else
                            <span class="text-red-600 font-medium">Tidak Aktif</span>
                        @endif
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Current Line</span>
                    <span class="font-medium text-gray-900">{{ $currentLine ? $currentLine->name : 'Unassigned' }}</span>
                </div>
            </div>
            
            <div class="mt-6 text-center">
                <a href="{{ route('hrd.edit', $employee->id) }}" class="inline-block bg-indigo-50 text-indigo-700 px-4 py-2 rounded-md hover:bg-indigo-100 transition w-full text-center">Edit Profil</a>
            </div>
        </div>

        <!-- History -->
        <div class="bg-white rounded-lg shadow p-6 md:col-span-2">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Assignment History</h3>
            
            @if(isset($histories) && count($histories) > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Line</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Shift</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($histories as $history)
                        <tr>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">{{ $history->date }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ $history->line->name ?? '-' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ $history->shift }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-8 text-gray-500 bg-gray-50 rounded">
                Belum ada history plotting untuk karyawan ini.
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
