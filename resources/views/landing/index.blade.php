
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Hibah | Universitas YARSI</title>
    <meta name="description" content="Portal E-Hibah Universitas YARSI untuk pengelolaan hibah penelitian dan pengabdian kepada masyarakat.">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --green: #20BF55;
            --primary: #16A34A;
            --dark: #064E2B;
            --deep: #04351E;
            --soft: #DDF5E4;
            --light: #EFF9F1;
            --text: #172B20;
            --muted: #647469;
            --border: #DCE9DF;
            --white: #FFFFFF;
            --background: #F8FAF8;
            --shadow: 0 8px 25px rgba(6,78,43,.07);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
            scroll-padding-top: 90px;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--background);
            color: var(--text);
            line-height: 1.7;
            overflow-x: hidden;
        }

        a {
            text-decoration: none;
        }

        .container {
            width: min(1160px, 90%);
            margin: auto;
        }

        /* NAVBAR */
        .navbar {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: rgba(255,255,255,.94);
            border-bottom: 1px solid var(--border);
            box-shadow: 0 3px 15px rgba(6,78,43,.05);
            backdrop-filter: blur(14px);
            transition: box-shadow .3s ease;
        }

        .nav-content {
            min-height: 76px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 25px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 11px;
            color: var(--dark);
        }

        .brand-icon {
            width: 43px;
            height: 43px;
            display: grid;
            place-items: center;
            border-radius: 12px;
            background: linear-gradient(135deg,var(--green),#12883C);
            color: white;
            font-size: 23px;
            box-shadow: 0 5px 12px rgba(32,191,85,.25);
        }

        .brand-name {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: .5px;
            line-height: 1.2;
        }

        .brand small {
            display: block;
            margin-top: 4px;
            font-size: 9px;
            letter-spacing: 1.4px;
            font-weight: 700;
            color: var(--muted);
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 24px;
        }

        .nav-links a {
            color: #344D3B;
            font-size: 12px;
            font-weight: 600;
            transition: color .25s ease;
        }

        .nav-links a:hover {
            color: var(--primary);
        }

        .btn-login {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 11px 23px;
            background: linear-gradient(135deg,var(--green),var(--primary));
            border-radius: 30px;
            color: white;
            font-size: 13px;
            font-weight: 700;
            box-shadow: 0 6px 16px rgba(22,163,74,.24);
        }

        .btn-login:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 9px 20px rgba(22,163,74,.3);
        }

        /* HERO */
        .hero {
            position: relative;
            overflow: hidden;
            padding: 90px 0 85px;
            background:
                radial-gradient(circle at 85% 20%,rgba(32,191,85,.22),transparent 30%),
                linear-gradient(120deg,#F0FAF2 0%,#DDF5E4 55%,#C4EBD0 100%);
        }

        .hero::before,
        .hero::after {
            content: "";
            position: absolute;
            border: 1px solid rgba(22,163,74,.15);
            border-radius: 50%;
            pointer-events: none;
        }

        .hero::before {
            width: 430px;
            height: 430px;
            right: -130px;
            top: -180px;
        }

        .hero::after {
            width: 260px;
            height: 260px;
            left: -130px;
            bottom: -150px;
        }

        .hero-grid {
            display: grid;
            grid-template-columns: 1.1fr .9fr;
            align-items: center;
            gap: 50px;
            position: relative;
            z-index: 2;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            padding: 8px 15px;
            background: white;
            border: 1px solid #B8E5C5;
            border-radius: 30px;
            color: var(--dark);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1px;
            margin-bottom: 23px;
        }

        .hero-badge span {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--green);
        }

        .hero h1 {
            color: var(--dark);
            font-size: clamp(36px,5vw,57px);
            line-height: 1.13;
            font-weight: 800;
            letter-spacing: -2px;
            margin-bottom: 22px;
        }

        .hero h1 span {
            color: var(--primary);
        }

        .hero-description {
            max-width: 550px;
            color: #405B49;
            font-size: 14px;
            line-height: 1.9;
            margin-bottom: 30px;
        }

        .hero-actions {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 13px;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            padding: 14px 23px;
            background: linear-gradient(135deg,var(--green),var(--primary));
            border-radius: 10px;
            color: white;
            font-size: 13px;
            font-weight: 700;
            box-shadow: 0 7px 18px rgba(22,163,74,.25);
        }

        .btn-primary:hover {
            background: #12883C;
            color: white;
            transform: translateY(-3px);
        }

        .btn-outline {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 13px 20px;
            background: white;
            border: 1px solid #86C99A;
            border-radius: 10px;
            color: var(--dark);
            font-size: 13px;
            font-weight: 700;
        }

        .btn-outline:hover {
            background: var(--soft);
            color: var(--dark);
        }

        .hero-visual {
            min-height: 370px;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            animation: fadeIn .9s ease both;
        }

        .visual-main {
            width: 340px;
            height: 310px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            border-radius: 28px;
            background: linear-gradient(145deg,#064E2B,#16A34A,#20BF55);
            box-shadow: 0 25px 55px rgba(6,78,43,.25);
            animation: floatVisual 6s ease-in-out infinite;
        }

        .visual-main i {
            font-size: 85px;
            margin-bottom: 17px;
        }

        .visual-main strong {
            font-size: 23px;
            font-weight: 800;
        }

        .visual-main span {
            font-size: 11px;
            opacity: .9;
            margin-top: 6px;
        }

        .floating-card {
            position: absolute;
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 13px 16px;
            background: white;
            border: 1px solid #E1EEE4;
            border-radius: 13px;
            box-shadow: var(--shadow);
        }

        .floating-card i {
            width: 38px;
            height: 38px;
            display: grid;
            place-items: center;
            border-radius: 10px;
            background: var(--soft);
            color: var(--dark);
            font-size: 19px;
        }

        .floating-card strong {
            display: block;
            font-size: 11px;
            color: var(--dark);
        }

        .floating-card small {
            display: block;
            font-size: 9px;
            color: var(--muted);
        }

        .float-one {
            top: 30px;
            left: -5px;
            animation: floatOne 5s ease-in-out infinite;
        }

        .float-two {
            right: -10px;
            bottom: 25px;
            animation: floatTwo 5.5s ease-in-out infinite;
        }

        /* SECTION UMUM */
        .section {
            padding: 80px 0;
            background: white;
        }

        .section-heading {
            max-width: 670px;
            margin: 0 auto 45px;
            text-align: center;
        }

        .mini-title {
            color: var(--primary);
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        .section-heading h2 {
            margin: 11px 0;
            color: var(--dark);
            font-size: clamp(25px,3vw,34px);
            line-height: 1.3;
            font-weight: 800;
        }

        .section-heading p {
            color: var(--muted);
            font-size: 13px;
        }

        /* TENTANG */
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(3,1fr);
            gap: 21px;
        }

        .feature-card {
            padding: 28px;
            background: white;
            border: 1px solid var(--border);
            border-radius: 17px;
            box-shadow: 0 5px 18px rgba(6,78,43,.04);
        }

        .feature-card:hover,
        .scheme-card:hover,
        .announcement-card:hover {
            transform: translateY(-7px);
            border-color: var(--green);
            box-shadow: 0 15px 35px rgba(6,78,43,.11);
        }

        .feature-icon {
            width: 51px;
            height: 51px;
            display: grid;
            place-items: center;
            background: var(--soft);
            color: #087A36;
            border-radius: 14px;
            font-size: 23px;
            margin-bottom: 18px;
        }

        .feature-card h3,
        .scheme-card h3,
        .announcement-card h3 {
            color: var(--dark);
            font-size: 15px;
            margin-bottom: 10px;
        }

        .feature-card p,
        .scheme-card p,
        .announcement-card p {
            color: var(--muted);
            font-size: 12px;
            line-height: 1.9;
        }

        /* INFORMASI HIBAH */
        .information-section {
            background: linear-gradient(180deg,#EAF7ED,#F5FBF6);
        }

        .scheme-card {
            padding: 28px;
            background: white;
            border: 1px solid var(--border);
            border-radius: 17px;
            box-shadow: 0 5px 18px rgba(6,78,43,.05);
        }

        .scheme-number {
            width: 48px;
            height: 48px;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg,var(--green),#12883C);
            border-radius: 13px;
            color: white;
            font-size: 20px;
            font-weight: 800;
            margin-bottom: 17px;
        }

        .info-note {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-top: 23px;
            padding: 15px 18px;
            border-left: 4px solid var(--green);
            border-radius: 9px;
            background: #EFF8F1;
            color: #345A40;
            font-size: 11px;
        }

        /* ALUR */
        .flow-grid {
            display: grid;
            grid-template-columns: repeat(6,1fr);
            gap: 13px;
        }

        .flow-item {
            text-align: center;
        }

        .flow-number {
            width: 48px;
            height: 48px;
            display: grid;
            place-items: center;
            margin: 0 auto 14px;
            border-radius: 50%;
            background: linear-gradient(135deg,var(--green),#12883C);
            color: white;
            font-weight: 800;
            box-shadow: 0 6px 15px rgba(22,163,74,.25);
        }

        .flow-item h3 {
            font-size: 12px;
            color: var(--dark);
            margin-bottom: 6px;
        }

        .flow-item p {
            font-size: 10px;
            line-height: 1.7;
            color: var(--muted);
        }

        /* PENGUMUMAN */
        .announcement-section {
            background: #F8FAF8;
        }

        .announcement-grid {
            display: grid;
            grid-template-columns: repeat(3,1fr);
            gap: 21px;
        }

        .announcement-card {
            background: white;
            padding: 25px;
            border: 1px solid var(--border);
            border-radius: 16px;
            box-shadow: 0 5px 18px rgba(6,78,43,.04);
        }

        .announcement-tag {
            display: inline-block;
            padding: 5px 11px;
            border-radius: 20px;
            background: var(--soft);
            color: #087A36;
            font-size: 10px;
            font-weight: 800;
            margin-bottom: 14px;
        }

        /* KEUNGGULAN */
        .advantages-section {
            background: #EAF7ED;
        }

        .advantages-grid {
            display: grid;
            grid-template-columns: repeat(2,1fr);
            gap: 17px;
        }

        .advantage-item {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            padding: 20px;
            background: white;
            border: 1px solid var(--border);
            border-radius: 13px;
        }

        .advantage-item i {
            color: var(--primary);
            font-size: 22px;
        }

        .advantage-item h3 {
            color: var(--dark);
            font-size: 13px;
            margin-bottom: 4px;
        }

        .advantage-item p {
            color: var(--muted);
            font-size: 11px;
            line-height: 1.7;
        }

        /* CTA */
        .cta {
            padding: 72px 0;
            text-align: center;
            background: linear-gradient(115deg,#04351E 0%,#087A36 50%,#16A34A 100%);
            position: relative;
            overflow: hidden;
        }

        .cta h2 {
            color: white;
            font-size: clamp(24px,3vw,32px);
            font-weight: 800;
            margin-bottom: 12px;
        }

        .cta p {
            color: #D5ECDD;
            font-size: 13px;
            margin-bottom: 25px;
        }

        .btn-white {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            padding: 14px 25px;
            border-radius: 9px;
            background: white;
            color: var(--dark);
            font-size: 13px;
            font-weight: 800;
        }

        .btn-white:hover {
            background: var(--soft);
            color: var(--dark);
            transform: translateY(-3px);
        }

        /* FOOTER */
        .footer {
            background: #042D19;
            color: white;
            padding: 55px 0 20px;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 1.1fr .9fr;
            gap: 60px;
            padding-bottom: 35px;
        }

        .footer-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 18px;
        }

        .footer-brand-icon {
            width: 43px;
            height: 43px;
            display: grid;
            place-items: center;
            border-radius: 12px;
            background: var(--green);
            font-size: 22px;
        }

        .footer-brand h3 {
            font-size: 18px;
            font-weight: 800;
        }

        .footer-brand small {
            display: block;
            color: #B8D1BF;
            font-size: 9px;
            letter-spacing: 1px;
        }

        .footer-description {
            max-width: 420px;
            color: #D0E4D5;
            font-size: 12px;
            line-height: 1.9;
        }

        .footer-heading {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 17px;
            color: white;
        }

        .team-list {
            display: grid;
            grid-template-columns: repeat(2,1fr);
            gap: 12px;
        }

        .team-member {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            background: rgba(255,255,255,.07);
            border: 1px solid rgba(255,255,255,.10);
            border-radius: 9px;
        }

        .team-member:hover {
            transform: translateY(-4px);
            background: rgba(32,191,85,.16);
        }

        .team-avatar {
            width: 33px;
            height: 33px;
            flex-shrink: 0;
            display: grid;
            place-items: center;
            border-radius: 50%;
            background: var(--green);
            color: white;
            font-size: 12px;
            font-weight: 800;
        }

        .team-member strong {
            display: block;
            color: white;
            font-size: 10px;
            line-height: 1.5;
        }

        .team-member small {
            display: block;
            color: #B8D1BF;
            font-size: 9px;
        }

        .footer-bottom {
            padding-top: 19px;
            border-top: 1px solid rgba(255,255,255,.15);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            color: #B8D1BF;
            font-size: 10px;
        }

        .footer-bottom a {
            color: var(--green);
        }

        /* ANIMASI */
        .hero-badge,
        .hero h1,
        .hero-description,
        .hero-actions {
            animation: fadeUp .8s ease both;
        }

        .hero-badge { animation-delay: .1s; }
        .hero h1 { animation-delay: .2s; }
        .hero-description { animation-delay: .35s; }
        .hero-actions { animation-delay: .5s; }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(24px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes floatVisual {
            0%,100% { transform: translateY(0) rotate(-3deg); }
            50% { transform: translateY(-10px) rotate(-1deg); }
        }

        @keyframes floatOne {
            0%,100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }

        @keyframes floatTwo {
            0%,100% { transform: translateY(0); }
            50% { transform: translateY(8px); }
        }

        .reveal {
            opacity: 0;
            transform: translateY(24px);
            transition: opacity .7s ease,
                        transform .7s cubic-bezier(.2,.8,.2,1);
        }

        .reveal.visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* TRANSISI */
        .feature-card,
        .scheme-card,
        .announcement-card,
        .advantage-item,
        .team-member,
        .btn-primary,
        .btn-login,
        .btn-outline,
        .btn-white {
            transition: transform .35s cubic-bezier(.2,.8,.2,1),
                        box-shadow .35s ease,
                        background-color .3s ease,
                        border-color .3s ease;
        }

        /* RESPONSIVE */
        @media(max-width:950px) {
            .nav-links { gap: 13px; }
            .nav-links a { font-size: 10px; }
            .hero-grid { gap: 25px; }
            .flow-grid { grid-template-columns: repeat(3,1fr); row-gap: 30px; }
        }

        @media(max-width:700px) {
            .nav-content {
                padding: 12px 0;
                flex-wrap: wrap;
            }

            .nav-links {
                order: 3;
                width: 100%;
                overflow-x: auto;
                padding: 8px 0;
                gap: 20px;
            }

            .nav-links a {
                white-space: nowrap;
                font-size: 11px;
            }

            .nav-content > .btn-login {
                margin-left: auto;
                padding: 9px 15px;
                font-size: 11px;
            }

            .hero { padding: 60px 0 50px; }
            .hero-grid { grid-template-columns: 1fr; }
            .hero h1 { font-size: 39px; letter-spacing: -1.5px; }
            .hero-description { font-size: 13px; }
            .hero-visual { min-height: 300px; }

            .visual-main {
                width: 270px;
                height: 245px;
            }

            .visual-main i { font-size: 65px; }
            .float-one { left: 0; }
            .float-two { right: 0; }

            .feature-grid,
            .announcement-grid,
            .advantages-grid {
                grid-template-columns: 1fr;
            }

            .flow-grid {
                grid-template-columns: repeat(2,1fr);
                row-gap: 25px;
            }

            .footer-grid {
                grid-template-columns: 1fr;
                gap: 35px;
            }

            .section { padding: 58px 0; }
            .cta { padding: 55px 0; }

            .footer-bottom {
                flex-direction: column;
                text-align: center;
            }
        }

        @media(max-width:400px) {
            .brand-name { font-size: 17px; }
            .hero h1 { font-size: 34px; }
            .team-list { grid-template-columns: 1fr; }
            .flow-grid { gap: 20px 10px; }
        }

        @media(prefers-reduced-motion:reduce) {
            *,
            *::before,
            *::after {
                scroll-behavior: auto !important;
                animation-duration: .01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: .01ms !important;
            }

            .reveal {
                opacity: 1;
                transform: none;
            }
        }

/* FOOTER */
.footer {
    position: relative;
    overflow: hidden;
    background: linear-gradient(110deg, #04351E, #07663A);
    color: #fff;
    padding: 28px 0 12px;
}

.footer-main {
    display: grid;
    grid-template-columns: 1.3fr 1fr 1fr 1fr;
    align-items: center;
    gap: 28px;
    padding-bottom: 24px;
}

.footer-brand {
    display: flex;
    align-items: center;
    gap: 14px;
}

.footer-brand img {
    width: 85px;
    height: 45px;
    object-fit: contain;
    filter: brightness(0) invert(1);
}

.footer-brand-info h3 {
    font-size: 14px;
    font-weight: 800;
    margin-bottom: 5px;
}

.footer-brand-info p {
    max-width: 210px;
    color: #c9e4d1;
    font-size: 9px;
    line-height: 1.7;
}

.footer-contact {
    display: flex;
    align-items: flex-start;
    gap: 11px;
}

.footer-contact > i {
    color: #8de2aa;
    font-size: 17px;
    margin-top: 2px;
}

.footer-contact div {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.footer-contact strong {
    color: white;
    font-size: 10px;
    font-weight: 700;
    margin-bottom: 2px;
}

.footer-contact span {
    color: #c9e4d1;
    font-size: 9px;
    line-height: 1.5;
}

.footer-bottom {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    padding-top: 13px;
    border-top: 1px solid rgba(255,255,255,.16);
}

.footer-bottom p {
    color: #c9e4d1;
    font-size: 9px;
}

.footer-links {
    display: flex;
    align-items: center;
    gap: 20px;
}

.footer-links a {
    color: #c9e4d1;
    font-size: 9px;
    transition: color .25s ease;
}

.footer-links a:hover {
    color: white;
}

/* RESPONSIVE FOOTER */
@media(max-width: 850px) {
    .footer-main {
        grid-template-columns: repeat(2, 1fr);
        gap: 22px;
    }
}

@media(max-width: 500px) {
    .footer {
        padding: 25px 0 15px;
    }

    .footer-main {
        grid-template-columns: 1fr;
        gap: 20px;
    }

    .footer-bottom {
        flex-direction: column;
        align-items: flex-start;
    }

    .footer-links {
        gap: 16px;
    }
}
    /* LOGO NAVBAR */
.brand-logo {
    width: 175px;
    height: 55px;
    object-fit: contain;
    display: block;
}

/* LOGO FOOTER */
.footer-logo {
    width: 145px;
    height: 65px;
    object-fit: contain;
    display: block;
    filter: none;
}

@media(max-width: 500px) {
    .brand-logo {
        width: 145px;
        height: 48px;
    }

    .footer-logo {
        width: 125px;
        height: 55px;
    }
}

    </style>
</head>

<body>

    <!-- NAVBAR -->
    <header class="navbar">
        <div class="container nav-content">

          <a href="#beranda" class="brand">
                <img
                    src="{{ asset('image/logo-e-hibah.png') }}"
                    alt="Logo E-Hibah YARSI"
                    class="brand-logo"
                >
            </a>

            <nav class="nav-links">
                <a href="#beranda">Beranda</a>
                <a href="#tentang">Tentang</a>
                <a href="#informasi">Informasi Hibah</a>
                <a href="#pengumuman">Pengumuman</a>
                <a href="#alur">Alur Pengajuan</a>
                <a href="#tim">Tim Pengembang</a>
            </nav>

            <a href="{{ route('login') }}" class="btn-login">
                <i class="bi bi-box-arrow-in-right"></i>
                Masuk
            </a>
        </div>
    </header>

    <!-- HERO -->
    <section class="hero" id="beranda">
        <div class="container hero-grid">

            <div>
                <div class="hero-badge">
                    <span></span>
                    PORTAL HIBAH UNIVERSITAS YARSI
                </div>

                <h1>
                    Dorong Inovasi,
                    Wujudkan
                    <span>Riset Berdampak.</span>
                </h1>

                <p class="hero-description">
                    E-Hibah Universitas YARSI merupakan platform digital
                    untuk mendukung pengelolaan hibah penelitian dan
                    pengabdian kepada masyarakat secara terintegrasi,
                    transparan, efektif, dan akuntabel.
                </p>

                <div class="hero-actions">
                    <a href="{{ route('login') }}" class="btn-primary">
                        Masuk ke Sistem
                        <i class="bi bi-arrow-right"></i>
                    </a>

                    <a href="#tentang" class="btn-outline">
                        Pelajari Lebih Lanjut
                        <i class="bi bi-chevron-down"></i>
                    </a>
                </div>
            </div>

            <div class="hero-visual">
                <div class="visual-main">
                    <i class="bi bi-mortarboard"></i>
                    <strong>E-HIBAH YARSI</strong>
                    <span>Research & Community Development</span>
                </div>

                <div class="floating-card float-one">
                    <i class="bi bi-file-earmark-text"></i>
                    <div>
                        <strong>Pengajuan Proposal</strong>
                        <small>Proses lebih terstruktur</small>
                    </div>
                </div>

                <div class="floating-card float-two">
                    <i class="bi bi-graph-up-arrow"></i>
                    <div>
                        <strong>Monitoring Hibah</strong>
                        <small>Pantau perkembangan</small>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- TENTANG -->
    <section class="section" id="tentang">
        <div class="container">

            <div class="section-heading">
                <span class="mini-title">Tentang E-Hibah</span>
                <h2>Pengelolaan Hibah dalam Satu Platform</h2>
                <p>
                    Mendukung proses pengelolaan hibah mulai dari
                    pengajuan proposal hingga monitoring dan pelaporan.
                </p>
            </div>

            <div class="feature-grid">

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-file-earmark-plus"></i>
                    </div>
                    <h3>Pengajuan Proposal</h3>
                    <p>
                        Memfasilitasi dosen dan pengusul dalam
                        mengajukan proposal penelitian dan pengabdian
                        kepada masyarakat.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-clipboard-check"></i>
                    </div>
                    <h3>Verifikasi dan Review</h3>
                    <p>
                        Mendukung proses verifikasi administrasi,
                        penilaian reviewer, dan pengambilan keputusan
                        secara terstruktur.
                    </p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-bar-chart-line"></i>
                    </div>
                    <h3>Monitoring dan Pelaporan</h3>
                    <p>
                        Membantu pemantauan pelaksanaan hibah,
                        pelaporan kegiatan, serta dokumentasi
                        hasil dan luaran penelitian.
                    </p>
                </div>

            </div>
        </div>
    </section>

    <!-- INFORMASI HIBAH -->
    <section class="section information-section" id="informasi">
        <div class="container">

            <div class="section-heading">
                <span class="mini-title">Informasi Program</span>
                <h2>Program dan Skema Hibah</h2>
                <p>
                    Kenali kategori hibah yang tersedia di lingkungan
                    Universitas YARSI.
                </p>
            </div>

            <div class="feature-grid">

                <div class="scheme-card">
                    <div class="scheme-number">A</div>
                    <h3>Hibah Kategori A</h3>
                    <p>
                        Informasi ketentuan, persyaratan, dan panduan
                        pengajuan mengikuti ketentuan resmi universitas.
                    </p>
                </div>

                <div class="scheme-card">
                    <div class="scheme-number">B</div>
                    <h3>Hibah Kategori B</h3>
                    <p>
                        Informasi skema dan persyaratan dapat dilihat
                        melalui pengumuman resmi program hibah.
                    </p>
                </div>

                <div class="scheme-card">
                    <div class="scheme-number">C</div>
                    <h3>Hibah Kategori C</h3>
                    <p>
                        Pengusul dapat mempelajari ketentuan dan
                        kelengkapan dokumen sebelum mengajukan proposal.
                    </p>
                </div>

            </div>

            <div class="info-note">
                <i class="bi bi-info-circle-fill"></i>
                <span>
                    Detail persyaratan, pagu pendanaan, dan jadwal
                    setiap skema mengikuti pengumuman resmi
                    Universitas YARSI.
                </span>
            </div>

        </div>
    </section>

    <!-- ALUR PENGAJUAN -->
    <section class="section" id="alur">
        <div class="container">

            <div class="section-heading">
                <span class="mini-title">Tahapan Pengajuan</span>
                <h2>Bagaimana Proses Hibah Berjalan?</h2>
                <p>
                    Gambaran umum tahapan pengelolaan proposal
                    hibah Universitas YARSI.
                </p>
            </div>

            <div class="flow-grid">

                <div class="flow-item">
                    <div class="flow-number">1</div>
                    <h3>Pengajuan</h3>
                    <p>Pengusul mengirimkan proposal dan dokumen.</p>
                </div>

                <div class="flow-item">
                    <div class="flow-number">2</div>
                    <h3>Verifikasi Fakultas</h3>
                    <p>Pemeriksaan awal oleh fakultas atau unit.</p>
                </div>

                <div class="flow-item">
                    <div class="flow-number">3</div>
                    <h3>Verifikasi LPPM</h3>
                    <p>Pemeriksaan administrasi oleh pengelola.</p>
                </div>

                <div class="flow-item">
                    <div class="flow-number">4</div>
                    <h3>Review</h3>
                    <p>Penilaian proposal oleh reviewer.</p>
                </div>

                <div class="flow-item">
                    <div class="flow-number">5</div>
                    <h3>Penetapan</h3>
                    <p>Proses keputusan penerima hibah.</p>
                </div>

                <div class="flow-item">
                    <div class="flow-number">6</div>
                    <h3>Pelaksanaan</h3>
                    <p>Kontrak, monitoring, dan pelaporan hibah.</p>
                </div>

            </div>
        </div>
    </section>

    <!-- PENGUMUMAN -->
    <section class="section announcement-section" id="pengumuman">
        <div class="container">

            <div class="section-heading">
                <span class="mini-title">Informasi Terbaru</span>
                <h2>Pengumuman dan Informasi Hibah</h2>
                <p>
                    Temukan informasi penting seputar program
                    hibah Universitas YARSI.
                </p>
            </div>

            <div class="announcement-grid">

                <div class="announcement-card">
                    <span class="announcement-tag">Informasi</span>
                    <h3>Informasi Program Hibah</h3>
                    <p>
                        Informasi mengenai skema dan ketentuan
                        program hibah dapat dilihat melalui
                        pengumuman resmi.
                    </p>
                </div>

                <div class="announcement-card">
                    <span class="announcement-tag">Panduan</span>
                    <h3>Persiapan Pengajuan Proposal</h3>
                    <p>
                        Pelajari persyaratan dan dokumen yang
                        diperlukan sebelum mengajukan proposal.
                    </p>
                </div>

                <div class="announcement-card">
                    <span class="announcement-tag">Layanan</span>
                    <h3>Layanan Informasi E-Hibah</h3>
                    <p>
                        Pengguna dapat mengakses panduan dan
                        informasi layanan melalui pengelola E-Hibah.
                    </p>
                </div>

            </div>

            <div class="info-note">
                <i class="bi bi-info-circle-fill"></i>
                <span>
                    Konten pengumuman ini merupakan contoh sementara
                    dan dapat dihubungkan dengan database pengumuman.
                </span>
            </div>

        </div>
    </section>

    <!-- KEUNGGULAN -->
    <section class="section advantages-section">
        <div class="container">

            <div class="section-heading">
                <span class="mini-title">Keunggulan Sistem</span>
                <h2>Pengelolaan Hibah Lebih Terstruktur</h2>
                <p>
                    Mendukung tata kelola hibah yang lebih efektif
                    dan terdokumentasi.
                </p>
            </div>

            <div class="advantages-grid">

                <div class="advantage-item">
                    <i class="bi bi-check-circle-fill"></i>
                    <div>
                        <h3>Proses Terintegrasi</h3>
                        <p>
                            Menghubungkan tahapan pengajuan,
                            verifikasi, review, hingga pelaporan.
                        </p>
                    </div>
                </div>

                <div class="advantage-item">
                    <i class="bi bi-shield-check"></i>
                    <div>
                        <h3>Pengelolaan Terstruktur</h3>
                        <p>
                            Mendukung pengelolaan data dan dokumen
                            hibah secara terorganisasi.
                        </p>
                    </div>
                </div>

                <div class="advantage-item">
                    <i class="bi bi-bell"></i>
                    <div>
                        <h3>Notifikasi Informasi</h3>
                        <p>
                            Membantu pengguna memperoleh informasi
                            terkait aktivitas dan status proposal.
                        </p>
                    </div>
                </div>

                <div class="advantage-item">
                    <i class="bi bi-graph-up-arrow"></i>
                    <div>
                        <h3>Monitoring dan Evaluasi</h3>
                        <p>
                            Mempermudah pemantauan pelaksanaan
                            serta perkembangan kegiatan hibah.
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="cta">
        <div class="container">
            <h2>Siap Mengembangkan Riset Anda?</h2>
            <p>
                Mulai proses pengajuan dan pengelolaan hibah
                melalui sistem E-Hibah Universitas YARSI.
            </p>

            <a href="{{ route('login') }}" class="btn-white">
                Masuk ke E-Hibah
                <i class="bi bi-arrow-right"></i>
            </a>
        </div>
    </section>

    <!-- FOOTER / TIM PENGEMBANG -->

<!-- FOOTER -->
<footer class="footer">
    <div class="container">

        <div class="footer-main">

            <!-- BRAND -->
            <div class="footer-brand">
                <img
                    src="{{ asset('image/logo-e-hibah.png') }}"
                    alt="Logo E-Hibah YARSI"
                    class="footer-logo"
                >

                <div class="footer-brand-info">
                    <h3>E-Hibah YARSI</h3>
                    <p>
                        Sistem Informasi Pengelolaan Hibah
                        Penelitian dan Pengabdian
                    </p>
                </div>
            </div>

            <!-- ALAMAT -->
            <div class="footer-contact">
                <i class="bi bi-geo-alt-fill"></i>
                <div>
                    <strong>Lokasi</strong>
                    <span>Universitas YARSI</span>
                    <span>Jakarta, Indonesia</span>
                </div>
            </div>

            <!-- EMAIL -->
            <div class="footer-contact">
                <i class="bi bi-envelope-fill"></i>
                <div>
                    <strong>Informasi</strong>
                    <span>Informasi dan layanan</span>
                    <span>E-Hibah Universitas YARSI</span>
                </div>
            </div>

            <!-- WEBSITE -->
            <div class="footer-contact">
                <i class="bi bi-globe"></i>
                <div>
                    <strong>Portal</strong>
                    <span>Universitas YARSI</span>
                    <span>Portal Informasi Hibah</span>
                </div>
            </div>

        </div>

        <!-- COPYRIGHT -->
        <div class="footer-bottom">
            <p>
                &copy; {{ date('Y') }} E-Hibah Universitas YARSI.
                All Rights Reserved.
            </p>

            <div class="footer-links">
                <a href="#dokumen">Panduan</a>
                <a href="#pengumuman">FAQ</a>
                <a href="mailto:lppm@yarsi.ac.id">Kontak</a>
            </div>
        </div>

    </div>
</footer>


    <!-- JAVASCRIPT ANIMASI -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            const revealElements = document.querySelectorAll(
                '.section-heading, .feature-card, .scheme-card, ' +
                '.flow-item, .announcement-card, .advantage-item, ' +
                '.info-note, .cta .container, .footer-grid'
            );

            revealElements.forEach((element, index) => {
                element.classList.add('reveal');

                if (
                    element.classList.contains('feature-card') ||
                    element.classList.contains('scheme-card') ||
                    element.classList.contains('announcement-card') ||
                    element.classList.contains('flow-item') ||
                    element.classList.contains('advantage-item')
                ) {
                    element.style.transitionDelay =
                        `${(index % 3) * 100}ms`;
                }
            });

            if ('IntersectionObserver' in window) {

                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('visible');
                            observer.unobserve(entry.target);
                        }
                    });
                }, {
                    threshold: 0.12,
                    rootMargin: '0px 0px -40px 0px'
                });

                revealElements.forEach(element => {
                    observer.observe(element);
                });

            } else {
                revealElements.forEach(element => {
                    element.classList.add('visible');
                });
            }

            // Smooth scroll navigasi internal
            document.querySelectorAll('a[href^="#"]').forEach(link => {
                link.addEventListener('click', function (event) {
                    const targetId = this.getAttribute('href');

                    if (!targetId || targetId === '#') return;

                    const target = document.querySelector(targetId);

                    if (target) {
                        event.preventDefault();

                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });

        });
    </script>
</body>
</html>
