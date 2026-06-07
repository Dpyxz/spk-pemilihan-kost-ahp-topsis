<?php
session_start();
if (!isset($_SESSION['user'])) { header('Location: login.php'); exit; }
require_once 'koneksi.php';
require_once 'fungsi_ahp.php';
require_once 'fungsi_topsis.php';

// Hitung AHP semua responden
$responden_all = $conn->query("SELECT id, nama FROM responden ORDER BY id");
$data_responden = [];
while ($r = $responden_all->fetch_assoc()) {
    $matriks = ambilMatriksResponden($conn, $r['id']);
    $hasil_r = hitungAHP($matriks);
    $status  = $hasil_r['konsisten'] ? 'konsisten' : 'tidak_konsisten';
    $cr      = $hasil_r['CR'];
    $conn->query("UPDATE responden SET status='$status', cr=$cr WHERE id={$r['id']}");
    $data_responden[] = array_merge($r, $hasil_r, ['matriks' => $matriks]);
}

// Bobot final AHP
$bobot_final = hitungBobotFinal($conn);

// Update bobot ke tabel kriteria
$kriteria_ids = $conn->query("SELECT id FROM kriteria ORDER BY id");
$idx = 0;
while ($k = $kriteria_ids->fetch_assoc()) {
    $b = $bobot_final[$idx];
    $conn->query("UPDATE kriteria SET bobot=$b WHERE id={$k['id']}");
    $idx++;
}

// Ambil data kriteria
$kriteria_data = [];
$res_k = $conn->query("SELECT * FROM kriteria ORDER BY id");
while ($k = $res_k->fetch_assoc()) $kriteria_data[] = $k;

// Hitung TOPSIS
$hasil_topsis = hitungTOPSIS($conn, $bobot_final);
$ranking      = $hasil_topsis['alternatif'];
$terbaik      = $ranking[0];

