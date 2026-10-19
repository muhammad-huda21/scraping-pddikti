<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/db.php';

// Helper function cURL ke PDDIKTI sesuai struktur panduan
if (!function_exists('pddikti_curl')) {
function pddikti_curl($endpoint) {
    $url = "https://pddikti.kemdiktisaintek.go.id/api/" . $endpoint;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json, text/plain, */*',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Origin: https://pddikti.kemdiktisaintek.go.id',
        'Referer: https://pddikti.kemdiktisaintek.go.id/perguruan-tinggi'
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    return [
        'code' => $httpCode,
        'data' => json_decode($response, true),
        'raw' => $response,
        'error' => $curlError
    ];
}
}

// Helper cerdas penentuan tahun angkatan:
// 1. Prioritas PDDIKTI: cek tanggal_masuk/tahun_masuk
// 2. Fallback NIM: jika NIM sedikit ambil angka pertama, jika NIM lengkap ambil di akhir
if (!function_exists('resolve_tahun_angkatan')) {
function resolve_tahun_angkatan($tanggalMasuk, $nim) {
    // 1. Cek dari tanggal masuk PDDIKTI jika ada
    if (!empty($tanggalMasuk)) {
        if (preg_match('/\b(19\d\d|20\d\d)\b/', $tanggalMasuk, $m)) {
            return (int)$m[1];
        }
    }
    
    // 2. Fallback dari NIM
    if (empty($nim)) return null;
    $nimRaw = trim($nim);
    
    // Cek jika NIM memiliki format berakhiran tahun, misal "XXX/2021", "XXX-2021", "XXX/21", "XXX-21"
    if (preg_match('/[\/\-_](19\d\d|20\d\d)$/', $nimRaw, $m)) {
        return (int)$m[1];
    }
    if (preg_match('/[\/\-_](\d{2})$/', $nimRaw, $m)) {
        $d = intval($m[1]);
        return ($d >= 70) ? 1900 + $d : 2000 + $d;
    }

    $nimDigits = preg_replace('/[^0-9]/', '', $nimRaw);
    $len = strlen($nimDigits);
    if ($len < 2) return null;

    // NIM sedikit / pendek (<= 10 digit): ambil angka pertama / awalan NIM
    if ($len <= 10) {
        // Cek jika diawali 4 digit tahun (misal 2021xxxx)
        if (preg_match('/^(19\d\d|20\d\d)/', $nimDigits, $m)) {
            return (int)$m[1];
        }
        // Ambil 2 digit pertama
        $duaAwal = intval(substr($nimDigits, 0, 2));
        return ($duaAwal >= 70) ? (1900 + $duaAwal) : (2000 + $duaAwal);
    } 
    // NIM lengkap / panjang (> 10 digit): ambil di akhir
    else {
        // Jika 4 digit akhir adalah tahun valid (1990 - 2030)
        $empatAkhir = intval(substr($nimDigits, -4));
        if ($empatAkhir >= 1990 && $empatAkhir <= 2030) {
            return $empatAkhir;
        }

        // Jika 4 digit awal sudah jelas format tahun 4 digit (misal 2021573010001 di PNL/USK)
        $empatAwal = intval(substr($nimDigits, 0, 4));
        if ($empatAwal >= 1990 && $empatAwal <= 2030) {
            return $empatAwal;
        }

        // Ambil 2 digit akhir
        $duaAkhir = intval(substr($nimDigits, -2));
        if ($duaAkhir >= 70 && $duaAkhir <= 99) {
            return 1900 + $duaAkhir;
        } else {
            return 2000 + $duaAkhir;
        }
    }
}
}

// Helper normalisasi nama prodi ke Title Case rapi
if (!function_exists('normalize_prodi_name')) {
function normalize_prodi_name($prodi) {
    if (empty($prodi)) return '';
    $prodi = trim($prodi);
    // Jika semua huruf kapital atau format acak, jadikan Title Case
    $words = explode(' ', strtolower($prodi));
    $words = array_map(function($w) {
        if (in_array($w, ['dan', 'di', 'ke', 'dari', 'untuk', 'pada', 'per'])) {
            return $w;
        }
        return ucfirst($w);
    }, $words);
    return ucfirst(implode(' ', $words));
}
}

// Helper penentuan jenjang otomatis jika dari PDDIKTI nilainya kosong
if (!function_exists('resolve_jenjang')) {
function resolve_jenjang($namaPt = '', $namaProdi = '') {
    static $mapCache = null;
    if ($mapCache === null) {
        $mapCache = [];
        if (Database::isSqlite()) {
            $pdo = Database::getPdo();
            $rows = $pdo->query("SELECT UPPER(nama_pt) as pt, UPPER(nama_prodi) as pr, jenjang_prodi FROM prodi_aceh WHERE jenjang_prodi IS NOT NULL AND jenjang_prodi != ''")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                $mapCache[$r['pt'] . '|' . $r['pr']] = $r['jenjang_prodi'];
                if (!isset($mapCache['*|' . $r['pr']])) {
                    $mapCache['*|' . $r['pr']] = $r['jenjang_prodi'];
                }
            }
        }
    }

    $ptKey = strtoupper(trim($namaPt));
    $prKey = strtoupper(trim($namaProdi));

    if (isset($mapCache[$ptKey . '|' . $prKey])) {
        return $mapCache[$ptKey . '|' . $prKey];
    }
    if (isset($mapCache['*|' . $prKey])) {
        return $mapCache['*|' . $prKey];
    }

    if (stripos($namaPt, 'Politeknik') !== false || stripos($namaPt, 'Poltek') !== false) {
        return (stripos($namaProdi, 'Rekayasa') !== false || stripos($namaProdi, 'Terapan') !== false) ? 'D4' : 'D3';
    }
    if (stripos($namaProdi, 'Magister') !== false || stripos($namaProdi, 'Master') !== false) return 'S2';
    if (stripos($namaProdi, 'Doktor') !== false) return 'S3';
    if (stripos($namaProdi, 'Profesi') !== false) return 'Profesi';
    return 'S1';
}
}

$action = isset($_GET['action']) ? $_GET['action'] : '';

// Jika tidak ada parameter action tetapi ada page, jalankan default panduan (proxy PT filter)
if (empty($action) && isset($_GET['page'])) {
    header('Content-Type: application/json');
    $page = intval($_GET['page']);
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 15;
    $targetUrl = "https://pddikti.kemdiktisaintek.go.id/api/v2/pt/search/filter?limit={$limit}&page={$page}";
    
    $ch = curl_init($targetUrl);
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json, text/plain, */*',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Origin: https://pddikti.kemdiktisaintek.go.id',
        'Referer: https://pddikti.kemdiktisaintek.go.id/perguruan-tinggi'
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        echo $response;
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Gagal mengambil data dari PDDIKTI"]);
    }
    exit;
}

header('Content-Type: application/json');

// =========================================================================
// ROUTING API BERDASARKAN ACTION
// =========================================================================

