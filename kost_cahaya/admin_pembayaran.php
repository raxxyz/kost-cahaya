<?php
session_start();
include "service/database.php";

// Proteksi: hanya admin
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$pesan = "";

// Verifikasi pembayaran
if (isset($_POST['verifikasi'])) {
    $id_pembayaran = (int) $_POST['id_pembayaran'];
    $id_admin      = (int) $_SESSION['id_admin'];
    $sql = "UPDATE pembayaran SET status_verifikasi = 'diterima', id_admin = $id_admin WHERE id_pembayaran = $id_pembayaran";
    if (mysqli_query($db, $sql)) {
        $pesan = "success|Pembayaran #$id_pembayaran berhasil diverifikasi.";
    } else {
        $pesan = "danger|Gagal memverifikasi pembayaran.";
    }
}

// Tolak pembayaran
if (isset($_POST['tolak'])) {
    $id_pembayaran = (int) $_POST['id_pembayaran'];
    $id_admin      = (int) $_SESSION['id_admin'];
    $sql = "UPDATE pembayaran SET status_verifikasi = 'ditolak', id_admin = $id_admin WHERE id_pembayaran = $id_pembayaran";
    if (mysqli_query($db, $sql)) {
        $pesan = "warning|Pembayaran #$id_pembayaran telah ditolak.";
    } else {
        $pesan = "danger|Gagal menolak pembayaran.";
    }
}

// Ambil semua data pembayaran
$query = "SELECT pembayaran.*, booking.lama_sewa, booking.total_harga, booking.tanggal_booking,
                 kamar.nama_kamar, user.nama AS nama_user, user.no_hp
          FROM pembayaran
          JOIN booking ON pembayaran.id_booking = booking.id_booking
          JOIN kamar ON booking.id_kamar = kamar.id_kamar
          JOIN user ON booking.id_user = user.id_user
          ORDER BY pembayaran.id_pembayaran DESC";
$result = mysqli_query($db, $query);

