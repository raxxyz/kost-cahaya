<?php
session_start();
include "service/database.php";

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$id_kamar = isset($_GET['id_kamar']) ? (int) $_GET['id_kamar'] : 0;
$pesan    = "";

// Cek kamar valid
$res_kamar = mysqli_query($db, "SELECT * FROM kamar WHERE id_kamar = $id_kamar");
if (mysqli_num_rows($res_kamar) === 0) {
    die("<div style='text-align:center;padding:60px;font-family:sans-serif;color:#e8eaf0;background:#0f1117;min-height:100vh'>
            <h3>Kamar tidak ditemukan.</h3>
            <a href='admin_kamar.php' style='color:#f0a500'>← Kembali</a>
         </div>");
}
$kamar = mysqli_fetch_assoc($res_kamar);

// Buat folder jika belum ada
if (!is_dir('uploads/kamar')) mkdir('uploads/kamar', 0755, true);

// UPLOAD GAMBAR
if (isset($_POST['upload'])) {
    $is_utama = isset($_POST['is_utama']) ? 1 : 0;

    if (!isset($_FILES['gambar']) || $_FILES['gambar']['error'] !== 0) {
        $pesan = ["type" => "error", "teks" => "Pilih file gambar terlebih dahulu."];
    } else {
        $file    = $_FILES['gambar'];
        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $maxSize = 3 * 1024 * 1024; // 3MB

        if (!in_array($ext, $allowed)) {
            $pesan = ["type" => "error", "teks" => "Format tidak didukung. Gunakan JPG, PNG, atau WEBP."];
        } elseif ($file['size'] > $maxSize) {
            $pesan = ["type" => "error", "teks" => "Ukuran file terlalu besar. Maksimal 3MB."];
        } else {
            $nama_file = 'kamar_' . $id_kamar . '_' . time() . '.' . $ext;
            $tujuan    = 'uploads/kamar/' . $nama_file;

            if (move_uploaded_file($file['tmp_name'], $tujuan)) {
                // Kalau set sebagai utama, reset yang lain
                if ($is_utama) {
                    mysqli_query($db, "UPDATE gambar_kamar SET is_utama = 0 WHERE id_kamar = $id_kamar");
                }
                // Kalau belum ada foto sama sekali, otomatis jadi utama
                $cek = mysqli_query($db, "SELECT COUNT(*) as c FROM gambar_kamar WHERE id_kamar = $id_kamar");
                $total = mysqli_fetch_assoc($cek)['c'];
                if ($total === 0) $is_utama = 1;

                $sql = "INSERT INTO gambar_kamar (id_kamar, nama_file, is_utama)
                        VALUES ($id_kamar, '$nama_file', $is_utama)";
                if (mysqli_query($db, $sql)) {
                    $pesan = ["type" => "sukses", "teks" => "Foto berhasil diupload!"];
                } else {
                    unlink($tujuan);
                    $pesan = ["type" => "error", "teks" => "Gagal menyimpan data foto."];
                }
            } else {
                $pesan = ["type" => "error", "teks" => "Gagal mengupload file."];
            }
        }
    }
}

// SET FOTO UTAMA
if (isset($_GET['utama'])) {
    $id_gambar = (int) $_GET['utama'];
    mysqli_query($db, "UPDATE gambar_kamar SET is_utama = 0 WHERE id_kamar = $id_kamar");
    mysqli_query($db, "UPDATE gambar_kamar SET is_utama = 1 WHERE id_gambar = $id_gambar AND id_kamar = $id_kamar");
    $pesan = ["type" => "sukses", "teks" => "Foto utama berhasil diubah!"];
}

// HAPUS FOTO
if (isset($_GET['hapus'])) {
    $id_gambar = (int) $_GET['hapus'];
    $res = mysqli_query($db, "SELECT * FROM gambar_kamar WHERE id_gambar = $id_gambar AND id_kamar = $id_kamar");
    if ($g = mysqli_fetch_assoc($res)) {
        $path = 'uploads/kamar/' . $g['nama_file'];
        if (file_exists($path)) unlink($path);
        mysqli_query($db, "DELETE FROM gambar_kamar WHERE id_gambar = $id_gambar");
        // Kalau foto yang dihapus adalah utama, set foto pertama yang tersisa jadi utama
        if ($g['is_utama']) {
            $sisa = mysqli_query($db, "SELECT id_gambar FROM gambar_kamar WHERE id_kamar = $id_kamar LIMIT 1");
            if ($s = mysqli_fetch_assoc($sisa)) {
                mysqli_query($db, "UPDATE gambar_kamar SET is_utama = 1 WHERE id_gambar = {$s['id_gambar']}");
            }
        }
        $pesan = ["type" => "sukses", "teks" => "Foto berhasil dihapus!"];
    }
}

// Ambil semua foto kamar ini
$gambar_result = mysqli_query($db, "SELECT * FROM gambar_kamar WHERE id_kamar = $id_kamar ORDER BY is_utama DESC, id_gambar ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Foto — <?= htmlspecialchars($kamar['nama_kamar']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg:      #0f1117;
            --surface: #181c27;
            --card:    #1e2333;
            --border:  #2a3050;
            --accent:  #f0a500;
            --accent2: #e06c00;
            --text:    #e8eaf0;
            --muted:   #7a82a0;
            --green:   #2ecc71;
            --red:     #e74c3c;
            --blue:    #3b82f6;
            --radius:  12px;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Sora', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; }

        .topbar { background: var(--surface); border-bottom: 1px solid var(--border); padding: 0 32px; height: 60px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 100; }
        .topbar-brand { font-size: 1.1rem; font-weight: 700; color: var(--accent); }
        .topbar-brand span { color: var(--text); }
        .topbar-nav { display: flex; gap: 8px; }
        .topbar-nav a { color: var(--muted); text-decoration: none; font-size: 0.82rem; font-weight: 500; padding: 6px 14px; border-radius: 8px; transition: all .2s; }
        .topbar-nav a:hover { color: var(--text); background: var(--border); }

        .page-wrap { max-width: 1000px; margin: 0 auto; padding: 36px 24px 60px; }

        .back-link { display: inline-flex; align-items: center; gap: 6px; color: var(--muted); font-size: .85rem; text-decoration: none; margin-bottom: 24px; transition: color .2s; }
        .back-link:hover { color: var(--text); }

        .page-title { font-size: 1.5rem; font-weight: 700; letter-spacing: -0.03em; margin-bottom: 4px; }
        .page-sub { color: var(--muted); font-size: .85rem; margin-bottom: 28px; }

        .layout { display: grid; grid-template-columns: 300px 1fr; gap: 24px; align-items: start; }

        .card { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); padding: 24px; }
        .card-title { font-size: .9rem; font-weight: 600; margin-bottom: 18px; display: flex; align-items: center; gap: 8px; }
        .card-title::before { content: ''; display: block; width: 4px; height: 16px; background: var(--accent); border-radius: 2px; }

        /* Upload area */
        .upload-area { border: 2px dashed var(--border); border-radius: 10px; padding: 28px 16px; text-align: center; cursor: pointer; transition: all .2s; position: relative; margin-bottom: 16px; }
        .upload-area:hover { border-color: var(--accent); background: rgba(240,165,0,.03); }
        .upload-area input[type=file] { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; }
        .upload-area .icon { font-size: 2rem; margin-bottom: 8px; }
        .upload-area p { font-size: .85rem; color: var(--muted); }
        .upload-area small { font-size: .75rem; color: #3a4260; }
        #previewWrap { margin-top: 12px; display: none; }
        #previewWrap img { max-height: 140px; border-radius: 8px; border: 1px solid var(--border); }

        .checkbox-row { display: flex; align-items: center; gap: 8px; margin-bottom: 16px; font-size: .85rem; color: var(--muted); cursor: pointer; }
        .checkbox-row input { width: 16px; height: 16px; accent-color: var(--accent); cursor: pointer; }

        /* Buttons */
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 10px 18px; border: none; border-radius: 8px; font-family: 'Sora', sans-serif; font-size: .88rem; font-weight: 600; cursor: pointer; transition: all .2s; text-decoration: none; }
        .btn-primary { background: var(--accent); color: #000; width: 100%; justify-content: center; }
        .btn-primary:hover { background: var(--accent2); color: #000; }
        .btn-sm { padding: 5px 10px; font-size: .75rem; }
        .btn-danger { background: rgba(231,76,60,.12); color: var(--red); border: 1px solid rgba(231,76,60,.25); }
        .btn-danger:hover { background: rgba(231,76,60,.22); }
        .btn-star { background: rgba(240,165,0,.1); color: var(--accent); border: 1px solid rgba(240,165,0,.2); }
        .btn-star:hover { background: rgba(240,165,0,.2); }

        /* Alert */
        .alert { padding: 11px 14px; border-radius: 8px; font-size: .85rem; font-weight: 500; margin-bottom: 20px; }
        .alert-sukses { background: rgba(46,204,113,.1); color: var(--green); border: 1px solid rgba(46,204,113,.2); }
        .alert-error  { background: rgba(231,76,60,.1); color: var(--red); border: 1px solid rgba(231,76,60,.2); }

        /* Grid foto */
        .foto-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 14px; }
        .foto-item { position: relative; border-radius: 10px; overflow: hidden; border: 2px solid var(--border); background: var(--bg); transition: border-color .2s; }
        .foto-item.utama { border-color: var(--accent); }
        .foto-item img { width: 100%; height: 140px; object-fit: cover; display: block; }
        .foto-item .overlay { position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(transparent, rgba(0,0,0,.8)); padding: 8px; display: flex; gap: 4px; justify-content: flex-end; }
        .foto-item .badge-utama { position: absolute; top: 8px; left: 8px; background: var(--accent); color: #000; font-size: .68rem; font-weight: 700; padding: 3px 8px; border-radius: 20px; }

        .empty-foto { text-align: center; padding: 48px 20px; color: var(--muted); }
        .empty-foto .icon { font-size: 3rem; margin-bottom: 12px; }

        @media (max-width: 700px) { .layout { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<nav class="topbar">
    <div class="topbar-brand">Kost <span>Cahaya</span> &mdash; Admin</div>
    <div class="topbar-nav">
        <a href="admin_kamar.php">← Kamar</a>
        <a href="logout.php">Keluar</a>
    </div>
</nav>

<div class="page-wrap">

    <a href="admin_kamar.php" class="back-link">← Kembali ke Daftar Kamar</a>

    <div class="page-title">📷 Kelola Foto Kamar</div>
    <div class="page-sub"><?= htmlspecialchars($kamar['nama_kamar']) ?> — Rp <?= number_format($kamar['harga'], 0, ',', '.') ?>/bulan</div>

    <?php if ($pesan): ?>
        <div class="alert alert-<?= $pesan['type'] ?>"><?= $pesan['teks'] ?></div>
    <?php endif; ?>

    <div class="layout">

        <!-- Upload Panel -->
        <div class="card">
            <div class="card-title">Upload Foto Baru</div>

            <form method="POST" enctype="multipart/form-data">
                <div class="upload-area" id="uploadArea">
                    <input type="file" name="gambar" accept=".jpg,.jpeg,.png,.webp" id="fileInput" onchange="previewFoto(this)">
                    <div class="icon" id="uploadIcon">📷</div>
                    <p id="uploadText">Klik atau seret foto ke sini</p>
                    <small>JPG, PNG, WEBP • Maks. 3MB</small>
                    <div id="previewWrap">
                        <img id="previewImg" src="" alt="Preview">
                        <div id="previewName" style="font-size:.75rem;color:var(--muted);margin-top:6px;"></div>
                    </div>
                </div>

                <label class="checkbox-row">
                    <input type="checkbox" name="is_utama" id="isUtama">
                    Jadikan foto utama (thumbnail)
                </label>

                <button type="submit" name="upload" class="btn btn-primary">
                    ⬆️ Upload Foto
                </button>
            </form>
        </div>

        <!-- Galeri Panel -->
        <div class="card">
            <div class="card-title">
                Foto Kamar
                <span style="font-size:.78rem;color:var(--muted);font-weight:400;margin-left:4px">
                    (<?= mysqli_num_rows($gambar_result) ?> foto)
                </span>
            </div>

            <?php if (mysqli_num_rows($gambar_result) === 0): ?>
                <div class="empty-foto">
                    <div class="icon">🖼️</div>
                    <p>Belum ada foto untuk kamar ini.</p>
                    <small>Upload foto pertama di panel kiri.</small>
                </div>
            <?php else: ?>
                <div class="foto-grid">
                <?php
                mysqli_data_seek($gambar_result, 0);
                while ($g = mysqli_fetch_assoc($gambar_result)):
                ?>
                    <div class="foto-item <?= $g['is_utama'] ? 'utama' : '' ?>">
                        <?php if ($g['is_utama']): ?>
                            <div class="badge-utama">⭐ Utama</div>
                        <?php endif; ?>
                        <img src="uploads/kamar/<?= htmlspecialchars($g['nama_file']) ?>"
                             alt="Foto kamar"
                             onerror="this.src='https://via.placeholder.com/160x140?text=No+Image'">
                        <div class="overlay">
                            <?php if (!$g['is_utama']): ?>
                                <a href="?id_kamar=<?= $id_kamar ?>&utama=<?= $g['id_gambar'] ?>"
                                   class="btn btn-sm btn-star" title="Jadikan utama">⭐</a>
                            <?php endif; ?>
                            <a href="?id_kamar=<?= $id_kamar ?>&hapus=<?= $g['id_gambar'] ?>"
                               class="btn btn-sm btn-danger"
                               onclick="return confirm('Hapus foto ini?')"
                               title="Hapus">🗑️</a>
                        </div>
                    </div>
                <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<script>
    function previewFoto(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            const reader = new FileReader();
            reader.onload = e => {
                document.getElementById('previewImg').src = e.target.result;
                document.getElementById('previewWrap').style.display = 'block';
                document.getElementById('uploadIcon').style.display = 'none';
                document.getElementById('uploadText').textContent = file.name;
                document.getElementById('previewName').textContent =
                    (file.size / 1024).toFixed(1) + ' KB';
            };
            reader.readAsDataURL(file);
        }
    }

    const area = document.getElementById('uploadArea');
    if (area) {
        area.addEventListener('dragover', () => area.style.borderColor = 'var(--accent)');
        area.addEventListener('dragleave', () => area.style.borderColor = 'var(--border)');
        area.addEventListener('drop', () => area.style.borderColor = 'var(--border)');
    }
</script>
</body>
</html>