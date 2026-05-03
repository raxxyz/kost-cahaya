<?php
session_start();
include "service/database.php";

// Proteksi: hanya admin
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$pesan = "";

// Konfirmasi booking
if (isset($_POST['konfirmasi'])) {
    $id_booking = (int) $_POST['id_booking'];
    $sql = "UPDATE booking SET status = 'diterima' WHERE id_booking = $id_booking";
    if (mysqli_query($db, $sql)) {
        // Tandai kamar jadi tidak tersedia
        $kamar_sql = "UPDATE kamar SET status = 'tidak tersedia'
                      WHERE id_kamar = (SELECT id_kamar FROM booking WHERE id_booking = $id_booking)";
        mysqli_query($db, $kamar_sql);
        $pesan = "success|Booking #$id_booking berhasil dikonfirmasi.";
    } else {
        $pesan = "danger|Gagal mengkonfirmasi booking.";
    }
}

// Tolak booking
if (isset($_POST['tolak'])) {
    $id_booking = (int) $_POST['id_booking'];
    $sql = "UPDATE booking SET status = 'ditolak' WHERE id_booking = $id_booking";
    if (mysqli_query($db, $sql)) {
        $pesan = "warning|Booking #$id_booking telah ditolak.";
    } else {
        $pesan = "danger|Gagal menolak booking.";
    }
}

// Ambil semua data booking
$query = "SELECT booking.*, kamar.nama_kamar, kamar.harga, user.nama AS nama_user, user.no_hp
          FROM booking
          JOIN kamar ON booking.id_kamar = kamar.id_kamar
          JOIN user ON booking.id_user = user.id_user
          ORDER BY booking.id_booking DESC";
$result = mysqli_query($db, $query);

// Hitung statistik
$stat_query = mysqli_query($db, "SELECT status, COUNT(*) as total FROM booking GROUP BY status");
$stats = ['pending' => 0, 'diterima' => 0, 'ditolak' => 0];
while ($s = mysqli_fetch_assoc($stat_query)) {
    $stats[$s['status']] = $s['total'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Booking — Admin Kost Cahaya</title>
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
        .badge-diterima { background: #d1e7dd; color: #0a3622; }
        .badge-ditolak { background: #f8d7da; color: #58151c; }
        .table-card { background: #fff; border-radius: 12px; box-shadow: 0 1px 4px rgba(0,0,0,.07); overflow: hidden; }
        .table thead th { background: #f8fafc; font-size: .82rem; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; color: #64748b; border-bottom: 1px solid #e2e8f0; }
        .table td { vertical-align: middle; font-size: .9rem; }
        .btn-sm { font-size: .8rem; }
        .filter-btn.active { background: #1e293b; color: #fff; border-color: #1e293b; }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="brand"><i class="bi bi-house-heart-fill me-2"></i>Kost Cahaya</div>
    <nav class="nav flex-column mt-3">
        <a href="admin_kamar.php" class="nav-link"><i class="bi bi-door-open"></i> Manajemen Kamar</a>
        <a href="admin_booking.php" class="nav-link active"><i class="bi bi-calendar-check"></i> Manajemen Booking</a>
        <a href="admin_pembayaran.php" class="nav-link"><i class="bi bi-credit-card"></i> Verifikasi Pembayaran</a>
        <hr style="border-color:#334155; margin: 10px 20px;">
        <a href="logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </nav>
</div>

<!-- Main -->
<div class="main-content">

    <!-- Topbar -->
    <div class="topbar">
        <div>
            <h5 class="mb-0 fw-bold">Manajemen Booking</h5>
            <small class="text-muted">Konfirmasi atau tolak permintaan booking dari penghuni</small>
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
                        <div style="opacity:.85;">Menunggu Konfirmasi</div>
                    </div>
                    <i class="bi bi-hourglass-split" style="font-size:2.5rem;opacity:.4;"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card" style="background: linear-gradient(135deg,#10b981,#059669);">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div style="font-size:2rem;font-weight:700;"><?= $stats['diterima'] ?></div>
                        <div style="opacity:.85;">Booking Diterima</div>
                    </div>
                    <i class="bi bi-check-circle" style="font-size:2.5rem;opacity:.4;"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card" style="background: linear-gradient(135deg,#ef4444,#dc2626);">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div style="font-size:2rem;font-weight:700;"><?= $stats['ditolak'] ?></div>
                        <div style="opacity:.85;">Booking Ditolak</div>
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
            <table class="table table-hover mb-0" id="tabelBooking">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Penghuni</th>
                        <th>Kamar</th>
                        <th>Tanggal Booking</th>
                        <th>Lama Sewa</th>
                        <th>Total Harga</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (mysqli_num_rows($result) === 0): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">Belum ada data booking.</td></tr>
                <?php else: while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr data-status="<?= $row['status'] ?>">
                        <td class="text-muted"><?= $row['id_booking'] ?></td>
                        <td>
                            <div class="fw-semibold"><?= htmlspecialchars($row['nama_user']) ?></div>
                            <small class="text-muted"><?= htmlspecialchars($row['no_hp'] ?? '-') ?></small>
                        </td>
                        <td><?= htmlspecialchars($row['nama_kamar']) ?></td>
                        <td><?= date('d M Y', strtotime($row['tanggal_booking'])) ?></td>
                        <td><?= $row['lama_sewa'] ?> bulan</td>
                        <td>Rp <?= number_format($row['total_harga']) ?></td>
                        <td>
                            <?php if ($row['status'] === 'pending'): ?>
                                <span class="badge-status badge-pending">⏳ Pending</span>
                            <?php elseif ($row['status'] === 'diterima'): ?>
                                <span class="badge-status badge-diterima">✅ Diterima</span>
                            <?php else: ?>
                                <span class="badge-status badge-ditolak">❌ Ditolak</span>
                            <?php endif; ?>
                        </td>
                        <td>
                        <?php if ($row['status'] === 'pending'): ?>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Konfirmasi booking ini?')">
                                <input type="hidden" name="id_booking" value="<?= $row['id_booking'] ?>">
                                <button name="konfirmasi" class="btn btn-success btn-sm"><i class="bi bi-check-lg"></i></button>
                            </form>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Tolak booking ini?')">
                                <input type="hidden" name="id_booking" value="<?= $row['id_booking'] ?>">
                                <button name="tolak" class="btn btn-danger btn-sm"><i class="bi bi-x-lg"></i></button>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Filter baris tabel berdasarkan status
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const filter = this.dataset.filter;
            document.querySelectorAll('#tabelBooking tbody tr[data-status]').forEach(row => {
                row.style.display = (filter === 'all' || row.dataset.status === filter) ? '' : 'none';
            });
        });
    });
</script>
</body>
</html>
