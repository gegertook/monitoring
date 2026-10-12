<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sesi Berakhir - MLMPS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="min-h-screen bg-slate-900 flex flex-col items-center justify-center p-4">

    <div class="w-full max-w-md bg-white rounded-3xl shadow-2xl overflow-hidden border border-slate-100 text-center p-8 sm:p-10">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-amber-100 mb-4">
            <i class="fa-solid fa-hourglass-half text-2xl text-amber-600"></i>
        </div>

        <h1 class="text-xl font-black text-slate-800 mb-2">Sesi Keamanan Berakhir</h1>
        <p class="text-sm text-slate-500 mb-6">
            Token keamanan halaman ini sudah kedaluwarsa (biasanya karena tab dibiarkan terbuka terlalu lama).
            Muat ulang halaman lalu coba lagi — data Anda aman.
        </p>

        <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
            <button onclick="window.location.reload()" class="w-full sm:w-auto px-6 py-2.5 text-sm font-semibold rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm shadow-indigo-600/20 transition">
                <i class="fa-solid fa-rotate-right mr-1.5"></i> Muat Ulang Halaman
            </button>
            <a href="{{ route('login') }}" class="w-full sm:w-auto px-6 py-2.5 text-sm font-semibold rounded-xl text-slate-600 bg-slate-100 hover:bg-slate-200 transition">
                Ke Halaman Login
            </a>
        </div>
    </div>

</body>
</html>
