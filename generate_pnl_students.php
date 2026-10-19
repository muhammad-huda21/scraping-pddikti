<?php
die("Skrip generator data simulasi/dummy telah dinonaktifkan secara permanen karena aplikasi kini 100% menggunakan data resmi riil dari PDDIKTI.");
$pdo = new PDO('sqlite:pddikti_aceh.db');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Seed deterministik
mt_srand(424242);

// Nama-nama khas Aceh & Indonesia
$namaDepanPria = [
    'Muhammad', 'Ahmad', 'Rahmat', 'Rizki', 'Fauzan', 'Fakhruzi', 'Zulkifli', 'Munawir',
    'Wildan', 'Dedi', 'Ilham', 'Firman', 'Wahyu', 'Bayu', 'Dimas', 'Aditya', 'Iqbal',
    'Fahmi', 'Arif', 'Syahrul', 'Farhan', 'Mahendra', 'Hendra', 'Rio', 'Danu', 'Alfi',
    'Akbar', 'Syarif', 'Mustafa', 'Iskandar', 'Fikri', 'Hafiz', 'Ridho', 'Taufiq',
    'Khairul', 'Hasan', 'Hamzah', 'Saiful', 'Mukhlis', 'Bukhari', 'Zainal', 'Yusuf',
    'Nazaruddin', 'Marzuki', 'Sulaiman', 'Mahdi', 'Nasrullah', 'Muzakkir', 'Iswandi',
    'Raihan', 'Zikri', 'Faisal', 'Amran', 'Helmi', 'Reza', 'Dedi', 'Agus', 'Andi',
    'Budi', 'Candra', 'Erwin', 'Fajri', 'Gilang', 'Hafidz', 'Ivan', 'Jefri', 'Kevin'
];
$namaDepanWanita = [
    'Nurul', 'Putri', 'Siti', 'Cut', 'Fitri', 'Ayu', 'Intan', 'Safitri', 'Nabila',
    'Maulida', 'Annisa', 'Rahmi', 'Dian', 'Desi', 'Rina', 'Devi', 'Marlinda', 'Harfia',
    'Eliyanti', 'Febriani', 'Tari', 'Sari', 'Indah', 'Lestari', 'Wulandari', 'Riska',
    'Suci', 'Dewi', 'Aulia', 'Mawaddah', 'Syakira', 'Nadia', 'Husna', 'Khairina',
    'Raudhatul', 'Maghfirah', 'Nurhaliza', 'Raudhah', 'Syahrani', 'Fadhilah', 'Salwa',
    'Yanti', 'Eka', 'Sri', 'Nisa', 'Laila', 'Fany', 'Gita', 'Hani', 'Irma', 'Jihan',
    'Khatijah', 'Lisa', 'Mira', 'Nova', 'Okta', 'Puspita', 'Qoriah', 'Rahma'
];
$namaTengah = [
    'Pratama', 'Saputra', 'Hidayat', 'Ramadhan', 'Kusuma', 'Wijaya', 'Maulana', 'Akbar',
    'Hakim', 'Azhari', 'Munandar', 'Alamsyah', 'Ananda', 'Firmansyah', 'Putra', 'Putri',
    'Salsabila', 'Wardani', 'Maharani', 'Utami', 'Lestari', 'Permata', 'Rahmadani', 'Aulia',
    'Fadillah', 'Iskandar', 'Rifai', 'Mujahid', 'Bahri', 'Khalil'
];
$namaBelakang = [
    'Syahputra', 'Hidayatullah', 'Zulkarnain', 'Nasution', 'Lubis', 'Siregar', 'Harahap',
    'Ginting', 'Sembiring', 'Hasibuan', 'Pohan', 'Daulay', 'Tanjung', 'Pasaribu',
    'Abdullah', 'Sulaiman', 'Ibrahim', 'Mahmud', 'Yusuf', 'Ismail', 'Ali', 'Usman',
    'Affan', 'Mansur', 'Salim', 'Halim', 'Jamil', 'Kamal', 'Basyir', 'Thahir',
    'Marpaung', 'Sihombing', 'Simanjuntak', 'Sitompul', 'Hutapea', 'Aritonang',
    'Sari', 'Wati', 'Yanti', 'Dewi', 'Lestari', 'Rahayu', 'Handayani'
];

function generateNama($isFemale) {
    global $namaDepanPria, $namaDepanWanita, $namaTengah, $namaBelakang;
    $first = $isFemale ? $namaDepanWanita[array_rand($namaDepanWanita)] : $namaDepanPria[array_rand($namaDepanPria)];
    $gelar = '';
    if (mt_rand(1, 10) === 1) {
        $gelar = $isFemale ? 'Cut ' : 'Teuku ';
    }
    $pattern = mt_rand(1, 3);
    if ($pattern === 1) {
        $mid = $namaTengah[array_rand($namaTengah)];
        $last = $namaBelakang[array_rand($namaBelakang)];
        $nama = trim("$gelar$first $mid $last");
    } elseif ($pattern === 2) {
        $last = $namaBelakang[array_rand($namaBelakang)];
        $nama = trim("$gelar$first $last");
    } else {
        $mid = $namaTengah[array_rand($namaTengah)];
        $nama = trim("$gelar$first $mid");
    }
    return strtoupper($nama);
}

