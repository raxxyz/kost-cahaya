<?php
session_start();
include "service/database.php";

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id_kamar = (int) $_GET['id'];
$res      = mysqli_query($db, "SELECT * FROM kamar WHERE id_kamar='$id_kamar'");
$kamar    = mysqli_fetch_assoc($res);

if (!$kamar) {
    header("Location: index.php");
    exit;
}

// Ambil semua foto kamar
$res_foto  = mysqli_query($db, "SELECT * FROM gambar_kamar WHERE id_kamar = $id_kamar ORDER BY is_utama DESC, id_gambar ASC");
$foto_list = [];
while ($f = mysqli_fetch_assoc($res_foto)) {
    $foto_list[] = $f;
}

$tersedia       = $kamar['status'] === 'tersedia';
$fasilitas_list = array_map('trim', explode(',', $kamar['fasilitas']));
$sudah_login    = isset($_SESSION['logged_in']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($kamar['nama_kamar']) ?> — Kost Cahaya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sora', sans-serif; background: #f8f9fc; }

        .navbar { background: #0f1117 !important; border-bottom: 1px solid #2a3050; }
        .navbar-brand { color: #f0a500 !important; font-weight: 700; letter-spacing: -0.03em; }
        .navbar-brand span { color: #e8eaf0; }
        .nav-link { color: #7a82a0 !important; font-size: .875rem; font-weight: 500; }
        .nav-link:hover { color: #e8eaf0 !important; }
        .btn-login-nav { background: #f0a500; color: #000 !important; font-weight: 700; border-radius: 8px; padding: 6px 18px; font-size: .85rem; }
        .btn-login-nav:hover { background: #e06c00 !important; }

        .breadcrumb-wrap { background: #fff; border-bottom: 1px solid #e5e8f0; padding: 12px 0; }
        .breadcrumb { margin: 0; font-size: .82rem; }
        .breadcrumb-item a { color: #f0a500; text-decoration: none; }
        .breadcrumb-item.active { color: #7a82a0; }

        .main-wrap { max-width: 960px; margin: 36px auto 60px; padding: 0 20px; }

        .detail-card { background: #fff; border: 1px solid #e5e8f0; border-radius: 16px; overflow: hidden; box-shadow: 0 2px 16px rgba(0,0,0,.07); margin-bottom: 20px; }

        /* ── GALERI ── */
        .galeri-main {
            position: relative;
            height: 320px;
            background: #0f1117;
            overflow: hidden;
        }
        .galeri-main img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .galeri-main .no-foto {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 5rem;
        }
        .status-pill { position: absolute; top: 16px; right: 16px; padding: 5px 14px; border-radius: 20px; font-size: .78rem; font-weight: 700; }
        .pill-tersedia { background: rgba(46,204,113,.2); color: #2ecc71; border: 1px solid rgba(46,204,113,.3); }
        .pill-tidak    { background: rgba(231,76,60,.15);  color: #e74c3c; border: 1px solid rgba(231,76,60,.25); }

        /* Thumbnail strip */
        .galeri-strip {
            display: flex;
            gap: 8px;
            padding: 12px 16px;
            background: #f8f9fc;
            border-bottom: 1px solid #e5e8f0;
            overflow-x: auto;
        }
        .galeri-strip::-webkit-scrollbar { height: 4px; }
        .galeri-strip::-webkit-scrollbar-thumb { background: #d0d5e8; border-radius: 2px; }
        .galeri-thumb {
            width: 64px;
            height: 52px;
            object-fit: cover;
            border-radius: 6px;
            cursor: pointer;
            border: 2px solid transparent;
            transition: border-color .2s, opacity .2s;
            flex-shrink: 0;
            opacity: .7;
        }
        .galeri-thumb.active, .galeri-thumb:hover { border-color: #f0a500; opacity: 1; }
        .foto-count { font-size: .75rem; color: #7a82a0; padding: 14px 16px 0; }

        /* BODY */
        .card-body-custom { padding: 28px 32px; }
        .kamar-nama { font-size: 1.6rem; font-weight: 700; letter-spacing: -0.03em; color: #0f1117; margin-bottom: 6px; }
        .kamar-harga { font-family: 'JetBrains Mono', monospace; font-size: 1.3rem; font-weight: 500; color: #f0a500; margin-bottom: 24px; }
        .kamar-harga span { font-family: 'Sora', sans-serif; font-size: .8rem; color: #b0b8d0; }

        .info-section { margin-bottom: 24px; }
        .info-label { font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .07em; color: #b0b8d0; margin-bottom: 10px; }
        .info-text { font-size: .92rem; color: #4a5068; line-height: 1.7; }

        .chip-wrap { display: flex; flex-wrap: wrap; gap: 8px; }
        .chip { padding: 5px 14px; background: #fff8e6; color: #b07800; border: 1px solid #f5dfa0; border-radius: 20px; font-size: .8rem; font-weight: 600; }

        /* BOOKING CARD */
        .booking-card { background: #0f1117; border-radius: 16px; padding: 28px; color: #e8eaf0; position: sticky; top: 80px; }
        .booking-card .harga-big { font-family: 'JetBrains Mono', monospace; font-size: 1.5rem; color: #f0a500; margin-bottom: 4px; }
        .booking-card .harga-label { font-size: .78rem; color: #7a82a0; margin-bottom: 20px; }
        .booking-card hr { border-color: #2a3050; margin: 18px 0; }
        .booking-row { display: flex; justify-content: space-between; font-size: .83rem; margin-bottom: 8px; }
        .booking-row span:first-child { color: #7a82a0; }
        .booking-row span:last-child { font-weight: 600; }

        .btn-booking-cta { display: block; width: 100%; padding: 13px; background: #f0a500; color: #000; font-weight: 700; font-family: 'Sora', sans-serif; font-size: .95rem; border: none; border-radius: 10px; text-align: center; text-decoration: none; transition: background .2s; cursor: pointer; }
        .btn-booking-cta:hover { background: #e06c00; color: #000; }
        .btn-booking-disabled { display: block; width: 100%; padding: 13px; background: #2a3050; color: #7a82a0; font-weight: 700; font-family: 'Sora', sans-serif; font-size: .95rem; border: none; border-radius: 10px; text-align: center; cursor: not-allowed; }

        .info-login { background: rgba(240,165,0,.1); border: 1px solid rgba(240,165,0,.25); border-radius: 8px; padding: 12px 14px; font-size: .8rem; color: #f0a500; text-align: center; margin-top: 12px; line-height: 1.5; }
        .info-login a { color: #f0a500; font-weight: 700; }

        footer { background: #0f1117; color: #4a5270; text-align: center; padding: 20px; font-size: .8rem; border-top: 1px solid #2a3050; }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="index.php">Kost <span>Cahaya</span></a>
        <div class="ms-auto d-flex align-items-center gap-2">
            <?php if ($sudah_login): ?>
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <a href="kamar.php" class="nav-link">Kamar</a>
                <a href="logout.php" class="nav-link">Keluar</a>
            <?php else: ?>
                <a href="login.php" class="nav-link">Masuk</a>
                <a href="register.php" class="btn-login-nav nav-link">Daftar</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="breadcrumb-wrap">
    <div class="container">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Beranda</a></li>
            <li class="breadcrumb-item"><a href="<?= $sudah_login ? 'kamar.php' : 'index.php' ?>">Kamar</a></li>
            <li class="breadcrumb-item active"><?= htmlspecialchars($kamar['nama_kamar']) ?></li>
        </ol>
    </div>
</div>

<main class="flex-grow-1">
    <div class="main-wrap">
        <div class="row g-4 align-items-start">

            <!-- KIRI: DETAIL -->
            <div class="col-lg-7">
                <div class="detail-card">

                    <!-- Galeri Foto -->
                    <div class="galeri-main">
                        <?php if (!empty($foto_list)): ?>
                            <img src="uploads/kamar/<?= htmlspecialchars($foto_list[0]['nama_file']) ?>"
                                 alt="<?= htmlspecialchars($kamar['nama_kamar']) ?>"
                                 id="fotoUtama">
                        <?php else: ?>
                            <div class="no-foto">🛏️</div>
                        <?php endif; ?>
                        <span class="status-pill <?= $tersedia ? 'pill-tersedia' : 'pill-tidak' ?>">
                            <?= $tersedia ? '✓ Tersedia' : '✗ Tidak Tersedia' ?>
                        </span>
                    </div>

                    <!-- Strip thumbnail (tampil kalau ada lebih dari 1 foto) -->
                    <?php if (count($foto_list) > 1): ?>
                    <div class="foto-count">📷 <?= count($foto_list) ?> foto</div>
                    <div class="galeri-strip">
                        <?php foreach ($foto_list as $i => $foto): ?>
                            <img src="uploads/kamar/<?= htmlspecialchars($foto['nama_file']) ?>"
                                 class="galeri-thumb <?= $i === 0 ? 'active' : '' ?>"
                                 onclick="gantiFoto(this, 'uploads/kamar/<?= htmlspecialchars($foto['nama_file']) ?>')"
                                 alt="Foto <?= $i + 1 ?>">
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Body -->
                    <div class="card-body-custom">
                        <div class="kamar-nama"><?= htmlspecialchars($kamar['nama_kamar']) ?></div>
                        <div class="kamar-harga">
                            Rp <?= number_format($kamar['harga'], 0, ',', '.') ?>
                            <span>/ bulan</span>
                        </div>

                        <?php if ($kamar['deskripsi']): ?>
                        <div class="info-section">
                            <div class="info-label">Deskripsi</div>
                            <div class="info-text"><?= nl2br(htmlspecialchars($kamar['deskripsi'])) ?></div>
                        </div>
                        <?php endif; ?>

                        <?php if ($kamar['fasilitas']): ?>
                        <div class="info-section">
                            <div class="info-label">Fasilitas</div>
                            <div class="chip-wrap">
                                <?php foreach ($fasilitas_list as $f): ?>
                                    <?php if ($f): ?><span class="chip"><?= htmlspecialchars($f) ?></span><?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- KANAN: BOOKING BOX -->
            <div class="col-lg-5">
                <div class="booking-card">
                    <div class="harga-big">Rp <?= number_format($kamar['harga'], 0, ',', '.') ?></div>
                    <div class="harga-label">per bulan</div>
                    <div class="booking-row">
                        <span>Status</span>
                        <span style="color: <?= $tersedia ? '#2ecc71' : '#e74c3c' ?>">
                            <?= $tersedia ? '✓ Tersedia' : '✗ Tidak Tersedia' ?>
                        </span>
                    </div>
                    <div class="booking-row">
                        <span>Tipe Pembayaran</span>
                        <span>Per Bulan</span>
                    </div>
                    <hr>

                    <?php if (!$tersedia): ?>
                        <div class="btn-booking-disabled">Kamar Tidak Tersedia</div>
                    <?php elseif ($sudah_login): ?>
                        <a href="booking.php?id=<?= $kamar['id_kamar'] ?>" class="btn-booking-cta">
                            Booking Sekarang →
                        </a>
                    <?php else: ?>
                        <a href="login.php?redirect=detail_kamar.php?id=<?= $kamar['id_kamar'] ?>" class="btn-booking-cta">
                            Masuk untuk Booking →
                        </a>
                        <div class="info-login">
                            Belum punya akun? <a href="register.php">Daftar gratis</a> sekarang.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</main>

<footer>&copy; <?= date('Y') ?> Kost Cahaya. All rights reserved.</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function gantiFoto(thumb, src) {
        document.getElementById('fotoUtama').src = src;
        document.querySelectorAll('.galeri-thumb').forEach(t => t.classList.remove('active'));
        thumb.classList.add('active');
    }
</script>
</body>
</html>
