<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Nexvo Finance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center min-vh-100">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <h3 class="fw-bold text-primary">Nexvo Finance</h3>
                        <p class="text-muted small">Sistem Manajemen Keuangan & Kas Organisasi</p>
                    </div>

                    @if($errors->any())
                        <div class="alert alert-danger py-2 small">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <form action="{{ url('/login') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}" placeholder="contoh: bendahara@nexvo.com" required autofocus>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" name="password" id="password" class="form-control" placeholder="Masukkan password" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 fw-semibold">Masuk</button>
                    </form>

                    <hr class="my-4">
                    <div class="small text-muted">
                        <p class="mb-1 fw-semibold">Akun Demo (Password: <code>password</code>):</p>
                        <ul class="mb-0 ps-3">
                            <li>Ketua: <code>ketua@nexvo.com</code></li>
                            <li>Bendahara: <code>bendahara@nexvo.com</code></li>
                            <li>Koordiv: <code>koordiv@nexvo.com</code></li>
                            <li>Admin: <code>admin@nexvo.com</code></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>