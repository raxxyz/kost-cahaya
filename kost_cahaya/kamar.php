<?php
session_start();
include "service/database.php";

// Proteksi login user
if (!isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit;
}

// Ambil semua kamar beserta foto utama
$result = mysqli_query($db, "
    SELECT kamar.*,
           (SELECT nama_file FROM gambar_kamar
            WHERE gambar_kamar.id_kamar = kamar.id_kamar AND is_utama = 1
            LIMIT 1) AS foto_utama
    FROM kamar
    ORDER BY status ASC, id_kamar ASC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Kamar — Kost Cahaya</title>
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
        body { font-family: 'Sora', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; }

        .topbar { background: var(--surface); border-bottom: 1px solid var(--border); padding: 0 32px; height: 60px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 100; }
        .topbar-brand { font-size: 1.1rem; font-weight: 700; letter-spacing: -0.03em; color: var(--accent); }
        .topbar-brand span { color: var(--text); }
        .topbar-nav { display: flex; gap: 8px; align-items: center; }
        .topbar-nav a { color: var(--muted); text-decoration: none; font-size: 0.82rem; font-weight: 500; padding: 6px 14px; border-radius: 8px; transition: all .2s; }
        .topbar-nav a:hover, .topbar-nav a.active { color: var(--text); background: var(--border); }
        .topbar-user { font-size: 0.82rem; color: var(--muted); padding: 6px 14px; }

        .hero { background: linear-gradient(135deg, #181c27 0%, #1a1f30 100%); border-bottom: 1px solid var(--border); padding: 48px 32px 40px; text-align: center; }
        .hero h1 { font-size: 2rem; font-weight: 700; letter-spacing: -0.04em; margin-bottom: 8px; }
        .hero h1 span { color: var(--accent); }
        .hero p { color: var(--muted); font-size: 0.92rem; }

        .filter-bar { max-width: 1100px; margin: 28px auto 0; padding: 0 24px; display: flex; gap: 10px; flex-wrap: wrap; }
        .filter-btn { padding: 7px 18px; border-radius: 20px; border: 1px solid var(--border); background: var(--surface); color: var(--muted); font-family: 'Sora', sans-serif; font-size: 0.82rem; font-weight: 500; cursor: pointer; transition: all .2s; }
        .filter-btn:hover, .filter-btn.active { background: var(--accent); color: #000; border-color: var(--accent); }

        .grid { max-width: 1100px; margin: 24px auto 48px; padding: 0 24px; display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }

        .kamar-card { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; transition: transform .2s, box-shadow .2s; display: flex; flex-direction: column; }
        .kamar-card:hover { transform: translateY(-4px); box-shadow: 0 16px 40px rgba(0,0,0,.4); }
        .kamar-card.tidak-tersedia { opacity: .55; }

        /* Thumbnail foto */
        .card-thumb {
            height: 180px;
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, #1a2040 0%, #252d4a 100%);
        }
        .card-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform .3s;
        }
        .kamar-card:hover .card-thumb img { transform: scale(1.05); }
        .card-thumb .no-foto {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3.5rem;
        }
        .card-status-badge { position: absolute; top: 12px; right: 12px; padding: 3px 10px; border-radius: 20px; font-size: 0.72rem; font-weight: 600; }
        .badge-tersedia { background: rgba(46,204,113,.2); color: #2ecc71; border: 1px solid rgba(46,204,113,.3); }
        .badge-tidak    { background: rgba(231,76,60,.15);  color: #e74c3c; border: 1px solid rgba(231,76,60,.25); }

        .card-body { padding: 20px; flex: 1; display: flex; flex-direction: column; }
        .card-nama { font-size: 1.05rem; font-weight: 700; margin-bottom: 6px; }
        .card-harga { font-family: 'JetBrains Mono', monospace; font-size: 1.1rem; font-weight: 500; color: var(--accent); margin-bottom: 12px; }
        .card-harga span { font-size: 0.75rem; color: var(--muted); font-family: 'Sora', sans-serif; }
        .card-fasilitas { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 12px; }
        .chip { padding: 3px 10px; background: rgba(240,165,0,.1); border: 1px solid rgba(240,165,0,.2); color: var(--accent); border-radius: 20px; font-size: 0.72rem; font-weight: 500; }
        .card-deskripsi { font-size: 0.83rem; color: var(--muted); line-height: 1.6; flex: 1; margin-bottom: 18px; }

        .btn-booking { display: block; width: 100%; padding: 11px; background: var(--accent); color: #000; text-align: center; text-decoration: none; font-weight: 700; font-size: 0.88rem; border-radius: 8px; transition: background .2s; }
        .btn-booking:hover { background: var(--accent2); color: #000; }
        .btn-detail { display: block; width: 100%; padding: 11px; background: var(--border); color: var(--muted); text-align: center; text-decoration: none; font-weight: 600; font-size: 0.88rem; border-radius: 8px; transition: background .2s; }
        .btn-detail:hover { background: #334155; color: var(--text); }

        .empty { grid-column: 1/-1; text-align: center; padding: 60px 20px; color: var(--muted); }
        .empty span { font-size: 3rem; display: block; margin-bottom: 12px; }

        @media (max-width: 600px) { .topbar { padding: 0 16px; } .hero { padding: 32px 16px 28px; } .hero h1 { font-size: 1.4rem; } }
    </style>
</head>
<body>

<nav class="topbar">
    <div class="topbar-brand">Kost <span>Cahaya</span></div>
    <div class="topbar-nav">
        <span class="topbar-user">👤 <?= htmlspecialchars($_SESSION['nama']) ?></span>
        <a href="dashboard.php">Riwayat Booking</a>
        <a href="kamar.php" class="active">Kamar</a>
        <a href="logout.php">Keluar</a>
    </div>
</nav>

<div class="hero">
    <h1>Pilih <span>Kamar</span> Kamu</h1>
    <p>Temukan kamar yang sesuai kebutuhanmu dan langsung booking</p>
</div>

<div class="filter-bar">
    <button class="filter-btn active" onclick="filterKamar('semua', this)">Semua</button>
    <button class="filter-btn" onclick="filterKamar('tersedia', this)">Tersedia</button>
    <button class="filter-btn" onclick="filterKamar('tidak tersedia', this)">Tidak Tersedia</button>
</div>

<div class="grid" id="gridKamar">
<?php while ($row = mysqli_fetch_assoc($result)): ?>
    <?php
        $tersedia       = $row['status'] === 'tersedia';
        $fasilitas_list = array_map('trim', explode(',', $row['fasilitas']));
        $foto_utama     = $row['foto_utama'];
    ?>
    <div class="kamar-card <?= !$tersedia ? 'tidak-tersedia' : '' ?>"
         data-status="<?= htmlspecialchars($row['status']) ?>">

        <!-- Thumbnail -->
        <div class="card-thumb">
            <?php if ($foto_utama): ?>
                <img src="uploads/kamar/<?= htmlspecialchars($foto_utama) ?>"
                     alt="<?= htmlspecialchars($row['nama_kamar']) ?>"
                     onerror="this.parentElement.innerHTML='<div class=\'no-foto\'>🛏️</div>'">
            <?php else: ?>
                <div class="no-foto">🛏️</div>
            <?php endif; ?>
            <span class="card-status-badge <?= $tersedia ? 'badge-tersedia' : 'badge-tidak' ?>">
                <?= $tersedia ? 'Tersedia' : 'Tidak Tersedia' ?>
            </span>
        </div>

        <div class="card-body">
            <div class="card-nama"><?= htmlspecialchars($row['nama_kamar']) ?></div>
            <div class="card-harga">
                Rp <?= number_format($row['harga'], 0, ',', '.') ?>
                <span>/ bulan</span>
            </div>
            <?php if ($row['fasilitas']): ?>
            <div class="card-fasilitas">
                <?php foreach ($fasilitas_list as $f): ?>
                    <?php if ($f): ?><span class="chip"><?= htmlspecialchars($f) ?></span><?php endif; ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php if ($row['deskripsi']): ?>
            <div class="card-deskripsi"><?= htmlspecialchars(mb_strimwidth($row['deskripsi'], 0, 80, '...')) ?></div>
            <?php endif; ?>

            <?php if ($tersedia): ?>
                <a href="detail_kamar.php?id=<?= $row['id_kamar'] ?>" class="btn-booking">Lihat Detail →</a>
            <?php else: ?>
                <a href="detail_kamar.php?id=<?= $row['id_kamar'] ?>" class="btn-detail">Lihat Detail</a>
            <?php endif; ?>
        </div>
    </div>
<?php endwhile; ?>

<?php if (mysqli_num_rows(mysqli_query($db, "SELECT * FROM kamar")) == 0): ?>
    <div class="empty"><span>🏠</span>Belum ada kamar yang terdaftar.</div>
<?php endif; ?>
</div>

<script>
function filterKamar(status, btn) {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.kamar-card').forEach(card => {
        card.style.display = (status === 'semua' || card.dataset.status === status) ? '' : 'none';
    });
}
</script>
</body>
</html>
