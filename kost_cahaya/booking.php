<?php
session_start();
include "service/database.php";

// Proteksi login
if (!isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['id_user'];
$pesan   = "";

// Ambil id kamar dari URL
if (!isset($_GET['id'])) {
    header("Location: kamar.php");
    exit;
}

$id_kamar = (int) $_GET['id'];
$res_kamar = mysqli_query($db, "SELECT * FROM kamar WHERE id_kamar='$id_kamar'");
$kamar = mysqli_fetch_assoc($res_kamar);

// Kamar tidak ditemukan atau tidak tersedia
if (!$kamar || $kamar['status'] !== 'tersedia') {
    header("Location: kamar.php");
    exit;
}

// Cek apakah user sudah punya booking aktif di kamar ini
$cek = mysqli_query($db, "SELECT * FROM booking WHERE id_user='$id_user' AND id_kamar='$id_kamar' AND status='pending'");
$sudah_booking = mysqli_num_rows($cek) > 0;

// PROSES BOOKING
if (isset($_POST['booking']) && !$sudah_booking) {
    $tanggal_booking = date('Y-m-d');
    $lama_sewa       = (int) $_POST['lama_sewa'];
    $total_harga     = $kamar['harga'] * $lama_sewa;
    $status          = 'pending';

    if ($lama_sewa < 1) {
        $pesan = ["type" => "error", "teks" => "Lama sewa minimal 1 bulan."];
    } else {
        $sql = "INSERT INTO booking (id_user, id_kamar, tanggal_booking, lama_sewa, total_harga, status)
                VALUES ('$id_user', '$id_kamar', '$tanggal_booking', '$lama_sewa', '$total_harga', '$status')";
        if (mysqli_query($db, $sql)) {
            $pesan = ["type" => "sukses", "teks" => "Booking berhasil dikirim! Tunggu konfirmasi dari admin."];
            $sudah_booking = true;
        } else {
            $pesan = ["type" => "error", "teks" => "Gagal melakukan booking. Silakan coba lagi."];
        }
    }
}

// Hitung estimasi (untuk JS)
$harga_per_bulan = $kamar['harga'];
$fasilitas_list = array_map('trim', explode(',', $kamar['fasilitas']));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Kamar — Kost Cahaya</title>
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
            --radius:   14px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Sora', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }

        /* TOPBAR */
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
        .back-link {
            color: var(--muted);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            padding: 6px 14px;
            border-radius: 8px;
            border: 1px solid var(--border);
            transition: all .2s;
        }
        .back-link:hover { color: var(--text); background: var(--border); }

        /* LAYOUT */
        .wrapper {
            max-width: 860px;
            margin: 40px auto 60px;
            padding: 0 24px;
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 24px;
            align-items: start;
        }

        /* CARD */
        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 28px;
        }
        .card-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .card-title::before {
            content: '';
            display: block;
            width: 4px;
            height: 18px;
            background: var(--accent);
            border-radius: 2px;
        }

        /* KAMAR INFO */
        .kamar-header {
            display: flex;
            gap: 16px;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border);
        }
        .kamar-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #1a2040, #252d4a);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            flex-shrink: 0;
        }
        .kamar-nama { font-size: 1.2rem; font-weight: 700; margin-bottom: 4px; }
        .kamar-harga {
            font-family: 'JetBrains Mono', monospace;
            color: var(--accent);
            font-size: 1rem;
        }
        .kamar-harga span { font-family: 'Sora', sans-serif; font-size: 0.78rem; color: var(--muted); }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 12px 0;
            border-bottom: 1px solid rgba(42,48,80,.5);
            font-size: 0.88rem;
        }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: var(--muted); font-weight: 500; }
        .info-value { font-weight: 600; text-align: right; max-width: 60%; }

        .chip-wrap { display: flex; flex-wrap: wrap; gap: 6px; justify-content: flex-end; }
        .chip {
            padding: 3px 10px;
            background: rgba(240,165,0,.1);
            border: 1px solid rgba(240,165,0,.2);
            color: var(--accent);
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 500;
        }

        /* FORM */
        .form-group { margin-bottom: 18px; }
        label {
            display: block;
            font-size: 0.78rem;
            font-weight: 600;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 8px;
        }
        input[type=number] {
            width: 100%;
            background: var(--bg);
            border: 1px solid var(--border);
            color: var(--text);
            font-family: 'Sora', sans-serif;
            font-size: 1rem;
            padding: 12px 14px;
            border-radius: 8px;
            outline: none;
            transition: border .2s;
        }
        input[type=number]:focus { border-color: var(--accent); }

        /* ESTIMASI HARGA */
        .estimasi-box {
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 18px;
        }
        .estimasi-label { font-size: 0.78rem; color: var(--muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 8px; }
        .estimasi-nominal {
            font-family: 'JetBrains Mono', monospace;
            font-size: 1.5rem;
            font-weight: 500;
            color: var(--accent);
        }
        .estimasi-detail { font-size: 0.78rem; color: var(--muted); margin-top: 4px; }

        /* BUTTONS */
        .btn-booking {
            width: 100%;
            padding: 14px;
            background: var(--accent);
            color: #000;
            border: none;
            border-radius: 10px;
            font-family: 'Sora', sans-serif;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            transition: background .2s;
        }
        .btn-booking:hover { background: var(--accent2); }
        .btn-booking:disabled {
            background: var(--border);
            color: var(--muted);
            cursor: not-allowed;
        }

        /* ALERTS */
        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            font-size: 0.88rem;
            font-weight: 500;
            margin-bottom: 18px;
            line-height: 1.5;
        }
        .alert-sukses { background: rgba(46,204,113,.1); color: var(--green); border: 1px solid rgba(46,204,113,.25); }
        .alert-error  { background: rgba(231,76,60,.1);  color: var(--red);   border: 1px solid rgba(231,76,60,.25); }
        .alert-info   { background: rgba(240,165,0,.1);  color: var(--accent); border: 1px solid rgba(240,165,0,.25); }

        .note { font-size: 0.78rem; color: var(--muted); text-align: center; margin-top: 12px; line-height: 1.5; }

        @media (max-width: 700px) {
            .wrapper { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<!-- TOPBAR -->
<nav class="topbar">
    <div class="topbar-brand">Kost <span>Cahaya</span></div>
    <a href="kamar.php" class="back-link">← Kembali</a>
</nav>

<!-- MAIN -->
<div class="wrapper">

    <!-- INFO KAMAR -->
    <div class="card">
        <div class="card-title">Detail Kamar</div>

        <div class="kamar-header">
            <div class="kamar-icon">🛏️</div>
            <div>
                <div class="kamar-nama"><?= htmlspecialchars($kamar['nama_kamar']) ?></div>
                <div class="kamar-harga">
                    Rp <?= number_format($kamar['harga'], 0, ',', '.') ?>
                    <span>/ bulan</span>
                </div>
            </div>
        </div>

        <div class="info-row">
            <span class="info-label">Status</span>
            <span class="info-value" style="color:var(--green)">✓ Tersedia</span>
        </div>

        <?php if ($kamar['fasilitas']): ?>
        <div class="info-row">
            <span class="info-label">Fasilitas</span>
            <div class="info-value">
                <div class="chip-wrap">
                    <?php foreach ($fasilitas_list as $f): ?>
                        <?php if ($f): ?><span class="chip"><?= htmlspecialchars($f) ?></span><?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($kamar['deskripsi']): ?>
        <div class="info-row">
            <span class="info-label">Deskripsi</span>
            <span class="info-value" style="color:var(--muted)"><?= htmlspecialchars($kamar['deskripsi']) ?></span>
        </div>
        <?php endif; ?>
    </div>

    <!-- FORM BOOKING -->
    <div class="card">
        <div class="card-title">Form Booking</div>

        <?php if ($pesan): ?>
            <div class="alert alert-<?= $pesan['type'] ?>"><?= $pesan['teks'] ?></div>
        <?php endif; ?>

        <?php if ($sudah_booking): ?>
            <div class="alert alert-info">
                Kamu sudah memiliki booking pending untuk kamar ini. Tunggu konfirmasi admin.
            </div>
            <a href="dashboard.php" style="display:block;text-align:center;color:var(--accent);font-size:.88rem;margin-top:8px">
                Lihat status booking →
            </a>
        <?php else: ?>
            <form method="POST">
                <div class="form-group">
                    <label>Lama Sewa (bulan)</label>
                    <input type="number" name="lama_sewa" id="lama_sewa"
                           min="1" max="24" value="1"
                           oninput="hitungEstimasi(this.value)" required>
                </div>

                <div class="estimasi-box">
                    <div class="estimasi-label">Estimasi Total Biaya</div>
                    <div class="estimasi-nominal" id="estimasi">
                        Rp <?= number_format($kamar['harga'], 0, ',', '.') ?>
                    </div>
                    <div class="estimasi-detail" id="estimasi-detail">
                        <?= number_format($kamar['harga'], 0, ',', '.') ?> × 1 bulan
                    </div>
                </div>

                <button type="submit" name="booking" class="btn-booking">
                    Kirim Booking →
                </button>
            </form>

            <p class="note">
                Booking akan berstatus <strong>pending</strong> hingga dikonfirmasi admin.<br>
                Pembayaran dilakukan setelah booking diterima.
            </p>
        <?php endif; ?>
    </div>

</div>

<script>
const hargaPerBulan = <?= $harga_per_bulan ?>;

function hitungEstimasi(bulan) {
    bulan = parseInt(bulan) || 1;
    if (bulan < 1) bulan = 1;

    const total = hargaPerBulan * bulan;
    document.getElementById('estimasi').textContent =
        'Rp ' + total.toLocaleString('id-ID');
    document.getElementById('estimasi-detail').textContent =
        hargaPerBulan.toLocaleString('id-ID') + ' × ' + bulan + ' bulan';
}
</script>
</body>
</html>
