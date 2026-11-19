
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Hibah | Login Universitas YARSI</title>

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
            padding: 9px 14px;
            border: 1px solid rgba(255,255,255,.65);
            border-radius: 30px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .3px;
            margin-bottom: 24px;
        }

        .panel-content h1 {
            font-size: clamp(35px, 4vw, 49px);
            line-height: 1.1;
            font-weight: 800;
            letter-spacing: -1.5px;
            margin-bottom: 13px;
        }

        .panel-content p {
            max-width: 440px;
            color: rgba(255,255,255,.88);
            font-size: 13px;
            line-height: 1.8;
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
            animation: none !important;
            transform: none !important;
            display: block;
        }

        .panel-bottom {
            position: relative;
            z-index: 2;
            font-size: 11px;
            color: rgba(255,255,255,.75);
            margin-top: 36px;
            text-align: left;
        }

        /* PANEL KANAN */
        .form-panel {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px;
            background: #eff9f2;
        }

        .form-content {
            width: 100%;
            max-width: 370px;
        }

        .eh-logo {
            display: block;
            width: 100px;
            height: 75px;
            object-fit: contain;
            margin: 0 auto 24px;
        }

        .login-card {
            background: white;
            border-radius: 22px;
            padding: 30px 28px;
            box-shadow: 0 3px 0 rgba(6, 78, 43, .18),
                        0 12px 35px rgba(6, 78, 43, .06);
        }

        .login-heading {
            text-align: center;
            margin-bottom: 25px;
        }

        .login-heading h2 {
            color: #17251c;
            font-size: 22px;
            font-weight: 800;
            margin-bottom: 7px;
        }

        .login-heading p {
            color: #77847b;
            font-size: 11px;
        }

        .form-group {
            margin-bottom: 17px;
        }

        .form-group label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            color: #263c30;
            margin-bottom: 8px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper input {
            width: 100%;
            height: 45px;
            border: 1px solid #dce6df;
            border-radius: 9px;
            padding: 0 42px 0 13px;
            outline: none;
            background: #fff;
            color: #263c30;
            font-family: inherit;
            font-size: 12px;
            transition: .25s ease;
        }

        .input-wrapper input:focus {
            border-color: #159447;
            box-shadow: 0 0 0 3px rgba(21,148,71,.09);
        }

        .input-wrapper input::placeholder {
            color: #aab5ad;
        }

        .toggle-password {
            position: absolute;
            right: 13px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            color: #89988e;
            cursor: pointer;
        }

        .forgot-password {
            text-align: right;
            margin: -4px 0 20px;
        }

        .forgot-password a {
            color: #159447;
            font-size: 10px;
            font-weight: 600;
            text-decoration: none;
        }

        .forgot-password a:hover {
            text-decoration: underline;
        }

        .btn-login {
            width: 100%;
            height: 46px;
            border: none;
            border-radius: 10px;
            background: linear-gradient(135deg, #20bd59, #11843c);
            color: white;
            font-family: inherit;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 5px 13px rgba(22,163,74,.18);
            transition: .3s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 9px 20px rgba(22,163,74,.28);
        }

        .error {
            padding: 11px 13px;
            margin-bottom: 17px;
            border: 1px solid #ffd2d2;
            border-radius: 9px;
            background: #fff0f0;
            color: #d12d2d;
            font-size: 11px;
            line-height: 1.6;
        }

        .security-info {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 7px;
            margin-top: 20px;
            color: #819087;
            font-size: 10px;
        }

        .security-info i {
            color: #159447;
        }

        .copyright {
            margin-top: 25px;
            text-align: center;
            color: #84948a;
            font-size: 9px;
            line-height: 1.7;
        }

        @keyframes appear {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-9px); }
        }

        /* RESPONSIVE */
        @media(max-width: 800px) {
            body {
                padding: 15px;
            }

            .login-container {
                max-width: 500px;
                grid-template-columns: 1fr;
            }

            .brand-panel {
                min-height: 330px;
                padding: 28px;
            }

            .panel-content {
                margin-top: 25px;
            }

            .panel-content h1 {
                font-size: 34px;
            }

            .panel-content p {
                font-size: 11px;
            }

            .illustration {
                display: none;
            }

            .panel-bottom {
                margin-top: 25px;
            }

            .form-panel {
                padding: 35px 25px;
            }
        }

        @media(max-width: 420px) {
            .brand-panel {
                padding: 23px;
            }

            .institution-logo {
                padding: 8px 11px;
            }

            .institution-logo span {
                font-size: 10px;
            }

            .panel-content h1 {
                font-size: 30px;
            }

            .form-panel {
                padding: 28px 18px;
            }

            .login-card {
                padding: 25px 20px;
            }

            .login-heading h2 {
                font-size: 20px;
            }
        }

        @media(prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation: none !important;
                transition: none !important;
            }
        }


/* =====================================
   LOGIN PAGE - PRECISION REFINEMENT
===================================== */

body {
    padding: 0;
    margin: 0;
    background: #eff9f2;
}

