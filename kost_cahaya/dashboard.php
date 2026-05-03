<?php
session_start();
include "service/database.php";

// Proteksi login
if (!isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit;
}

// Logout
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

$id_user = $_SESSION['id_user'];

// Ambil data booking user beserta status pembayaran
$query = "SELECT booking.*, kamar.nama_kamar,
                 pembayaran.status_verifikasi, pembayaran.id_pembayaran
          FROM booking
          JOIN kamar ON booking.id_kamar = kamar.id_kamar
          LEFT JOIN pembayaran ON pembayaran.id_booking = booking.id_booking
          WHERE booking.id_user = '$id_user'
          ORDER BY booking.id_booking DESC";
$result = mysqli_query($db, $query);

// Hitung ringkasan
$total_booking  = mysqli_num_rows($result);
$q_diterima     = mysqli_query($db, "SELECT COUNT(*) as c FROM booking WHERE id_user='$id_user' AND status='diterima'");
$q_pending      = mysqli_query($db, "SELECT COUNT(*) as c FROM booking WHERE id_user='$id_user' AND status='pending'");
$total_diterima = mysqli_fetch_assoc($q_diterima)['c'];
$total_pending  = mysqli_fetch_assoc($q_pending)['c'];

mysqli_data_seek($result, 0);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Kost Cahaya</title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg:       #0f1117;
            --surface:  #181c27;
            --card:     #1e2333;
            --border:   #2a3050;
            --accent:   #f0a500;
            --accent2:  #e06c00;
            --text:     #e8eaf0;
            --muted:    #7a82a0;
            --green:    #2ecc71;
            --red:      #e74c3c;
            --yellow:   #f0a500;
            --radius:   12px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Sora', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }

        /* ── TOPBAR ── */
        .topbar {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 0 32px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .topbar-brand {
            font-size: 1.1rem;
            font-weight: 700;
            letter-spacing: -0.03em;
            color: var(--accent);
        }
        .topbar-brand span { color: var(--text); }
        .topbar-right { display: flex; align-items: center; gap: 8px; }
        .topbar-nav a {
            color: var(--muted);
            text-decoration: none;
            font-size: 0.82rem;
            font-weight: 500;
            padding: 6px 14px;
            border-radius: 8px;
            transition: all .2s;
        }
        .topbar-nav a:hover, .topbar-nav a.active {
            color: var(--text);
            background: var(--border);
        }

        /* ── WRAPPER ── */
        .wrapper {
            max-width: 1100px;
            margin: 0 auto;
            padding: 36px 24px 60px;
        }

        /* ── GREETING ── */
        .greeting { margin-bottom: 28px; }
        .greeting h1 {
            font-size: 1.6rem;
            font-weight: 700;
            letter-spacing: -0.03em;
            margin-bottom: 4px;
        }
        .greeting h1 span { color: var(--accent); }
        .greeting p { color: var(--muted); font-size: 0.88rem; }

        /* ── STAT CARDS ── */
        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 32px;
        }
        .stat-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 20px 22px;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .stat-icon {
            width: 44px; height: 44px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem; flex-shrink: 0;
        }
        .stat-icon.all  { background: rgba(240,165,0,.12); }
        .stat-icon.ok   { background: rgba(46,204,113,.12); }
        .stat-icon.wait { background: rgba(240,165,0,.12); }
        .stat-num {
            font-family: 'JetBrains Mono', monospace;
            font-size: 1.6rem; font-weight: 500; line-height: 1;
        }
        .stat-num.all  { color: var(--text); }
        .stat-num.ok   { color: var(--green); }
        .stat-num.wait { color: var(--yellow); }
        .stat-label { font-size: 0.78rem; color: var(--muted); font-weight: 500; margin-top: 4px; }

        /* ── SECTION HEADER ── */
        .section-header {
            display: flex; align-items: center;
            justify-content: space-between; margin-bottom: 16px;
        }
        .section-title {
            font-size: 1rem; font-weight: 600;
            display: flex; align-items: center; gap: 10px;
        }
        .section-title::before {
            content: ''; display: block;
            width: 4px; height: 18px;
            background: var(--accent); border-radius: 2px;
        }

        /* ── BUTTONS ── */
        .btn-cta {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 18px;
            background: var(--accent); color: #000;
            font-family: 'Sora', sans-serif;
            font-size: 0.85rem; font-weight: 700;
            border-radius: 8px; text-decoration: none;
            transition: background .2s;
        }
        .btn-cta:hover { background: var(--accent2); color: #000; }

        .btn-upload {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 6px 14px;
            background: rgba(46,204,113,.12);
            color: var(--green);
            border: 1px solid rgba(46,204,113,.25);
            font-family: 'Sora', sans-serif;
            font-size: 0.78rem; font-weight: 600;
            border-radius: 7px; text-decoration: none;
            transition: all .2s; white-space: nowrap;
        }
        .btn-upload:hover { background: rgba(46,204,113,.22); color: var(--green); }

        .btn-upload-ulang {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 6px 14px;
            background: rgba(240,165,0,.1);
            color: var(--yellow);
            border: 1px solid rgba(240,165,0,.2);
            font-family: 'Sora', sans-serif;
            font-size: 0.78rem; font-weight: 600;
            border-radius: 7px; text-decoration: none;
            transition: all .2s; white-space: nowrap;
        }
        .btn-upload-ulang:hover { background: rgba(240,165,0,.2); color: var(--yellow); }

        /* ── TABLE ── */
        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
        }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
        th {
            text-align: left; padding: 13px 16px;
            font-size: 0.72rem; text-transform: uppercase;
            letter-spacing: 0.07em; color: var(--muted);
            border-bottom: 1px solid var(--border); white-space: nowrap;
        }
        td {
            padding: 15px 16px;
            border-bottom: 1px solid rgba(42,48,80,.45);
            vertical-align: middle;
        }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: rgba(255,255,255,.02); }

        .harga-mono {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.85rem; color: var(--accent);
        }

        /* ── STATUS BADGE ── */
        .badge {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 4px 11px; border-radius: 20px;
            font-size: 0.74rem; font-weight: 600; white-space: nowrap;
        }
        .badge::before {
            content: ''; width: 6px; height: 6px;
            border-radius: 50%; background: currentColor;
        }
        .badge-pending  { background: rgba(240,165,0,.12);  color: var(--yellow); }
        .badge-diterima { background: rgba(46,204,113,.12); color: var(--green); }
        .badge-ditolak  { background: rgba(231,76,60,.12);  color: var(--red); }

        /* Status verifikasi pembayaran */
        .verif-badge {
            display: inline-block;
            padding: 3px 9px; border-radius: 12px;
            font-size: 0.7rem; font-weight: 600;
        }
        .verif-pending      { background: rgba(240,165,0,.1);  color: var(--yellow); }
        .verif-terverifikasi{ background: rgba(46,204,113,.1); color: var(--green); }
        .verif-ditolak      { background: rgba(231,76,60,.1);  color: var(--red); }

        /* ── EMPTY STATE ── */
        .empty-state {
            text-align: center; padding: 60px 20px; color: var(--muted);
        }
        .empty-state span { font-size: 3rem; display: block; margin-bottom: 12px; }
        .empty-state p { font-size: 0.88rem; margin-bottom: 16px; }

        /* ── LOGOUT ── */
        .btn-logout {
            padding: 6px 14px;
            background: rgba(231,76,60,.1);
            color: var(--red);
            border: 1px solid rgba(231,76,60,.2);
            border-radius: 8px;
            font-family: 'Sora', sans-serif;
            font-size: 0.82rem; font-weight: 600;
            cursor: pointer; transition: all .2s;
        }
        .btn-logout:hover { background: rgba(231,76,60,.2); }

        @media (max-width: 600px) {
            .stats { grid-template-columns: 1fr; }
            .topbar { padding: 0 16px; }
        }
    </style>
