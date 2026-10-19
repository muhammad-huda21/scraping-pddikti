<?php
die("Skrip generator data simulasi/dummy telah dinonaktifkan secara permanen karena aplikasi kini 100% menggunakan data resmi riil dari PDDIKTI.");

$pdo = new PDO('sqlite:' . __DIR__ . '/pddikti_aceh.db');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

mt_srand(582910);

// Pool nama pria & wanita Aceh/Nasional
$namaPria = [
    'Muhammad', 'Ahmad', 'Rahmat', 'Rizki', 'Fauzan', 'Fakhruzi', 'Zulkifli', 'Munawir',
    'Wildan', 'Dedi', 'Ilham', 'Firman', 'Wahyu', 'Bayu', 'Dimas', 'Aditya', 'Iqbal',
    'Fahmi', 'Arif', 'Syahrul', 'Farhan', 'Mahendra', 'Hendra', 'Rio', 'Danu', 'Alfi',
    'Akbar', 'Syarif', 'Mustafa', 'Iskandar', 'Fikri', 'Hafiz', 'Ridho', 'Taufiq',
    'Khairul', 'Hasan', 'Hamzah', 'Saiful', 'Mukhlis', 'Bukhari', 'Zainal', 'Yusuf',
    'Nazaruddin', 'Marzuki', 'Sulaiman', 'Mahdi', 'Nasrullah', 'Muzakkir', 'Iswandi',
    'Raihan', 'Zikri', 'Faisal', 'Amran', 'Helmi', 'Reza', 'Budi', 'Candra', 'Erwin',
    'Teuku', 'Teuku Iskandar', 'Teuku Reza', 'Teuku Faisal', 'Sayed', 'Sayed Mahdi'
];

$namaWanita = [
    'Cut', 'Cut Mutia', 'Cut Nyak', 'Nurul', 'Putri', 'Siti', 'Fitri', 'Ayu', 'Intan',
    'Safitri', 'Nabila', 'Maulida', 'Annisa', 'Rahmi', 'Dian', 'Desi', 'Rina', 'Devi',
    'Marlinda', 'Harfia', 'Eliyanti', 'Febriani', 'Tari', 'Sari', 'Indah', 'Lestari',
    'Wulandari', 'Riska', 'Suci', 'Dewi', 'Aulia', 'Mawaddah', 'Syakira', 'Nadia',
    'Husna', 'Khairina', 'Raudhatul', 'Maghfirah', 'Nurhaliza', 'Raudhah', 'Syahrani',
    'Fadhilah', 'Salwa', 'Yanti', 'Eka', 'Sri', 'Nisa', 'Laila', 'Fany', 'Gita'
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
    'Gayo', 'Al-Asyi', 'Pasee', 'Meuraxa', 'Samalanga', 'Peusangan', 'Bakongan'
];

