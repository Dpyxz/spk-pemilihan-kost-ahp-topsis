-- =============================================
-- DATABASE SPK PEMILIHAN TEMPAT KOST
-- AHP + TOPSIS | Versi Final UAS
-- =============================================

CREATE DATABASE IF NOT EXISTS spk_kost;
USE spk_kost;

-- =============================================
-- TABEL USERS (LOGIN)
-- =============================================
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  nama VARCHAR(100) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
-- Jalankan generate_hash.php setelah import untuk set password!
INSERT INTO users (username, password, nama) VALUES
('admin', 'set_via_generate_hash', 'Administrator');

-- =============================================
-- TABEL KRITERIA
-- =============================================
CREATE TABLE IF NOT EXISTS kriteria (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kode VARCHAR(10),
  nama VARCHAR(100),
  jenis ENUM('cost','benefit'),
  bobot DECIMAL(10,6) DEFAULT 0
);
INSERT INTO kriteria (kode, nama, jenis) VALUES
('C1', 'Harga Kost', 'cost'),
('C2', 'Jarak ke Kampus', 'cost'),
('C3', 'Fasilitas', 'benefit'),
('C4', 'WiFi', 'benefit'),
('C5', 'Keamanan', 'benefit');

-- =============================================
-- TABEL ALTERNATIF
-- =============================================
CREATE TABLE IF NOT EXISTS alternatif (
  id INT AUTO_INCREMENT PRIMARY KEY,
  kode VARCHAR(10),
  nama VARCHAR(100),
  c1 DECIMAL(10,2) COMMENT 'Harga (ribu/bulan)',
  c2 DECIMAL(10,2) COMMENT 'Jarak ke kampus (km)',
  c3 DECIMAL(10,2) COMMENT 'Fasilitas (1-5)',
  c4 DECIMAL(10,2) COMMENT 'WiFi (1-5)',
  c5 DECIMAL(10,2) COMMENT 'Keamanan (1-5)'
);
INSERT INTO alternatif (kode, nama, c1, c2, c3, c4, c5) VALUES
('A1', 'Kost Mawar',   800, 1, 4, 4, 4),
('A2', 'Kost Melati',  650, 2, 3, 3, 4),
('A3', 'Kost Tulip',   900, 1, 5, 5, 5),
('A4', 'Kost Anggrek', 700, 3, 4, 3, 3),
('A5', 'Kost Sakura',  750, 2, 4, 4, 5);

-- =============================================
-- TABEL RESPONDEN AHP
-- =============================================
CREATE TABLE IF NOT EXISTS responden (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100),
  status ENUM('konsisten','tidak_konsisten') DEFAULT 'konsisten',
  cr DECIMAL(10,6) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
INSERT INTO responden (nama, cr) VALUES
('Okta Mianda',            0.0),
('Sekar Ayu Nida',         0.0),
('Rahmadhani Armawahyudi', 0.0),
('Elsya Anggraini',        0.0),
('Yasmin Aulia',           0.0);

-- =============================================
-- TABEL MATRIKS AHP
-- =============================================
CREATE TABLE IF NOT EXISTS matriks_ahp (
  id INT AUTO_INCREMENT PRIMARY KEY,
  responden_id INT,
  baris INT,
  kolom INT,
  nilai DECIMAL(10,6),
  FOREIGN KEY (responden_id) REFERENCES responden(id) ON DELETE CASCADE
);

-- Matriks R1 — Okta Mianda
INSERT INTO matriks_ahp (responden_id, baris, kolom, nilai) VALUES
(1,1,1,1),(1,1,2,3),(1,1,3,5),(1,1,4,5),(1,1,5,1),
(1,2,1,0.333333),(1,2,2,1),(1,2,3,3),(1,2,4,5),(1,2,5,0.333333),
(1,3,1,0.2),(1,3,2,0.333333),(1,3,3,1),(1,3,4,3),(1,3,5,0.333333),
(1,4,1,0.2),(1,4,2,0.2),(1,4,3,0.333333),(1,4,4,1),(1,4,5,0.2),
(1,5,1,1),(1,5,2,3),(1,5,3,3),(1,5,4,5),(1,5,5,1);

