<?php
session_start();
include "service/database.php";

$search = isset($_GET['search']) ? mysqli_real_escape_string($db, $_GET['search']) : '';

if ($search) {
    $query = "SELECT * FROM kamar WHERE nama_kamar LIKE '%$search%' OR fasilitas LIKE '%$search%' ORDER BY status ASC";
} else {
    $query = "SELECT * FROM kamar ORDER BY status ASC, id_kamar ASC";
}
$result = mysqli_query($db, $query);
$total  = mysqli_num_rows($result);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kost Cahaya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sora', sans-serif; background: #f8f9fc; }

        /* NAVBAR */
        .navbar { background: #0f1117 !important; border-bottom: 1px solid #2a3050; }
        .navbar-brand { color: #f0a500 !important; font-weight: 700; letter-spacing: -0.03em; }
        .navbar-brand span { color: #e8eaf0; }
        .nav-link { color: #7a82a0 !important; font-size: .875rem; font-weight: 500; }
        .nav-link:hover { color: #e8eaf0 !important; }
        .btn-login {
            background: #f0a500;
            color: #000 !important;
            font-weight: 700;
            border-radius: 8px;
            padding: 6px 18px;
            font-size: .85rem;
        }
        .btn-login:hover { background: #e06c00; }

        /* HERO */
        .hero {
            background: linear-gradient(135deg, #0f1117 0%, #1a1f30 60%, #1e2333 100%);
            color: #e8eaf0;
            padding: 72px 0 56px;
            text-align: center;
        }
        .hero h1 {
            font-size: 2.4rem;
            font-weight: 700;
            letter-spacing: -0.04em;
            margin-bottom: 12px;
        }
        .hero h1 span { color: #f0a500; }
        .hero p { color: #7a82a0; font-size: .95rem; max-width: 480px; margin: 0 auto 28px; }

        /* SEARCH */
        .search-wrap { max-width: 460px; margin: 0 auto; }
        .search-wrap .form-control {
            background: #1e2333;
            border: 1px solid #2a3050;
            color: #e8eaf0;
            border-radius: 10px 0 0 10px;
            padding: 11px 16px;
            font-family: 'Sora', sans-serif;
        }
        .search-wrap .form-control::placeholder { color: #4a5270; }
        .search-wrap .form-control:focus { border-color: #f0a500; box-shadow: none; background: #1e2333; color: #e8eaf0; }
        .btn-cari {
            background: #f0a500;
            color: #000;
            font-weight: 700;
            border: none;
            border-radius: 0 10px 10px 0;
            padding: 11px 20px;
            font-family: 'Sora', sans-serif;
        }
        .btn-cari:hover { background: #e06c00; }

        /* MAIN */
        .main-wrap { max-width: 1100px; margin: 0 auto; padding: 36px 20px 60px; }

        /* HASIL INFO */
        .result-info {
            font-size: .83rem;
            color: #7a82a0;
            margin-bottom: 16px;
        }
        .result-info strong { color: #0f1117; }

        /* BADGE STATUS */
        .badge-tersedia { background: #d1f5e0; color: #1a7a3e; font-weight: 600; }
        .badge-tidak    { background: #fde8e8; color: #c0392b; font-weight: 600; }

        /* TABLE */
        .table-card {
            background: #fff;
            border: 1px solid #e5e8f0;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0,0,0,.06);
        }
        .table { margin: 0; font-size: .9rem; }
        .table thead th {
            background: #f3f5fb;
            color: #7a82a0;
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            border-bottom: 1px solid #e5e8f0;
            padding: 13px 16px;
            font-weight: 600;
        }
        .table tbody td { padding: 15px 16px; vertical-align: middle; border-color: #f0f2f8; }
        .table tbody tr:last-child td { border-bottom: none; }
        .table tbody tr:hover { background: #fafbff; }

        .nama-kamar { font-weight: 600; color: #0f1117; }
        .harga-text { font-weight: 700; color: #f0a500; font-size: .92rem; }

        .chip-wrap { display: flex; flex-wrap: wrap; gap: 5px; }
        .chip {
            padding: 2px 9px;
            background: #fff3d6;
            color: #b07800;
            border-radius: 20px;
            font-size: .72rem;
            font-weight: 500;
        }

        .btn-detail {
            background: #0f1117;
            color: #fff;
            border-radius: 8px;
            padding: 6px 14px;
            font-size: .8rem;
            font-weight: 600;
            text-decoration: none;
            transition: background .2s;
            white-space: nowrap;
        }
        .btn-detail:hover { background: #f0a500; color: #000; }

        /* EMPTY */
        .empty-state { text-align: center; padding: 52px 20px; color: #7a82a0; }
        .empty-state .icon { font-size: 3rem; margin-bottom: 12px; }

        /* FOOTER */
        footer {
            background: #0f1117;
            color: #4a5270;
            text-align: center;
            padding: 20px;
            font-size: .8rem;
            border-top: 1px solid #2a3050;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="index.php">Kost <span>Cahaya</span></a>
        <div class="ms-auto d-flex align-items-center gap-2">
            <?php if (isset($_SESSION['logged_in'])): ?>
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <a href="logout.php" class="nav-link">Keluar</a>
            <?php else: ?>
                <a href="login.php" class="nav-link">Masuk</a>
                <a href="register.php" class="btn-login">Daftar</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- HERO -->
<div class="hero">
    <h1>Selamat Datang di <span>Kost Cahaya</span></h1>
    <p>Temukan kamar yang nyaman, terjangkau, dan sesuai kebutuhanmu.</p>

    <form method="GET" class="search-wrap">
        <div class="input-group">
            <input type="text" name="search" class="form-control"
                   placeholder="Cari nama kamar atau fasilitas..."
                   value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn-cari">Cari</button>
        </div>
    </form>
</div>

<!-- MAIN -->
<main class="flex-grow-1">
    <div class="main-wrap">

        <div class="result-info">
            <?php if ($search): ?>
                Menampilkan <strong><?= $total ?> kamar</strong> untuk pencarian "<strong><?= htmlspecialchars($search) ?></strong>"
                &mdash; <a href="index.php" style="color:#f0a500;text-decoration:none">Hapus filter</a>
            <?php else: ?>
                Menampilkan <strong><?= $total ?> kamar</strong> tersedia
            <?php endif; ?>
        </div>

        <div class="table-card">
            <?php if ($total == 0): ?>
                <div class="empty-state">
                    <div class="icon">🏠</div>
                    <p>Tidak ada kamar yang ditemukan<?= $search ? " untuk \"$search\"" : '' ?>.</p>
                    <?php if ($search): ?>
                        <a href="index.php" style="color:#f0a500">Lihat semua kamar</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nama Kamar</th>
                            <th>Harga</th>
                            <th>Fasilitas</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $no = 1;
                    while ($row = mysqli_fetch_assoc($result)):
                        $fasilitas_list = array_map('trim', explode(',', $row['fasilitas']));
                        $tersedia = $row['status'] === 'tersedia';
                    ?>
                        <tr>
                            <td style="color:#b0b8d0"><?= $no++ ?></td>
                            <td class="nama-kamar"><?= htmlspecialchars($row['nama_kamar']) ?></td>
                            <td class="harga-text">Rp <?= number_format($row['harga'], 0, ',', '.') ?><span style="font-size:.75rem;color:#b0b8d0;font-weight:400">/bln</span></td>
                            <td>
                                <div class="chip-wrap">
                                    <?php foreach ($fasilitas_list as $f): ?>
                                        <?php if ($f): ?><span class="chip"><?= htmlspecialchars($f) ?></span><?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge <?= $tersedia ? 'badge-tersedia' : 'badge-tidak' ?>">
                                    <?= $tersedia ? 'Tersedia' : 'Tidak Tersedia' ?>
                                </span>
                            </td>
                            <td>
                                <a href="detail_kamar.php?id=<?= $row['id_kamar'] ?>" class="btn-detail">
                                    Lihat Detail →
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

    </div>
</main>

<!-- FOOTER -->
<footer>
    &copy; <?= date('Y') ?> Kost Cahaya. All rights reserved.
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