$nama_kriteria = ['Harga', 'Jarak', 'Fasilitas', 'WiFi', 'Keamanan'];
$user_nama = htmlspecialchars($_SESSION['nama'] ?? $_SESSION['user']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SPK Pemilihan Tempat Kost | AHP & TOPSIS</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    :root {
      --pink-50:#fff0f5; --pink-100:#ffe0ec; --pink-200:#ffc2d4;
      --pink-300:#ff9abf; --pink-400:#ff6fa3; --pink-500:#f04e8a; --pink-600:#d43070;
      --rose-50:#fff5f7; --nude-50:#fdf8f8; --nude-100:#f8f0f2;
      --text-dark:#2d1b25; --text-mid:#6b4158; --text-light:#b08090;
      --white:#ffffff;
      --shadow-card:0 2px 16px rgba(0,0,0,0.06);
      --radius:16px; --radius-sm:10px;
    }
    *{box-sizing:border-box;margin:0;padding:0;}
    body{font-family:'DM Sans',sans-serif;background:var(--nude-50);color:var(--text-dark);}

    /* SIDEBAR */
    .sidebar{position:fixed;top:0;left:0;width:240px;height:100vh;background:var(--white);border-right:1px solid var(--pink-100);display:flex;flex-direction:column;padding:22px 18px;z-index:100;overflow-y:auto;}
    .sidebar-logo{display:flex;align-items:center;gap:10px;margin-bottom:28px;}
    .logo-icon{width:42px;height:42px;background:linear-gradient(135deg,var(--pink-400),var(--pink-600));border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;color:white;flex-shrink:0;}
    .logo-text{font-family:'Playfair Display',serif;font-size:17px;font-weight:700;color:var(--text-dark);line-height:1.2;}
    .logo-sub{font-size:10px;color:var(--text-light);letter-spacing:0.5px;}
    .nav-label{font-size:9px;font-weight:700;letter-spacing:1.4px;text-transform:uppercase;color:var(--text-light);padding:0 8px;margin:14px 0 5px;}
    .nav-item{display:flex;align-items:center;gap:9px;padding:9px 11px;border-radius:var(--radius-sm);text-decoration:none;color:var(--text-mid);font-size:13px;font-weight:500;transition:all 0.2s;cursor:pointer;margin-bottom:1px;}
    .nav-item:hover,.nav-item.active{background:var(--pink-50);color:var(--pink-500);}
    .nav-icon{font-size:15px;width:20px;text-align:center;flex-shrink:0;}
    .nav-badge{margin-left:auto;background:var(--pink-100);color:var(--pink-500);font-size:10px;font-weight:700;padding:2px 7px;border-radius:20px;}
    .user-box{margin-top:auto;padding:11px 13px;background:var(--pink-50);border-radius:var(--radius-sm);border:1px solid var(--pink-100);}
    .user-box-name{font-size:12px;font-weight:600;color:var(--text-dark);margin-bottom:2px;}
    .user-box-role{font-size:10px;color:var(--text-light);}
    .user-box-logout{display:block;margin-top:8px;font-size:11px;color:var(--pink-500);text-decoration:none;font-weight:600;}
    .user-box-logout:hover{color:var(--pink-600);}

    /* MAIN */
    .main{margin-left:240px;padding:28px 32px;min-height:100vh;}
    .section{display:none;}
    .section.active{display:block;animation:fadeIn 0.25s ease;}
    @keyframes fadeIn{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:translateY(0)}}

    /* PAGE HEADER */
    .page-header{margin-bottom:22px;}
    .page-title{font-family:'Playfair Display',serif;font-size:26px;font-weight:700;color:var(--text-dark);}
    .page-sub{color:var(--text-light);font-size:13px;margin-top:3px;}

    /* STATS */
    .stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:22px;}
    .stat-card{background:var(--white);border-radius:var(--radius);padding:18px 20px;box-shadow:var(--shadow-card);border:1px solid var(--pink-100);}
    .stat-icon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:17px;margin-bottom:10px;}
    .ic-pink{background:var(--pink-50);} .ic-rose{background:#fff0f0;} .ic-mint{background:#f0fff8;} .ic-lav{background:#f5f0ff;}
    .stat-value{font-size:24px;font-weight:700;color:var(--text-dark);line-height:1;}
    .stat-label{font-size:11px;color:var(--text-light);font-weight:500;margin-top:3px;}

    /* CARD */
    .card{background:var(--white);border-radius:var(--radius);border:1px solid var(--pink-100);box-shadow:var(--shadow-card);overflow:hidden;margin-bottom:18px;}
    .card-header{padding:15px 22px;border-bottom:1px solid var(--pink-50);display:flex;align-items:center;gap:9px;}
    .card-title{font-family:'Playfair Display',serif;font-size:15px;font-weight:600;color:var(--text-dark);}
    .card-badge{background:var(--pink-50);color:var(--pink-500);font-size:10px;font-weight:600;padding:3px 10px;border-radius:20px;margin-left:auto;}
    .card-body{padding:18px 22px;}

    /* TABLE */
    .tbl-wrap{overflow-x:auto;}
    table{width:100%;border-collapse:collapse;font-size:12.5px;}
    th{background:var(--pink-50);color:var(--pink-600);font-weight:600;padding:9px 12px;text-align:center;font-size:11px;letter-spacing:0.3px;border-bottom:2px solid var(--pink-100);}
    th:first-child{text-align:left;}
    td{padding:9px 12px;text-align:center;border-bottom:1px solid var(--nude-100);color:var(--text-dark);}
    td:first-child{text-align:left;font-weight:500;}
    tr:last-child td{border-bottom:none;}
    tr:hover td{background:var(--pink-50);}

    /* PILLS */
    .pill{display:inline-block;padding:2px 9px;border-radius:20px;font-size:10px;font-weight:600;}
    .pill-green{background:#f0fff4;color:#2d8a4e;} .pill-red{background:#fff0f0;color:#c0392b;}
    .pill-pink{background:var(--pink-50);color:var(--pink-600);}
    .pill-cost{background:#fff5e6;color:#d4700a;} .pill-benefit{background:#f0fff8;color:#2d7a4e;}

    /* WINNER BANNER */
    .winner-banner{background:linear-gradient(135deg,var(--pink-400),var(--pink-600));border-radius:var(--radius);padding:20px 26px;color:white;display:flex;align-items:center;gap:18px;margin-bottom:20px;}
    .winner-banner .w-icon{font-size:42px;flex-shrink:0;}
    .winner-banner h2{font-family:'Playfair Display',serif;font-size:20px;font-weight:700;margin-bottom:3px;}
    .winner-banner p{font-size:13px;opacity:0.85;}
    .winner-banner .w-score{margin-left:auto;text-align:center;flex-shrink:0;}
    .winner-banner .w-val{font-size:32px;font-weight:700;}
    .winner-banner .w-lbl{font-size:10px;opacity:0.8;}

    /* RANK GRID */
    .rank-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:12px;}
    .rank-card{border-radius:var(--radius);padding:16px 14px;text-align:center;border:1px solid var(--pink-100);position:relative;overflow:hidden;}
    .rank-card.r1{background:linear-gradient(135deg,var(--pink-50),#fff5f7);border-color:var(--pink-200);}
    .rank-card.ro{background:var(--white);}
    .rank-num{font-size:10px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--text-light);margin-bottom:5px;}
    .r1 .rank-num{color:var(--pink-500);}
    .rank-name{font-family:'Playfair Display',serif;font-size:14px;font-weight:700;color:var(--text-dark);margin-bottom:6px;}
    .rank-score{font-size:20px;font-weight:700;color:var(--pink-500);margin-bottom:3px;}
    .rank-lbl{font-size:9px;color:var(--text-light);}
    .crown{position:absolute;top:8px;right:8px;font-size:16px;}

    /* BOBOT BAR */
    .bobot-item{display:flex;align-items:center;gap:12px;margin-bottom:12px;}
    .bobot-label{width:120px;font-size:12px;font-weight:500;color:var(--text-mid);flex-shrink:0;}
    .bobot-bar-wrap{flex:1;background:var(--pink-50);border-radius:8px;height:9px;overflow:hidden;}
    .bobot-bar{height:100%;background:linear-gradient(90deg,var(--pink-400),var(--pink-600));border-radius:8px;}
    .bobot-val{width:52px;text-align:right;font-size:12px;font-weight:600;color:var(--pink-600);}

    /* RESPONDEN GRID */
    .resp-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:18px;}
    .resp-card{border-radius:var(--radius-sm);padding:14px;border:1px solid var(--pink-100);background:var(--white);}
    .resp-name{font-size:12px;font-weight:700;color:var(--text-dark);margin-bottom:8px;line-height:1.3;}
    .resp-row{display:flex;justify-content:space-between;font-size:11px;color:var(--text-light);margin-bottom:3px;}
    .resp-row span:last-child{color:var(--text-dark);font-weight:500;}

    /* LAYOUT */
    .two-col{display:grid;grid-template-columns:1fr 1fr;gap:18px;}

    /* ACTION BTNS */
    .action-bar{display:flex;gap:10px;margin-bottom:18px;flex-wrap:wrap;}
    .btn-action{display:inline-flex;align-items:center;gap:7px;padding:10px 18px;border-radius:10px;font-size:13px;font-weight:600;font-family:'DM Sans',sans-serif;text-decoration:none;transition:all 0.2s;cursor:pointer;border:none;}
    .btn-pink{background:linear-gradient(135deg,var(--pink-400),var(--pink-600));color:white;box-shadow:0 3px 12px rgba(240,78,138,0.2);}
    .btn-pink:hover{opacity:0.9;transform:translateY(-1px);}
    .btn-outline{background:var(--white);color:var(--pink-500);border:1.5px solid var(--pink-200);}
    .btn-outline:hover{background:var(--pink-50);}

    /* INFO BOX */
    .info-box{background:var(--pink-50);border:1px solid var(--pink-100);border-radius:10px;padding:12px 16px;font-size:12px;color:var(--text-mid);margin-bottom:14px;line-height:1.6;}
    .info-box strong{color:var(--pink-600);}

    /* HIGHLIGHT */
    .hl-max{background:#f0fff4!important;color:#2d8a4e;font-weight:700;}
    .hl-min{background:#fff0f0!important;color:#c0392b;font-weight:700;}
    .hl-rank1{background:var(--pink-50)!important;}
  </style>
</head>
<body>

<!-- =========== SIDEBAR =========== -->
<aside class="sidebar">
  <div class="sidebar-logo">
    <div class="logo-icon">🏠</div>
    <div>
      <div class="logo-text">SPK Kost</div>
      <div class="logo-sub">AHP &amp; TOPSIS</div>
    </div>
  </div>

  <div class="nav-label">Utama</div>
  <a class="nav-item active" onclick="show('dashboard')"><span class="nav-icon">📊</span>Dashboard</a>
  <a class="nav-item" onclick="show('kriteria')"><span class="nav-icon">📋</span>Kriteria &amp; Bobot</a>
  <a class="nav-item" onclick="show('alternatif')"><span class="nav-icon">🏘️</span>Data Alternatif</a>

  <div class="nav-label">Metode AHP</div>
  <a class="nav-item" onclick="show('responden')"><span class="nav-icon">👥</span>Responden<span class="nav-badge">5</span></a>
  <a class="nav-item" onclick="show('matriks')"><span class="nav-icon">🔢</span>Matriks AHP</a>
  <a class="nav-item" onclick="show('bobot')"><span class="nav-icon">⚖️</span>Bobot Final</a>

  <div class="nav-label">Metode TOPSIS</div>
  <a class="nav-item" onclick="show('normalisasi')"><span class="nav-icon">📐</span>Normalisasi</a>
  <a class="nav-item" onclick="show('ideal')"><span class="nav-icon">🎯</span>Solusi Ideal</a>
  <a class="nav-item" onclick="show('ranking')"><span class="nav-icon">🏆</span>Hasil Ranking</a>

  <div class="nav-label">Data &amp; Laporan</div>
  <a class="nav-item" href="crud_kriteria.php"><span class="nav-icon">📋</span>Kelola Kriteria</a>
  <a class="nav-item" href="crud_alternatif.php"><span class="nav-icon">✏️</span>Kelola Alternatif</a>
  <a class="nav-item" href="crud_responden.php"><span class="nav-icon">👤</span>Kelola Responden</a>
  <a class="nav-item" href="laporan.php" target="_blank"><span class="nav-icon">📄</span>Cetak Laporan</a>

  <div class="user-box">
    <div class="user-box-name">👤 <?= $user_nama ?></div>
    <div class="user-box-role">Administrator</div>
    <a href="logout.php" class="user-box-logout">🚪 Logout</a>
  </div>
</aside>

<!-- =========== MAIN =========== -->
<main class="main">

<!-- ====== DASHBOARD ====== -->
<section id="dashboard" class="section active">
  <div class="page-header">
    <div class="page-title">Dashboard SPK Pemilihan Kost</div>
    <div class="page-sub">Sistem Pendukung Keputusan · Metode AHP &amp; TOPSIS · Selamat datang, <?= $user_nama ?>!</div>
  </div>

  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon ic-pink">🏘️</div>
      <div class="stat-value"><?= $conn->query("SELECT COUNT(*) as n FROM alternatif")->fetch_assoc()['n'] ?></div>
      <div class="stat-label">Alternatif Kost</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon ic-rose">📋</div>
      <div class="stat-value">5</div>
      <div class="stat-label">Kriteria Penilaian</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon ic-mint">👥</div>
      <div class="stat-value"><?= count($data_responden) ?></div>
      <div class="stat-label">Total Responden</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon ic-lav">✅</div>
      <div class="stat-value"><?= count(array_filter($data_responden, fn($r)=>$r['konsisten'])) ?></div>
      <div class="stat-label">Responden Konsisten</div>
    </div>
  </div>

  <div class="winner-banner">
    <div class="w-icon">🏆</div>
    <div>
      <h2>Kost Terbaik: <?= htmlspecialchars($terbaik['nama']) ?></h2>
      <p>Rekomendasi terbaik berdasarkan AHP &amp; TOPSIS dari 5 kriteria dan <?= count($ranking) ?> alternatif</p>
    </div>
    <div class="w-score">
      <div class="w-val"><?= number_format($terbaik['preferensi'],4) ?></div>
      <div class="w-lbl">Nilai Preferensi</div>
    </div>
  </div>

  <div class="action-bar">
    <a href="crud_kriteria.php" class="btn-action btn-pink">📋 Kelola Kriteria</a>
    <a href="crud_alternatif.php" class="btn-action btn-pink">✏️ Kelola Alternatif</a>
    <a href="crud_responden.php" class="btn-action btn-outline">👥 Kelola Responden</a>
    <a href="laporan.php" target="_blank" class="btn-action btn-outline">📄 Cetak Laporan</a>
  </div>

  <div class="card">
    <div class="card-header"><span>🏅</span><div class="card-title">Ranking Alternatif Kost</div><span class="card-badge">TOPSIS Result</span></div>
    <div class="card-body">
      <div class="rank-grid">
        <?php foreach ($ranking as $r): ?>
        <div class="rank-card <?= $r['ranking']==1?'r1':'ro' ?>">
          <?php if($r['ranking']==1): ?><div class="crown">👑</div><?php endif; ?>
          <div class="rank-num">RANK #<?= $r['ranking'] ?></div>
          <div class="rank-name"><?= htmlspecialchars($r['nama']) ?></div>
          <div class="rank-score"><?= number_format($r['preferensi'],4) ?></div>
          <div class="rank-lbl">Nilai Preferensi</div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><span>⚖️</span><div class="card-title">Bobot Kriteria (AHP)</div></div>
    <div class="card-body">
      <?php
      $maxB = max($bobot_final);
      foreach ($kriteria_data as $i => $k):
        $pct = ($bobot_final[$i]/$maxB)*100;
      ?>
      <div class="bobot-item">
        <div class="bobot-label"><?= htmlspecialchars($k['nama']) ?></div>
        <div class="bobot-bar-wrap"><div class="bobot-bar" style="width:<?= $pct ?>%"></div></div>
        <div class="bobot-val"><?= number_format($bobot_final[$i],4) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ====== KRITERIA ====== -->
<section id="kriteria" class="section">
  <div class="page-header"><div class="page-title">Kriteria &amp; Bobot</div><div class="page-sub">5 kriteria penilaian pemilihan tempat kost beserta bobot AHP</div></div>
  <div class="info-box"><strong>Keterangan:</strong> <span class="pill pill-cost">COST</span> = semakin kecil semakin baik &nbsp;|&nbsp; <span class="pill pill-benefit">BENEFIT</span> = semakin besar semakin baik</div>
  <div class="card">
    <div class="card-header"><span>📋</span><div class="card-title">Daftar Kriteria</div></div>
    <div class="card-body tbl-wrap">
      <table>
        <thead><tr><th>Kode</th><th>Nama Kriteria</th><th>Jenis</th><th>Bobot AHP</th><th>Persentase</th></tr></thead>
        <tbody>
          <?php foreach ($kriteria_data as $i => $k): ?>
          <tr>
            <td><?= $k['kode'] ?></td>
            <td><?= htmlspecialchars($k['nama']) ?></td>
            <td><span class="pill <?= $k['jenis']=='cost'?'pill-cost':'pill-benefit' ?>"><?= strtoupper($k['jenis']) ?></span></td>
            <td><?= number_format($bobot_final[$i],6) ?></td>
            <td><?= number_format($bobot_final[$i]*100,2) ?>%</td>
          </tr>
          <?php endforeach; ?>
          <tr style="border-top:2px solid var(--pink-100)">
            <td colspan="3"><strong>Total</strong></td>
            <td><strong><?= number_format(array_sum($bobot_final),4) ?></strong></td>
            <td><strong>100%</strong></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</section>

<!-- ====== ALTERNATIF ====== -->
<section id="alternatif" class="section">
  <div class="page-header"><div class="page-title">Data Alternatif Kost</div><div class="page-sub">Matriks keputusan — nilai asli setiap alternatif</div></div>
  <div class="action-bar">
    <a href="crud_alternatif.php" class="btn-action btn-pink">✏️ Kelola Data Alternatif</a>
  </div>
  <div class="card">
    <div class="card-header"><span>🏘️</span><div class="card-title">Matriks Keputusan (X)</div><span class="card-badge">Data Asli</span></div>
    <div class="card-body tbl-wrap">
      <table>
        <thead><tr><th>Kode</th><th>Nama Kost</th><th>Harga (rb/bln)</th><th>Jarak (km)</th><th>Fasilitas (1-5)</th><th>WiFi (1-5)</th><th>Keamanan (1-5)</th></tr></thead>
        <tbody>
          <?php $alt_data=$conn->query("SELECT * FROM alternatif ORDER BY id"); while($a=$alt_data->fetch_assoc()): ?>
          <tr>
            <td><?= $a['kode'] ?></td><td><?= htmlspecialchars($a['nama']) ?></td>
            <td>Rp <?= number_format($a['c1']) ?>rb</td>
            <td><?= $a['c2'] ?> km</td><td><?= $a['c3'] ?></td><td><?= $a['c4'] ?></td><td><?= $a['c5'] ?></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<!-- ====== RESPONDEN ====== -->
<section id="responden" class="section">
  <div class="page-header"><div class="page-title">Data Responden AHP</div><div class="page-sub">Uji konsistensi tiap responden — CR &lt; 0.1 dinyatakan konsisten</div></div>
  <div class="action-bar">
    <a href="crud_responden.php" class="btn-action btn-pink">👤 Kelola Responden</a>
  </div>
  <div class="resp-grid">
    <?php foreach ($data_responden as $idx => $r): ?>
    <div class="resp-card">
      <div class="resp-name"><?= htmlspecialchars($r['nama']) ?></div>
      <div class="resp-row"><span>λ Max</span><span><?= number_format($r['lambda_max'],4) ?></span></div>
      <div class="resp-row"><span>CI</span><span><?= number_format($r['CI'],4) ?></span></div>
      <div class="resp-row"><span>RI</span><span><?= $r['RI'] ?></span></div>
      <div class="resp-row"><span>CR</span><span style="font-weight:700;color:<?= $r['konsisten']?'#2d8a4e':'#c0392b' ?>"><?= number_format($r['CR'],4) ?></span></div>
      <div style="margin-top:9px;text-align:center">
        <span class="pill <?= $r['konsisten']?'pill-green':'pill-red' ?>"><?= $r['konsisten']?'✅ Konsisten':'❌ Tidak Konsisten' ?></span>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="card">
    <div class="card-header"><span>📊</span><div class="card-title">Bobot Prioritas per Responden</div></div>
    <div class="card-body tbl-wrap">
      <table>
        <thead>
          <tr><th>Responden</th><?php foreach($nama_kriteria as $nk): ?><th><?= $nk ?></th><?php endforeach; ?><th>Status</th></tr>
        </thead>
        <tbody>
          <?php foreach ($data_responden as $r): ?>
          <tr>
            <td><?= htmlspecialchars($r['nama']) ?></td>
            <?php foreach ($r['prioritas'] as $p): ?><td><?= number_format($p,4) ?></td><?php endforeach; ?>
            <td><span class="pill <?= $r['konsisten']?'pill-green':'pill-red' ?>"><?= $r['konsisten']?'Konsisten':'Tidak' ?></span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<!-- ====== MATRIKS AHP ====== -->
<section id="matriks" class="section">
  <div class="page-header"><div class="page-title">Matriks Perbandingan AHP</div><div class="page-sub">Matriks berpasangan 5×5 dari tiap responden</div></div>
  <?php foreach ($data_responden as $r):
    $matriks = $r['matriks'];
  ?>
  <div class="card" style="margin-bottom:14px">
    <div class="card-header">
      <span>🔢</span>
      <div class="card-title"><?= htmlspecialchars($r['nama']) ?></div>
      <span class="card-badge" style="background:<?= $r['konsisten']?'#f0fff4':'#fff0f0' ?>;color:<?= $r['konsisten']?'#2d8a4e':'#c0392b' ?>">
        CR = <?= number_format($r['CR'],4) ?> — <?= $r['konsisten']?'KONSISTEN':'TIDAK KONSISTEN' ?>
      </span>
    </div>
    <div class="card-body tbl-wrap">
      <table>
        <thead><tr><th>Kriteria</th><?php foreach($nama_kriteria as $nk): ?><th><?= $nk ?></th><?php endforeach; ?></tr></thead>
        <tbody>
          <?php foreach ($matriks as $i => $baris): ?>
          <tr>
            <td><?= $nama_kriteria[$i] ?></td>
            <?php foreach ($baris as $val): ?><td><?= ($val==round($val))?number_format($val,0):number_format($val,3) ?></td><?php endforeach; ?>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endforeach; ?>
</section>

<!-- ====== BOBOT FINAL ====== -->
<section id="bobot" class="section">
  <div class="page-header"><div class="page-title">Bobot Final AHP</div><div class="page-sub">Geometric mean dari seluruh responden konsisten, kemudian dinormalisasi</div></div>
  <div class="two-col">
    <div class="card">
      <div class="card-header"><span>⚖️</span><div class="card-title">Tabel Bobot Final</div></div>
      <div class="card-body tbl-wrap">
        <table>
          <thead><tr><th>Kriteria</th><th>Jenis</th><th>Bobot</th><th>%</th></tr></thead>
          <tbody>
            <?php foreach ($kriteria_data as $i => $k): ?>
            <tr>
              <td><?= htmlspecialchars($k['nama']) ?></td>
              <td><span class="pill <?= $k['jenis']=='cost'?'pill-cost':'pill-benefit' ?>"><?= strtoupper($k['jenis']) ?></span></td>
              <td><strong><?= number_format($bobot_final[$i],6) ?></strong></td>
              <td><?= number_format($bobot_final[$i]*100,2) ?>%</td>
            </tr>
            <?php endforeach; ?>
            <tr style="border-top:2px solid var(--pink-100)">
              <td colspan="2"><strong>Total</strong></td>
              <td><strong><?= number_format(array_sum($bobot_final),4) ?></strong></td>
              <td><strong>100%</strong></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    <div class="card">
      <div class="card-header"><span>📊</span><div class="card-title">Visualisasi Bobot</div></div>
      <div class="card-body">
        <?php $mxB=max($bobot_final); foreach($kriteria_data as $i=>$k): ?>
        <div class="bobot-item">
          <div class="bobot-label"><?= $k['nama'] ?></div>
          <div class="bobot-bar-wrap"><div class="bobot-bar" style="width:<?= ($bobot_final[$i]/$mxB)*100 ?>%"></div></div>
          <div class="bobot-val"><?= number_format($bobot_final[$i],4) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<!-- ====== NORMALISASI ====== -->
<section id="normalisasi" class="section">
  <div class="page-header"><div class="page-title">Normalisasi TOPSIS</div><div class="page-sub">Matriks normalisasi R dan normalisasi terbobot Y</div></div>
  <div class="info-box">
    <strong>Rumus Normalisasi:</strong> r<sub>ij</sub> = x<sub>ij</sub> / √Σx<sub>ij</sub>² &nbsp;|&nbsp;
    <strong>Normalisasi Terbobot:</strong> y<sub>ij</sub> = r<sub>ij</sub> × w<sub>j</sub>
  </div>
  <div class="card">
    <div class="card-header"><span>📐</span><div class="card-title">Matriks Normalisasi (R)</div></div>
    <div class="card-body tbl-wrap">
      <table>
        <thead><tr><th>Alternatif</th><?php foreach($nama_kriteria as $nk): ?><th><?= $nk ?></th><?php endforeach; ?></tr></thead>
        <tbody>
          <?php foreach ($hasil_topsis['alternatif'] as $a): ?>
          <tr><td><?= htmlspecialchars($a['nama']) ?></td><?php foreach($a['R'] as $v): ?><td><?= number_format($v,6) ?></td><?php endforeach; ?></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><span>📊</span><div class="card-title">Matriks Normalisasi Terbobot (Y)</div></div>
    <div class="card-body tbl-wrap">
      <table>
        <thead><tr><th>Alternatif</th><?php foreach($nama_kriteria as $nk): ?><th><?= $nk ?></th><?php endforeach; ?></tr></thead>
        <tbody>
          <?php foreach ($hasil_topsis['alternatif'] as $a): ?>
          <tr><td><?= htmlspecialchars($a['nama']) ?></td><?php foreach($a['Y'] as $v): ?><td><?= number_format($v,6) ?></td><?php endforeach; ?></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<!-- ====== SOLUSI IDEAL ====== -->
<section id="ideal" class="section">
  <div class="page-header"><div class="page-title">Solusi Ideal TOPSIS</div><div class="page-sub">A⁺ Solusi Ideal Positif &amp; A⁻ Solusi Ideal Negatif</div></div>
  <div class="info-box">
    <strong>COST:</strong> A⁺ = nilai terkecil, A⁻ = nilai terbesar &nbsp;|&nbsp;
    <strong>BENEFIT:</strong> A⁺ = nilai terbesar, A⁻ = nilai terkecil
  </div>
  <div class="two-col">
    <div class="card">
      <div class="card-header"><span>✅</span><div class="card-title">Solusi Ideal Positif (A⁺)</div></div>
      <div class="card-body tbl-wrap">
        <table>
          <thead><tr><th>Kriteria</th><th>Jenis</th><th>Nilai A⁺</th></tr></thead>
          <tbody>
            <?php foreach($nama_kriteria as $j=>$nk): ?>
            <tr>
              <td><?= $nk ?></td>
              <td><span class="pill <?= $kriteria_data[$j]['jenis']=='cost'?'pill-cost':'pill-benefit' ?>"><?= strtoupper($kriteria_data[$j]['jenis']) ?></span></td>
              <td class="hl-max"><?= number_format($hasil_topsis['Aplus'][$j],6) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <div class="card">
      <div class="card-header"><span>❌</span><div class="card-title">Solusi Ideal Negatif (A⁻)</div></div>
      <div class="card-body tbl-wrap">
        <table>
          <thead><tr><th>Kriteria</th><th>Jenis</th><th>Nilai A⁻</th></tr></thead>
          <tbody>
            <?php foreach($nama_kriteria as $j=>$nk): ?>
            <tr>
              <td><?= $nk ?></td>
              <td><span class="pill <?= $kriteria_data[$j]['jenis']=='cost'?'pill-cost':'pill-benefit' ?>"><?= strtoupper($kriteria_data[$j]['jenis']) ?></span></td>
              <td class="hl-min"><?= number_format($hasil_topsis['Amin'][$j],6) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="card">
    <div class="card-header"><span>📏</span><div class="card-title">Jarak ke Solusi Ideal (D⁺ dan D⁻)</div></div>
    <div class="card-body tbl-wrap">
      <table>
        <thead><tr><th>Alternatif</th><th>D⁺ (ke Ideal Positif)</th><th>D⁻ (ke Ideal Negatif)</th><th>Vi = D⁻/(D⁺+D⁻)</th></tr></thead>
        <tbody>
          <?php foreach ($ranking as $a): ?>
          <tr>
            <td><?= htmlspecialchars($a['nama']) ?></td>
            <td><?= number_format($a['Dplus'],6) ?></td>
            <td><?= number_format($a['Dmin'],6) ?></td>
            <td style="font-weight:700;color:var(--pink-600)"><?= number_format($a['preferensi'],6) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<!-- ====== RANKING ====== -->
<section id="ranking" class="section">
  <div class="page-header"><div class="page-title">Hasil Ranking TOPSIS</div><div class="page-sub">Semakin tinggi nilai preferensi Vi, semakin baik alternatif tersebut</div></div>
  <div class="winner-banner">
    <div class="w-icon">🏆</div>
    <div>
      <h2>🎉 <?= htmlspecialchars($terbaik['nama']) ?> — Tempat Kost Terbaik!</h2>
      <p>Rekomendasi terbaik berdasarkan metode AHP &amp; TOPSIS</p>
    </div>
    <div class="w-score">
      <div class="w-val"><?= number_format($terbaik['preferensi'],4) ?></div>
      <div class="w-lbl">Nilai Preferensi Tertinggi</div>
    </div>
  </div>
  <div class="action-bar">
    <a href="laporan.php" target="_blank" class="btn-action btn-pink">📄 Cetak Laporan Lengkap</a>
  </div>
  <div class="card">
    <div class="card-header"><span>🏅</span><div class="card-title">Tabel Ranking Lengkap</div></div>
    <div class="card-body tbl-wrap">
      <table>
        <thead>
          <tr><th>Ranking</th><th>Kode</th><th>Nama Kost</th><th>D⁺</th><th>D⁻</th><th>Nilai Vi</th><th>Keterangan</th></tr>
        </thead>
        <tbody>
          <?php foreach ($ranking as $r): ?>
          <tr <?= $r['ranking']==1?'class="hl-rank1"':'' ?>>
            <td><strong><?= $r['ranking']==1?'👑':'' ?> #<?= $r['ranking'] ?></strong></td>
            <td><?= $r['kode'] ?></td>
            <td><?= htmlspecialchars($r['nama']) ?></td>
            <td><?= number_format($r['Dplus'],6) ?></td>
            <td><?= number_format($r['Dmin'],6) ?></td>
            <td style="color:var(--pink-600);font-weight:700"><?= number_format($r['preferensi'],6) ?></td>
            <td>
              <?php if($r['ranking']==1): ?><span class="pill pill-green">⭐ Terbaik</span>
              <?php elseif($r['ranking']==2): ?><span class="pill pill-pink">👍 Baik</span>
              <?php else: ?><span class="pill" style="background:#f8f8f8;color:#999">Alternatif</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

</main>

<script>
function show(id) {
  document.querySelectorAll('.section').forEach(s=>s.classList.remove('active'));
  document.getElementById(id).classList.add('active');
  document.querySelectorAll('.nav-item').forEach(n=>{
    n.classList.remove('active');
    if(n.getAttribute('onclick')?.includes("'"+id+"'")) n.classList.add('active');
  });
}
</script>
</body>
</html>
