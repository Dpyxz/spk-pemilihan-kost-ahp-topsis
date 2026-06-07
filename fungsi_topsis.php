<?php
// fungsi_topsis.php - Semua fungsi perhitungan TOPSIS

function hitungTOPSIS($conn, $bobot) {
    // Ambil data alternatif
    $result = $conn->query("SELECT * FROM alternatif ORDER BY id");
    $alternatif = [];
    while ($row = $result->fetch_assoc()) {
        $alternatif[] = $row;
    }
    
    // Ambil jenis kriteria
    $res_kriteria = $conn->query("SELECT jenis FROM kriteria ORDER BY id");
    $jenis = [];
    while ($k = $res_kriteria->fetch_assoc()) {
        $jenis[] = $k['jenis'];
    }
    
    $n_alt  = count($alternatif);
    $n_krit = 5;
    
    // Susun matriks keputusan X
    $X = [];
    for ($i = 0; $i < $n_alt; $i++) {
        $X[$i] = [
            (float)$alternatif[$i]['c1'],
            (float)$alternatif[$i]['c2'],
            (float)$alternatif[$i]['c3'],
            (float)$alternatif[$i]['c4'],
            (float)$alternatif[$i]['c5'],
        ];
    }
    
    // ---- NORMALISASI TOPSIS ----
    $R = [];
    for ($j = 0; $j < $n_krit; $j++) {
        $sum_sq = 0;
        for ($i = 0; $i < $n_alt; $i++) {
            $sum_sq += pow($X[$i][$j], 2);
        }
        $pembagi = sqrt($sum_sq);
        for ($i = 0; $i < $n_alt; $i++) {
            $R[$i][$j] = $X[$i][$j] / $pembagi;
        }
    }
    
    // ---- NORMALISASI TERBOBOT ----
    $Y = [];
    for ($i = 0; $i < $n_alt; $i++) {
        for ($j = 0; $j < $n_krit; $j++) {
            $Y[$i][$j] = $R[$i][$j] * $bobot[$j];
        }
    }
    
    // ---- SOLUSI IDEAL POSITIF & NEGATIF ----
    $Aplus = [];
    $Amin  = [];
    for ($j = 0; $j < $n_krit; $j++) {
        $col = array_column($Y, $j);
        if ($jenis[$j] == 'benefit') {
            $Aplus[$j] = max($col);
            $Amin[$j]  = min($col);
        } else {
            $Aplus[$j] = min($col);
            $Amin[$j]  = max($col);
        }
    }
    
    // ---- JARAK KE SOLUSI IDEAL ----
    $Dplus = [];
    $Dmin  = [];
    for ($i = 0; $i < $n_alt; $i++) {
        $sum_plus = 0;
        $sum_min  = 0;
        for ($j = 0; $j < $n_krit; $j++) {
            $sum_plus += pow($Y[$i][$j] - $Aplus[$j], 2);
            $sum_min  += pow($Y[$i][$j] - $Amin[$j], 2);
        }
        $Dplus[$i] = sqrt($sum_plus);
        $Dmin[$i]  = sqrt($sum_min);
    }
    
    // ---- NILAI PREFERENSI ----
    $V = [];
    for ($i = 0; $i < $n_alt; $i++) {
        $V[$i] = $Dmin[$i] / ($Dmin[$i] + $Dplus[$i]);
    }
    
    // ---- SUSUN HASIL ----
    $hasil = [];
    for ($i = 0; $i < $n_alt; $i++) {
        $hasil[] = [
            'id'          => $alternatif[$i]['id'],
            'kode'        => $alternatif[$i]['kode'],
            'nama'        => $alternatif[$i]['nama'],
            'data'        => $X[$i],
            'R'           => $R[$i],
            'Y'           => $Y[$i],
            'Dplus'       => $Dplus[$i],
            'Dmin'        => $Dmin[$i],
            'preferensi'  => $V[$i],
        ];
    }
    
    // Sort berdasarkan preferensi descending
    usort($hasil, fn($a, $b) => $b['preferensi'] <=> $a['preferensi']);
    
    // Tambah ranking
    for ($i = 0; $i < count($hasil); $i++) {
        $hasil[$i]['ranking'] = $i + 1;
    }
    
    return [
        'alternatif' => $hasil,
        'X'          => $X,
        'R'          => $R,
        'Y'          => $Y,
        'Aplus'      => $Aplus,
        'Amin'       => $Amin,
        'Dplus'      => $Dplus,
        'Dmin'       => $Dmin,
    ];
}
?>
