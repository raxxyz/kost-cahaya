<?php
session_start();
include "service/database.php";

// Kalau sudah login, langsung redirect sesuai role
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        header("Location: admin_kamar.php");
    } else {
        header("Location: dashboard.php");
    }
    exit();
}

$error   = "";
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '';

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($db, $_POST['username']);
    $password = $_POST['password'];

    // ── 1. Cek tabel admin dulu ──
    $sql_admin  = "SELECT * FROM admin WHERE username='$username' AND password='$password'";
    $res_admin  = mysqli_query($db, $sql_admin);

    if (mysqli_num_rows($res_admin) > 0) {
        $data = mysqli_fetch_assoc($res_admin);

        $_SESSION['logged_in']  = true;
        $_SESSION['role']       = 'admin';
        $_SESSION['id_admin']   = $data['id_admin'];
        $_SESSION['nama']       = $data['nama_admin'];

        header("Location: admin_kamar.php");
        exit();
    }

    // ── 2. Cek tabel user ──
    $sql_user = "SELECT * FROM user WHERE nama='$username' AND password='$password'";
    $res_user = mysqli_query($db, $sql_user);

    if (mysqli_num_rows($res_user) > 0) {
        $data = mysqli_fetch_assoc($res_user);

        $_SESSION['logged_in']  = true;
        $_SESSION['role']       = 'user';
        $_SESSION['id_user']    = $data['id_user'];
        $_SESSION['nama']       = $data['nama'];

        // Redirect ke halaman asal jika ada, atau dashboard
        $tujuan = !empty($redirect) ? $redirect : 'dashboard.php';
        header("Location: $tujuan");
        exit();

    } else {
        $error = "Username atau password salah.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — Kost Cahaya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Sora', sans-serif;
            background: #0f1117;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        /* LOGO */
        .brand {
            font-size: 1.4rem;
            font-weight: 700;
            letter-spacing: -0.03em;
            color: #f0a500;
            margin-bottom: 32px;
            text-align: center;
        }
        .brand span { color: #e8eaf0; }

        /* CARD */
        .login-card {
            background: #1e2333;
            border: 1px solid #2a3050;
            border-radius: 16px;
            padding: 36px 32px;
            width: 100%;
            max-width: 400px;
        }

        .card-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #e8eaf0;
            margin-bottom: 6px;
        }
        .card-sub {
            font-size: .83rem;
            color: #7a82a0;
            margin-bottom: 28px;
        }

        /* FORM */
        .form-label {
            font-size: .75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #7a82a0;
            margin-bottom: 7px;
        }
        .form-control {
            background: #0f1117;
            border: 1px solid #2a3050;
            color: #e8eaf0;
            font-family: 'Sora', sans-serif;
            font-size: .9rem;
            padding: 11px 14px;
            border-radius: 9px;
        }
        .form-control::placeholder { color: #3a4260; }
        .form-control:focus {
            background: #0f1117;
            border-color: #f0a500;
            color: #e8eaf0;
            box-shadow: 0 0 0 3px rgba(240,165,0,.12);
        }

        /* ERROR */
        .alert-error {
            background: rgba(231,76,60,.1);
            border: 1px solid rgba(231,76,60,.25);
            color: #e74c3c;
            border-radius: 9px;
            padding: 11px 14px;
            font-size: .85rem;
            font-weight: 500;
            margin-bottom: 20px;
        }

        /* BUTTON */
        .btn-masuk {
            width: 100%;
            padding: 12px;
            background: #f0a500;
            color: #000;
            font-family: 'Sora', sans-serif;
            font-size: .92rem;
            font-weight: 700;
            border: none;
            border-radius: 9px;
            cursor: pointer;
            transition: background .2s;
            margin-top: 6px;
        }
        .btn-masuk:hover { background: #e06c00; }

        /* DIVIDER */
        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 22px 0;
            color: #3a4260;
            font-size: .78rem;
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #2a3050;
        }

        /* REGISTER LINK */
        .register-link {
            text-align: center;
            font-size: .83rem;
            color: #7a82a0;
        }
        .register-link a {
            color: #f0a500;
            font-weight: 600;
            text-decoration: none;
        }
        .register-link a:hover { text-decoration: underline; }

        /* BACK */
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            font-size: .8rem;
            color: #4a5270;
            text-decoration: none;
            transition: color .2s;
        }
        .back-link:hover { color: #7a82a0; }
    </style>
</head>
<body>

    <div class="brand">Kost <span>Cahaya</span></div>

    <div class="login-card">
        <div class="card-title">Masuk ke Akun</div>
        <div class="card-sub">Silakan login untuk melanjutkan</div>

        <?php if ($error): ?>
            <div class="alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php<?= $redirect ? '?redirect='.urlencode($redirect) : '' ?>">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control"
                       placeholder="Masukkan username"
                       value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>"
                       required autofocus>
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control"
                       placeholder="Masukkan password"
                       required>
            </div>

            <button type="submit" name="login" class="btn-masuk">Masuk →</button>
        </form>

        <div class="divider">atau</div>

        <div class="register-link">
            Belum punya akun? <a href="register.php">Daftar sekarang</a>
        </div>
    </div>

    <a href="index.php" class="back-link">← Kembali ke Beranda</a>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
