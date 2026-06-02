<?php
header('Content-Type: application/json');
$logFile = __DIR__ . '/indexstats.json';

/* ==========================
   MODE: tampilkan log lama
========================== */
if (isset($_GET['logs'])) {
    if (file_exists($logFile)) {
        echo file_get_contents($logFile);
    } else {
        echo '[]';
    }
    exit;
}

/* ==========================
   MODE: cek index baru
========================== */
$input = json_decode(file_get_contents("php://input"), true);
$domain = trim($input['domain'] ?? '');
$engine = strtolower(trim($input['engine'] ?? 'google'));

if(!$domain){
    echo json_encode(['error'=>'Domain kosong.']); 
    exit;
}

// Normalisasi domain
$domain = preg_replace('#^https?://#','',$domain);
$domain = rtrim($domain, '/');

/* ==========================
   Fungsi ambil HTML pakai cURL
========================== */
function getHTML($url){
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_USERAGENT => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/118.0 Safari/537.36",
        CURLOPT_HTTPHEADER => [
            "Accept-Language: en-US,en;q=0.9,id;q=0.8",
            "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8"
        ]
    ]);
    $html = curl_exec($ch);
    curl_close($ch);
    return $html;
}

/* ==========================
   MODE 1: Coba lewat DuckDuckGo HTML
========================== */
$count = 0;
$links = [];
$fallbackUsed = false;

if($engine === 'google'){
    // Gunakan DuckDuckGo HTML agar tidak diblokir
    $url = "https://html.duckduckgo.com/html/?q=site:" . urlencode($domain);
    $html = getHTML($url);

    if($html){
        // Ambil semua link hasil pencarian
        preg_match_all('/<a rel="nofollow" class="result__a" href="([^"]+)"/i', $html, $matches);
        $links = array_values(array_filter(array_unique($matches[1]), fn($x)=>stripos($x,$domain)!==false));
        $count = count($links);
    }

    // Jika kosong → fallback ke Bing
    if($count === 0){
        $fallbackUsed = true;
        $engine = 'bing';
    }
}

/* ==========================
   MODE 2: Bing (langsung atau fallback)
========================== */
if($engine === 'bing'){
    $url = "https://www.bing.com/search?q=site:" . urlencode($domain);
    $html = getHTML($url);

    if($html){
        if(preg_match('/([\d.,]+)\s*results/i', $html, $m)){
            $count = (int)str_replace(['.',','],'',$m[1]);
        }
        preg_match_all('/<a href="(https?:\/\/[^"]+)" h="ID=SERP,[^>]+>/', $html, $matches);
        $links = array_values(array_filter(array_unique($matches[1]), fn($x)=>stripos($x,$domain)!==false));
        $links = array_slice($links, 0, 10);
        if($count === 0) $count = count($links);
    }
}

/* ==========================
   Simpan ke log JSON
========================== */
$logs = file_exists($logFile) ? json_decode(file_get_contents($logFile), true) : [];
$logs[] = [
    'date' => date('Y-m-d H:i:s'),
    'domain' => $domain,
    'engine' => ucfirst($engine) . ($fallbackUsed ? " (fallback)" : ""),
    'count' => $count,
    'samples' => $links
];
file_put_contents($logFile, json_encode($logs, JSON_PRETTY_PRINT));

/* ==========================
   Kirim hasil ke frontend
========================== */
echo json_encode([
    'engine' => ucfirst($engine) . ($fallbackUsed ? " (fallback)" : ""),
    'domain' => $domain,
    'query_url' => $url,
    'count' => $count,
    'sample_links' => $links
], JSON_PRETTY_PRINT);