</head>
<body>

<!-- TOPBAR -->
<nav class="topbar">
    <div class="topbar-brand">Kost <span>Cahaya</span></div>
    <div class="topbar-right">
        <div class="topbar-nav">
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="kamar.php">Lihat Kamar</a>
        </div>
        <form method="POST" style="margin:0">
            <button type="submit" name="logout" class="btn-logout">Keluar</button>
        </form>
    </div>
</nav>

<!-- WRAPPER -->
<div class="wrapper">

    <!-- GREETING -->
    <div class="greeting">
        <h1>Selamat datang, <span><?= htmlspecialchars($_SESSION['nama']) ?></span> 👋</h1>
        <p>Pantau status booking dan riwayat sewa kamarmu di sini</p>
    </div>

    <!-- STAT CARDS -->
    <div class="stats">
        <div class="stat-card">
            <div class="stat-icon all">📋</div>
            <div>
                <div class="stat-num all"><?= $total_booking ?></div>
                <div class="stat-label">Total Booking</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon ok">✅</div>
            <div>
                <div class="stat-num ok"><?= $total_diterima ?></div>
                <div class="stat-label">Diterima</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon wait">⏳</div>
            <div>
                <div class="stat-num wait"><?= $total_pending ?></div>
                <div class="stat-label">Menunggu Konfirmasi</div>
            </div>
        </div>
    </div>

    <!-- RIWAYAT BOOKING -->
    <div class="section-header">
        <div class="section-title">Riwayat Booking</div>
        <a href="kamar.php" class="btn-cta">+ Booking Baru</a>
    </div>

    <div class="card">
        <?php if ($total_booking == 0): ?>
            <div class="empty-state">
                <span>🏠</span>
                <p>Kamu belum punya riwayat booking.</p>
                <a href="kamar.php" class="btn-cta">Lihat Kamar Tersedia</a>
            </div>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nama Kamar</th>
                        <th>Tanggal Booking</th>
                        <th>Lama Sewa</th>
                        <th>Total Harga</th>
                        <th>Status Booking</th>
                        <th>Status Pembayaran</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $no = 1;
                while ($row = mysqli_fetch_assoc($result)):
                    $status_booking = $row['status'];
                    $status_verif   = $row['status_verifikasi']; // null jika belum ada
                ?>
                    <tr>
                        <td style="color:var(--muted)"><?= $no++ ?></td>
                        <td><strong><?= htmlspecialchars($row['nama_kamar']) ?></strong></td>
                        <td style="color:var(--muted)"><?= date('d M Y', strtotime($row['tanggal_booking'])) ?></td>
                        <td><?= $row['lama_sewa'] ?> bulan</td>
                        <td class="harga-mono">Rp <?= number_format($row['total_harga'], 0, ',', '.') ?></td>

                        <!-- Status Booking -->
                        <td>
                            <?php
                            $cls = match($status_booking) {
                                'diterima' => 'badge-diterima',
                                'ditolak'  => 'badge-ditolak',
                                default    => 'badge-pending',
                            };
                            $label = match($status_booking) {
                                'diterima' => 'Diterima',
                                'ditolak'  => 'Ditolak',
                                default    => 'Pending',
                            };
                            ?>
                            <span class="badge <?= $cls ?>"><?= $label ?></span>
                        </td>

                        <!-- Status Pembayaran -->
                        <td>
                            <?php if ($status_booking !== 'diterima'): ?>
                                <span style="color:var(--muted);font-size:.78rem;">—</span>
                            <?php elseif (!$status_verif): ?>
                                <span class="verif-badge verif-pending">Belum Upload</span>
                            <?php else: ?>
                                <?php
                                $vLabel = match($status_verif) {
                                    'diterima' => 'Terverifikasi',
                                    'ditolak'  => 'Ditolak',
                                    default    => 'Menunggu Verifikasi',
                                };
                                $vClass = match($status_verif) {
                                    'diterima' => 'verif-terverifikasi',
                                    'ditolak'  => 'verif-ditolak',
                                    default    => 'verif-pending',
                                };
                                ?>
                                <span class="verif-badge <?= $vClass ?>"><?= $vLabel ?></span>
                            <?php endif; ?>
                        </td>

                        <!-- Aksi -->
                        <td>
                            <?php if ($status_booking === 'diterima'): ?>
                                <?php if (!$status_verif || $status_verif === 'ditolak'): ?>
                                    <!-- Belum upload atau ditolak → tombol upload -->
                                    <a href="pembayaran.php?id_booking=<?= $row['id_booking'] ?>" class="btn-upload">
                                        📎 Upload Bukti
                                    </a>
                                <?php elseif ($status_verif === 'pending'): ?>
                                    <!-- Sudah upload, menunggu verifikasi → bisa kirim ulang -->
                                    <a href="pembayaran.php?id_booking=<?= $row['id_booking'] ?>" class="btn-upload-ulang">
                                        🔄 Kirim Ulang
                                    </a>
                                <?php else: ?>
                                    <!-- Sudah terverifikasi -->
                                    <span style="color:var(--green);font-size:.8rem;font-weight:600;">✅ Lunas</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color:var(--muted);font-size:.78rem;">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

</div>
</body>
</html>
