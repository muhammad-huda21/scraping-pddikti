<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 15; // Jumlah data per halaman API

// URL Endpoint API PDDikti yang aktif
$targetUrl = "https://pddikti.kemdiktisaintek.go.id/api/v2/pt/search/filter?limit={$limit}&page={$page}";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $targetUrl);
// Ubah ke GET request
curl_setopt($ch, CURLOPT_HTTPGET, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

// Bypass SSL Verification
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

// Header HTTP yang lengkap agar cURL diterima sebagai browser asli
$headers = [
    'Content-Type: application/json',
    'Accept: application/json, text/plain, */*',
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    'Origin: https://pddikti.kemdiktisaintek.go.id',
    'Referer: https://pddikti.kemdiktisaintek.go.id/perguruan-tinggi'
];
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($httpCode === 200 && $response) {
    echo $response;
} else {
    // Jika endpoint /api/pt/search mengembalikan 404, coba endpoint alternatif /api/perguruan-tinggi/search
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Gagal mengambil data (HTTP $httpCode). Error: " . $curlError
    ]);
}
?>