// Angkatan aktif
$angkatanList = [2021, 2022, 2023, 2024];

// ============================================================
// 1. Generate mahasiswa untuk SEMUA kampus di Aceh
// ============================================================
$kampusList = $pdo->query("SELECT id, nama FROM kampus_aceh ORDER BY nama ASC")->fetchAll(PDO::FETCH_ASSOC);
echo "Total kampus: " . count($kampusList) . "\n";

// Ambil semua prodi
$allProdis = $pdo->query(
    "SELECT kode_prodi, nama_prodi, jenjang_prodi, jumlah_mahasiswa, nama_pt, pt_id 
     FROM prodi_aceh 
     WHERE jumlah_mahasiswa > 0
     ORDER BY nama_pt ASC, jumlah_mahasiswa DESC"
)->fetchAll(PDO::FETCH_ASSOC);
echo "Total prodi aktif: " . count($allProdis) . "\n";
$totalTarget = array_sum(array_column($allProdis, 'jumlah_mahasiswa'));
echo "Total target mahasiswa: $totalTarget\n";

// Hapus semua data mahasiswa lama
echo "\nMenghapus data mahasiswa lama...\n";
$pdo->exec("DELETE FROM mahasiswa_aceh");

$pdo->beginTransaction();

$stmtIns = $pdo->prepare(
    "INSERT OR IGNORE INTO mahasiswa_aceh (id, nama, nim, pt_id, nama_pt, nama_prodi, jenjang, status_saat_ini, tanggal_masuk) 
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
);

$usedNims = [];
$totalInserted = 0;

foreach ($allProdis as $p) {
    $kodeProdi  = preg_replace('/[^a-zA-Z0-9]/', '', $p['kode_prodi'] ?? 'XX');
    $namaProdi  = $p['nama_prodi'];
    $jenjang    = $p['jenjang_prodi'];
    $quota      = (int)$p['jumlah_mahasiswa'];
    $namaPt     = $p['nama_pt'];
    $ptId       = $p['pt_id'] ?? '';

    // Identifikasi prefix NIM dari nama kampus
    $ptKey = substr(preg_replace('/[^A-Z0-9]/', '', strtoupper($namaPt)), 0, 3);

    $basePerAngkatan = intdiv($quota, 4);
    $remainder = $quota % 4;

    $i = 0;
    foreach ($angkatanList as $angIdx => $angkatan) {
        $countForAngkatan = $basePerAngkatan + ($angIdx < $remainder ? 1 : 0);
        for ($j = 0; $j < $countForAngkatan; $j++) {
            $isFemale = (mt_rand(1, 100) > 55);
            $nama = generateNama($isFemale);

            // Generate NIM unik
            $attempt = 0;
            do {
                $seq = $j + 1 + ($attempt * 1000);
                // Format: <Tahun 4 digit><KodeProdi Pendek><Urut 4 digit>
                $nim = sprintf("%04d%s%04d", $angkatan, str_pad(substr($kodeProdi, 0, 5), 5, '0', STR_PAD_LEFT), $seq);
                $attempt++;
            } while (isset($usedNims[$nim]) && $attempt < 1000);
            $usedNims[$nim] = true;

            $id = 'mhs_' . md5($namaPt . $nim);
            $status = (mt_rand(1, 40) === 1) ? 'Cuti' : 'Aktif';
            $tglMasuk = "$angkatan-09-01";

            $stmtIns->execute([$id, $nama, $nim, $ptId, $namaPt, $namaProdi, $jenjang, $status, $tglMasuk]);
            $totalInserted++;
        }
    }
}

$pdo->commit();

echo "Total mahasiswa digenerate: $totalInserted\n";

// Buat index
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_mhs_pt ON mahasiswa_aceh(nama_pt)");
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_mhs_nama ON mahasiswa_aceh(nama)");
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_mhs_nim ON mahasiswa_aceh(nim)");
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_mhs_prodi ON mahasiswa_aceh(nama_prodi)");
$pdo->exec("CREATE INDEX IF NOT EXISTS idx_mhs_tgl ON mahasiswa_aceh(tanggal_masuk)");

// Verifikasi per kampus
$stats = $pdo->query(
    "SELECT nama_pt, COUNT(*) as cnt FROM mahasiswa_aceh GROUP BY nama_pt ORDER BY cnt DESC LIMIT 15"
)->fetchAll(PDO::FETCH_ASSOC);

echo "\nVerifikasi per kampus (top 15):\n";
foreach ($stats as $s) {
    echo "  {$s['nama_pt']}: {$s['cnt']}\n";
}

$pnl = $pdo->query("SELECT COUNT(*) FROM mahasiswa_aceh WHERE nama_pt LIKE '%LHOKSEUMAWE%'")->fetchColumn();
echo "\nTotal PNL: $pnl\n";
echo "Total all: " . $pdo->query("SELECT COUNT(*) FROM mahasiswa_aceh")->fetchColumn() . "\n";

// Update JSON fallback
echo "\nUpdating data/mahasiswa_aceh.json...\n";
$allStudents = $pdo->query("SELECT * FROM mahasiswa_aceh ORDER BY nama ASC")->fetchAll(PDO::FETCH_ASSOC);
file_put_contents(__DIR__ . '/data/mahasiswa_aceh.json', json_encode($allStudents, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Done! JSON updated with " . count($allStudents) . " records.\n";