// Statistik
$stat_q = mysqli_query($db, "SELECT status_verifikasi, COUNT(*) as total FROM pembayaran GROUP BY status_verifikasi");
$stats = ['pending' => 0, 'diterima' => 0, 'ditolak' => 0];
while ($s = mysqli_fetch_assoc($stat_q)) {
    $stats[$s['status_verifikasi']] = $s['total'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Pembayaran — Admin Kost Cahaya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
        .sidebar { min-height: 100vh; background: #1e293b; color: #fff; width: 240px; position: fixed; top: 0; left: 0; padding-top: 20px; z-index: 100; }
        .sidebar .brand { font-size: 1.2rem; font-weight: 700; padding: 10px 20px 20px; border-bottom: 1px solid #334155; color: #f8fafc; }
        .sidebar .nav-link { color: #94a3b8; padding: 10px 20px; display: flex; align-items: center; gap: 10px; border-radius: 0; transition: all .2s; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background: #334155; color: #fff; }
        .sidebar .nav-link i { font-size: 1.1rem; }
        .main-content { margin-left: 240px; padding: 30px; }
        .topbar { background: #fff; border-radius: 12px; padding: 15px 25px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 1px 4px rgba(0,0,0,.07); }
        .stat-card { border-radius: 12px; padding: 20px; color: #fff; border: none; }
        .badge-status { font-size: .78rem; padding: 5px 10px; border-radius: 20px; font-weight: 600; }
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-terverifikasi { background: #d1e7dd; color: #0a3622; }
        .badge-ditolak { background: #f8d7da; color: #58151c; }
        .table-card { background: #fff; border-radius: 12px; box-shadow: 0 1px 4px rgba(0,0,0,.07); overflow: hidden; }
        .table thead th { background: #f8fafc; font-size: .82rem; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; color: #64748b; border-bottom: 1px solid #e2e8f0; }
        .table td { vertical-align: middle; font-size: .9rem; }
        .bukti-img { width: 70px; height: 70px; object-fit: cover; border-radius: 8px; cursor: pointer; border: 2px solid #e2e8f0; transition: transform .2s; }
        .bukti-img:hover { transform: scale(1.05); }
        .filter-btn.active { background: #1e293b; color: #fff; border-color: #1e293b; }
        /* Modal preview gambar */
        #previewModal .modal-body { text-align: center; background: #0f172a; }
        #previewModal img { max-width: 100%; border-radius: 8px; }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="brand"><i class="bi bi-house-heart-fill me-2"></i>Kost Cahaya</div>
    <nav class="nav flex-column mt-3">
        <a href="admin_kamar.php" class="nav-link"><i class="bi bi-door-open"></i> Manajemen Kamar</a>
        <a href="admin_booking.php" class="nav-link"><i class="bi bi-calendar-check"></i> Manajemen Booking</a>
        <a href="admin_pembayaran.php" class="nav-link active"><i class="bi bi-credit-card"></i> Verifikasi Pembayaran</a>
        <hr style="border-color:#334155; margin: 10px 20px;">
        <a href="logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </nav>
</div>

<!-- Main -->
<div class="main-content">

    <!-- Topbar -->
    <div class="topbar">
        <div>
            <h5 class="mb-0 fw-bold">Verifikasi Pembayaran</h5>
            <small class="text-muted">Periksa dan verifikasi bukti pembayaran dari penghuni</small>
        </div>
        <span class="text-muted"><i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($_SESSION['nama']) ?> (Admin)</span>
    </div>

    <!-- Alert -->
    <?php if ($pesan): [$type, $msg] = explode('|', $pesan, 2); ?>
    <div class="alert alert-<?= $type ?> alert-dismissible fade show rounded-3" role="alert">
        <?= htmlspecialchars($msg) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Statistik -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="stat-card" style="background: linear-gradient(135deg,#f59e0b,#d97706);">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div style="font-size:2rem;font-weight:700;"><?= $stats['pending'] ?></div>
                        <div style="opacity:.85;">Menunggu Verifikasi</div>
                    </div>
                    <i class="bi bi-clock-history" style="font-size:2.5rem;opacity:.4;"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card" style="background: linear-gradient(135deg,#10b981,#059669);">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div style="font-size:2rem;font-weight:700;"><?= $stats['diterima'] ?></div>
                        <div style="opacity:.85;">Terverifikasi</div>
                    </div>
                    <i class="bi bi-shield-check" style="font-size:2.5rem;opacity:.4;"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card" style="background: linear-gradient(135deg,#ef4444,#dc2626);">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div style="font-size:2rem;font-weight:700;"><?= $stats['ditolak'] ?></div>
                        <div style="opacity:.85;">Ditolak</div>
                    </div>
                    <i class="bi bi-x-circle" style="font-size:2.5rem;opacity:.4;"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter -->
    <div class="mb-3 d-flex gap-2 flex-wrap">
        <button class="btn btn-outline-secondary btn-sm filter-btn active" data-filter="all">Semua</button>
        <button class="btn btn-outline-warning btn-sm filter-btn" data-filter="pending">Pending</button>
        <button class="btn btn-outline-success btn-sm filter-btn" data-filter="diterima">Diterima</button>
        <button class="btn btn-outline-danger btn-sm filter-btn" data-filter="ditolak">Ditolak</button>
    </div>

    <!-- Tabel -->
    <div class="table-card">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="tabelPembayaran">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Penghuni</th>
                        <th>Kamar</th>
                        <th>Total Bayar</th>
                        <th>Tanggal Bayar</th>
                        <th>Bukti Transfer</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (mysqli_num_rows($result) === 0): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">Belum ada data pembayaran.</td></tr>
                <?php else: while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr data-status="<?= $row['status_verifikasi'] ?>">
                        <td class="text-muted"><?= $row['id_pembayaran'] ?></td>
                        <td>
                            <div class="fw-semibold"><?= htmlspecialchars($row['nama_user']) ?></div>
                            <small class="text-muted"><?= htmlspecialchars($row['no_hp'] ?? '-') ?></small>
                        </td>
                        <td><?= htmlspecialchars($row['nama_kamar']) ?></td>
                        <td>Rp <?= number_format($row['total_harga']) ?></td>
                        <td><?= isset($row['tanggal_bayar']) ? date('d M Y', strtotime($row['tanggal_bayar'])) : '-' ?></td>
                        <td>
                            <?php if (!empty($row['bukti_pembayaran'])): ?>
                                <img src="uploads/<?= htmlspecialchars($row['bukti_pembayaran']) ?>"
                                     class="bukti-img"
                                     alt="Bukti Transfer"
                                     onclick="previewGambar(this.src)"
                                     title="Klik untuk perbesar">
                            <?php else: ?>
                                <span class="text-muted small">Tidak ada</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($row['status_verifikasi'] === 'pending'): ?>
                                <span class="badge-status badge-pending">⏳ Pending</span>
                            <?php elseif ($row['status_verifikasi'] === 'diterima'): ?>
                                <span class="badge-status badge-terverifikasi">✅ Diterima</span>
                            <?php else: ?>
                                <span class="badge-status badge-ditolak">❌ Ditolak</span>
                            <?php endif; ?>
                        </td>
                        <td>
                        <?php if ($row['status_verifikasi'] === 'pending'): ?>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Verifikasi pembayaran ini?')">
                                <input type="hidden" name="id_pembayaran" value="<?= $row['id_pembayaran'] ?>">
                                <button name="verifikasi" class="btn btn-success btn-sm" title="Verifikasi">
                                    <i class="bi bi-check-lg"></i>
                                </button>
                            </form>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Tolak pembayaran ini?')">
                                <input type="hidden" name="id_pembayaran" value="<?= $row['id_pembayaran'] ?>">
                                <button name="tolak" class="btn btn-danger btn-sm" title="Tolak">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </form>
                        <?php else: ?>
                            <span class="text-muted small">—</span>
                        <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal Preview Gambar -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header border-0 bg-dark text-white">
                <h6 class="modal-title">Bukti Transfer</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-2" style="background:#0f172a;">
                <img id="previewImg" src="" alt="Bukti Transfer" style="max-width:100%;border-radius:8px;display:block;margin:auto;">
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function previewGambar(src) {
        document.getElementById('previewImg').src = src;
        new bootstrap.Modal(document.getElementById('previewModal')).show();
    }

    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const filter = this.dataset.filter;
            document.querySelectorAll('#tabelPembayaran tbody tr[data-status]').forEach(row => {
                row.style.display = (filter === 'all' || row.dataset.status === filter) ? '' : 'none';
            });
        });
    });
</script>
</body>
</html>
