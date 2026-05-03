<?php
session_start();
include "service/database.php";

// Proteksi: hanya admin
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$pesan = "";

// TAMBAH KAMAR
if (isset($_POST['tambah'])) {
    $nama_kamar = mysqli_real_escape_string($db, $_POST['nama_kamar']);
    $harga      = (int) $_POST['harga'];
    $status     = $_POST['status'];
    $fasilitas  = mysqli_real_escape_string($db, $_POST['fasilitas']);
    $deskripsi  = mysqli_real_escape_string($db, $_POST['deskripsi']);

    $sql = "INSERT INTO kamar (nama_kamar, harga, status, fasilitas, deskripsi)
            VALUES ('$nama_kamar', '$harga', '$status', '$fasilitas', '$deskripsi')";
    if (mysqli_query($db, $sql)) {
        $pesan = ["type" => "sukses", "teks" => "Kamar berhasil ditambahkan! Sekarang tambahkan foto kamar."];
    } else {
        $pesan = ["type" => "error", "teks" => "Gagal menambahkan kamar."];
    }
}

// EDIT KAMAR
if (isset($_POST['edit'])) {
    $id_kamar   = (int) $_POST['id_kamar'];
    $nama_kamar = mysqli_real_escape_string($db, $_POST['nama_kamar']);
    $harga      = (int) $_POST['harga'];
    $status     = $_POST['status'];
    $fasilitas  = mysqli_real_escape_string($db, $_POST['fasilitas']);
    $deskripsi  = mysqli_real_escape_string($db, $_POST['deskripsi']);

    $sql = "UPDATE kamar SET
                nama_kamar='$nama_kamar',
                harga='$harga',
                status='$status',
                fasilitas='$fasilitas',
                deskripsi='$deskripsi'
            WHERE id_kamar='$id_kamar'";
    if (mysqli_query($db, $sql)) {
        $pesan = ["type" => "sukses", "teks" => "Kamar berhasil diperbarui!"];
    } else {
        $pesan = ["type" => "error", "teks" => "Gagal memperbarui kamar."];
    }
}

// HAPUS KAMAR
if (isset($_GET['hapus'])) {
    $id_kamar = (int) $_GET['hapus'];
    // Hapus juga gambar dari folder
    $res_gambar = mysqli_query($db, "SELECT nama_file FROM gambar_kamar WHERE id_kamar=$id_kamar");
    while ($g = mysqli_fetch_assoc($res_gambar)) {
        $path = "uploads/kamar/" . $g['nama_file'];
        if (file_exists($path)) unlink($path);
    }
    mysqli_query($db, "DELETE FROM gambar_kamar WHERE id_kamar=$id_kamar");
    $sql = "DELETE FROM kamar WHERE id_kamar='$id_kamar'";
    if (mysqli_query($db, $sql)) {
        $pesan = ["type" => "sukses", "teks" => "Kamar berhasil dihapus!"];
    } else {
        $pesan = ["type" => "error", "teks" => "Gagal menghapus kamar."];
    }
}

// Ambil data untuk edit
$edit_data = null;
if (isset($_GET['edit'])) {
    $id_kamar  = (int) $_GET['edit'];
    $res       = mysqli_query($db, "SELECT * FROM kamar WHERE id_kamar='$id_kamar'");
    $edit_data = mysqli_fetch_assoc($res);
}

