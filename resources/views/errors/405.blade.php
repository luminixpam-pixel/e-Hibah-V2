{{--
    Halaman error 405 — Metode Tidak Diizinkan (tema E-Hibah).
    Standalone (tidak extends layouts/app): halaman error harus tetap
    tampil bila layout utama gagal dirender (mis. user belum login
    atau session rusak), dan tidak boleh memuat Vite/Livewire.
    Dibuat mengikuti pola errors/403.blade.php (uji ke-5 butir 9).
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>405 — Metode Tidak Diizinkan | E-Hibah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background: linear-gradient(135deg, #d9fcd4, #f6fef7); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .error-card { background: #fff; border-radius: 16px; box-shadow: 0 10px 30px rgba(18, 60, 45, .12); max-width: 560px; width: 100%; padding: 3rem 2.5rem; text-align: center; }
        .error-code { font-size: 5rem; font-weight: 600; color: #123c2d; line-height: 1; }
        .error-icon { font-size: 3rem; color: #1fbd4c; }
        .btn-hijau { background: #267451; border-color: #267451; color: #fff; }
        .btn-hijau:hover { background: #1c5942; border-color: #1c5942; color: #fff; }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-card mx-auto">
            <div class="error-icon mb-2"><i class="bi bi-sign-stop"></i></div>
            <div class="error-code">405</div>
            <h1 class="h4 mt-3">Metode Tidak Diizinkan</h1>
            <p class="text-muted">Maaf, cara yang digunakan untuk membuka halaman ini tidak diizinkan. Halaman ini hanya menerima cara tertentu — misalnya, formulir harus dikirim lewat tombol kirim, bukan lewat tautan.</p>
            <p class="text-muted small">Bila Anda merasa ini kesalahan, hubungi admin LPPM YARSI.</p>
            <div class="d-flex gap-2 justify-content-center mt-4 flex-wrap">
                <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="btn btn-hijau"><i class="bi bi-house-door me-1"></i> Kembali ke Dashboard</a>
                <button type="button" class="btn btn-outline-secondary" onclick="history.back()"><i class="bi bi-arrow-left me-1"></i> Kembali</button>
            </div>
        </div>
        <p class="text-center text-muted small mt-3">E-Hibah &middot; LPPM Universitas YARSI</p>
    </div>
</body>
</html>
