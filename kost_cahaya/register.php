<?php
session_start();
include "service/database.php";

// Kalau sudah login, redirect
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: dashboard.php");
    exit();
}

$error   = "";
$sukses  = "";

if (isset($_POST['register'])) {
    $nama     = mysqli_real_escape_string($db, trim($_POST['nama']));
    $email    = mysqli_real_escape_string($db, trim($_POST['email']));
    $no_hp    = mysqli_real_escape_string($db, trim($_POST['no_hp']));
    $alamat   = mysqli_real_escape_string($db, trim($_POST['alamat']));
    $password = $_POST['password'];
    $konfirm  = $_POST['konfirm_password'];

    // ── Validasi ──
    if (empty($nama) || empty($email) || empty($password)) {
        $error = "Nama, email, dan password wajib diisi.";

    } elseif (strlen($password) < 4) {
        $error = "Password minimal 4 karakter.";

    } elseif ($password !== $konfirm) {
        $error = "Konfirmasi password tidak cocok.";

    } else {
        // Cek nama sudah dipakai
        $cek = mysqli_query($db, "SELECT id_user FROM user WHERE nama='$nama'");
        if (mysqli_num_rows($cek) > 0) {
            $error = "Username '$nama' sudah digunakan, coba yang lain.";
        } else {
            // Cek email sudah dipakai
            $cek_email = mysqli_query($db, "SELECT id_user FROM user WHERE email='$email'");
            if (mysqli_num_rows($cek_email) > 0) {
                $error = "Email sudah terdaftar.";
            } else {
                $sql = "INSERT INTO user (nama, email, no_hp, alamat, password)
                        VALUES ('$nama', '$email', '$no_hp', '$alamat', '$password')";

                if (mysqli_query($db, $sql)) {
                    $sukses = "Pendaftaran berhasil! Silakan masuk dengan akun kamu.";
                } else {
                    $error = "Pendaftaran gagal, coba lagi.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar — Kost Cahaya</title>
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
            padding: 32px 24px;
        }

        .brand {
            font-size: 1.4rem;
            font-weight: 700;
            letter-spacing: -0.03em;
            color: #f0a500;
            margin-bottom: 28px;
            text-align: center;
        }
        .brand span { color: #e8eaf0; }

        .register-card {
            background: #1e2333;
            border: 1px solid #2a3050;
            border-radius: 16px;
            padding: 36px 32px;
            width: 100%;
            max-width: 460px;
        }

        .card-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #e8eaf0;
            margin-bottom: 4px;
        }
        .card-sub {
            font-size: .83rem;
            color: #7a82a0;
            margin-bottom: 28px;
        }

        .form-label {
            font-size: .75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #7a82a0;
            margin-bottom: 7px;
        }
        .form-label .req { color: #f0a500; margin-left: 2px; }

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

        /* Divider section */
        .section-divider {
            font-size: .72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #3a4260;
            border-bottom: 1px solid #2a3050;
            padding-bottom: 8px;
            margin: 20px 0 16px;
        }

        /* Alert */
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
        .alert-sukses {
            background: rgba(46,204,113,.1);
            border: 1px solid rgba(46,204,113,.25);
            color: #2ecc71;
            border-radius: 9px;
            padding: 11px 14px;
            font-size: .85rem;
            font-weight: 500;
            margin-bottom: 20px;
        }

        .btn-daftar {
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
        .btn-daftar:hover { background: #e06c00; }

        .btn-ke-login {
            width: 100%;
            padding: 12px;
            background: #2a3050;
            color: #e8eaf0;
            font-family: 'Sora', sans-serif;
            font-size: .92rem;
            font-weight: 700;
            border: none;
            border-radius: 9px;
            cursor: pointer;
            transition: background .2s;
            text-align: center;
            text-decoration: none;
            display: block;
        }
        .btn-ke-login:hover { background: #333d6a; color: #e8eaf0; }

        .divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 20px 0;
            color: #3a4260;
            font-size: .78rem;
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #2a3050;
        }

        .login-link {
            text-align: center;
            font-size: .83rem;
            color: #7a82a0;
        }
        .login-link a {
            color: #f0a500;
            font-weight: 600;
            text-decoration: none;
        }
        .login-link a:hover { text-decoration: underline; }

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

        .opsional {
            font-size: .7rem;
            color: #4a5270;
            font-weight: 400;
            text-transform: none;
            letter-spacing: 0;
            margin-left: 4px;
        }
    </style>
</head>
<body>

    <div class="brand">Kost <span>Cahaya</span></div>

    <div class="register-card">
        <div class="card-title">Buat Akun Baru</div>
        <div class="card-sub">Daftar gratis dan mulai booking kamar</div>

        <?php if ($error): ?>
            <div class="alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($sukses): ?>
            <div class="alert-sukses">✓ <?= htmlspecialchars($sukses) ?></div>
            <a href="login.php" class="btn-ke-login">Masuk Sekarang →</a>
            <a href="index.php" class="back-link">← Kembali ke Beranda</a>

        <?php else: ?>

        <form method="POST" action="register.php">

            <div class="section-divider">Informasi Akun</div>

            <div class="mb-3">
                <label class="form-label">Username <span class="req">*</span></label>
                <input type="text" name="nama" class="form-control"
                       placeholder="Masukkan username"
                       value="<?= isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : '' ?>"
                       required autofocus>
            </div>

            <div class="mb-3">
                <label class="form-label">Email <span class="req">*</span></label>
                <input type="email" name="email" class="form-control"
                       placeholder="contoh@email.com"
                       value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                       required>
            </div>

            <div class="mb-3">
                <label class="form-label">Password <span class="req">*</span></label>
                <input type="password" name="password" class="form-control"
                       placeholder="Minimal 4 karakter"
                       required>
            </div>

            <div class="mb-3">
                <label class="form-label">Konfirmasi Password <span class="req">*</span></label>
                <input type="password" name="konfirm_password" class="form-control"
                       placeholder="Ulangi password"
                       required>
            </div>

            <div class="section-divider">Informasi Pribadi <span class="opsional">(opsional)</span></div>

            <div class="mb-3">
                <label class="form-label">No. HP</label>
                <input type="text" name="no_hp" class="form-control"
                       placeholder="cth: 08123456789"
                       value="<?= isset($_POST['no_hp']) ? htmlspecialchars($_POST['no_hp']) : '' ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Alamat</label>
                <textarea name="alamat" class="form-control" rows="2"
                          placeholder="Alamat asal kamu"><?= isset($_POST['alamat']) ? htmlspecialchars($_POST['alamat']) : '' ?></textarea>
            </div>

            <button type="submit" name="register" class="btn-daftar">Daftar Sekarang →</button>
        </form>

        <div class="divider">atau</div>

        <div class="login-link">
            Sudah punya akun? <a href="login.php">Masuk di sini</a>
        </div>

        <?php endif; ?>
    </div>

    <a href="index.php" class="back-link">← Kembali ke Beranda</a>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