function generateGelarDanNama($prodiName) {
    global $namaPria, $namaWanita, $namaTengah, $namaBelakang;

    $isFemale = mt_rand(1, 100) <= 45;
    $first = $isFemale ? $namaWanita[array_rand($namaWanita)] : $namaPria[array_rand($namaPria)];
    $mid = $namaTengah[array_rand($namaTengah)];
    $last = $namaBelakang[array_rand($namaBelakang)];
    $baseName = strtoupper(trim("$first $mid $last"));

    $pUpper = strtoupper($prodiName);

    // Kategori Gelar
    $prefix = '';
    $suffix = '';

    if (preg_match('/(TEKNIK|INFORMATIKA|KOMPUTER|ELEKTRO|MESIN|SIPIL|KIMIA|INDUSTRI|ARSITEKTUR)/', $pUpper)) {
        $prefixes = ['DR. ', 'DR. ENG. ', 'IR. ', 'PROF. DR. IR. ', 'PROF. DR. ', ''];
        $suffixes = [', S.T., M.T.', ', S.T., M.Sc.', ', S.Kom., M.Kom.', ', S.T., M.Eng.', ', S.T., Ph.D.', ', S.Kom., M.Cs.', ', S.T., M.Si.', ', S.T.r., M.T.'];
        $prefix = $prefixes[array_rand($prefixes)];
        $suffix = $suffixes[array_rand($suffixes)];
    } elseif (preg_match('/(EKONOMI|MANAJEMEN|AKUNTANSI|BISNIS|KEUANGAN|PERBANKAN)/', $pUpper)) {
        $prefixes = ['DR. ', 'PROF. DR. ', ''];
        $suffixes = [', S.E., M.M.', ', S.E., M.Si.', ', S.E., M.Si., Ak., CA', ', S.E., M.Ak.', ', S.E., M.B.A.', ', S.E., Ph.D.', ', S.Ak., M.Ak.'];
        $prefix = $prefixes[array_rand($prefixes)];
        $suffix = $suffixes[array_rand($suffixes)];
    } elseif (preg_match('/(PENDIDIKAN|GURU|PGSD|PAUD|TARBIYAH)/', $pUpper)) {
        $prefixes = ['DR. ', 'PROF. DR. ', ''];
        $suffixes = [', S.Pd., M.Pd.', ', S.Pd., M.Si.', ', S.Pd., M.Hum.', ', S.Pd., M.Ed.', ', S.Pd., Ph.D.'];
        $prefix = $prefixes[array_rand($prefixes)];
        $suffix = $suffixes[array_rand($suffixes)];
    } elseif (preg_match('/(AGAMA|SYARIAH|ISLAM|DAKWAH|USHULUDDIN|QURAN|HADIS|ARAB)/', $pUpper)) {
        $prefixes = ['DR. ', 'PROF. DR. ', 'TGK. DR. ', ''];
        $suffixes = [', Lc., M.A.', ', S.Ag., M.Ag.', ', S.H.I., M.H.I.', ', S.Pd.I., M.Pd.I.', ', Lc., M.H.I.', ', S.Sy., M.Sy.'];
        $prefix = $prefixes[array_rand($prefixes)];
        $suffix = $suffixes[array_rand($suffixes)];
    } elseif (preg_match('/(KEDOKTERAN|KEPERAWATAN|KEBIDANAN|KESEHATAN|FARMASI|MEDIS)/', $pUpper)) {
        $prefixes = ['DR. ', 'PROF. DR. ', 'DR. DR. ', ''];
        $suffixes = [', M.Kes.', ', S.Kep., Ns., M.Kep.', ', S.Tr.Keb., M.Keb.', ', S.Farm., M.Farm., Apt.', ', S.K.M., M.Kes.', ', M.Biomed', ', Sp.A', ', Sp.PD'];
        $prefix = $prefixes[array_rand($prefixes)];
        $suffix = $suffixes[array_rand($suffixes)];
    } elseif (preg_match('/(HUKUM)/', $pUpper)) {
        $prefixes = ['DR. ', 'PROF. DR. ', ''];
        $suffixes = [', S.H., M.H.', ', S.H., M.Kn.', ', S.H., LL.M.', ', S.H., Ph.D.'];
        $prefix = $prefixes[array_rand($prefixes)];
        $suffix = $suffixes[array_rand($suffixes)];
    } elseif (preg_match('/(PERTANIAN|AGRIBISNIS|AGROTEKNOLOGI|PETERNAKAN|PERIKANAN|KEHUTANAN)/', $pUpper)) {
        $prefixes = ['DR. ', 'PROF. DR. ', 'IR. ', ''];
        $suffixes = [', S.P., M.P.', ', S.P., M.Si.', ', S.Pi., M.Si.', ', S.Pt., M.Si.', ', S.P., M.Sc.'];
        $prefix = $prefixes[array_rand($prefixes)];
        $suffix = $suffixes[array_rand($suffixes)];
    } else {
        $prefixes = ['DR. ', 'PROF. DR. ', ''];
        $suffixes = [', S.Sos., M.Si.', ', S.I.P., M.Si.', ', S.Si., M.Si.', ', S.Kom., M.Kom.', ', S.Pd., M.Pd.'];
        $prefix = $prefixes[array_rand($prefixes)];
        $suffix = $suffixes[array_rand($suffixes)];
    }

    return trim($prefix . $baseName . $suffix);
}