-- Matriks R2 — Sekar Ayu Nida
INSERT INTO matriks_ahp (responden_id, baris, kolom, nilai) VALUES
(2,1,1,1),(2,1,2,3),(2,1,3,5),(2,1,4,5),(2,1,5,3),
(2,2,1,0.333333),(2,2,2,1),(2,2,3,3),(2,2,4,3),(2,2,5,1),
(2,3,1,0.2),(2,3,2,0.333333),(2,3,3,1),(2,3,4,1),(2,3,5,0.333333),
(2,4,1,0.2),(2,4,2,0.333333),(2,4,3,1),(2,4,4,1),(2,4,5,0.333333),
(2,5,1,0.333333),(2,5,2,1),(2,5,3,3),(2,5,4,3),(2,5,5,1);

-- Matriks R3 — Rahmadhani Armawahyudi
INSERT INTO matriks_ahp (responden_id, baris, kolom, nilai) VALUES
(3,1,1,1),(3,1,2,3),(3,1,3,5),(3,1,4,5),(3,1,5,5),
(3,2,1,0.333333),(3,2,2,1),(3,2,3,3),(3,2,4,3),(3,2,5,3),
(3,3,1,0.2),(3,3,2,0.333333),(3,3,3,1),(3,3,4,1),(3,3,5,1),
(3,4,1,0.2),(3,4,2,0.333333),(3,4,3,1),(3,4,4,1),(3,4,5,1),
(3,5,1,0.2),(3,5,2,0.333333),(3,5,3,1),(3,5,4,1),(3,5,5,1);

-- Matriks R4 — Elsya Anggraini
INSERT INTO matriks_ahp (responden_id, baris, kolom, nilai) VALUES
(4,1,1,1),(4,1,2,3),(4,1,3,5),(4,1,4,3),(4,1,5,1),
(4,2,1,0.333333),(4,2,2,1),(4,2,3,1),(4,2,4,3),(4,2,5,0.333333),
(4,3,1,0.2),(4,3,2,1),(4,3,3,1),(4,3,4,3),(4,3,5,0.333333),
(4,4,1,0.333333),(4,4,2,0.333333),(4,4,3,0.333333),(4,4,4,1),(4,4,5,0.333333),
(4,5,1,1),(4,5,2,3),(4,5,3,3),(4,5,4,3),(4,5,5,1);

-- Matriks R5 — Yasmin Aulia
INSERT INTO matriks_ahp (responden_id, baris, kolom, nilai) VALUES
(5,1,1,1),(5,1,2,3),(5,1,3,5),(5,1,4,5),(5,1,5,3),
(5,2,1,0.333333),(5,2,2,1),(5,2,3,3),(5,2,4,3),(5,2,5,1),
(5,3,1,0.2),(5,3,2,0.333333),(5,3,3,1),(5,3,4,1),(5,3,5,0.333333),
(5,4,1,0.2),(5,4,2,0.333333),(5,4,3,1),(5,4,4,1),(5,4,5,0.333333),
(5,5,1,0.333333),(5,5,2,1),(5,5,3,3),(5,5,4,3),(5,5,5,1);

-- =============================================
-- TABEL HASIL TOPSIS
-- =============================================
CREATE TABLE IF NOT EXISTS hasil_topsis (
  id INT AUTO_INCREMENT PRIMARY KEY,
  alternatif_id INT,
  dplus DECIMAL(10,6),
  dmin DECIMAL(10,6),
  preferensi DECIMAL(10,6),
  ranking INT,
  FOREIGN KEY (alternatif_id) REFERENCES alternatif(id)
);
