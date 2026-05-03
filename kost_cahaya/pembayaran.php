<?php
session_start();
include "service/database.php";

// Proteksi: harus login sebagai user
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['id_user'];
$pesan   = "";

// Ambil id_booking dari URL
$id_booking = isset($_GET['id_booking']) ? (int) $_GET['id_booking'] : 0;

// Ambil data booking milik user ini
$query = "SELECT booking.*, kamar.nama_kamar, kamar.harga
          FROM booking
          JOIN kamar ON booking.id_kamar = kamar.id_kamar
          WHERE booking.id_booking = $id_booking AND booking.id_user = $id_user";
$result = mysqli_query($db, $query);

if (mysqli_num_rows($result) === 0) {
    die("<div style='text-align:center;padding:60px;font-family:sans-serif;'>
            <h3>Booking tidak ditemukan.</h3>
            <a href='dashboard.php'>← Kembali ke Dashboard</a>
         </div>");
}

$booking = mysqli_fetch_assoc($result);

// Cek apakah sudah pernah upload bukti
$cek = mysqli_query($db, "SELECT * FROM pembayaran WHERE id_booking = $id_booking");
$pembayaran = mysqli_fetch_assoc($cek);

// Proses upload
if (isset($_POST['upload'])) {

    // Validasi: booking harus diterima dulu
    if ($booking['status'] !== 'diterima') {
        $pesan = "danger|Booking kamu belum dikonfirmasi admin. Tunggu konfirmasi dulu.";
    } elseif (!isset($_FILES['bukti']) || $_FILES['bukti']['error'] !== 0) {
        $pesan = "danger|Pilih file bukti transfer terlebih dahulu.";
    } else {
        $file     = $_FILES['bukti'];
        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed  = ['jpg', 'jpeg', 'png', 'pdf'];
        $maxSize  = 2 * 1024 * 1024; // 2MB

        if (!in_array($ext, $allowed)) {
            $pesan = "danger|Format file tidak didukung. Gunakan JPG, PNG, atau PDF.";
        } elseif ($file['size'] > $maxSize) {
            $pesan = "danger|Ukuran file terlalu besar. Maksimal 2MB.";
        } else {
            // Buat folder uploads jika belum ada
            if (!is_dir('uploads')) mkdir('uploads', 0755, true);

            $nama_file = 'bukti_' . $id_booking . '_' . time() . '.' . $ext;
            $tujuan    = 'uploads/' . $nama_file;

            if (move_uploaded_file($file['tmp_name'], $tujuan)) {
                if ($pembayaran) {
                    // Update jika sudah pernah upload
                    $sql = "UPDATE pembayaran SET
                                bukti_pembayaran = '$nama_file',
                                tanggal_bayar = CURDATE(),
                                status_verifikasi = 'pending'
                            WHERE id_booking = $id_booking";
                } else {
                    // Insert baru
                    $sql = "INSERT INTO pembayaran (id_booking, tanggal_bayar, bukti_pembayaran, status_verifikasi)
                            VALUES ($id_booking, CURDATE(), '$nama_file', 'pending')";
                }

                if (mysqli_query($db, $sql)) {
                    $pesan = "success|Bukti transfer berhasil dikirim! Menunggu verifikasi admin.";
                    // Refresh data pembayaran
                    $cek2 = mysqli_query($db, "SELECT * FROM pembayaran WHERE id_booking = $id_booking");
                    $pembayaran = mysqli_fetch_assoc($cek2);
                } else {
                    $pesan = "danger|Gagal menyimpan data. Coba lagi.";
                }
            } else {
                $pesan = "danger|Gagal mengupload file. Coba lagi.";
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
    <title>Upload Bukti Pembayaran — Kost Cahaya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: #0f1117;
            font-family: 'Sora', sans-serif;
            color: #e8eaf0;
            min-height: 100vh;
        }

        /* TOPBAR */
        .topbar {
            background: #1e2333;
            border-bottom: 1px solid #2a3050;
            padding: 14px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .topbar .brand { font-size: 1.1rem; font-weight: 700; color: #f0a500; text-decoration: none; }
        .topbar .nav-right a { color: #7a82a0; font-size: .85rem; text-decoration: none; margin-left: 20px; }
        .topbar .nav-right a:hover { color: #e8eaf0; }

        /* LAYOUT */
        .page-wrap {
            max-width: 760px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #7a82a0;
            font-size: .85rem;
            text-decoration: none;
            margin-bottom: 24px;
            transition: color .2s;
        }
        .back-link:hover { color: #e8eaf0; }

        h4 { font-weight: 700; font-size: 1.3rem; margin-bottom: 4px; }
        .sub { color: #7a82a0; font-size: .85rem; margin-bottom: 28px; }

        /* CARD */
        .card-dark {
            background: #1e2333;
            border: 1px solid #2a3050;
            border-radius: 14px;
            padding: 24px;
            margin-bottom: 20px;
        }
        .card-dark h6 {
            font-size: .7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #7a82a0;
            margin-bottom: 16px;
        }

        /* DETAIL BOOKING */
        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        .detail-item label {
            display: block;
            font-size: .75rem;
            color: #7a82a0;
            margin-bottom: 3px;
        }
        .detail-item span {
            font-size: .95rem;
            font-weight: 600;
            color: #e8eaf0;
        }
        .total-harga {
            font-size: 1.4rem;
            font-weight: 700;
            color: #f0a500;
        }

        /* STATUS BADGE */
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: .78rem;
            font-weight: 600;
        }
        .status-pending   { background: rgba(240,165,0,.15); color: #f0a500; }
        .status-diterima  { background: rgba(16,185,129,.15); color: #10b981; }
        .status-ditolak   { background: rgba(239,68,68,.15); color: #ef4444; }

        /* UPLOAD AREA */
        .upload-area {
            border: 2px dashed #2a3050;
            border-radius: 12px;
            padding: 36px;
            text-align: center;
            cursor: pointer;
            transition: all .2s;
            position: relative;
        }
        .upload-area:hover, .upload-area.drag-over {
            border-color: #f0a500;
            background: rgba(240,165,0,.04);
        }
        .upload-area input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }
        .upload-area i { font-size: 2.2rem; color: #3a4260; margin-bottom: 10px; display: block; }
        .upload-area .upload-text { font-size: .9rem; color: #7a82a0; }
        .upload-area .upload-hint { font-size: .78rem; color: #3a4260; margin-top: 6px; }
        .preview-wrap { margin-top: 14px; display: none; }
        .preview-wrap img { max-height: 180px; border-radius: 8px; border: 1px solid #2a3050; }

        /* SUDAH UPLOAD */
        .sudah-upload {
            display: flex;
            align-items: center;
            gap: 14px;
            background: rgba(16,185,129,.07);
            border: 1px solid rgba(16,185,129,.2);
            border-radius: 10px;
            padding: 14px 18px;
            margin-bottom: 16px;
        }
        .sudah-upload i { font-size: 1.6rem; color: #10b981; }
        .sudah-upload .info { flex: 1; }
        .sudah-upload .info small { color: #7a82a0; font-size: .8rem; }

        /* BUTTON */
        .btn-upload {
            width: 100%;
            padding: 13px;
            background: #f0a500;
            color: #000;
            font-family: 'Sora', sans-serif;
            font-size: .92rem;
            font-weight: 700;
            border: none;
            border-radius: 9px;
            cursor: pointer;
            transition: background .2s;
            margin-top: 16px;
        }
        .btn-upload:hover { background: #e06c00; }
        .btn-upload:disabled { background: #3a4260; color: #7a82a0; cursor: not-allowed; }

        /* ALERT */
        .alert-custom {
            border-radius: 10px;
            padding: 12px 16px;
            font-size: .87rem;
            font-weight: 500;
            margin-bottom: 20px;
            border: 1px solid transparent;
        }
        .alert-success { background: rgba(16,185,129,.1); border-color: rgba(16,185,129,.25); color: #10b981; }
        .alert-danger  { background: rgba(239,68,68,.1); border-color: rgba(239,68,68,.25); color: #ef4444; }
        .alert-warning { background: rgba(240,165,0,.1); border-color: rgba(240,165,0,.25); color: #f0a500; }

        /* TIMELINE STATUS */
        .timeline { display: flex; gap: 0; margin-top: 8px; }
        .timeline-step {
            flex: 1;
            text-align: center;
            position: relative;
            font-size: .75rem;
            color: #3a4260;
        }
        .timeline-step::before {
            content: '';
            display: block;
            width: 28px; height: 28px;
            border-radius: 50%;
            background: #2a3050;
            margin: 0 auto 8px;
            border: 2px solid #2a3050;
            position: relative;
            z-index: 1;
        }
        .timeline-step::after {
            content: '';
            position: absolute;
            top: 13px; left: 50%;
            width: 100%; height: 2px;
            background: #2a3050;
            z-index: 0;
        }
        .timeline-step:last-child::after { display: none; }
        .timeline-step.done::before { background: #10b981; border-color: #10b981; }
        .timeline-step.done { color: #10b981; }
        .timeline-step.active::before { background: #f0a500; border-color: #f0a500; }
        .timeline-step.active { color: #f0a500; }
        .timeline-step .icon {
            position: absolute;
            top: 5px;
            left: 50%;
            transform: translateX(-50%);
            font-size: .75rem;
            z-index: 2;
            color: #0f1117;
        }
    </style>
</head>
<body>

<!-- Topbar -->
<div class="topbar">
    <a href="index.php" class="brand"><i class="bi bi-house-heart-fill me-2"></i>Kost Cahaya</a>
    <div class="nav-right">
        <a href="dashboard.php"><i class="bi bi-grid me-1"></i>Dashboard</a>
        <a href="kamar.php"><i class="bi bi-door-open me-1"></i>Kamar</a>
        <a href="logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
    </div>
</div>

<div class="page-wrap">

    <a href="dashboard.php" class="back-link"><i class="bi bi-arrow-left"></i> Kembali ke Dashboard</a>

    <h4>Upload Bukti Pembayaran</h4>
    <p class="sub">Booking #<?= $id_booking ?> — <?= htmlspecialchars($booking['nama_kamar']) ?></p>

    <!-- Alert -->
    <?php if ($pesan): [$type, $msg] = explode('|', $pesan, 2); ?>
    <div class="alert-custom alert-<?= $type ?>">
        <?php if ($type === 'success'): ?>✅<?php elseif ($type === 'danger'): ?>⚠️<?php else: ?>ℹ️<?php endif; ?>
        <?= htmlspecialchars($msg) ?>
    </div>
    <?php endif; ?>

    <!-- Detail Booking -->
    <div class="card-dark">
        <h6>Detail Booking</h6>
        <div class="detail-grid">
            <div class="detail-item">
                <label>Nama Kamar</label>
                <span><?= htmlspecialchars($booking['nama_kamar']) ?></span>
            </div>
            <div class="detail-item">
                <label>Lama Sewa</label>
                <span><?= $booking['lama_sewa'] ?> bulan</span>
            </div>
            <div class="detail-item">
                <label>Tanggal Booking</label>
                <span><?= date('d M Y', strtotime($booking['tanggal_booking'])) ?></span>
            </div>
            <div class="detail-item">
                <label>Status Booking</label>
                <span class="status-badge status-<?= $booking['status'] ?>"><?= ucfirst($booking['status']) ?></span>
            </div>
            <div class="detail-item" style="grid-column: span 2;">
                <label>Total yang Harus Dibayar</label>
                <div class="total-harga">Rp <?= number_format($booking['total_harga']) ?></div>
            </div>
        </div>
    </div>

    <!-- Timeline Status -->
    <div class="card-dark">
        <h6>Status Proses</h6>
        <div class="timeline">
            <?php
                $step_booking   = 'done';
                $step_konfirmasi = ($booking['status'] === 'diterima') ? 'done' : 'active';
                $step_bayar      = $pembayaran ? (($pembayaran['status_verifikasi'] !== 'pending') ? 'done' : 'active') : '';
                $step_verifikasi = ($pembayaran && $pembayaran['status_verifikasi'] === 'diterima') ? 'done' : '';
            ?>
            <div class="timeline-step done">
                <span class="icon"><i class="bi bi-check"></i></span>
                Booking Dibuat
            </div>
            <div class="timeline-step <?= $step_konfirmasi ?>">
                <span class="icon"><?= $booking['status'] === 'diterima' ? '<i class="bi bi-check"></i>' : '' ?></span>
                Dikonfirmasi Admin
            </div>
            <div class="timeline-step <?= $step_bayar ?>">
                <span class="icon"><?= $pembayaran ? '<i class="bi bi-check"></i>' : '' ?></span>
                Bukti Dikirim
            </div>
            <div class="timeline-step <?= $step_verifikasi ?>">
                <span class="icon"><?= ($pembayaran && $pembayaran['status_verifikasi'] === 'diterima') ? '<i class="bi bi-check"></i>' : '' ?></span>
                Terverifikasi
            </div>
        </div>
    </div>

    <!-- Upload Form -->
    <div class="card-dark">
        <h6>Bukti Transfer</h6>

        <?php if ($booking['status'] !== 'diterima'): ?>
            <!-- Booking belum dikonfirmasi -->
            <div class="alert-custom alert-warning">
                ⏳ Booking kamu masih <strong><?= $booking['status'] ?></strong>. Upload bukti bisa dilakukan setelah admin mengkonfirmasi booking.
            </div>

        <?php else: ?>
            <!-- Sudah pernah upload -->
            <?php if ($pembayaran && !empty($pembayaran['bukti_pembayaran'])): ?>
            <div class="sudah-upload">
                <i class="bi bi-file-earmark-check-fill"></i>
                <div class="info">
                    <div style="font-weight:600;font-size:.9rem;">Bukti sudah dikirim</div>
                    <small>
                        <?= date('d M Y', strtotime($pembayaran['tanggal_bayar'])) ?> •
                        Status: <span class="status-badge status-<?= $pembayaran['status_verifikasi'] ?>"><?= ucfirst($pembayaran['status_verifikasi']) ?></span>
                    </small>
                </div>
                <?php
                    $ext_file = pathinfo($pembayaran['bukti_pembayaran'], PATHINFO_EXTENSION);
                    if (in_array(strtolower($ext_file), ['jpg','jpeg','png'])):
                ?>
                <a href="uploads/<?= htmlspecialchars($pembayaran['bukti_pembayaran']) ?>" target="_blank">
                    <img src="uploads/<?= htmlspecialchars($pembayaran['bukti_pembayaran']) ?>"
                         style="height:50px;border-radius:6px;border:1px solid #2a3050;">
                </a>
                <?php else: ?>
                <a href="uploads/<?= htmlspecialchars($pembayaran['bukti_pembayaran']) ?>"
                   target="_blank"
                   style="color:#f0a500;font-size:.82rem;">
                    <i class="bi bi-file-pdf"></i> Lihat PDF
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Form Upload -->
            <form method="POST" enctype="multipart/form-data">
                <div class="upload-area" id="uploadArea">
                    <input type="file" name="bukti" accept=".jpg,.jpeg,.png,.pdf"
                           id="fileInput" onchange="previewFile(this)">
                    <i class="bi bi-cloud-arrow-up" id="uploadIcon"></i>
                    <div class="upload-text" id="uploadText">
                        <?= ($pembayaran && !empty($pembayaran['bukti_pembayaran'])) ? 'Ganti bukti transfer' : 'Klik atau seret file ke sini' ?>
                    </div>
                    <div class="upload-hint">JPG, PNG, atau PDF • Maksimal 2MB</div>
                    <div class="preview-wrap" id="previewWrap">
                        <img id="previewImg" src="" alt="Preview">
                        <div id="previewName" style="font-size:.8rem;color:#7a82a0;margin-top:6px;"></div>
                    </div>
                </div>

                <button type="submit" name="upload" class="btn-upload">
                    <i class="bi bi-send me-2"></i>
                    <?= ($pembayaran && !empty($pembayaran['bukti_pembayaran'])) ? 'Kirim Ulang Bukti' : 'Kirim Bukti Pembayaran' ?>
                </button>
            </form>
        <?php endif; ?>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function previewFile(input) {
        const wrap = document.getElementById('previewWrap');
        const img  = document.getElementById('previewImg');
        const name = document.getElementById('previewName');
        const icon = document.getElementById('uploadIcon');
        const text = document.getElementById('uploadText');

        if (input.files && input.files[0]) {
            const file = input.files[0];
            name.textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';

            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = e => {
                    img.src = e.target.result;
                    wrap.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                img.src = '';
                wrap.style.display = 'block';
                img.style.display = 'none';
            }

            icon.style.display = 'none';
            text.textContent = 'File dipilih ✓';
        }
    }

    // Drag & drop highlight
    const area = document.getElementById('uploadArea');
    if (area) {
        area.addEventListener('dragover', () => area.classList.add('drag-over'));
        area.addEventListener('dragleave', () => area.classList.remove('drag-over'));
        area.addEventListener('drop', () => area.classList.remove('drag-over'));
    }
</script>
</body>
</html>
