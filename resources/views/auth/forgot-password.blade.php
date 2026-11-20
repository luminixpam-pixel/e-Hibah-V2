<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Hibah | Lupa Kata Sandi Universitas YARSI</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            display: flex;
            margin: 0;
            padding: 0;
            background: #eff9f2;
            font-family: 'Inter', sans-serif;
            color: #173b29;
            overflow-x: hidden;
        }

        .login-container {
            width: 100vw;
            max-width: 100%;
            min-height: 100vh;
            display: grid;
            grid-template-columns: 58% 42%;
            background: #eff9f2;
            overflow: visible;
            box-shadow: none;
            border-radius: 0;
            animation: appear .7s ease;
        }

        /* PANEL KIRI */
        .brand-panel {
            position: relative;
            overflow: hidden;
            padding: 40px 55px;
            color: white;
            background:
                radial-gradient(circle at 85% 10%, rgba(255,255,255,.2), transparent 18%),
                linear-gradient(145deg, #087b43, #009653 60%, #08713d);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .brand-panel::before,
        .brand-panel::after {
            content: "";
            position: absolute;
            border: 1px solid rgba(255,255,255,.17);
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
        }

        .brand-panel::before {
            width: 280px;
            height: 280px;
            right: -100px;
            top: -110px;
        }

        .brand-panel::after {
            width: 200px;
            height: 200px;
            left: -100px;
            bottom: 20px;
        }

        .brand-content {
            width: 100%;
            max-width: 580px;
            display: flex;
            flex-direction: column;
            position: relative;
            z-index: 2;
        }

        .panel-content {
            position: relative;
            z-index: 2;
            margin-top: 0;
            margin-bottom: 20px;
        }

        .system-label {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            padding: 10px 17px;
            border: 1px solid rgba(255,255,255,.65);
            border-radius: 30px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .3px;
            margin-bottom: 24px;
        }

        .panel-content h1 {
            font-size: clamp(38px, 4.2vw, 58px);
            line-height: 1.1;
            font-weight: 800;
            letter-spacing: -1.8px;
            margin-bottom: 15px;
        }

        .panel-content p {
            max-width: 440px;
            color: rgba(255,255,255,.88);
            font-size: 14px;
            line-height: 1.9;
        }

        /* ILUSTRASI */
        .illustration {
            position: relative;
            z-index: 2;
            display: flex;
            justify-content: center;
            align-items: flex-end;
            margin-top: 0;
            margin-bottom: 18px;
            width: 100%;
        }

        .login-cover-img {
            max-width: 560px;
            width: 100%;
            height: auto;
            max-height: 420px;
            object-fit: contain;
            object-position: bottom center;
            filter: drop-shadow(0 15px 35px rgba(0, 0, 0, 0.16));
            display: block;
        }

        .panel-bottom {
            position: relative;
            z-index: 2;
            font-size: 11px;
            color: rgba(255,255,255,.75);
            margin-top: 27px;
            text-align: left;
        }

        /* PANEL KANAN */
        .form-panel {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 45px 48px;
            background: #eff9f2;
        }

        .form-content {
            width: 100%;
            max-width: 445px;
        }

        .eh-logo {
            display: block;
            width: 220px;
            height: 105px;
            object-fit: contain;
            margin: 0 auto 22px;
        }

        .login-card {
            background: white;
            border-radius: 25px;
            padding: 36px 34px 30px;
            box-shadow: 0 3px 0 rgba(6, 78, 43, .20),
                        0 15px 35px rgba(6, 78, 43, .055);
        }

        .login-heading {
            text-align: center;
            margin-bottom: 29px;
        }

        .login-heading h2 {
            color: #17251c;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -.5px;
            margin-bottom: 8px;
        }

        .login-heading p {
            color: #77847b;
            font-size: 12px;
        }

        .forgot-icon {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }

        .forgot-icon span {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: rgba(21, 148, 71, .10);
            color: #159447;
            font-size: 26px;
        }

        .forgot-text {
            color: #4c5b51;
            font-size: 13px;
            line-height: 1.9;
            margin-bottom: 20px;
        }

        .forgot-text strong {
            color: #263c30;
        }

        .contact-box {
            border: 1px solid #dce6df;
            border-radius: 12px;
            padding: 16px 18px;
            margin-bottom: 24px;
            background: #f7fbf8;
        }

        .contact-box .contact-title {
            font-size: 11px;
            font-weight: 700;
            color: #263c30;
            margin-bottom: 12px;
            letter-spacing: .3px;
        }

        .contact-row {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 7px 0;
            font-size: 13px;
            color: #263c30;
        }

        .contact-row i {
            width: 20px;
            text-align: center;
            color: #159447;
            font-size: 14px;
        }

        .contact-row a {
            color: #159447;
            font-weight: 600;
            text-decoration: none;
        }

        .contact-row a:hover {
            text-decoration: underline;
        }

        .contact-row span {
            font-weight: 600;
        }

        .btn-login {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 9px;
            width: 100%;
            height: 55px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, #20bd59, #11843c);
            color: white;
            font-family: inherit;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            box-shadow: 0 5px 13px rgba(22,163,74,.18);
            transition: .3s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 9px 20px rgba(22,163,74,.28);
        }

        .security-info {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 7px;
            margin-top: 23px;
            color: #819087;
            font-size: 11px;
        }

        .security-info i {
            color: #159447;
        }

        .copyright {
            margin-top: 27px;
            text-align: center;
            color: #84948a;
            font-size: 10px;
            line-height: 1.8;
        }

        @keyframes appear {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* RESPONSIVE */
        @media(max-width: 900px) {
            .brand-panel {
                padding: 35px;
            }

            .form-panel {
                padding: 30px 25px;
            }

            .panel-content h1 {
                font-size: 40px;
            }

            .illustration {
                height: 230px;
            }
        }

        @media(max-width: 800px) {
            body {
                padding: 12px;
            }

            .login-container {
                min-height: auto;
                grid-template-columns: 1fr;
            }

            .brand-panel {
                min-height: 300px;
                padding: 27px;
            }

            .panel-content {
                margin-top: 25px;
            }

            .illustration {
                display: none;
            }

            .panel-bottom {
                margin-top: 25px;
            }

            .form-panel {
                padding: 35px 22px;
            }

            .login-card {
                padding: 28px 23px;
            }

            .eh-logo {
                width: 155px;
                height: 90px;
            }
        }

        @media(prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation: none !important;
                transition: none !important;
            }
        }
    </style>
</head>

<body>

<div class="login-container">

    <!-- PANEL KIRI -->
    <section class="brand-panel">

        <div class="brand-content">
            <div class="panel-content">
                <div class="system-label">
                    <i class="fa-solid fa-shield-halved"></i>
                    SISTEM INFORMASI MANAJEMEN HIBAH INTERNAL (E-HIBAH)
                </div>

                <h1>E-HIBAH</h1>

                <p>
                    Masuk untuk mengelola data dan layanan Sistem Informasi
                    Manajemen Hibah Internal (E-Hibah) Universitas YARSI.
                </p>
            </div>

            <div class="illustration">
                <img
                    src="{{ asset('image/login-image.png') }}?v={{ file_exists(public_path('image/login-image.png')) ? filemtime(public_path('image/login-image.png')) : time() }}"
                    alt="Ilustrasi E-Hibah Universitas YARSI"
                    class="login-cover-img"
                >
            </div>

            <div class="panel-bottom">
                Universitas YARSI · Sistem Informasi Pengelolaan Hibah
            </div>
        </div>

    </section>


    <!-- PANEL KANAN -->
    <section class="form-panel">

        <div class="form-content">

            <img
                src="{{ asset('image/logo-e-hibah.png') }}"
                alt="Logo E-Hibah"
                class="eh-logo"
            >

            <div class="login-card">

                <div class="forgot-icon">
                    <span><i class="fa-solid fa-key"></i></span>
                </div>

                <div class="login-heading">
                    <h2>Lupa Kata Sandi?</h2>
                    <p>Reset kata sandi tidak bisa dilakukan dari aplikasi ini</p>
                </div>

                <p class="forgot-text">
                    Akun E-Hibah terhubung dengan <strong>akun kampus Universitas YARSI</strong>,
                    sehingga kata sandi hanya dapat direset oleh pihak YARSI.
                    Silakan hubungi pihak YARSI dan sertakan <strong>nama lengkap, NPM,
                    serta fakultas/program studi</strong> Anda agar proses reset dapat dibantu.
                </p>

                <div class="contact-box">
                    <div class="contact-title">HUBUNGI PIHAK YARSI</div>

                    <div class="contact-row">
                        <i class="fa-solid fa-envelope"></i>
                        <a href="mailto:registrar@yarsi.ac.id">registrar@yarsi.ac.id</a>
                    </div>

                    <div class="contact-row">
                        <i class="fa-solid fa-phone"></i>
                        <a href="tel:+62214206675">+62 (21) 4206675</a>
                    </div>

                    <div class="contact-row">
                        <i class="fa-solid fa-location-dot"></i>
                        <span>Menara YARSI, Jl. Letjen Suprapto, Cempaka Putih, Jakarta Pusat 10510</span>
                    </div>
                </div>

                <a href="{{ route('login') }}" class="btn-login">
                    <i class="fa-solid fa-arrow-left"></i>
                    Kembali ke Login
                </a>

                <div class="security-info">
                    <i class="fa-solid fa-shield-halved"></i>
                    Login aman dan terlindungi
                </div>

            </div>

            <div class="copyright">
                © {{ date('Y') }} E-Hibah Universitas YARSI.<br>
                All Rights Reserved.
            </div>

        </div>

    </section>

</div>

</body>
</html>