.login-container {
    width: 100vw;
    max-width: 100%;
    min-height: 100vh;
    grid-template-columns: 58% 42%;
    border-radius: 0;
    box-shadow: none;
}

/* LEFT PANEL */
.brand-panel {
    padding: 40px 55px;
    justify-content: center;
    align-items: center;
}

.panel-content {
    margin-top: 0;
}

.system-label {
    padding: 10px 17px;
    margin-bottom: 24px;
    font-size: 11px;
}

.panel-content h1 {
    font-size: clamp(38px, 4.2vw, 58px);
    letter-spacing: -1.8px;
    margin-bottom: 15px;
}

.panel-content p {
    max-width: 440px;
    font-size: 14px;
    line-height: 1.9;
}

.panel-bottom {
    margin-top: 27px;
    padding-top: 0;
    font-size: 11px;
}

/* RIGHT PANEL */
.form-panel {
    padding: 45px 48px;
    justify-content: center;
}

.form-content {
    width: 100%;
    max-width: 445px;
}

.eh-logo {
    width: 125px;
    height: 75px;
    margin-bottom: 24px;
}

.login-card {
    width: 100%;
    padding: 36px 34px 30px;
    border-radius: 25px;
    box-shadow:
        0 3px 0 rgba(6, 78, 43, .20),
        0 15px 35px rgba(6, 78, 43, .055);
}

.login-heading {
    margin-bottom: 29px;
}

.login-heading h2 {
    font-size: 24px;
    letter-spacing: -.5px;
    margin-bottom: 8px;
}

.login-heading p {
    font-size: 12px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    font-size: 12px;
    margin-bottom: 9px;
}

.input-wrapper input {
    height: 54px;
    border-radius: 11px;
    padding-left: 15px;
    font-size: 13px;
}

.toggle-password {
    right: 15px;
    font-size: 14px;
}

.forgot-password {
    margin: 0 0 24px;
}

.forgot-password a {
    font-size: 11px;
}

.btn-login {
    height: 55px;
    border-radius: 12px;
    font-size: 14px;
}

.security-info {
    margin-top: 23px;
    font-size: 11px;
}

.copyright {
    margin-top: 27px;
    font-size: 10px;
    line-height: 1.8;
}

/* TABLET */
@media(max-width: 900px) {
    .brand-panel {
        padding: 35px;
    }

    .form-panel {
        padding: 30px 25px;
    }

    .institution-logo {
        min-width: 0;
    }

    .panel-content h1 {
        font-size: 40px;
    }

    .illustration {
        height: 230px;
    }
}

/* MOBILE */
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
}

/* LOGO UNIVERSITAS YARSI - PANEL KIRI */
.institution-logo {
    min-width: 310px;
    min-height: 82px;
    padding: 12px 22px;
    gap: 16px;
    border-radius: 12px;
}

.institution-logo img {
    width: 58px;
    height: 58px;
    object-fit: contain;
}

.institution-logo span {
    font-size: 14px;
    line-height: 1.45;
    font-weight: 800;
}

/* LOGO E-HIBAH - PANEL KANAN */
.eh-logo {
    width: 220px;
    height: 105px;
    object-fit: contain;
    margin: 0 auto 22px;
    display: block;
}

/* RESPONSIVE */
@media(max-width: 800px) {
    .institution-logo {
        min-width: 270px;
        min-height: 72px;
    }

    .institution-logo img {
        width: 48px;
        height: 48px;
    }

    .eh-logo {
        width: 155px;
        height: 90px;
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

                <div class="login-heading">
                    <h2>Selamat Datang di E-Hibah</h2>
                    <p>Masukkan informasi login Anda untuk melanjutkan</p>
                </div>

                @if ($errors->any())
                    <div class="error">
                        <i class="fa-solid fa-circle-exclamation"></i>
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('login.post') }}">
                    @csrf

                    <div class="form-group">
                        <label for="username">Nama Pengguna</label>

                        <div class="input-wrapper">
                            <input
                                type="text"
                                id="username"
                                name="username"
                                placeholder="Masukkan nama pengguna"
                                value="{{ old('username') }}"
                                autocomplete="username"
                                required
                                autofocus
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Kata Sandi</label>

                        <div class="input-wrapper">
                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Masukkan kata sandi"
                                autocomplete="current-password"
                                required
                            >

                            <button
                                type="button"
                                class="toggle-password"
                                onclick="togglePassword()"
                                aria-label="Tampilkan atau sembunyikan password"
                            >
                                <i class="fa-solid fa-eye" id="eyeIcon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="forgot-password">
                        <a href="{{ route('password.request') }}">
                            Lupa Password?
                        </a>
                    </div>

                    <button type="submit" class="btn-login">
                        Masuk
                        <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </form>

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

<script>
    function togglePassword() {
        const password = document.getElementById('password');
        const icon = document.getElementById('eyeIcon');

        if (password.type === 'password') {
            password.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            password.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
</script>

</body>
</html>
