<?php
session_start();
if (!isset($_SESSION['user'])) { header('Location: login.php'); exit; }
require_once 'koneksi.php';
require_once 'fungsi_ahp.php';
require_once 'fungsi_topsis.php';

// Hitung semua
$responden_all = $conn->query("SELECT id, nama FROM responden ORDER BY id");
$data_responden = [];
while ($r = $responden_all->fetch_assoc()) {
    $matriks  = ambilMatriksResponden($conn, $r['id']);
    $hasil_r  = hitungAHP($matriks);
    $status   = $hasil_r['konsisten'] ? 'konsisten' : 'tidak_konsisten';
    $cr       = $hasil_r['CR'];
    $conn->query("UPDATE responden SET status='$status', cr=$cr WHERE id={$r['id']}");
    $data_responden[] = array_merge($r, $hasil_r, ['matriks' => $matriks]);
}

$bobot_final = hitungBobotFinal($conn);

$kriteria_data = [];
$res_k = $conn->query("SELECT * FROM kriteria ORDER BY id");
while ($k = $res_k->fetch_assoc()) $kriteria_data[] = $k;

$hasil_topsis = hitungTOPSIS($conn, $bobot_final);
$ranking = $hasil_topsis['alternatif'];
$terbaik = $ranking[0];
$nama_k  = ['Harga Kost','Jarak ke Kampus','Fasilitas','WiFi','Keamanan'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Laporan SPK Pemilihan Tempat Kost</title>
  <style>
    @media print {
      .no-print { display: none !important; }
      body { margin: 0; }
      .page-break { page-break-before: always; }
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Times New Roman', serif; font-size: 12pt; color: #1a1a1a; background: #f5f5f5; }
    .print-area { background: white; max-width: 800px; margin: 0 auto; padding: 40px 48px; box-shadow: 0 2px 20px rgba(0,0,0,0.1); }

    /* Header laporan */
    .lap-header { text-align: center; border-bottom: 3px double #d43070; padding-bottom: 18px; margin-bottom: 24px; }
    .lap-logo { font-size: 28pt; margin-bottom: 6px; }
    .lap-title { font-size: 15pt; font-weight: bold; color: #2d1b25; line-height: 1.4; }
    .lap-sub { font-size: 10pt; color: #6b4158; margin-top: 4px; }
    .lap-meta { font-size: 9pt; color: #888; margin-top: 8px; }

    /* Section */
    .section-title {
      font-size: 12pt; font-weight: bold; color: white;
      background: linear-gradient(90deg, #d43070, #ff6fa3);
      padding: 7px 14px; border-radius: 6px;
      margin: 22px 0 12px;
    }
    .sub-title {
      font-size: 11pt; font-weight: bold; color: #d43070;
      margin: 16px 0 8px; border-left: 3px solid #ff9abf; padding-left: 10px;
    }

    /* Tabel */
    table { width: 100%; border-collapse: collapse; font-size: 9.5pt; margin-bottom: 14px; }
    th { background: #fff0f5; color: #d43070; font-weight: bold; padding: 7px 10px; border: 1px solid #ffc2d4; text-align: center; }
    th:first-child { text-align: left; }
    td { padding: 6px 10px; border: 1px solid #f0d0dc; text-align: center; }
    td:first-child { text-align: left; }
    tr:nth-child(even) td { background: #fff8fb; }

    /* Info box */
    .info-box { background: #fff0f5; border: 1px solid #ffc2d4; border-radius: 8px; padding: 12px 16px; margin-bottom: 14px; font-size: 10pt; }
    .info-box strong { color: #d43070; }

    /* Winner */
    .winner-box {
      background: linear-gradient(135deg, #fff0f5, #ffe0ec);
      border: 2px solid #ffc2d4; border-radius: 10px;
      padding: 18px 22px; margin: 16px 0;
      text-align: center;
    }
    .winner-box .w-title { font-size: 14pt; font-weight: bold; color: #d43070; }
    .winner-box .w-score { font-size: 22pt; font-weight: bold; color: #f04e8a; margin: 6px 0; }
    .winner-box .w-sub { font-size: 10pt; color: #6b4158; }

    /* Pill */
    .pill-green { background: #f0fff4; color: #2d8a4e; font-size: 9pt; font-weight: bold; padding: 2px 8px; border-radius: 20px; border: 1px solid #b7f5cc; }
    .pill-red   { background: #fff0f0; color: #c0392b; font-size: 9pt; font-weight: bold; padding: 2px 8px; border-radius: 20px; border: 1px solid #ffc2c2; }
    .pill-cost    { background: #fff5e6; color: #d4700a; font-size: 9pt; padding: 2px 8px; border-radius: 20px; }
    .pill-benefit { background: #f0fff8; color: #2d7a4e; font-size: 9pt; padding: 2px 8px; border-radius: 20px; }

    /* Print button */
    .no-print { text-align: center; padding: 20px; }
    .btn-print { background: linear-gradient(135deg,#ff6fa3,#d43070); color:white; border:none; padding:12px 32px; border-radius:10px; font-size:14px; font-weight:600; cursor:pointer; box-shadow:0 4px 16px rgba(240,78,138,0.25); }
    .btn-back  { background:#f8f0f2; color:#6b4158; border:none; padding:12px 24px; border-radius:10px; font-size:14px; font-weight:600; cursor:pointer; margin-right:12px; }

    /* Ranking highlight */
    .rank-1 td { background: #fff0f5 !important; font-weight: bold; }
    .crown { color: #f04e8a; }
  </style>
</head>
<body>

<div class="no-print" style="background:white;border-bottom:2px solid #ffc2d4;padding:16px 32px;display:flex;align-items:center;justify-content:space-between;">
  <div style="font-family:'DM Sans',sans-serif;font-size:14px;font-weight:600;color:#6b4158;">
    🖨️ Laporan SPK Pemilihan Kost — AHP &amp; TOPSIS
  </div>
  <div>
    <button class="btn-back" onclick="window.location='index.php'">← Kembali</button>
    <button class="btn-print" onclick="window.print()">🖨️ Cetak / Simpan PDF</button>
  </div>
</div>

<div class="print-area">

  <!-- ===== HEADER ===== -->
  <div class="lap-header">
    <div class="lap-logo">🏠</div>
    <div class="lap-title">LAPORAN SISTEM PENDUKUNG KEPUTUSAN<br>PEMILIHAN TEMPAT KOST UNTUK MAHASISWA</div>
    <div class="lap-sub">Menggunakan Metode AHP (Analytic Hierarchy Process) dan TOPSIS</div>
    <div class="lap-meta">Tanggal Cetak: <?= date('d F Y, H:i') ?> &nbsp;|&nbsp; Dicetak oleh: <?= htmlspecialchars($_SESSION['nama'] ?? $_SESSION['user']) ?></div>
  </div>

  <!-- ===== PENDAHULUAN ===== -->
  <div class="section-title">A. Pendahuluan</div>
  <div class="info-box">
    <strong>Tujuan Sistem:</strong> Membantu mahasiswa memilih tempat kost yang paling sesuai secara objektif menggunakan perhitungan multi-kriteria.<br><br>
    <strong>Metode:</strong> AHP digunakan untuk menentukan bobot kriteria melalui kuesioner kepada responden, sedangkan TOPSIS digunakan untuk menentukan ranking alternatif kost terbaik.<br><br>
    <strong>Jumlah Alternatif:</strong> <?= $conn->query("SELECT COUNT(*) as n FROM alternatif")->fetch_assoc()['n'] ?> kost &nbsp;|&nbsp;
    <strong>Jumlah Kriteria:</strong> 5 &nbsp;|&nbsp;
    <strong>Jumlah Responden:</strong> <?= count($data_responden) ?> orang
  </div>

  <!-- ===== KRITERIA ===== -->
  <div class="section-title">B. Kriteria Penilaian</div>
  <table>
    <thead><tr><th>Kode</th><th>Nama Kriteria</th><th>Jenis</th><th>Bobot AHP</th><th>Persentase</th></tr></thead>
    <tbody>
      <?php foreach ($kriteria_data as $i => $k): ?>
      <tr>
        <td><?= $k['kode'] ?></td>
        <td><?= htmlspecialchars($k['nama']) ?></td>
        <td><?= strtoupper($k['jenis']) ?></td>
        <td><?= number_format($bobot_final[$i],6) ?></td>
        <td><?= number_format($bobot_final[$i]*100,2) ?>%</td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <!-- ===== DATA ALTERNATIF ===== -->
  <div class="section-title">C. Data Alternatif Kost</div>
  <table>
    <thead><tr><th>Kode</th><th>Nama Kost</th><th>Harga (rb)</th><th>Jarak (km)</th><th>Fasilitas</th><th>WiFi</th><th>Keamanan</th></tr></thead>
    <tbody>
      <?php
      $alt_data = $conn->query("SELECT * FROM alternatif ORDER BY id");
      while ($a = $alt_data->fetch_assoc()):
      ?>
      <tr>
        <td><?= $a['kode'] ?></td><td><?= htmlspecialchars($a['nama']) ?></td>
        <td><?= number_format($a['c1']) ?></td><td><?= $a['c2'] ?></td>
        <td><?= $a['c3'] ?></td><td><?= $a['c4'] ?></td><td><?= $a['c5'] ?></td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>

  <!-- ===== AHP ===== -->
  <div class="section-title">D. Perhitungan AHP</div>

  <?php foreach ($data_responden as $r): ?>
  <div class="sub-title"><?= htmlspecialchars($r['nama']) ?> — CR = <?= number_format($r['CR'],4) ?>
    &nbsp;<span class="<?= $r['konsisten'] ? 'pill-green' : 'pill-red' ?>"><?= $r['konsisten'] ? '✅ Konsisten' : '❌ Tidak Konsisten' ?></span>
  </div>
  <table>
    <thead>
      <tr><th>Kriteria</th><?php foreach ($nama_k as $nk): ?><th><?= $nk ?></th><?php endforeach; ?></tr>
    </thead>
    <tbody>
      <?php foreach ($r['matriks'] as $i => $baris): ?>
      <tr>
        <td><?= $nama_k[$i] ?></td>
        <?php foreach ($baris as $val): ?>
        <td><?= ($val == round($val)) ? number_format($val,0) : number_format($val,3) ?></td>
        <?php endforeach; ?>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <table style="margin-top:-10px">
    <thead><tr><th>Kriteria</th><?php foreach ($nama_k as $nk): ?><th><?= $nk ?></th><?php endforeach; ?><th>Bobot</th></tr></thead>
    <tbody>
      <?php
      $norm = $r['normalisasi'];
      foreach ($norm as $i => $baris):
      ?>
      <tr>
        <td><?= $nama_k[$i] ?></td>
        <?php foreach ($baris as $val): ?><td><?= number_format($val,4) ?></td><?php endforeach; ?>
        <td><strong><?= number_format($r['prioritas'][$i],4) ?></strong></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <div style="font-size:9.5pt;color:#6b4158;margin-bottom:12px;">
    λ max = <?= number_format($r['lambda_max'],4) ?> &nbsp;|&nbsp;
    CI = <?= number_format($r['CI'],4) ?> &nbsp;|&nbsp;
    RI = <?= $r['RI'] ?> &nbsp;|&nbsp;
    CR = <?= number_format($r['CR'],4) ?>
  </div>
  <?php endforeach; ?>

  <div class="sub-title">Bobot Final AHP (Geometric Mean)</div>
  <table>
    <thead><tr><th>Kriteria</th><th>Bobot Final</th><th>Persentase</th></tr></thead>
    <tbody>
      <?php foreach ($kriteria_data as $i => $k): ?>
      <tr>
        <td><?= htmlspecialchars($k['nama']) ?></td>
        <td><strong><?= number_format($bobot_final[$i],6) ?></strong></td>
        <td><?= number_format($bobot_final[$i]*100,2) ?>%</td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <!-- ===== TOPSIS ===== -->
  <div class="section-title page-break">E. Perhitungan TOPSIS</div>

  <div class="sub-title">Matriks Normalisasi (R)</div>
  <table>
    <thead><tr><th>Alternatif</th><?php foreach ($nama_k as $nk): ?><th><?= $nk ?></th><?php endforeach; ?></tr></thead>
    <tbody>
      <?php foreach ($hasil_topsis['alternatif'] as $a): ?>
      <tr><td><?= htmlspecialchars($a['nama']) ?></td>
        <?php foreach ($a['R'] as $v): ?><td><?= number_format($v,6) ?></td><?php endforeach; ?>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="sub-title">Matriks Normalisasi Terbobot (Y)</div>
  <table>
    <thead><tr><th>Alternatif</th><?php foreach ($nama_k as $nk): ?><th><?= $nk ?></th><?php endforeach; ?></tr></thead>
    <tbody>
      <?php foreach ($hasil_topsis['alternatif'] as $a): ?>
      <tr><td><?= htmlspecialchars($a['nama']) ?></td>
        <?php foreach ($a['Y'] as $v): ?><td><?= number_format($v,6) ?></td><?php endforeach; ?>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="sub-title">Solusi Ideal Positif (A⁺) dan Negatif (A⁻)</div>
  <table>
    <thead><tr><th>Kriteria</th><th>Jenis</th><th>A⁺ (Ideal Positif)</th><th>A⁻ (Ideal Negatif)</th></tr></thead>
    <tbody>
      <?php foreach ($nama_k as $j => $nk): ?>
      <tr>
        <td><?= $nk ?></td>
        <td><?= strtoupper($kriteria_data[$j]['jenis']) ?></td>
        <td><?= number_format($hasil_topsis['Aplus'][$j],6) ?></td>
        <td><?= number_format($hasil_topsis['Amin'][$j],6) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <!-- ===== HASIL RANKING ===== -->
  <div class="section-title">F. Hasil Ranking TOPSIS</div>

  <div class="winner-box">
    <div class="w-title">🏆 Kost Terbaik: <?= htmlspecialchars($terbaik['nama']) ?></div>
    <div class="w-score"><?= number_format($terbaik['preferensi'],4) ?></div>
    <div class="w-sub">Nilai Preferensi Tertinggi — Rekomendasi Terbaik</div>
  </div>

  <table>
    <thead>
      <tr><th>Ranking</th><th>Kode</th><th>Nama Kost</th><th>D⁺</th><th>D⁻</th><th>Nilai Vi</th></tr>
    </thead>
    <tbody>
      <?php foreach ($ranking as $r): ?>
      <tr <?= $r['ranking']==1 ? 'class="rank-1"' : '' ?>>
        <td><?= $r['ranking']==1 ? '<span class="crown">👑</span> ' : '' ?>#<?= $r['ranking'] ?></td>
        <td><?= $r['kode'] ?></td>
        <td><?= htmlspecialchars($r['nama']) ?></td>
        <td><?= number_format($r['Dplus'],6) ?></td>
        <td><?= number_format($r['Dmin'],6) ?></td>
        <td><strong><?= number_format($r['preferensi'],6) ?></strong></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <!-- ===== KESIMPULAN ===== -->
  <div class="section-title">G. Kesimpulan</div>
  <div class="info-box">
    Berdasarkan hasil perhitungan menggunakan metode AHP dan TOPSIS dengan <?= count($data_responden) ?> responden
    dan 5 kriteria penilaian (Harga Kost, Jarak ke Kampus, Fasilitas, WiFi, Keamanan),
    diperoleh bahwa <strong><?= htmlspecialchars($terbaik['nama']) ?></strong> merupakan alternatif
    tempat kost terbaik dengan nilai preferensi tertinggi sebesar
    <strong><?= number_format($terbaik['preferensi'],4) ?></strong>.<br><br>
    Urutan ranking selengkapnya:
    <?php
    // Use proper formatting
    $rank_list = [];
    foreach ($ranking as $r) $rank_list[] = "#{$r['ranking']} " . $r['nama'] . " (Vi=" . number_format($r['preferensi'],4) . ")";
    echo implode(', ', $rank_list);
    ?>.
  </div>

  <!-- Footer -->
  <div style="margin-top:32px;padding-top:16px;border-top:1px solid #ffc2d4;text-align:center;font-size:9pt;color:#b08090;">
    Laporan digenerate otomatis oleh Sistem Pendukung Keputusan Pemilihan Tempat Kost &nbsp;|&nbsp;
    Metode AHP &amp; TOPSIS &nbsp;|&nbsp; <?= date('Y') ?>
  </div>

</div><!-- /print-area -->
</body>
</html>