function generateNidn() {
    // NIDN 10 digit, misal '00' + tanggal/bulan/tahun lahir acak (1970 - 1995)
    $thn = mt_rand(70, 95);
    $bln = sprintf('%02d', mt_rand(1, 12));
    $tgl = sprintf('%02d', mt_rand(1, 28));
    $seq = sprintf('%02d', mt_rand(1, 99));
    return "00{$tgl}{$bln}{$thn}";
}

function generateNuptk() {
    // 16 digit
    $res = '';
    for ($i = 0; $i < 16; $i++) {
        $res .= mt_rand(0, 9);
    }
    return $res;
}

echo "=== MEMULAI GENERASI DOSEN ACEH LENGKAP ===\n";

$prodis = $pdo->query("SELECT kode_prodi, nama_prodi, nama_pt, pt_id, jumlah_dosen FROM prodi_aceh WHERE jumlah_dosen > 0 ORDER BY nama_pt, nama_prodi")->fetchAll(PDO::FETCH_ASSOC);

$checkStmt = $pdo->prepare("SELECT COUNT(*) FROM dosen_aceh WHERE UPPER(TRIM(nama_pt)) = UPPER(TRIM(?)) AND UPPER(TRIM(nama_prodi)) = UPPER(TRIM(?))");
$insStmt = $pdo->prepare("INSERT OR REPLACE INTO dosen_aceh (id, nama, nidn, nuptk, pt_id, nama_pt, sinkatan_pt, nama_prodi, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

$totalAdded = 0;
$pdo->beginTransaction();

foreach ($prodis as $idx => $p) {
    $checkStmt->execute([$p['nama_pt'], $p['nama_prodi']]);
    $curr = (int)$checkStmt->fetchColumn();
    $target = (int)$p['jumlah_dosen'];

    if ($curr < $target) {
        $needed = $target - $curr;
        for ($i = 0; $i < $needed; $i++) {
            $id = 'dsn_' . md5($p['kode_prodi'] . $p['nama_pt'] . $i . mt_rand());
            $nama = generateGelarDanNama($p['nama_prodi']);
            $nidn = generateNidn();
            $nuptk = generateNuptk();
            $ptId = $p['pt_id'];
            $namaPt = strtoupper(trim($p['nama_pt']));
            $namaProdi = trim($p['nama_prodi']);
            $status = 'Aktif';

            $insStmt->execute([$id, $nama, $nidn, $nuptk, $ptId, $namaPt, '', $namaProdi, $status]);
            $totalAdded++;
        }
    }
}

$pdo->commit();
echo "Berhasil menambahkan $totalAdded dosen baru!\n";

$finalTotal = $pdo->query("SELECT COUNT(*) FROM dosen_aceh")->fetchColumn();
echo "Total akhir dosen di database SQLite: $finalTotal\n";

// Sekarang export seluruh tabel dosen_aceh ke data/dosen_aceh.json
echo "\nMengekspor seluruh data dosen ke data/dosen_aceh.json...\n";
$allDosen = $pdo->query("SELECT * FROM dosen_aceh ORDER BY nama_pt, nama")->fetchAll(PDO::FETCH_ASSOC);
$jsonFile = __DIR__ . '/data/dosen_aceh.json';
file_put_contents($jsonFile, json_encode($allDosen, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Selesai! data/dosen_aceh.json berhasil diperbarui dengan " . count($allDosen) . " records.\n";

// Cek ringkasan kampus top
echo "\n=== TOP 10 KAMPUS SETELAH GENERASI ===\n";
$topKampus = $pdo->query("SELECT nama_pt, COUNT(*) as cnt FROM dosen_aceh GROUP BY nama_pt ORDER BY cnt DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
foreach ($topKampus as $k) {
    echo "  {$k['nama_pt']}: {$k['cnt']} dosen\n";
}

// Cek PNL
$pnlCount = $pdo->query("SELECT COUNT(*) FROM dosen_aceh WHERE nama_pt LIKE '%LHOKSEUMAWE%' OR nama_pt LIKE '%POLITEKNIK NEGERI LHOKSEUMAWE%'")->fetchColumn();
echo "\nTotal Dosen PNL di DB: $pnlCount\n";
