<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MLMPS (Manufacturing Line Monitoring & Employee Performance)</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .animate-fade-in-up {
            animation: fadeInUp 0.5s ease-out forwards;
        }
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(16px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body class="min-h-screen bg-slate-900 flex flex-col items-center justify-center p-4">

    <div class="w-full max-w-md bg-white rounded-3xl shadow-2xl overflow-hidden animate-fade-in-up border border-slate-100">
        
        <!-- Header -->
        <div class="bg-slate-950 p-6 text-center border-b border-slate-800 text-white">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-indigo-600 mb-3 shadow-lg shadow-indigo-600/40">
                <i class="fa-solid fa-industry text-2xl text-white"></i>
            </div>
            <h1 class="text-2xl font-black tracking-wider text-white">MLMPS</h1>
            <p class="text-xs text-indigo-300 font-semibold uppercase tracking-wider mt-1">Manufacturing Line Monitoring & Employee Performance System</p>
        </div>

        <!-- Form -->
        <div class="p-6 sm:p-8">
            @if(session('error'))
                <div class="mb-5 p-3.5 bg-rose-50 border-l-4 border-rose-500 text-rose-700 text-xs font-semibold rounded-lg">
                    {{ session('error') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf

                <!-- NIK / Email -->
                <div>
                    <label for="login" class="block text-xs font-bold uppercase text-slate-600 mb-1.5">NIK atau Email</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <i class="fa-solid fa-id-card"></i>
                        </div>
                        <input id="login" name="login" type="text" required autofocus
                            class="pl-10 w-full py-2.5 px-3.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none transition @error('login') border-rose-500 @enderror" 
                            placeholder="Masukkan NIK atau Email terdaftar" value="{{ old('login') }}">
                    </div>
                    @error('login')
                        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <i class="fa-solid fa-lock"></i>
                        </div>
                        <input id="password" name="password" type="password" required 
                            class="pl-10 pr-10 w-full py-2.5 px-3.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none transition @error('password') border-rose-500 @enderror" 
                            placeholder="Masukkan password akun">
                        <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center">
                            <button type="button" id="togglePassword" class="text-slate-400 hover:text-slate-600 focus:outline-none">
                                <i class="fa-solid fa-eye" id="eyeIcon"></i>
                            </button>
                        </div>
                    </div>
                    @error('password')
                        <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Remember Me -->
                <div class="flex items-center justify-between text-xs pt-1">
                    <label class="flex items-center gap-2 cursor-pointer text-slate-600">
                        <input id="remember" name="remember" type="checkbox" class="w-4 h-4 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500">
                        <span>Ingat saya di perangkat ini</span>
                    </label>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full flex justify-center py-3 px-4 rounded-xl text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-md shadow-indigo-600/30 transition">
                    Masuk ke Sistem (Login)
                </button>
            </form>
        </div>
    </div>

    <div class="mt-6 text-center text-slate-400 text-xs">
        &copy; {{ date('Y') }} Manufacturing Line Monitoring & Employee Performance System (MLMPS)
    </div>

    <script>
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        });
    </script>
</body>
</html>
