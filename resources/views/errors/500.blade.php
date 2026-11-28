{{--
    Halaman error 500 — Terjadi Kesalahan (tema E-Hibah).
    Sengaja TIDAK menampilkan pesan exception / stack trace apa pun:
    detail error hanya tercatat di log server. Bila APP_DEBUG aktif
    di .env lokal, Laravel tetap memakai halaman ini untuk HTTP
    exception, dan halaman debug bawaan hanya muncul untuk exception
    yang tidak tertangkap — jangan aktifkan APP_DEBUG di server
    bersama. Standalone: lihat catatan di 403.blade.php.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 — Terjadi Kesalahan | E-Hibah</title>
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
            <div class="error-icon mb-2"><i class="bi bi-exclamation-triangle"></i></div>
            <div class="error-code">500</div>
            <h1 class="h4 mt-3">Terjadi Kesalahan</h1>
            <p class="text-muted">Maaf, server mengalami gangguan. Tim kami sudah mencatat kejadian ini. Silakan coba lagi beberapa saat lagi.</p>
            <p class="text-muted small">Bila masalah berlanjut, hubungi admin LPPM YARSI.</p>
            <div class="d-flex gap-2 justify-content-center mt-4 flex-wrap">
                <button type="button" class="btn btn-hijau" onclick="location.reload()"><i class="bi bi-arrow-clockwise me-1"></i> Coba Lagi</button>
                <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="btn btn-outline-secondary"><i class="bi bi-house-door me-1"></i> Kembali ke Dashboard</a>
            </div>
        </div>
        <p class="text-center text-muted small mt-3">E-Hibah &middot; LPPM Universitas YARSI</p>
    </div>
</body>
</html>