// Ambil semua kamar beserta jumlah foto & foto utama
$result = mysqli_query($db, "
    SELECT kamar.*,
           COUNT(gambar_kamar.id_gambar) AS jumlah_foto,
           MAX(CASE WHEN gambar_kamar.is_utama = 1 THEN gambar_kamar.nama_file END) AS foto_utama
    FROM kamar
    LEFT JOIN gambar_kamar ON kamar.id_kamar = gambar_kamar.id_kamar
    GROUP BY kamar.id_kamar
    ORDER BY kamar.id_kamar DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kamar — Kost Cahaya</title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg:        #0f1117;
            --surface:   #181c27;
            --card:      #1e2333;
            --border:    #2a3050;
            --accent:    #f0a500;
            --accent2:   #e06c00;
            --text:      #e8eaf0;
            --muted:     #7a82a0;
            --green:     #2ecc71;
            --red:       #e74c3c;
            --blue:      #3b82f6;
            --radius:    12px;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Sora', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; }

        .topbar { background: var(--surface); border-bottom: 1px solid var(--border); padding: 0 32px; height: 60px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 100; }
        .topbar-brand { font-size: 1.1rem; font-weight: 700; letter-spacing: -0.03em; color: var(--accent); }
        .topbar-brand span { color: var(--text); }
        .topbar-nav { display: flex; gap: 8px; }
        .topbar-nav a { color: var(--muted); text-decoration: none; font-size: 0.82rem; font-weight: 500; padding: 6px 14px; border-radius: 8px; transition: all .2s; }
        .topbar-nav a:hover, .topbar-nav a.active { color: var(--text); background: var(--border); }

        .wrapper { max-width: 1200px; margin: 0 auto; padding: 36px 24px; display: grid; grid-template-columns: 380px 1fr; gap: 28px; align-items: start; }
        .card { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 28px; }
        .card-title { font-size: 1rem; font-weight: 600; margin-bottom: 22px; display: flex; align-items: center; gap: 10px; }
        .card-title::before { content: ''; display: block; width: 4px; height: 18px; background: var(--accent); border-radius: 2px; }

        .form-group { margin-bottom: 16px; }
        label { display: block; font-size: 0.78rem; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 7px; }
        input[type=text], input[type=number], select, textarea { width: 100%; background: var(--bg); border: 1px solid var(--border); color: var(--text); font-family: 'Sora', sans-serif; font-size: 0.9rem; padding: 10px 14px; border-radius: 8px; outline: none; transition: border .2s; }
        input:focus, select:focus, textarea:focus { border-color: var(--accent); }
        textarea { resize: vertical; min-height: 80px; }
        select option { background: var(--card); }

        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 10px 20px; border: none; border-radius: 8px; font-family: 'Sora', sans-serif; font-size: 0.88rem; font-weight: 600; cursor: pointer; transition: all .2s; text-decoration: none; }
        .btn-primary { background: var(--accent); color: #000; width: 100%; justify-content: center; margin-top: 6px; }
        .btn-primary:hover { background: var(--accent2); color: #000; }
        .btn-sm { padding: 6px 12px; font-size: 0.78rem; }
        .btn-edit { background: rgba(240,165,0,.15); color: var(--accent); border: 1px solid rgba(240,165,0,.3); }
        .btn-edit:hover { background: rgba(240,165,0,.25); }
        .btn-hapus { background: rgba(231,76,60,.12); color: var(--red); border: 1px solid rgba(231,76,60,.25); }
        .btn-hapus:hover { background: rgba(231,76,60,.22); }
        .btn-foto { background: rgba(59,130,246,.12); color: var(--blue); border: 1px solid rgba(59,130,246,.25); }
        .btn-foto:hover { background: rgba(59,130,246,.22); }

        .alert { padding: 12px 16px; border-radius: 8px; font-size: 0.88rem; font-weight: 500; margin-bottom: 20px; }
        .alert-sukses { background: rgba(46,204,113,.12); color: var(--green); border: 1px solid rgba(46,204,113,.25); }
        .alert-error  { background: rgba(231,76,60,.12);  color: var(--red);   border: 1px solid rgba(231,76,60,.25); }

        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
        th { text-align: left; padding: 12px 14px; font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.07em; color: var(--muted); border-bottom: 1px solid var(--border); }
        td { padding: 12px 14px; border-bottom: 1px solid rgba(42,48,80,.5); vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: rgba(255,255,255,.02); }

        .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .badge-tersedia { background: rgba(46,204,113,.15); color: var(--green); }
        .badge-tidak    { background: rgba(231,76,60,.12);  color: var(--red); }

        .harga-text { font-family: 'JetBrains Mono', monospace; font-size: 0.85rem; color: var(--accent); }

        .page-header { max-width: 1200px; margin: 32px auto 0; padding: 0 24px; }
        .page-header h1 { font-size: 1.6rem; font-weight: 700; letter-spacing: -0.03em; }
        .page-header p { color: var(--muted); font-size: 0.88rem; margin-top: 4px; }

        .actions { display: flex; gap: 6px; flex-wrap: wrap; }

        /* Thumbnail foto di tabel */
        .thumb { width: 48px; height: 48px; object-fit: cover; border-radius: 8px; border: 1px solid var(--border); }
        .thumb-empty { width: 48px; height: 48px; border-radius: 8px; border: 2px dashed var(--border); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; color: var(--muted); }

        /* Badge jumlah foto */
        .foto-count { display: inline-flex; align-items: center; gap: 4px; font-size: 0.75rem; color: var(--muted); margin-top: 4px; }

        @media (max-width: 800px) { .wrapper { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<nav class="topbar">
    <div class="topbar-brand">Kost <span>Cahaya</span> &mdash; Admin</div>
    <div class="topbar-nav">
        <a href="admin_kamar.php" class="active">Kamar</a>
        <a href="admin_booking.php">Booking</a>
        <a href="admin_pembayaran.php">Pembayaran</a>
        <a href="logout.php">Keluar</a>
    </div>
</nav>

<div class="page-header">
    <h1>Kelola Kamar</h1>
    <p>Tambah, edit, hapus, dan kelola foto kamar kost</p>
</div>

<div class="wrapper">

    <!-- FORM PANEL -->
    <div class="card">
        <div class="card-title"><?= $edit_data ? 'Edit Kamar' : 'Tambah Kamar Baru' ?></div>

        <?php if ($pesan): ?>
            <div class="alert alert-<?= $pesan['type'] ?>"><?= $pesan['teks'] ?></div>
        <?php endif; ?>

        <form method="POST">
            <?php if ($edit_data): ?>
                <input type="hidden" name="id_kamar" value="<?= $edit_data['id_kamar'] ?>">
            <?php endif; ?>

            <div class="form-group">
                <label>Nama Kamar</label>
                <input type="text" name="nama_kamar" placeholder="cth: Kamar A1"
                    value="<?= $edit_data ? htmlspecialchars($edit_data['nama_kamar']) : '' ?>" required>
            </div>
            <div class="form-group">
                <label>Harga / Bulan (Rp)</label>
                <input type="number" name="harga" placeholder="cth: 750000"
                    value="<?= $edit_data ? $edit_data['harga'] : '' ?>" required>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="tersedia"       <?= ($edit_data && $edit_data['status'] == 'tersedia')       ? 'selected' : '' ?>>Tersedia</option>
                    <option value="tidak tersedia" <?= ($edit_data && $edit_data['status'] == 'tidak tersedia') ? 'selected' : '' ?>>Tidak Tersedia</option>
                </select>
            </div>
            <div class="form-group">
                <label>Fasilitas</label>
                <textarea name="fasilitas" placeholder="cth: AC, WiFi, Kamar Mandi Dalam"><?= $edit_data ? htmlspecialchars($edit_data['fasilitas']) : '' ?></textarea>
            </div>
            <div class="form-group">
                <label>Deskripsi</label>
                <textarea name="deskripsi" placeholder="Deskripsi singkat kamar..."><?= $edit_data ? htmlspecialchars($edit_data['deskripsi']) : '' ?></textarea>
            </div>

            <button type="submit" name="<?= $edit_data ? 'edit' : 'tambah' ?>" class="btn btn-primary">
                <?= $edit_data ? '💾 Simpan Perubahan' : '+ Tambah Kamar' ?>
            </button>

            <?php if ($edit_data): ?>
                <a href="admin_kamar.php" class="btn btn-sm" style="margin-top:10px;width:100%;justify-content:center;background:var(--border);color:var(--text);">Batal</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- TABLE PANEL -->
    <div class="card">
        <div class="card-title">Daftar Kamar</div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Foto</th>
                        <th>Nama Kamar</th>
                        <th>Harga</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td>
                            <?php if ($row['foto_utama']): ?>
                                <img src="uploads/kamar/<?= htmlspecialchars($row['foto_utama']) ?>"
                                     class="thumb" alt="foto kamar">
                            <?php else: ?>
                                <div class="thumb-empty">📷</div>
                            <?php endif; ?>
                            <div class="foto-count">📁 <?= $row['jumlah_foto'] ?> foto</div>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($row['nama_kamar']) ?></strong>
                            <div style="font-size:.78rem;color:var(--muted);margin-top:2px"><?= htmlspecialchars(mb_strimwidth($row['fasilitas'], 0, 40, '...')) ?></div>
                        </td>
                        <td class="harga-text">Rp <?= number_format($row['harga'], 0, ',', '.') ?></td>
                        <td>
                            <?php if ($row['status'] == 'tersedia'): ?>
                                <span class="badge badge-tersedia">Tersedia</span>
                            <?php else: ?>
                                <span class="badge badge-tidak">Tidak Tersedia</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="actions">
                                <a href="admin_gambar.php?id_kamar=<?= $row['id_kamar'] ?>" class="btn btn-sm btn-foto" title="Kelola Foto">📷 Foto</a>
                                <a href="?edit=<?= $row['id_kamar'] ?>" class="btn btn-sm btn-edit">✏️ Edit</a>
                                <a href="?hapus=<?= $row['id_kamar'] ?>"
                                   class="btn btn-sm btn-hapus"
                                   onclick="return confirm('Yakin hapus kamar ini? Semua foto akan ikut terhapus.')">🗑️</a>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
</body>
</html>