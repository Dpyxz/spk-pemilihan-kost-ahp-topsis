<?php
// fungsi_ahp.php - Semua fungsi perhitungan AHP

function hitungAHP($matriks) {
    $n = count($matriks);
    
    // Hitung jumlah kolom
    $jumlah_kolom = array_fill(0, $n, 0);
    for ($j = 0; $j < $n; $j++) {
        for ($i = 0; $i < $n; $i++) {
            $jumlah_kolom[$j] += $matriks[$i][$j];
        }
    }
    
    // Normalisasi matriks
    $normalisasi = [];
    for ($i = 0; $i < $n; $i++) {
        for ($j = 0; $j < $n; $j++) {
            $normalisasi[$i][$j] = $matriks[$i][$j] / $jumlah_kolom[$j];
        }
    }
    
    // Bobot prioritas (rata-rata baris)
    $prioritas = [];
    for ($i = 0; $i < $n; $i++) {
        $prioritas[$i] = array_sum($normalisasi[$i]) / $n;
    }
    
    // Lambda max
    $lambda_max = 0;
    for ($j = 0; $j < $n; $j++) {
        $lambda_max += $jumlah_kolom[$j] * $prioritas[$j];
    }
    
    // CI & CR
    $CI = ($lambda_max - $n) / ($n - 1);
    $RI = 1.12; // RI untuk n=5
    $CR = $CI / $RI;
    
    return [
        'jumlah_kolom'  => $jumlah_kolom,
        'normalisasi'   => $normalisasi,
        'prioritas'     => $prioritas,
        'lambda_max'    => $lambda_max,
        'CI'            => $CI,
        'RI'            => $RI,
        'CR'            => $CR,
        'konsisten'     => ($CR < 0.1)
    ];
}

function ambilMatriksResponden($conn, $responden_id) {
    $matriks = [];
    $sql = "SELECT baris, kolom, nilai FROM matriks_ahp 
            WHERE responden_id = $responden_id 
            ORDER BY baris, kolom";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $matriks[$row['baris']-1][$row['kolom']-1] = (float)$row['nilai'];
    }
    return $matriks;
}

function hitungBobotFinal($conn) {
    // Ambil semua responden yang konsisten
    $responden_list = $conn->query("SELECT id FROM responden WHERE status = 'konsisten'");
    
    $semua_prioritas = [];
    while ($r = $responden_list->fetch_assoc()) {
        $matriks = ambilMatriksResponden($conn, $r['id']);
        $hasil   = hitungAHP($matriks);
        $semua_prioritas[] = $hasil['prioritas'];
    }
    
    $n_kriteria = 5;
    $n_resp     = count($semua_prioritas);
    
    // Geometric mean per kriteria
    $geomean = [];
    for ($j = 0; $j < $n_kriteria; $j++) {
        $produk = 1;
        foreach ($semua_prioritas as $p) {
            $produk *= $p[$j];
        }
        $geomean[$j] = pow($produk, 1/$n_resp);
    }
    
    // Normalisasi geometric mean
    $total    = array_sum($geomean);
    $bobot    = [];
    for ($j = 0; $j < $n_kriteria; $j++) {
        $bobot[$j] = $geomean[$j] / $total;
    }
    
    return $bobot;
}
?>
