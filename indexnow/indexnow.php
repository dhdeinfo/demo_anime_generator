<?php
header('Content-Type: application/json');

$configFile = __DIR__ . '/config.json';
$logFile = __DIR__ . '/logs.json';

// ===== tampilkan config =====
if (isset($_GET['config'])) {
    if (file_exists($configFile)) {
        echo file_get_contents($configFile);
    } else {
        echo json_encode(['host'=>'','key'=>'']);
    }
    exit;
}

// ===== simpan config =====
if (isset($_GET['saveConfig'])) {
    $input = json_decode(file_get_contents("php://input"), true);
    file_put_contents($configFile, json_encode([
        'host' => trim($input['host'] ?? ''),
        'key'  => trim($input['key'] ?? '')
    ], JSON_PRETTY_PRINT));
    echo json_encode(['message'=>'Config berhasil disimpan.']);
    exit;
}

// ===== tampilkan logs =====
if (isset($_GET['logs'])) {
    if (file_exists($logFile)) {
        echo file_get_contents($logFile);
    } else {
        echo '[]';
    }
    exit;
}

// ===== ambil sitemap =====
if (isset($_GET['getsitemap'])) {
    $input = json_decode(file_get_contents("php://input"), true);
    $sitemap = trim($input['sitemap'] ?? '');

    if(!$sitemap){
        echo json_encode(['error'=>'URL sitemap kosong.']);
        exit;
    }

    if(str_starts_with($sitemap, 'http')){
        $xmlContent = @file_get_contents($sitemap);
    } else {
        $path = __DIR__ . '/' . ltrim($sitemap, '/');
        $xmlContent = @file_get_contents($path);
    }

    if(!$xmlContent){
        echo json_encode(['error'=>'Gagal membaca sitemap. Pastikan URL benar atau file tersedia.']);
        exit;
    }

    libxml_use_internal_errors(true);
    $xml = simplexml_load_string($xmlContent);
    if(!$xml){
        echo json_encode(['error'=>'Format sitemap tidak valid.']);
        exit;
    }

    $urls = [];
    foreach($xml->url as $u){
        $urls[] = trim((string)$u->loc);
    }

    echo json_encode(['urls'=>$urls, 'count'=>count($urls)]);
    exit;
}

// ===== kirim ke IndexNow =====
$input = json_decode(file_get_contents("php://input"), true);
$host = trim($input['host'] ?? '');
$key = trim($input['key'] ?? '');
$urls = $input['urls'] ?? [];

if(!$host || !$key || !is_array($urls) || count($urls)==0){
    echo json_encode(['error'=>'Data tidak lengkap.']); exit;
}

$apiUrl = "https://api.indexnow.org/indexnow";
$payload = json_encode([
    'host' => parse_url($host, PHP_URL_HOST),
    'key' => $key,
    'urlList' => $urls
]);

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// ===== simpan log =====
$logs = file_exists($logFile) ? json_decode(file_get_contents($logFile), true) : [];
$logs[] = [
    'date' => date('Y-m-d H:i:s'),
    'host' => $host,
    'urls' => $urls,
    'response_code' => $httpCode
];
file_put_contents($logFile, json_encode($logs, JSON_PRETTY_PRINT));

echo json_encode([
    'sent_to' => $apiUrl,
    'payload' => json_decode($payload, true),
    'response_code' => $httpCode,
    'response_body' => json_decode($response, true)
], JSON_PRETTY_PRINT);
