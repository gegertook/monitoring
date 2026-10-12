<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'MLMPS') - Manufacturing Line Monitoring & Employee Performance</title>

    <!-- Tailwind CSS (Play CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        indigo: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            200: '#c7d2fe',
                            300: '#a5b4fc',
                            400: '#818cf8',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            800: '#3730a3',
                            900: '#312e81',
                            950: '#1e1b4b',
                        }
                    }
                }
            }
        }
    </script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- SweetAlert2 -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css" rel="stylesheet">

    @yield('styles')
</head>
<body class="bg-slate-50 font-sans antialiased text-gray-800">

    <div class="flex h-screen overflow-hidden">
        
        <!-- Mobile sidebar backdrop -->
        <div id="sidebarBackdrop" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-20 lg:hidden hidden" onclick="toggleSidebar()"></div>

        <!-- Sidebar -->
        <aside id="sidebar" class="bg-slate-900 text-white w-64 flex-shrink-0 flex flex-col transition-transform duration-300 ease-in-out transform -translate-x-full lg:translate-x-0 fixed lg:relative z-30 h-full">
            
            <!-- Sidebar Header -->
            <div class="flex items-center justify-between px-5 h-16 bg-slate-950 border-b border-slate-800">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-lg bg-indigo-600 flex items-center justify-center text-white shadow-md shadow-indigo-500/30">
                        <i class="fa-solid fa-industry text-lg"></i>
                    </div>
                    <div>
                        <span class="text-lg font-black tracking-wider text-white">MLMPS</span>
                        <span class="block text-[10px] text-indigo-400 font-semibold uppercase -mt-1 tracking-widest">Manufacturing</span>
                    </div>
                </div>
            </div>

            <!-- Sidebar Navigation -->
            <nav class="flex-1 overflow-y-auto py-4">
                <ul class="space-y-1 px-3">
                    <li>
                        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                            <i class="fa-solid fa-gauge-high w-5 text-center"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>

                    @if(auth()->check())
                        @php
                            $role = auth()->user()->role;
                        @endphp
                        
                        {{-- PPIC Role --}}
                        @if($role === 'ppic')
                            <li class="pt-4 pb-1"><span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider px-3.5">PPIC Planning</span></li>
                            <li>
                                <a href="{{ route('ppic.style-orders.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('ppic.style-orders.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                    <i class="fa-solid fa-file-invoice w-5 text-center"></i>
                                    <span>Style Orders & Line</span>
                                </a>
                            </li>
                        @endif

                        {{-- IE Role --}}
                        @if($role === 'ie')
                            <li class="pt-4 pb-1"><span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider px-3.5">Industrial Engineering</span></li>
                            <li>
                                <a href="{{ route('ie.breakdown.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('ie.breakdown.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                    <i class="fa-solid fa-network-wired w-5 text-center"></i>
                                    <span>Breakdown & SMV</span>
                                </a>
                            </li>
                        @endif

                        {{-- HRD Role --}}
                        @if($role === 'hrd')
                            <li class="pt-4 pb-1"><span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider px-3.5">Human Resources</span></li>
                            <li>
                                <a href="{{ route('hrd.employees.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('hrd.employees.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                    <i class="fa-solid fa-users w-5 text-center"></i>
                                    <span>Master Karyawan</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('hrd.plotting.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('hrd.plotting.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                    <i class="fa-solid fa-user-tag w-5 text-center"></i>
                                    <span>Plotting Karyawan Line</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('hrd.users.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('hrd.users.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                    <i class="fa-solid fa-users-gear w-5 text-center"></i>
                                    <span>Akun Staff & Supervisor</span>
                                </a>
                            </li>
                        @endif

                        {{-- SPV Role --}}
                        @if($role === 'spv')
                            <li class="pt-4 pb-1">
                                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider px-3.5">Supervisor Line</span>
                                @if(auth()->user()->assignedLine)
                                    <span class="block text-[10px] font-semibold text-indigo-400 px-3.5 mt-0.5"><i class="fa-solid fa-industry mr-1"></i>{{ auth()->user()->assignedLine->name }}</span>
                                @else
                                    <span class="block text-[10px] font-semibold text-rose-400 px-3.5 mt-0.5"><i class="fa-solid fa-triangle-exclamation mr-1"></i>Belum ada line</span>
                                @endif
                            </li>
                            <li>
                                <a href="{{ route('spv.task-assignment.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('spv.task-assignment.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                    <i class="fa-solid fa-clipboard-list w-5 text-center"></i>
                                    <span>Assignment Operator</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('spv.hourly-output.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('spv.hourly-output.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                    <i class="fa-solid fa-clock w-5 text-center"></i>
                                    <span>Output Per Jam Line</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('spv.operator-output.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('spv.operator-output.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                    <i class="fa-solid fa-user-check w-5 text-center"></i>
                                    <span>Output Individu &amp; Mesin</span>
                                </a>
                            </li>
                        @endif

                        {{-- Admin Mekanik Role --}}
                        @if($role === 'admin_mekanik')
                            <li class="pt-4 pb-1"><span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider px-3.5">Admin Mekanik</span></li>
                            <li>
                                <a href="{{ route('mekanik.dashboard') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('mekanik.dashboard') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                    <i class="fa-solid fa-gauge-high w-5 text-center"></i>
                                    <span>Dashboard Mekanik</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('mekanik.machines.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('mekanik.machines.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                    <i class="fa-solid fa-gears w-5 text-center"></i>
                                    <span>Master Mesin & Line</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('mekanik.downtime.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('mekanik.downtime.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                    <i class="fa-solid fa-triangle-exclamation w-5 text-center"></i>
                                    <span>Papan Downtime</span>
                                </a>
                            </li>
                        @endif

                        {{-- Admin Produksi Role --}}
                        @if($role === 'admin_produksi')
                            <li class="pt-4 pb-1"><span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider px-3.5">Admin Produksi</span></li>
                            <li>
                                <a href="{{ route('admin.validation.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('admin.validation.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                    <i class="fa-solid fa-check-double w-5 text-center"></i>
                                    <span>Validasi Input SPV</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.downtime.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('admin.downtime.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                    <i class="fa-solid fa-screwdriver-wrench w-5 text-center"></i>
                                    <span>Downtime & Kirim Mekanik</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.reports.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('admin.reports.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                    <i class="fa-solid fa-file-contract w-5 text-center"></i>
                                    <span>Laporan & Export</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('admin.defects.recap') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('admin.defects.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                    <i class="fa-solid fa-chart-pie w-5 text-center"></i>
                                    <span>Rekap Defect QC</span>
                                </a>
                            </li>
                        @endif

                        {{-- Manager Role --}}
                        @if($role === 'manager')
                            <li class="pt-4 pb-1"><span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider px-3.5">Executive Management</span></li>
                            <li>
                                <a href="{{ route('manager.dashboard') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('manager.dashboard') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                    <i class="fa-solid fa-chart-line w-5 text-center"></i>
                                    <span>Executive Dashboard</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('manager.lines.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('manager.lines.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                    <i class="fa-solid fa-industry w-5 text-center"></i>
                                    <span>Kelola Line Produksi</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('manager.evaluation.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('manager.evaluation.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                    <i class="fa-solid fa-magnifying-glass-chart w-5 text-center"></i>
                                    <span>Evaluasi Line & Alert</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('manager.defects.recap') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('manager.defects.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                    <i class="fa-solid fa-chart-pie w-5 text-center"></i>
                                    <span>Rekap Defect QC</span>
                                </a>
                            </li>
                        @endif

                        {{-- Admin QC Role --}}
                        @if($role === 'admin_qc')
                            <li class="pt-4 pb-1"><span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider px-3.5">Quality Control (QC)</span></li>
                            <li>
                                <a href="{{ route('qc.defects.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('qc.defects.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                    <i class="fa-solid fa-microscope w-5 text-center"></i>
                                    <span>Input Defect Line</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('qc.recap.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('qc.recap.*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                                    <i class="fa-solid fa-chart-pie w-5 text-center"></i>
                                    <span>Rekap Defect Keseluruhan</span>
                                </a>
                            </li>
                        @endif
                    @endif
                </ul>
            </nav>

            <!-- User Info (Bottom) -->
            <div class="p-4 bg-slate-950 border-t border-slate-800">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-indigo-600 to-indigo-400 flex items-center justify-center text-white font-bold shadow">
                        {{ substr(auth()->user()->name ?? 'U', 0, 1) }}
                    </div>
                    <div class="overflow-hidden">
                        <p class="text-sm font-semibold text-white truncate">{{ auth()->user()->name ?? 'Pengguna' }}</p>
                        <span class="inline-block px-2 py-0.5 mt-0.5 text-[10px] font-bold uppercase tracking-wider text-indigo-200 bg-indigo-900/80 rounded-full">
                            {{ str_replace('_', ' ', auth()->user()->role ?? 'Role') }}
                        </span>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center justify-center gap-2 py-2 px-3 text-xs font-semibold text-slate-300 bg-slate-800 hover:bg-rose-600 hover:text-white rounded-lg transition-colors duration-200">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        <span>Keluar (Logout)</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content Wrapper -->
        <div class="flex-1 flex flex-col overflow-hidden">
            
            <!-- Top Header -->
            <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-4 sm:px-6 lg:px-8 z-10 shadow-xs">
                
                <!-- Left: Mobile toggle & Title -->
                <div class="flex items-center gap-4">
                    <button onclick="toggleSidebar()" class="lg:hidden text-gray-500 hover:text-gray-700 focus:outline-none">
                        <i class="fa-solid fa-bars text-xl"></i>
                    </button>
                    <div>
                        <h1 class="text-lg font-bold text-gray-800 hidden sm:block">@yield('title')</h1>
                    </div>
                </div>

                <!-- Right: Notifications & Quick Switch -->
                <div class="flex items-center gap-5">
                    
                    <!-- Notification Bell -->
                    <div class="relative" id="notificationDropdownContainer">
                        <button id="notificationBtn" class="text-gray-500 hover:text-indigo-600 focus:outline-none transition-colors relative p-2 rounded-full hover:bg-gray-100">
                            <i class="fa-regular fa-bell text-xl"></i>
                            <span id="unreadCountBadge" class="absolute top-1 right-1 bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.2 rounded-full min-w-[16px] text-center hidden">0</span>
                        </button>
                        
                        <!-- Dropdown panel -->
                        <div id="notificationDropdown" class="absolute right-0 mt-2 w-80 sm:w-96 bg-white rounded-xl shadow-2xl border border-gray-100 hidden z-50 overflow-hidden">
                            <div class="p-4 text-center text-sm text-gray-500">Memuat notifikasi...</div>
                        </div>
                    </div>

                    <!-- User Greeting -->
                    <div class="hidden md:flex items-center gap-2 pl-3 border-l border-gray-200">
                        <span class="text-xs text-gray-400">Login sebagai:</span>
                        <span class="text-xs font-bold text-gray-700">{{ auth()->user()->name ?? 'User' }} (NIK: {{ auth()->user()->nik ?? '-' }})</span>
                    </div>
                </div>
            </header>

            <!-- Main Scrollable Area -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-slate-50 p-4 sm:p-6 lg:p-8">
                @yield('content')
            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    
    <script>
        // Global AJAX Setup
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            }
        });

        // Mobile Sidebar Toggle
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            sidebar.classList.toggle('-translate-x-full');
            backdrop.classList.toggle('hidden');
        }

        // SweetAlert Toast Notification
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3500,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });

        function showNotification(type, message) {
            Toast.fire({
                icon: type,
                title: message
            });
        }

        // Notifications logic
        $(document).ready(function() {
            let notificationsOpen = false;

            $('#notificationBtn').click(function(e) {
                e.stopPropagation();
                notificationsOpen = !notificationsOpen;
                $('#notificationDropdown').toggleClass('hidden');
                
                if (notificationsOpen) {
                    loadNotificationsDropdown();
                }
            });

            $(document).click(function(e) {
                if (!$(e.target).closest('#notificationDropdownContainer').length) {
                    $('#notificationDropdown').addClass('hidden');
                    notificationsOpen = false;
                }
            });

            function loadNotificationsDropdown() {
                $.get("{{ route('notifications.dropdown') }}", function(html) {
                    $('#notificationDropdown').html(html);
                }).fail(function() {
                    $('#notificationDropdown').html('<div class="p-4 text-center text-sm text-red-500">Gagal memuat notifikasi.</div>');
                });
            }

            function checkUnreadNotifications() {
                $.get("{{ route('notifications.unreadCount') }}", function(data) {
                    if (data.count > 0) {
                        $('#unreadCountBadge').text(data.count > 99 ? '99+' : data.count).removeClass('hidden');
                    } else {
                        $('#unreadCountBadge').addClass('hidden');
                    }
                });
            }

            // Check immediately and poll every 15 seconds
            checkUnreadNotifications();
            setInterval(checkUnreadNotifications, 15000);

            // Handle mark single as read
            $(document).on('click', '.mark-single-read', function(e) {
                e.preventDefault();
                let notifId = $(this).data('id');
                $.post(`/notifications/${notifId}/read`, function(res) {
                    checkUnreadNotifications();
                    loadNotificationsDropdown();
                });
            });

            // Handle mark all as read
            $(document).on('click', '.mark-all-read-btn', function(e) {
                e.preventDefault();
                $.post("{{ route('notifications.readAll') }}", function(res) {
                    checkUnreadNotifications();
                    loadNotificationsDropdown();
                    showNotification('success', 'Semua notifikasi ditandai telah dibaca.');
                });
            });

            // Handle flash messages
            @if(session('success'))
                showNotification('success', "{{ session('success') }}");
            @endif
            @if(session('error'))
                showNotification('error', "{{ session('error') }}");
            @endif
        });
    </script>

    @yield('scripts')
</body>
</html>
