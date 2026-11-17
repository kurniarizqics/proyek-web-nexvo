<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Nexvo Finance</title>
    <!-- Google Fonts: Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #ffffff;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            width: 100%;
            max-width: 440px;
            text-align: center;
        }

        .logo-wrapper {
            margin-bottom: 18px;
            display: flex;
            justify-content: center;
        }

        .welcome-title {
            font-size: 28px;
            font-weight: 700;
            color: #141414;
            margin-bottom: 6px;
            letter-spacing: -0.5px;
        }

        .gradient-text {
            background: linear-gradient(90deg, #E244C5 0%, #92C86A 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .subtitle {
            font-size: 13.5px;
            color: #2b2b2b;
            font-weight: 400;
            margin-bottom: 32px;
        }

        .alert-error {
            background-color: #fee2e2;
            color: #b91c1c;
            padding: 10px 18px;
            border-radius: 50px;
            font-size: 13px;
            margin-bottom: 18px;
            border: 1px solid #fecaca;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .pill-input {
            width: 100%;
            padding: 16px 28px;
            border-radius: 50px;
            border: 1px solid #9ca3af;
            font-size: 14px;
            color: #141414;
            outline: none;
            transition: all 0.2s ease;
        }

        .pill-input::placeholder {
            color: #9ca3af;
        }

        .pill-input:focus {
            border-color: #141414;
            box-shadow: 0 0 0 3px rgba(20, 20, 20, 0.08);
        }

        .pill-button {
            width: 100%;
            padding: 16px 28px;
            border-radius: 50px;
            border: none;
            background-color: #141414;
            color: #ffffff;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            margin-top: 6px;
            transition: background-color 0.2s ease, transform 0.1s ease;
        }

        .pill-button:hover {
            background-color: #2b2b2b;
        }

        .pill-button:active {
            transform: scale(0.99);
        }

        /* Bantuan klik cepat akun demo (opsional & rapi untuk presentasi) */
        .demo-helper {
            margin-top: 32px;
            padding-top: 16px;
            border-top: 1px dashed #e5e7eb;
            font-size: 12px;
            color: #6b7280;
        }

        .demo-badges {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 6px;
            margin-top: 8px;
        }

        .demo-badge {
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 11px;
            color: #374151;
            cursor: pointer;
            transition: all 0.15s;
        }

        .demo-badge:hover {
            background: #141414;
            color: #ffffff;
            border-color: #141414;
        }
    </style>
</head>
<body>

<div class="login-container">
    <!-- Logo Nexvo -->
    <div class="logo-wrapper">
        <svg width="72" height="84" viewBox="0 0 100 120" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M22 108C14 82 22 45 45 20L66 56C82 38 86 18 82 6C92 26 88 58 63 82L43 46C26 66 20 90 22 108Z" fill="#141414"/>
        </svg>
    </div>

    <!-- Judul & Subjudul -->
    <h1 class="welcome-title">
        <span class="gradient-text">Welcome</span> Nexvo Team
    </h1>
    <p class="subtitle">Log in to oversee your team's financial performance</p>

    <!-- Pesan Error Validasi -->
    @if($errors->any())
        <div class="alert-error">
            {{ $errors->first() }}
        </div>
    @endif

    <!-- Form Login -->
    <form action="{{ url('/login') }}" method="POST">
        @csrf
        <div class="form-group">
            <input type="email" name="email" id="emailInput" class="pill-input" value="{{ old('email') }}" placeholder="Enter Username (Email)" required autofocus>
        </div>

        <div class="form-group">
            <input type="password" name="password" id="passwordInput" class="pill-input" placeholder="Enter Password" required>
        </div>

        <button type="submit" class="pill-button">Login</button>
    </form>

    <!-- Quick-fill untuk memudahkan demo ke dosen -->
    <div class="demo-helper">
        <span>Quick Fill Akun Demo (Klik untuk mengisi):</span>
        <div class="demo-badges">
            <button type="button" class="demo-badge" onclick="fillDemo('ketua@nexvo.com')">Ketua</button>
            <button type="button" class="demo-badge" onclick="fillDemo('bendahara@nexvo.com')">Bendahara</button>
            <button type="button" class="demo-badge" onclick="fillDemo('koordiv@nexvo.com')">Koordiv</button>
            <button type="button" class="demo-badge" onclick="fillDemo('admin@nexvo.com')">Admin</button>
        </div>
    </div>
</div>

<script>
    function fillDemo(email) {
        document.getElementById('emailInput').value = email;
        document.getElementById('passwordInput').value = 'password';
    }
</script>

</body>
</html>