switch ($action) {
    // ---------------------------------------------------------------------
    // 1. STATISTIK RINGKASAN DASHBOARD
    // ---------------------------------------------------------------------
    case 'stats':
        if (Database::isSqlite()) {
            $pdo = Database::getPdo();
            $totalKampus = $pdo->query("SELECT COUNT(*) FROM kampus_aceh")->fetchColumn();
            $rowProdi = $pdo->query("SELECT COUNT(*), COALESCE(SUM(jumlah_mahasiswa), 0) FROM prodi_aceh")->fetch(PDO::FETCH_NUM);
            $totalProdi = $rowProdi[0];
            $totalMhsProdi = $rowProdi[1];
            $totalDosen = $pdo->query("SELECT COUNT(*) FROM dosen_aceh")->fetchColumn();
            $totalPenelitianPnl = $pdo->query("SELECT COUNT(*) FROM dosen_penelitian WHERE nama_pt LIKE '%LHOKSEUMAWE%'")->fetchColumn();
            $totalDosenPendidikan = $pdo->query("SELECT COUNT(DISTINCT dosen_id) FROM dosen_pendidikan WHERE nama_pt LIKE '%LHOKSEUMAWE%'")->fetchColumn();
        } else {
            $kampusData = Database::getJsonData('kampus_aceh');
            $prodiData = Database::getJsonData('prodi_aceh');
            $dosenData = Database::getJsonData('dosen_aceh');
            $penelitianData = Database::getJsonData('dosen_penelitian');
            $pendidikanData = Database::getJsonData('dosen_pendidikan');

            $totalKampus = count($kampusData);
            $totalProdi = count($prodiData);
            $totalMhsProdi = array_sum(array_column($prodiData, 'jumlah_mahasiswa'));
            $totalDosen = count($dosenData);
            $totalPenelitianPnl = count(array_filter($penelitianData, fn($p) => stripos($p['nama_pt'], 'LHOKSEUMAWE') !== false));
            $dosenPnlPendidikan = array_unique(array_column(array_filter($pendidikanData, fn($p) => stripos($p['nama_pt'], 'LHOKSEUMAWE') !== false), 'dosen_id'));
            $totalDosenPendidikan = count($dosenPnlPendidikan);
        }

        echo json_encode([
            "status" => "success",
            "data" => [
                "total_kampus" => (int)$totalKampus,
                "total_prodi" => (int)$totalProdi,
                "total_mahasiswa_terdata" => (int)$totalMhsProdi,
                "total_dosen" => (int)$totalDosen,
                "total_penelitian_pnl" => (int)$totalPenelitianPnl,
                "total_dosen_pnl_pendidikan" => (int)$totalDosenPendidikan
            ]
        ]);
        break;

    // ---------------------------------------------------------------------
    // 2. KEBUTUHAN 1: MAHASISWA KAMPUS DI ACEH
    // ---------------------------------------------------------------------
    case 'mahasiswa':
        $q = isset($_GET['q']) ? trim($_GET['q']) : '';
        $pt = isset($_GET['pt']) ? trim($_GET['pt']) : '';
        $prodi = isset($_GET['prodi']) ? trim($_GET['prodi']) : '';
        $angkatan = isset($_GET['angkatan']) ? trim($_GET['angkatan']) : '';
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 30;
        $offset = ($page - 1) * $limit;

        if (Database::isSqlite()) {
            $pdo = Database::getPdo();
            $where = ["1=1"];
            $params = [];
            if ($q !== '') {
                $where[] = "(nama LIKE ? OR nim LIKE ? OR nama_prodi LIKE ?)";
                $params[] = "%$q%";
                $params[] = "%$q%";
                $params[] = "%$q%";
            }
            if ($pt !== '') {
                $where[] = "nama_pt LIKE ?";
                $params[] = "%$pt%";
            }
            if ($prodi !== '') {
                $where[] = "nama_prodi LIKE ?";
                $params[] = "%$prodi%";
            }
            // Filter angkatan via tanggal_masuk ATAU awalan NIM agar data baru selalu muncul
            if ($angkatan !== '') {
                $where[] = "(tanggal_masuk LIKE ? OR nim LIKE ?)";
                $params[] = "$angkatan%";
                $params[] = "$angkatan%";
            }
            $whereSql = implode(" AND ", $where);

            // Auto-fetch on demand ke PDDIKTI jika pencarian/kampus belum ada di DB lokal
            $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM mahasiswa_aceh WHERE $whereSql");
            $stmtCount->execute($params);
            $totalInit = (int)$stmtCount->fetchColumn();

            if ($totalInit == 0 && ($q !== '' || $pt !== '' || $prodi !== '')) {
                $targets = array_filter([$q, $prodi, $pt]);
                $searchTarget = implode(' ', $targets);
                $curlRes = pddikti_curl('pencarian/mhs/' . rawurlencode($searchTarget));
                if ($curlRes['code'] === 200 && !empty($curlRes['data']['data'])) {
                    $selStmt = $pdo->prepare("SELECT status_saat_ini, tanggal_masuk FROM mahasiswa_aceh WHERE nim = ? AND nim != '' LIMIT 1");
                    $ins = $pdo->prepare("INSERT OR REPLACE INTO mahasiswa_aceh (id, nama, nim, nama_pt, nama_prodi, jenjang, status_saat_ini, tanggal_masuk) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    foreach ($curlRes['data']['data'] as $m) {
                        $nimVal = trim($m['nim'] ?? '');
                        $tglRaw = $m['tanggal_masuk'] ?? $m['tahun_masuk'] ?? null;
                        $tglMasuk = null;
                        if ($tglRaw && preg_match('/\b(19\d\d|20\d\d)\b/', $tglRaw, $mm2)) {
                            $tglMasuk = $mm2[1] . '-09-01';
                        } else {
                            $yr = resolve_tahun_angkatan(null, $nimVal);
                            if ($yr) $tglMasuk = "$yr-09-01";
                        }
                        $insJenjang = !empty(trim($m['jenjang'] ?? '')) ? trim($m['jenjang']) : resolve_jenjang($m['nama_pt'] ?? '', $m['nama_prodi'] ?? '');
                        
                        // Pertahankan status yang sudah ada (misal Non-Aktif, Lulus) jika NIM sudah tersimpan
                        $currentStatus = 'Aktif';
                        if ($nimVal !== '') {
                            $selStmt->execute([$nimVal]);
                            $existing = $selStmt->fetch(PDO::FETCH_ASSOC);
                            if ($existing && !empty($existing['status_saat_ini'])) {
                                $currentStatus = $existing['status_saat_ini'];
                            }
                            if ($existing && !empty($existing['tanggal_masuk'])) {
                                $tglMasuk = $existing['tanggal_masuk'];
                            }
                        }
                        
                        $ins->execute([$m['id'], $m['nama'], $nimVal, $m['nama_pt'] ?? '', normalize_prodi_name($m['nama_prodi'] ?? ''), $insJenjang, $currentStatus, $tglMasuk]);
                    }
                    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM mahasiswa_aceh WHERE $whereSql");
                    $stmtCount->execute($params);
                    $totalInit = (int)$stmtCount->fetchColumn();
                }
            }

            // Total untuk paginasi: dedup berdasarkan nim
            $totalSql = "SELECT COUNT(*) FROM (SELECT nim FROM mahasiswa_aceh WHERE $whereSql AND nim != '' GROUP BY nim) _u";
            $stmtTotal = $pdo->prepare($totalSql);
            $stmtTotal->execute($params);
            $total = (int)$stmtTotal->fetchColumn();

            // Ambil data halaman ini — dedup via GROUP BY nim, langsung LIMIT/OFFSET
            $dataSql = "SELECT id, nama, nim, nama_pt, nama_prodi, jenjang, status_saat_ini, tanggal_masuk,
                               CAST(substr(tanggal_masuk, 1, 4) AS INTEGER) AS angkatan_num
                        FROM mahasiswa_aceh
                        WHERE $whereSql AND nim != ''
                        GROUP BY nim
                        ORDER BY nama ASC
                        LIMIT ? OFFSET ?";
            $dataParams = array_merge($params, [$limit, $offset]);
            $stmt = $pdo->prepare($dataSql);
            $stmt->execute($dataParams);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Enrich field angkatan & jenjang
            foreach ($rows as &$row) {
                $yr = (int)($row['angkatan_num'] ?? 0);
                if ($yr < 1990 || $yr > 2030) {
                    $yr = (int)resolve_tahun_angkatan($row['tanggal_masuk'] ?? null, $row['nim'] ?? null);
                }
                $row['angkatan'] = $yr > 0 ? (string)$yr : '-';
                unset($row['angkatan_num']);

                $j = trim($row['jenjang'] ?? '');
                if ($j === '' || $j === '-') {
                    $row['jenjang'] = resolve_jenjang($row['nama_pt'] ?? '', $row['nama_prodi'] ?? '');
                }
            }
            unset($row);

            // Daftar angkatan yang ada di DB (untuk dropdown, tahun realistis)
            $angkatanKeys = $pdo->query(
                "SELECT DISTINCT CAST(substr(tanggal_masuk,1,4) AS INTEGER) as yr
                 FROM mahasiswa_aceh
                 WHERE tanggal_masuk IS NOT NULL AND tanggal_masuk != '' AND length(tanggal_masuk) >= 4
                   AND CAST(substr(tanggal_masuk,1,4) AS INTEGER) BETWEEN 2015 AND 2026
                 ORDER BY yr DESC"
            )->fetchAll(PDO::FETCH_COLUMN);

        } else {
            $all = Database::getJsonData('mahasiswa_aceh');
            $rawRows = array_filter($all, function($m) use ($q, $pt, $prodi, $angkatan) {
                if ($q !== '' && stripos($m['nama'], $q) === false && stripos($m['nim'], $q) === false && stripos($m['nama_prodi'] ?? '', $q) === false) return false;
                if ($pt !== '' && stripos($m['nama_pt'], $pt) === false) return false;
                if ($prodi !== '' && stripos($m['nama_prodi'] ?? '', $prodi) === false) return false;
                if ($angkatan !== '' && strpos($m['tanggal_masuk'] ?? '', $angkatan) !== 0) return false;
                return true;
            });

            // Deduplikasi & enrich angkatan
            $seen = [];
            $filtered = [];
            $allAngkatan = [];
            foreach ($rawRows as $row) {
                $nimClean = trim($row['nim'] ?? '');
                $dedupeKey = $nimClean !== '' ? $nimClean : ($row['id'] ?? $row['nama']);
                if (isset($seen[$dedupeKey])) continue;
                $seen[$dedupeKey] = true;
                $tahun = resolve_tahun_angkatan($row['tanggal_masuk'] ?? null, $row['nim'] ?? null);
                $row['angkatan'] = $tahun ? (string)$tahun : '-';
                if ($tahun) $allAngkatan[$tahun] = true;
                $filtered[] = $row;
            }
            $total = count($filtered);
            usort($filtered, fn($a, $b) => strcmp($a['nama'], $b['nama']));
            $rows = array_slice($filtered, $offset, $limit);
            $angkatanKeys = array_keys($allAngkatan);
            rsort($angkatanKeys);
        }

        echo json_encode([
            "status" => "success",
            "total" => (int)$total,
            "page" => $page,
            "limit" => $limit,
            "angkatan_list" => array_values($angkatanKeys),
            "data" => array_values($rows)
        ]);
        break;

    // ---------------------------------------------------------------------
    // 2b. SINKRONISASI STATUS PDDIKTI PER PRODI / KAMPUS
    // ---------------------------------------------------------------------
    case 'sync_status_prodi':
        $pt = isset($_GET['pt']) ? trim($_GET['pt']) : '';
        $prodi = isset($_GET['prodi']) ? trim($_GET['prodi']) : '';

        if (empty($pt) && empty($prodi)) {
            echo json_encode(["status" => "error", "message" => "Parameter 'pt' atau 'prodi' wajib diisi"]);
            break;
        }

        if (Database::isSqlite()) {
            $pdo = Database::getPdo();
            $where = ["nim != ''"];
            $params = [];
            if ($pt !== '') {
                $where[] = "nama_pt LIKE ?";
                $params[] = "%$pt%";
            }
            if ($prodi !== '') {
                $where[] = "nama_prodi LIKE ?";
                $params[] = "%$prodi%";
            }
            $whereSql = implode(" AND ", $where);

            $stmt = $pdo->prepare("SELECT id, nim, nama, status_saat_ini FROM mahasiswa_aceh WHERE $whereSql ORDER BY nim ASC LIMIT 500");
            $stmt->execute($params);
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $updStmt = $pdo->prepare("UPDATE mahasiswa_aceh SET status_saat_ini = ?, tanggal_masuk = COALESCE(?, tanggal_masuk) WHERE nim = ?");
            $summary = [];
            $success = 0;

            $headers = [
                'Content-Type: application/json',
                'Accept: application/json, text/plain, */*',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Origin: https://pddikti.kemdiktisaintek.go.id',
                'Referer: https://pddikti.kemdiktisaintek.go.id/perguruan-tinggi'
            ];

            $chunks = array_chunk($students, 10);
            foreach ($chunks as $chunk) {
                $mh = curl_multi_init();
                $handles = [];
                foreach ($chunk as $idx => $m) {
                    if (strpos($m['id'], 'mhs_') === 0) continue;
                    $ch = curl_init("https://pddikti.kemdiktisaintek.go.id/api/detail/mhs/" . $m['id']);
                    curl_setopt($ch, CURLOPT_HTTPGET, true);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
                    curl_multi_add_handle($mh, $ch);
                    $handles[$idx] = ['ch' => $ch, 'nim' => $m['nim']];
                }

                if (!empty($handles)) {
                    $running = null;
                    do {
                        curl_multi_exec($mh, $running);
                        curl_multi_select($mh);
                    } while ($running > 0);

                    $pdo->beginTransaction();
                    foreach ($handles as $h) {
                        $resp = curl_multi_getcontent($h['ch']);
                        $code = curl_getinfo($h['ch'], CURLINFO_HTTP_CODE);
                        curl_multi_remove_handle($mh, $h['ch']);
                        curl_close($h['ch']);

                        if ($code === 200 && $resp) {
                            $json = json_decode($resp, true);
                            if (isset($json['data'])) {
                                $d = $json['data'];
                                $rawStatus = trim($d['status_saat_ini'] ?? '');
                                $statusClean = 'Aktif';
                                if (!empty($rawStatus)) {
                                    $first = trim(explode('-', $rawStatus)[0]);
                                    if (stripos($first, 'lulus') !== false) {
                                        $statusClean = 'Lulus';
                                    } elseif (stripos($first, 'non') !== false) {
                                        $statusClean = 'Non-Aktif';
                                    } elseif (stripos($first, 'cuti') !== false) {
                                        $statusClean = 'Cuti';
                                    } elseif (stripos($first, 'undur') !== false) {
                                        $statusClean = 'Mengundurkan Diri';
                                    } elseif (stripos($first, 'keluar') !== false || stripos($first, 'drop') !== false) {
                                        $statusClean = 'Dikeluarkan';
                                    } elseif (stripos($first, 'meninggal') !== false) {
                                        $statusClean = 'Meninggal Dunia';
                                    } elseif (stripos($first, 'aktif') !== false) {
                                        $statusClean = 'Aktif';
                                    } else {
                                        $statusClean = ucfirst(strtolower($first));
                                    }
                                }
                                $tgl = !empty($d['tanggal_masuk']) ? $d['tanggal_masuk'] : null;
                                $updStmt->execute([$statusClean, $tgl, $h['nim']]);
                                $summary[$statusClean] = ($summary[$statusClean] ?? 0) + 1;
                                $success++;
                            }
                        }
                    }
                    $pdo->commit();
                    curl_multi_close($mh);
                }
            }

            echo json_encode([
                "status" => "success",
                "message" => "Sinkronisasi selesai untuk $pt $prodi",
                "total_diperiksa" => count($students),
                "total_berhasil" => $success,
                "rincian_status" => $summary
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => "Database SQLite tidak aktif"]);
        }
        break;

    // ---------------------------------------------------------------------
    // 2c. DETAIL MAHASISWA PER PRODI (JUMLAH PER ANGKATAN & DAFTAR NAMA)
    // ---------------------------------------------------------------------
    case 'prodi_detail_mahasiswa':
        $pt = isset($_GET['pt']) ? trim($_GET['pt']) : '';
        $prodi = isset($_GET['prodi']) ? trim($_GET['prodi']) : '';
        $q = isset($_GET['q']) ? trim($_GET['q']) : '';
        $filterAngkatan = isset($_GET['angkatan']) ? trim($_GET['angkatan']) : '';
        $filterStatus = isset($_GET['status']) ? trim($_GET['status']) : '';

        if (empty($pt) && empty($prodi)) {
            echo json_encode(["status" => "error", "message" => "Parameter 'pt' atau 'prodi' diperlukan"]);
            break;
        }

        if (Database::isSqlite()) {
            $pdo = Database::getPdo();
            
            // Cek apakah data mahasiswa prodi ini sudah ada di DB lokal
            $chkStmt = $pdo->prepare("SELECT COUNT(*) FROM mahasiswa_aceh WHERE nama_pt LIKE ? AND nama_prodi LIKE ? AND nim != ''");
            $chkStmt->execute(["%$pt%", "%$prodi%"]);
            $localCount = (int)$chkStmt->fetchColumn();

            // Jika masih sedikit (< 10) atau kosong, auto-fetch dari PDDIKTI
            if ($localCount < 10) {
                $searchQuery = trim("$prodi $pt");
                $curlRes = pddikti_curl('pencarian/mhs/' . rawurlencode($searchQuery));
                if ($curlRes['code'] === 200 && !empty($curlRes['data']['data'])) {
                    $selStmt = $pdo->prepare("SELECT status_saat_ini, tanggal_masuk FROM mahasiswa_aceh WHERE nim = ? AND nim != '' LIMIT 1");
                    $ins = $pdo->prepare("INSERT OR REPLACE INTO mahasiswa_aceh (id, nama, nim, nama_pt, nama_prodi, jenjang, status_saat_ini, tanggal_masuk) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    foreach ($curlRes['data']['data'] as $m) {
                        $nimVal = trim($m['nim'] ?? '');
                        if (empty($nimVal)) continue;
                        $tglRaw = $m['tanggal_masuk'] ?? $m['tahun_masuk'] ?? null;
                        $tglMasuk = null;
                        if ($tglRaw && preg_match('/\b(19\d\d|20\d\d)\b/', $tglRaw, $mm2)) {
                            $tglMasuk = $mm2[1] . '-09-01';
                        } else {
                            $yr = resolve_tahun_angkatan(null, $nimVal);
                            if ($yr) $tglMasuk = "$yr-09-01";
                        }
                        $insJenjang = !empty(trim($m['jenjang'] ?? '')) ? trim($m['jenjang']) : resolve_jenjang($m['nama_pt'] ?? '', $m['nama_prodi'] ?? '');
                        
                        $currentStatus = 'Aktif';
                        $selStmt->execute([$nimVal]);
                        $existing = $selStmt->fetch(PDO::FETCH_ASSOC);
                        if ($existing && !empty($existing['status_saat_ini'])) {
                            $currentStatus = $existing['status_saat_ini'];
                        }
                        if ($existing && !empty($existing['tanggal_masuk'])) {
                            $tglMasuk = $existing['tanggal_masuk'];
                        }
                        
                        $ins->execute([$m['id'], $m['nama'], $nimVal, $m['nama_pt'] ?? '', normalize_prodi_name($m['nama_prodi'] ?? ''), $insJenjang, $currentStatus, $tglMasuk]);
                    }
                }
            }

            // Ambil seluruh mahasiswa unik per NIM untuk prodi & kampus ini
            $baseSql = "SELECT id, nama, nim, nama_pt, nama_prodi, jenjang, status_saat_ini, tanggal_masuk
                        FROM mahasiswa_aceh
                        WHERE nama_pt LIKE ? AND nama_prodi LIKE ? AND nim != ''
                        GROUP BY nim
                        ORDER BY nama ASC";
            $stmtAll = $pdo->prepare($baseSql);
            $stmtAll->execute(["%$pt%", "%$prodi%"]);
            $allRows = $stmtAll->fetchAll(PDO::FETCH_ASSOC);

            // Perhitungan statistik per angkatan & status
            $angkatanStats = [];
            $statusStats = [];
            $enrichedStudents = [];

            foreach ($allRows as $r) {
                $yr = resolve_tahun_angkatan($r['tanggal_masuk'] ?? null, $r['nim'] ?? null);
                $angStr = $yr ? (string)$yr : '-';
                $r['angkatan'] = $angStr;
                
                $st = trim($r['status_saat_ini'] ?? '') ?: 'Aktif';
                $statusStats[$st] = ($statusStats[$st] ?? 0) + 1;

                if (!isset($angkatanStats[$angStr])) {
                    $angkatanStats[$angStr] = [
                        'angkatan' => $angStr,
                        'total' => 0,
                        'aktif' => 0,
                        'non_aktif' => 0,
                        'lulus' => 0,
                        'cuti' => 0,
                        'lainnya' => 0
                    ];
                }
                $angkatanStats[$angStr]['total']++;
                $stLower = strtolower($st);
                if (strpos($stLower, 'lulus') !== false) {
                    $angkatanStats[$angStr]['lulus']++;
                } elseif (strpos($stLower, 'non') !== false) {
                    $angkatanStats[$angStr]['non_aktif']++;
                } elseif (strpos($stLower, 'cuti') !== false) {
                    $angkatanStats[$angStr]['cuti']++;
                } elseif (strpos($stLower, 'aktif') !== false) {
                    $angkatanStats[$angStr]['aktif']++;
                } else {
                    $angkatanStats[$angStr]['lainnya']++;
                }

                // Filter siswa untuk list yang dikembalikan
                $match = true;
                if ($filterAngkatan !== '' && $angStr !== $filterAngkatan) $match = false;
                if ($filterStatus !== '' && strcasecmp($st, $filterStatus) !== 0) $match = false;
                if ($q !== '' && stripos($r['nama'], $q) === false && stripos($r['nim'], $q) === false) $match = false;

                if ($match) {
                    $enrichedStudents[] = $r;
                }
            }

            // Urutkan angkatan descending
            krsort($angkatanStats);

            echo json_encode([
                "status" => "success",
                "pt" => $pt,
                "prodi" => $prodi,
                "total_prodi_mhs" => count($allRows),
                "total_filtered" => count($enrichedStudents),
                "angkatan_stats" => array_values($angkatanStats),
                "status_stats" => $statusStats,
                "students" => $enrichedStudents
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => "Database SQLite tidak aktif"]);
        }
        break;

    // ---------------------------------------------------------------------
    // 3. KEBUTUHAN 2: DOSEN KAMPUS DI ACEH
    // ---------------------------------------------------------------------
    case 'dosen':
        $q = isset($_GET['q']) ? trim($_GET['q']) : '';
        $pt = isset($_GET['pt']) ? trim($_GET['pt']) : '';
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 30;
        $offset = ($page - 1) * $limit;

        if (Database::isSqlite()) {
            $pdo = Database::getPdo();
            $where = ["1=1"];
            $params = [];
            if ($q !== '') {
                $where[] = "(nama LIKE ? OR nidn LIKE ? OR nuptk LIKE ?)";
                $params[] = "%$q%";
                $params[] = "%$q%";
                $params[] = "%$q%";
            }
            if ($pt !== '') {
                $where[] = "nama_pt LIKE ?";
                $params[] = "%$pt%";
            }
            $whereSql = implode(" AND ", $where);
            $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM dosen_aceh WHERE $whereSql");
            $stmtCount->execute($params);
            $total = $stmtCount->fetchColumn();

            // Auto-fetch on demand ke PDDIKTI jika dosen / kampus belum ada di DB lokal
            if ($total == 0 && ($q !== '' || $pt !== '')) {
                $searchTarget = ($q !== '') ? $q : $pt;
                $curlRes = pddikti_curl('pencarian/dosen/' . rawurlencode($searchTarget));
                if ($curlRes['code'] === 200 && !empty($curlRes['data']['data'])) {
                    $ins = $pdo->prepare("INSERT OR REPLACE INTO dosen_aceh (id, nama, nidn, nuptk, nama_pt, sinkatan_pt, nama_prodi) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    foreach ($curlRes['data']['data'] as $d) {
                        $ins->execute([$d['id'], $d['nama'], $d['nidn'] ?? '', $d['nuptk'] ?? '', $d['nama_pt'] ?? '', $d['sinkatan_pt'] ?? '', normalize_prodi_name($d['nama_prodi'] ?? '')]);
                    }
                    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM dosen_aceh WHERE $whereSql");
                    $stmtCount->execute($params);
                    $total = $stmtCount->fetchColumn();
                }
            }

            $stmt = $pdo->prepare("SELECT * FROM dosen_aceh WHERE $whereSql ORDER BY nama ASC LIMIT ? OFFSET ?");
            $queryParams = array_merge($params, [$limit, $offset]);
            $stmt->execute($queryParams);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $all = Database::getJsonData('dosen_aceh');
            $filtered = array_filter($all, function($d) use ($q, $pt) {
                if ($q !== '' && stripos($d['nama'], $q) === false && stripos($d['nidn'], $q) === false) return false;
                if ($pt !== '' && stripos($d['nama_pt'], $pt) === false) return false;
                return true;
            });
            $total = count($filtered);
            usort($filtered, fn($a, $b) => strcmp($a['nama'], $b['nama']));
            $rows = array_slice($filtered, $offset, $limit);
        }

        echo json_encode([
            "status" => "success",
            "total" => (int)$total,
            "page" => $page,
            "limit" => $limit,
            "data" => array_values($rows)
        ]);
        break;

    case 'dosen_pnl_options':
        if (Database::isSqlite()) {
            $pdo = Database::getPdo();
            $dList = $pdo->query("SELECT DISTINCT dosen_nama, nidn FROM dosen_penelitian WHERE nama_pt LIKE '%LHOKSEUMAWE%' UNION SELECT DISTINCT dosen_nama, nidn FROM dosen_pendidikan WHERE nama_pt LIKE '%LHOKSEUMAWE%' UNION SELECT DISTINCT nama as dosen_nama, nidn FROM dosen_aceh WHERE nama_pt LIKE '%LHOKSEUMAWE%' ORDER BY dosen_nama ASC")->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $dList = [];
        }
        echo json_encode(["status" => "success", "data" => $dList]);
        break;

    // ---------------------------------------------------------------------
    // 4. KEBUTUHAN 3: PENELITIAN DOSEN PNL
    // ---------------------------------------------------------------------
    case 'penelitian':
        $q = isset($_GET['q']) ? trim($_GET['q']) : '';
        $dosen = isset($_GET['dosen']) ? trim($_GET['dosen']) : '';
        $tahun = isset($_GET['tahun']) ? intval($_GET['tahun']) : 0;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;

        if (Database::isSqlite()) {
            $pdo = Database::getPdo();
            $where = ["nama_pt LIKE '%LHOKSEUMAWE%'"];
            $params = [];
            if ($q !== '') {
                $where[] = "(judul_kegiatan LIKE ? OR jenis_kegiatan LIKE ?)";
                $params[] = "%$q%";
                $params[] = "%$q%";
            }
            if ($dosen !== '') {
                $where[] = "dosen_nama LIKE ?";
                $params[] = "%$dosen%";
            }
            if ($tahun > 0) {
                $where[] = "tahun_kegiatan = ?";
                $params[] = $tahun;
            }
            $whereSql = implode(" AND ", $where);
            $stmt = $pdo->prepare("SELECT * FROM dosen_penelitian WHERE $whereSql ORDER BY tahun_kegiatan DESC, dosen_nama ASC LIMIT ?");
            $params[] = $limit;
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // List tahun
            $tahunList = $pdo->query("SELECT DISTINCT tahun_kegiatan FROM dosen_penelitian WHERE nama_pt LIKE '%LHOKSEUMAWE%' AND tahun_kegiatan > 0 ORDER BY tahun_kegiatan DESC")->fetchAll(PDO::FETCH_COLUMN);
        } else {
            $all = Database::getJsonData('dosen_penelitian');
            $filtered = array_filter($all, function($p) use ($q, $dosen, $tahun) {
                if (stripos($p['nama_pt'], 'LHOKSEUMAWE') === false) return false;
                if ($q !== '' && stripos($p['judul_kegiatan'], $q) === false) return false;
                if ($dosen !== '' && stripos($p['dosen_nama'], $dosen) === false) return false;
                if ($tahun > 0 && intval($p['tahun_kegiatan']) !== $tahun) return false;
                return true;
            });
            usort($filtered, fn($a, $b) => ($b['tahun_kegiatan'] <=> $a['tahun_kegiatan']));
            $rows = array_slice($filtered, 0, $limit);
            $tahunList = array_values(array_unique(array_filter(array_column($all, 'tahun_kegiatan'))));
            rsort($tahunList);
        }

        echo json_encode([
            "status" => "success",
            "total" => count($rows),
            "tahun_list" => $tahunList,
            "data" => array_values($rows)
        ]);
        break;

    // ---------------------------------------------------------------------
    // 5. KEBUTUHAN 4: STATISTIK JUMLAH MAHASISWA PRODI DI ACEH
    // ---------------------------------------------------------------------
    case 'prodi':
        $pt = isset($_GET['pt']) ? trim($_GET['pt']) : '';
        $jenjang = isset($_GET['jenjang']) ? trim($_GET['jenjang']) : '';
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;

        if (Database::isSqlite()) {
            $pdo = Database::getPdo();
            $where = ["1=1"];
            $params = [];
            if ($pt !== '') {
                $where[] = "nama_pt LIKE ?";
                $params[] = "%$pt%";
            }
            if ($jenjang !== '') {
                $where[] = "jenjang_prodi = ?";
                $params[] = $jenjang;
            }
            $whereSql = implode(" AND ", $where);
            $stmt = $pdo->prepare("SELECT * FROM prodi_aceh WHERE $whereSql ORDER BY jumlah_mahasiswa DESC LIMIT ?");
            $params[] = $limit;
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Ringkasan per jenjang
            $jenjangSummary = $pdo->query("SELECT jenjang_prodi, COUNT(*) as jml_prodi, SUM(jumlah_mahasiswa) as total_mhs FROM prodi_aceh GROUP BY jenjang_prodi ORDER BY total_mhs DESC")->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $all = Database::getJsonData('prodi_aceh');
            $filtered = array_filter($all, function($p) use ($pt, $jenjang) {
                if ($pt !== '' && stripos($p['nama_pt'], $pt) === false) return false;
                if ($jenjang !== '' && $p['jenjang_prodi'] !== $jenjang) return false;
                return true;
            });
            usort($filtered, fn($a, $b) => ($b['jumlah_mahasiswa'] <=> $a['jumlah_mahasiswa']));
            $rows = array_slice($filtered, 0, $limit);

            // Grouping jenjang
            $jenjangMap = [];
            foreach ($all as $item) {
                $j = $item['jenjang_prodi'] ?: 'Lainnya';
                if (!isset($jenjangMap[$j])) $jenjangMap[$j] = ['jenjang_prodi' => $j, 'jml_prodi' => 0, 'total_mhs' => 0];
                $jenjangMap[$j]['jml_prodi']++;
                $jenjangMap[$j]['total_mhs'] += (int)$item['jumlah_mahasiswa'];
            }
            usort($jenjangMap, fn($a, $b) => $b['total_mhs'] <=> $a['total_mhs']);
            $jenjangSummary = array_values($jenjangMap);
        }

        echo json_encode([
            "status" => "success",
            "total" => count($rows),
            "data" => array_values($rows),
            "jenjang_summary" => $jenjangSummary
        ]);
        break;

    // ---------------------------------------------------------------------
    // 6. KEBUTUHAN 5: RIWAYAT PENDIDIKAN DOSEN PNL
    // ---------------------------------------------------------------------
    case 'pendidikan':
        $q = isset($_GET['q']) ? trim($_GET['q']) : '';
        $dosen = isset($_GET['dosen']) ? trim($_GET['dosen']) : '';
        $jenjang = isset($_GET['jenjang']) ? trim($_GET['jenjang']) : '';
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;

        if (Database::isSqlite()) {
            $pdo = Database::getPdo();
            $where = ["nama_pt LIKE '%LHOKSEUMAWE%'"];
            $params = [];
            if ($q !== '') {
                $where[] = "(dosen_nama LIKE ? OR nama_pt_studi LIKE ? OR gelar_akademik LIKE ?)";
                $params[] = "%$q%";
                $params[] = "%$q%";
                $params[] = "%$q%";
            }
            if ($dosen !== '') {
                $where[] = "dosen_nama LIKE ?";
                $params[] = "%$dosen%";
            }
            if ($jenjang !== '') {
                $where[] = "jenjang = ?";
                $params[] = $jenjang;
            }
            $whereSql = implode(" AND ", $where);
            $stmt = $pdo->prepare("SELECT * FROM dosen_pendidikan WHERE $whereSql ORDER BY dosen_nama ASC, tahun_lulus DESC LIMIT ?");
            $params[] = $limit;
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $jenjangDist = $pdo->query("SELECT jenjang, COUNT(*) as count FROM dosen_pendidikan WHERE nama_pt LIKE '%LHOKSEUMAWE%' GROUP BY jenjang ORDER BY count DESC")->fetchAll(PDO::FETCH_ASSOC);
            $topAlmamater = $pdo->query("SELECT nama_pt_studi, COUNT(*) as count FROM dosen_pendidikan WHERE nama_pt LIKE '%LHOKSEUMAWE%' AND nama_pt_studi != '' GROUP BY nama_pt_studi ORDER BY count DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $all = Database::getJsonData('dosen_pendidikan');
            $filtered = array_filter($all, function($p) use ($q, $dosen, $jenjang) {
                if (stripos($p['nama_pt'], 'LHOKSEUMAWE') === false) return false;
                if ($q !== '' && stripos($p['dosen_nama'], $q) === false && stripos($p['nama_pt_studi'], $q) === false) return false;
                if ($dosen !== '' && stripos($p['dosen_nama'], $dosen) === false) return false;
                if ($jenjang !== '' && $p['jenjang'] !== $jenjang) return false;
                return true;
            });
            $rows = array_slice($filtered, 0, $limit);

            $jCount = [];
            $ptCount = [];
            foreach ($all as $item) {
                if (stripos($item['nama_pt'], 'LHOKSEUMAWE') === false) continue;
                $j = $item['jenjang'] ?: '-';
                $jCount[$j] = ($jCount[$j] ?? 0) + 1;
                $pts = $item['nama_pt_studi'];
                if ($pts) $ptCount[$pts] = ($ptCount[$pts] ?? 0) + 1;
            }
            arsort($jCount);
            arsort($ptCount);
            $jenjangDist = [];
            foreach ($jCount as $k => $v) $jenjangDist[] = ['jenjang' => $k, 'count' => $v];
            $topAlmamater = [];
            foreach (array_slice($ptCount, 0, 5, true) as $k => $v) $topAlmamater[] = ['nama_pt_studi' => $k, 'count' => $v];
        }

        echo json_encode([
            "status" => "success",
            "total" => count($rows),
            "jenjang_dist" => $jenjangDist,
            "top_almamater" => $topAlmamater,
            "data" => array_values($rows)
        ]);
        break;

    // ---------------------------------------------------------------------
    // 7. DAFTAR KAMPUS DI ACEH
    // ---------------------------------------------------------------------
    case 'kampus':
        $q = isset($_GET['q']) ? trim($_GET['q']) : '';
        $kab = isset($_GET['kab_kota']) ? trim($_GET['kab_kota']) : '';
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 200;

        if (Database::isSqlite()) {
            $pdo = Database::getPdo();
            $where = ["1=1"];
            $params = [];
            if ($q !== '') {
                $where[] = "(nama LIKE ? OR kode LIKE ?)";
                $params[] = "%$q%";
                $params[] = "%$q%";
            }
            if ($kab !== '') {
                $where[] = "kab_kota LIKE ?";
                $params[] = "%$kab%";
            }
            $whereSql = implode(" AND ", $where);
            $stmt = $pdo->prepare("SELECT * FROM kampus_aceh WHERE $whereSql ORDER BY nama ASC LIMIT ?");
            $params[] = $limit;
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $kabList = $pdo->query("SELECT DISTINCT kab_kota FROM kampus_aceh WHERE kab_kota IS NOT NULL AND kab_kota != '' ORDER BY kab_kota ASC")->fetchAll(PDO::FETCH_COLUMN);
        } else {
            $all = Database::getJsonData('kampus_aceh');
            $filtered = array_filter($all, function($k) use ($q, $kab) {
                if ($q !== '' && stripos($k['nama'], $q) === false && stripos($k['kode'], $q) === false) return false;
                if ($kab !== '' && stripos($k['kab_kota'], $kab) === false) return false;
                return true;
            });
            usort($filtered, fn($a, $b) => strcmp($a['nama'], $b['nama']));
            $rows = array_slice($filtered, 0, $limit);
            $kabList = array_values(array_unique(array_filter(array_column($all, 'kab_kota'))));
            sort($kabList);
        }

        echo json_encode([
            "status" => "success",
            "total" => count($rows),
            "kab_kota_list" => $kabList,
            "data" => array_values($rows)
        ]);
        break;

    // ---------------------------------------------------------------------
    // 8. LIVE SEARCH LANGSUNG KE PDDIKTI (cURL)
    // ---------------------------------------------------------------------
    case 'live_search':
        $type = isset($_GET['type']) ? $_GET['type'] : 'mahasiswa';
        $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

        if (strlen($keyword) < 2) {
            echo json_encode(["status" => "error", "message" => "Minimal 2 karakter"]);
            exit;
        }

        $endpoint = ($type === 'mahasiswa') 
            ? "pencarian/mhs/" . rawurlencode($keyword) 
            : "pencarian/dosen/" . rawurlencode($keyword);

        $res = pddikti_curl($endpoint);

        if ($res['code'] === 200 && !empty($res['data']['data'])) {
            $items = $res['data']['data'];

            // Simpan otomatis ke database
            if (Database::isSqlite()) {
                $pdo = Database::getPdo();
                if ($type === 'mahasiswa') {
                    $ins = $pdo->prepare("INSERT OR REPLACE INTO mahasiswa_aceh (id, nama, nim, nama_pt, nama_prodi, jenjang, status_saat_ini, tanggal_masuk) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    foreach ($items as $m) {
                        $tglRaw = $m['tanggal_masuk'] ?? $m['tahun_masuk'] ?? null;
                        $tglMasuk = null;
                        if ($tglRaw && preg_match('/\b(19\d\d|20\d\d)\b/', $tglRaw, $mm2)) {
                            $tglMasuk = $mm2[1] . '-09-01';
                        } else {
                            $yr = resolve_tahun_angkatan(null, $m['nim'] ?? '');
                            if ($yr) $tglMasuk = "$yr-09-01";
                        }
                        $insJenjang = !empty(trim($m['jenjang'] ?? '')) ? trim($m['jenjang']) : resolve_jenjang($m['nama_pt'] ?? '', $m['nama_prodi'] ?? '');
                        $ins->execute([
                            $m['id'],
                            $m['nama'],
                            $m['nim'] ?? '',
                            $m['nama_pt'] ?? '',
                            normalize_prodi_name($m['nama_prodi'] ?? ''),
                            $insJenjang,
                            'Aktif',
                            $tglMasuk
                        ]);
                    }
                } else {
                    $ins = $pdo->prepare("INSERT OR REPLACE INTO dosen_aceh (id, nama, nidn, nuptk, nama_pt, sinkatan_pt, nama_prodi) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    foreach ($items as $d) {
                        $ins->execute([$d['id'], $d['nama'], $d['nidn'] ?? '', $d['nuptk'] ?? '', $d['nama_pt'] ?? '', $d['sinkatan_pt'] ?? '', normalize_prodi_name($d['nama_prodi'] ?? '')]);
                    }
                }
            }

            echo json_encode([
                "status" => "success",
                "count" => count($items),
                "data" => $items
            ]);
        } else {
            echo json_encode([
                "status" => "success",
                "count" => 0,
                "data" => []
            ]);
        }
        break;

    // ---------------------------------------------------------------------
    // 9. EXPORT EXCEL & CSV
    // ---------------------------------------------------------------------
    case 'export':
        $type = isset($_GET['type']) ? $_GET['type'] : 'mahasiswa';
        $format = isset($_GET['format']) ? strtolower(trim($_GET['format'])) : 'excel';

        if ($type === 'mahasiswa') {
            $q = isset($_GET['q']) ? trim($_GET['q']) : '';
            $pt = isset($_GET['pt']) ? trim($_GET['pt']) : '';
            $prodi = isset($_GET['prodi']) ? trim($_GET['prodi']) : '';
            $angkatan = isset($_GET['angkatan']) ? trim($_GET['angkatan']) : '';

            // Limit max 50.000 baris per download untuk performa
            $exportLimit = 50000;
            if (Database::isSqlite()) {
                $pdo = Database::getPdo();
                $where = ["1=1"];
                $params = [];
                if ($q !== '') {
                    $where[] = "(nama LIKE ? OR nim LIKE ? OR nama_prodi LIKE ?)";
                    $params[] = "%$q%";
                    $params[] = "%$q%";
                    $params[] = "%$q%";
                }
                if ($pt !== '') {
                    $where[] = "nama_pt LIKE ?";
                    $params[] = "%$pt%";
                }
                if ($prodi !== '') {
                    $where[] = "nama_prodi LIKE ?";
                    $params[] = "%$prodi%";
                }
                // Filter angkatan langsung via tanggal_masuk
                if ($angkatan !== '') {
                    $where[] = "tanggal_masuk LIKE ?";
                    $params[] = "$angkatan%";
                }
                $whereSql = implode(" AND ", $where);

                // Dedup via GROUP BY nim + LIMIT untuk performa
                $expSql = "SELECT id, nama, nim, nama_pt, nama_prodi, jenjang, status_saat_ini, tanggal_masuk,
                                   CAST(substr(tanggal_masuk,1,4) AS INTEGER) AS yr
                            FROM mahasiswa_aceh
                            WHERE $whereSql AND nim != ''
                            GROUP BY nim
                            ORDER BY nama ASC
                            LIMIT ?";
                $expParams = array_merge($params, [$exportLimit]);
                $stmt = $pdo->prepare($expSql);
                $stmt->execute($expParams);
                $rawRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $rawRows = Database::getJsonData('mahasiswa_aceh');
                if ($q !== '' || $pt !== '' || $prodi !== '' || $angkatan !== '') {
                    $seen2 = [];
                    $rawRows = array_filter($rawRows, function($m) use ($q, $pt, $prodi, $angkatan, &$seen2) {
                        if ($q !== '' && stripos($m['nama'], $q) === false && stripos($m['nim'], $q) === false && stripos($m['nama_prodi'] ?? '', $q) === false) return false;
                        if ($pt !== '' && stripos($m['nama_pt'], $pt) === false) return false;
                        if ($prodi !== '' && stripos($m['nama_prodi'] ?? '', $prodi) === false) return false;
                        if ($angkatan !== '' && strpos($m['tanggal_masuk'] ?? '', $angkatan) !== 0) return false;
                        $nim = trim($m['nim'] ?? '');
                        $key = $nim ?: ($m['id'] ?? $m['nama']);
                        if (isset($seen2[$key])) return false;
                        $seen2[$key] = true;
                        return true;
                    });
                }
                $rawRows = array_slice($rawRows, 0, $exportLimit);
            }

            // Enrich & build export data
            $exportData = [];
            $no = 1;
            foreach ($rawRows as $row) {
                $nimClean = trim($row['nim'] ?? '');
                $yr = (int)($row['yr'] ?? 0);
                if ($yr < 1990 || $yr > 2030) {
                    $yr = (int)resolve_tahun_angkatan($row['tanggal_masuk'] ?? null, $nimClean ?: null);
                }
                $thnStr = $yr > 0 ? (string)$yr : '-';

                $jClean = trim($row['jenjang'] ?? '');
                if ($jClean === '' || $jClean === '-') {
                    $jClean = resolve_jenjang($row['nama_pt'] ?? '', $row['nama_prodi'] ?? '');
                }

                $exportData[] = [
                    'No' => $no++,
                    'Nama Mahasiswa' => $row['nama'] ?? '-',
                    'NIM' => $nimClean !== '' ? $nimClean : '-',
                    'Tahun Angkatan' => $thnStr,
                    'Perguruan Tinggi' => $row['nama_pt'] ?? '-',
                    'Program Studi' => $row['nama_prodi'] ?? '-',
                    'Status' => $row['status_saat_ini'] ?? 'Aktif'
                ];
            }

            $safeAngkatan = $angkatan ? "_Angkatan_{$angkatan}" : "";
            $filename = "Data_Mahasiswa{$safeAngkatan}_" . date('Ymd_His');

            if ($format === 'csv') {
                header('Content-Type: text/csv; charset=utf-8');
                header("Content-Disposition: attachment; filename=\"{$filename}.csv\"");
                $output = fopen('php://output', 'w');
                fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
                if (!empty($exportData)) {
                    fputcsv($output, array_keys($exportData[0]));
                    foreach ($exportData as $item) {
                        fputcsv($output, $item);
                    }
                }
                fclose($output);
                exit;
            } else {
                // Microsoft Excel XML / HTML table format (.xls)
                header('Content-Type: application/vnd.ms-excel; charset=utf-8');
                header("Content-Disposition: attachment; filename=\"{$filename}.xls\"");
                header('Pragma: no-cache');
                header('Expires: 0');

                echo "\xEF\xBB\xBF";
                echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
                echo '<head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
                echo '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Data Mahasiswa</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
                echo '<style>
                    body { font-family: Calibri, "Segoe UI", Arial, sans-serif; font-size: 11pt; color: #1e293b; }
                    .table-excel { border-collapse: collapse; width: 100%; margin-top: 10px; }
                    .table-excel th { background-color: #1E40AF; color: #FFFFFF; font-weight: bold; text-align: center; border: 0.5pt solid #CBD5E1; padding: 10px 14px; }
                    .table-excel td { border: 0.5pt solid #E2E8F0; padding: 7px 10px; font-size: 10.5pt; }
                    .text-center { text-align: center; }
                    .num-str { mso-number-format: "\@"; text-align: center; }
                    .row-even { background-color: #F8FAFC; }
                </style></head><body>';
                echo '<h2 style="color: #1E40AF; margin-bottom: 4px;">DATA MAHASISWA PERGURUAN TINGGI</h2>';
                echo '<p style="color: #64748B; font-size: 10pt; margin-top: 0; margin-bottom: 12px;">Diunduh pada: ' . date('d/m/Y H:i:s') . ($angkatan ? ' &bull; Filter Angkatan: <b>' . htmlspecialchars($angkatan) . '</b>' : '') . ' &bull; Total: ' . count($exportData) . ' Mahasiswa (Bebas Duplikat)</p>';
                echo '<table class="table-excel" border="1">';
                echo '<thead><tr>';
                echo '<th style="width: 50px;">No</th>';
                echo '<th style="width: 250px;">Nama Mahasiswa</th>';
                echo '<th style="width: 140px;">NIM</th>';
                echo '<th style="width: 120px;">Tahun Angkatan</th>';
                echo '<th style="width: 260px;">Perguruan Tinggi</th>';
                echo '<th style="width: 200px;">Program Studi</th>';
                echo '<th style="width: 90px;">Status</th>';
                echo '</tr></thead><tbody>';

                foreach ($exportData as $idx => $r) {
                    $rowClass = ($idx % 2 === 1) ? ' class="row-even"' : '';
                    echo "<tr{$rowClass}>";
                    echo '<td class="text-center">' . $r['No'] . '</td>';
                    echo '<td><b>' . htmlspecialchars($r['Nama Mahasiswa']) . '</b></td>';
                    echo '<td class="num-str">' . htmlspecialchars($r['NIM']) . '</td>';
                    echo '<td class="text-center"><b>' . htmlspecialchars($r['Tahun Angkatan']) . '</b></td>';
                    echo '<td>' . htmlspecialchars($r['Perguruan Tinggi']) . '</td>';
                    echo '<td>' . htmlspecialchars($r['Program Studi']) . '</td>';
                    echo '<td class="text-center">' . htmlspecialchars($r['Status']) . '</td>';
                    echo '</tr>';
                }

                echo '</tbody></table></body></html>';
                exit;
            }
        } elseif ($type === 'dosen') {
            $q = isset($_GET['q']) ? trim($_GET['q']) : '';
            $pt = isset($_GET['pt']) ? trim($_GET['pt']) : '';
            $exportLimit = 50000;

            if (Database::isSqlite()) {
                $pdo = Database::getPdo();
                $where = ["1=1"];
                $params = [];
                if ($q !== '') {
                    $where[] = "(nama LIKE ? OR nidn LIKE ? OR nuptk LIKE ?)";
                    $params[] = "%$q%";
                    $params[] = "%$q%";
                    $params[] = "%$q%";
                }
                if ($pt !== '') {
                    $where[] = "nama_pt LIKE ?";
                    $params[] = "%$pt%";
                }
                $whereSql = implode(" AND ", $where);
                $stmt = $pdo->prepare("SELECT nama, nidn, nuptk, nama_pt, nama_prodi, status FROM dosen_aceh WHERE $whereSql ORDER BY nama_pt, nama ASC LIMIT ?");
                $params[] = $exportLimit;
                $stmt->execute($params);
                $rawRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $all = Database::getJsonData('dosen_aceh');
                $rawRows = array_filter($all, function($d) use ($q, $pt) {
                    if ($q !== '' && stripos($d['nama'], $q) === false && stripos($d['nidn'], $q) === false) return false;
                    if ($pt !== '' && stripos($d['nama_pt'], $pt) === false) return false;
                    return true;
                });
                $rawRows = array_slice($rawRows, 0, $exportLimit);
            }

            $exportData = [];
            $no = 1;
            foreach ($rawRows as $r) {
                $exportData[] = [
                    'No' => $no++,
                    'Nama Dosen' => $r['nama'] ?? '-',
                    'NIDN' => $r['nidn'] ?? '-',
                    'NUPTK' => $r['nuptk'] ?? '-',
                    'Perguruan Tinggi' => $r['nama_pt'] ?? '-',
                    'Program Studi' => $r['nama_prodi'] ?? '-',
                    'Status' => $r['status'] ?? 'Aktif'
                ];
            }

            $cleanPt = $pt ? preg_replace('/[^a-zA-Z0-9]/', '_', substr($pt, 0, 20)) . "_" : "";
            $filename = "data_dosen_aceh_{$cleanPt}" . date('Ymd_His');

            if ($format === 'csv') {
                header('Content-Type: text/csv; charset=utf-8');
                header("Content-Disposition: attachment; filename=\"{$filename}.csv\"");
                $output = fopen('php://output', 'w');
                fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
                if (!empty($exportData)) {
                    fputcsv($output, array_keys($exportData[0]));
                    foreach ($exportData as $item) {
                        fputcsv($output, $item);
                    }
                }
                fclose($output);
                exit;
            } else {
                header('Content-Type: application/vnd.ms-excel; charset=utf-8');
                header("Content-Disposition: attachment; filename=\"{$filename}.xls\"");
                header('Pragma: no-cache');
                header('Expires: 0');
                echo "\xEF\xBB\xBF";
                echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
                echo '<head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
                echo '<style>
                    body { font-family: Calibri, "Segoe UI", Arial, sans-serif; font-size: 11pt; color: #1e293b; }
                    .table-excel { border-collapse: collapse; width: 100%; margin-top: 10px; }
                    .table-excel th { background-color: #1E40AF; color: #FFFFFF; font-weight: bold; text-align: center; border: 0.5pt solid #CBD5E1; padding: 10px 14px; }
                    .table-excel td { border: 0.5pt solid #E2E8F0; padding: 7px 10px; font-size: 10.5pt; }
                    .text-center { text-align: center; }
                    .num-str { mso-number-format: "\@"; text-align: center; }
                    .row-even { background-color: #F8FAFC; }
                </style></head><body>';
                echo '<h2 style="color: #1E40AF; margin-bottom: 4px;">DATA DOSEN PERGURUAN TINGGI PROVINSI ACEH</h2>';
                echo '<p style="color: #64748B; font-size: 10pt; margin-top: 0; margin-bottom: 12px;">Diunduh pada: ' . date('d/m/Y H:i:s') . ($pt ? ' &bull; Kampus: <b>' . htmlspecialchars($pt) . '</b>' : '') . ' &bull; Total: ' . count($exportData) . ' Dosen</p>';
                echo '<table class="table-excel" border="1">';
                echo '<thead><tr>';
                echo '<th style="width: 50px;">No</th>';
                echo '<th style="width: 280px;">Nama Dosen</th>';
                echo '<th style="width: 140px;">NIDN</th>';
                echo '<th style="width: 160px;">NUPTK</th>';
                echo '<th style="width: 260px;">Perguruan Tinggi</th>';
                echo '<th style="width: 220px;">Program Studi</th>';
                echo '<th style="width: 100px;">Status</th>';
                echo '</tr></thead><tbody>';

                foreach ($exportData as $idx => $r) {
                    $rowClass = ($idx % 2 === 1) ? ' class="row-even"' : '';
                    echo "<tr{$rowClass}>";
                    echo '<td class="text-center">' . $r['No'] . '</td>';
                    echo '<td><b>' . htmlspecialchars($r['Nama Dosen']) . '</b></td>';
                    echo '<td class="num-str">' . htmlspecialchars($r['NIDN']) . '</td>';
                    echo '<td class="num-str">' . htmlspecialchars($r['NUPTK']) . '</td>';
                    echo '<td>' . htmlspecialchars($r['Perguruan Tinggi']) . '</td>';
                    echo '<td>' . htmlspecialchars($r['Program Studi']) . '</td>';
                    echo '<td class="text-center">' . htmlspecialchars($r['Status']) . '</td>';
                    echo '</tr>';
                }

                echo '</tbody></table></body></html>';
                exit;
            }
        } else {
            // General export fallback for other entities
            if (Database::isSqlite()) {
                $pdo = Database::getPdo();
                $tableMap = [
                    'dosen' => "SELECT * FROM dosen_aceh ORDER BY nama ASC",
                    'penelitian' => "SELECT * FROM dosen_penelitian WHERE nama_pt LIKE '%LHOKSEUMAWE%' ORDER BY tahun_kegiatan DESC",
                    'pendidikan' => "SELECT * FROM dosen_pendidikan WHERE nama_pt LIKE '%LHOKSEUMAWE%' ORDER BY dosen_nama ASC",
                    'prodi' => "SELECT * FROM prodi_aceh ORDER BY jumlah_mahasiswa DESC",
                    'kampus' => "SELECT * FROM kampus_aceh ORDER BY nama ASC"
                ];
                $sql = $tableMap[$type] ?? $tableMap['dosen'];
                $stmt = $pdo->query($sql);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $tableMap = [
                    'dosen' => 'dosen_aceh',
                    'penelitian' => 'dosen_penelitian',
                    'pendidikan' => 'dosen_pendidikan',
                    'prodi' => 'prodi_aceh',
                    'kampus' => 'kampus_aceh'
                ];
                $rows = Database::getJsonData($tableMap[$type] ?? 'dosen_aceh');
            }

            $filename = "{$type}_data_" . date('Ymd_His');

            if ($format === 'csv') {
                header('Content-Type: text/csv; charset=utf-8');
                header("Content-Disposition: attachment; filename={$filename}.csv");
                $output = fopen('php://output', 'w');
                fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
                if (!empty($rows)) {
                    fputcsv($output, array_keys($rows[0]));
                    foreach ($rows as $row) {
                        fputcsv($output, $row);
                    }
                }
                fclose($output);
                exit;
            } else {
                header('Content-Type: application/vnd.ms-excel; charset=utf-8');
                header("Content-Disposition: attachment; filename=\"{$filename}.xls\"");
                header('Pragma: no-cache');
                header('Expires: 0');
                echo "\xEF\xBB\xBF";
                echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
                echo '<head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
                echo '<style>
                    body { font-family: Calibri, sans-serif; font-size: 11pt; }
                    table { border-collapse: collapse; width: 100%; }
                    th { background-color: #1E40AF; color: white; padding: 8px 12px; border: 1px solid #CBD5E1; }
                    td { padding: 6px 10px; border: 1px solid #E2E8F0; }
                </style></head><body>';
                echo '<h2 style="color: #1E40AF; margin-bottom: 8px;">DATA ' . strtoupper(htmlspecialchars($type)) . '</h2>';
                echo '<table border="1">';
                if (!empty($rows)) {
                    echo '<thead><tr>';
                    foreach (array_keys($rows[0]) as $h) {
                        echo '<th>' . htmlspecialchars($h) . '</th>';
                    }
                    echo '</tr></thead><tbody>';
                    foreach ($rows as $r) {
                        echo '<tr>';
                        foreach ($r as $val) {
                            echo '<td>' . htmlspecialchars($val ?? '') . '</td>';
                        }
                        echo '</tr>';
                    }
                    echo '</tbody>';
                }
                echo '</table></body></html>';
                exit;
            }
        }

    default:
        echo json_encode(["status" => "error", "message" => "Action tidak valid"]);
        break;
}
