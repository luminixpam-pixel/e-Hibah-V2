
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
            justify-content: center;
            align-items: center;
            padding: 25px;
            background: #eaf5ed;
            font-family: 'Inter', sans-serif;
            color: #173b29;
        }

        .login-container {
            width: 100%;
            max-width: 1120px;
            min-height: 650px;
            display: grid;
            grid-template-columns: 60% 40%;
            background: #eff9f2;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(5, 75, 37, .13);
            animation: appear .7s ease;
        }

        /* PANEL KIRI */
        .brand-panel {
            position: relative;
            overflow: hidden;
            padding: 42px;
            color: white;
            background:
                radial-gradient(circle at 85% 10%, rgba(255,255,255,.2), transparent 18%),
                linear-gradient(145deg, #087b43, #009653 60%, #08713d);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .brand-panel::before,
        .brand-panel::after {
            content: "";
            position: absolute;
            border: 1px solid rgba(255,255,255,.17);
            border-radius: 50%;
            pointer-events: none;
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

      .institution-logo {
    width: 230px;
    height: 95px;
    padding: 8px;
    display: flex;
    align-items: center;
    justify-content: flex-start;
    background: transparent;
    border-radius: 0;
}

.institution-logo img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    object-position: left center;
}

        .institution-logo span {
            color: #173b29;
            font-size: 20px;
            font-weight: 800;
            line-height: 1.4;
        }

        .panel-content {
            position: relative;
            z-index: 2;
            margin-top: 28px;
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
            max-width: 400px;
            color: rgba(255,255,255,.88);
            font-size: 13px;
            line-height: 1.8;
        }

        /* ILUSTRASI */
        .illustration {
            position: relative;
            z-index: 2;
            height: 280px;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 15px;
        }

        .illustration-circle {
            position: absolute;
            width: 245px;
            height: 245px;
            border-radius: 50%;
            background: rgba(255,255,255,.13);
            border: 1px solid rgba(255,255,255,.2);
        }

        .illustration-person {
            position: relative;
            z-index: 2;
            width: 180px;
            height: 190px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            color: #e9f2dc;
            animation: float 4s ease-in-out infinite;
        }

        .illustration-person .head {
            width: 76px;
            height: 76px;
            border-radius: 50%;
            background: #dce8cf;
            color: #526d52;
            display: grid;
            place-items: center;
            font-size: 48px;
            margin-bottom: -5px;
        }

        .illustration-person .body {
            width: 150px;
            height: 105px;
            border-radius: 75px 75px 15px 15px;
            background: #c5d5b8;
            color: #5d785d;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 48px;
        }

        .illustration-laptop {
            position: absolute;
            z-index: 3;
            bottom: 8px;
            font-size: 65px;
            color: #dce4dc;
            text-shadow: 0 5px 8px rgba(0,0,0,.2);
        }

        .decor-icon {
            position: absolute;
            color: rgba(255,255,255,.8);
            font-size: 42px;
            animation: float 5s ease-in-out infinite;
        }

        .decor-one {
            left: 8%;
            bottom: 60px;
        }

        .decor-two {
            right: 12%;
            top: 25px;
            font-size: 34px;
        }

        .decor-three {
            right: 8%;
            bottom: 55px;
            font-size: 38px;
        }

        .panel-bottom {
            position: relative;
            z-index: 2;
            font-size: 10px;
            color: rgba(255,255,255,.75);
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
    padding: 16px;
    background: #eaf5ed;
}

.login-container {
    width: 100%;
    max-width: 1440px;
    min-height: calc(100vh - 32px);
    grid-template-columns: 60% 40%;
    border-radius: 0;
    box-shadow: 0 12px 45px rgba(5, 75, 37, .10);
}

/* LEFT PANEL */
.brand-panel {
    padding: 50px;
    justify-content: flex-start;
}

.institution-logo {
    min-width: 256px;
    min-height: 66px;
    padding: 10px 18px;
    gap: 13px;
    border-radius: 11px;
}

.institution-logo img {
    width: 40px;
    height: 40px;
}

.institution-logo span {
    font-size: 12px;
    line-height: 1.5;
}

.panel-content {
    margin-top: 36px;
}

.system-label {
    padding: 10px 17px;
    margin-bottom: 29px;
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

/* ILLUSTRATION */
.illustration {
    height: 285px;
    margin-top: 25px;
}

.illustration-circle {
    width: 295px;
    height: 295px;
}

.illustration-person {
    transform: scale(1.12);
}

.decor-icon {
    opacity: .9;
}

.decor-one {
    left: 8%;
    bottom: 45px;
    font-size: 48px;
}

.decor-two {
    right: 12%;
    top: 20px;
    font-size: 40px;
}

.decor-three {
    right: 9%;
    bottom: 40px;
    font-size: 42px;
}

.panel-bottom {
    margin-top: auto;
    padding-top: 20px;
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

            <div class="illustration">
                <div class="illustration-circle"></div>

                <i class="fa-solid fa-gear decor-icon decor-one"></i>
                <i class="fa-solid fa-coins decor-icon decor-two"></i>
                <i class="fa-solid fa-lightbulb decor-icon decor-three"></i>

                <div class="illustration-person">
                    <div class="head">
                        <i class="fa-solid fa-user-tie"></i>
                    </div>
                    <div class="body">
                        <i class="fa-solid fa-laptop"></i>
                    </div>
                </div>

                <i class="fa-solid fa-laptop illustration-laptop"></i>
            </div>
        </div>

        <div class="panel-bottom">
            Universitas YARSI · Sistem Informasi Pengelolaan Hibah
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
