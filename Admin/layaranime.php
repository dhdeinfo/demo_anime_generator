<?php
// admin/layaranime.php
libxml_use_internal_errors(true);

function toSlug($text) {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

function getText($xpath, $query) {
    $nodeList = $xpath->query($query);
    return ($nodeList && $nodeList->length > 0) ? trim($nodeList->item(0)->nodeValue) : '';
}

function getAttr($xpath, $query, $attr) {
    $nodeList = $xpath->query($query);
    return ($nodeList && $nodeList->length > 0) ? $nodeList->item(0)->getAttribute($attr) : '';
}

function generateDownloadHtml($downloads) {
    if (empty($downloads)) return '';
    $html = '<li><strong>Links:</strong> ';
    $links = [];
    foreach ($downloads as $d) {
        if (!empty($d['label']) && !empty($d['url'])) {
            $links[] = '<a href="' . $d['url'] . '" target="_blank">' . $d['label'] . '</a>';
        }
    }
    $html .= implode(' | ', $links) . '</li>';
    return $html;
}

function scrapeAndBuild($url) {
    $html = @file_get_contents($url);
    if (!$html) return ['error' => "❌ Gagal mengakses $url"];

    $doc = new DOMDocument();
    @$doc->loadHTML($html);
    $xpath = new DOMXPath($doc);

    $title = getText($xpath, '//h1[contains(@class, "font-bold")]');
    if (!$title) return ['error' => "❌ Judul tidak ditemukan di $url"];

    $slug = toSlug($title);
    $thumbnail = getAttr($xpath, '//meta[@property="og:image"]', 'content');
    $published = date('Y-m-d');
    $summary = getAttr($xpath, '//meta[@property="og:description"]', 'content');

    // iframe ambil dari script JSON
    $iframe = '';
    $htmlClean = preg_replace('/\s+/', ' ', $html);
    if (preg_match('/playerPage\((.*?)\)/', $htmlClean, $m)) {
        $json = html_entity_decode($m[1]);
        $decoded = json_decode($json, true);
        if (isset($decoded['S1_DIRECT'][0])) {
            $iframeUrl = $decoded['S1_DIRECT'][0];
            $iframe = '<iframe src="' . $iframeUrl . '" width="100%" height="480" frameborder="0" allowfullscreen></iframe>';
        }
    }

    // Download links
    $downloads = [];
    $downloadButtons = $xpath->query('//div[contains(@class, "bg-white") and contains(.,"LINK DOWNLOAD")]//a');
    foreach ($downloadButtons as $btn) {
        $label = trim($btn->nodeValue);
        $href = $btn->getAttribute('href');
        if ($href) $downloads[] = ['label' => $label, 'url' => $href];
    }
    $download_links_html = generateDownloadHtml($downloads);

    return [
        'slug' => $slug,
        'data' => [
            'title' => $title,
            'slug' => $slug,
            'url' => parse_url($url, PHP_URL_PATH),
            'thumbnail' => $thumbnail,
            'published' => $published,
            'summary' => $summary,
            'type' => 'Anime',
            'season' => date('Y'),
            'studio' => '',
            'status' => 'Ongoing',
            'genres' => [],
            'series' => 'ANIME',
            'iframe' => $iframe,
            'durasi' => '',
            'country' => 'Japan',
            'network' => '',
            'download_links' => $download_links_html
        ]
    ];
}

// === MODE SSE ===
if (isset($_GET['stream'])) {
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');

    // Ambil daftar URL dari query string
    $urls = isset($_GET['urls']) ? explode(",", $_GET['urls']) : [];
    $total = count($urls);
    $done = 0;

    $outputFile = __DIR__ . '/../data.json';
    if (!is_dir(dirname($outputFile))) mkdir(dirname($outputFile), 0777, true);
    $existing = file_exists($outputFile) ? json_decode(file_get_contents($outputFile), true) : [];
    if (!is_array($existing)) $existing = [];

    foreach ($urls as $url) {
        $url = urldecode(trim($url));
        if (!$url) continue;

        echo "data: ⏳ Memproses $url...\n\n";
        ob_flush(); flush();

        $result = scrapeAndBuild($url);
        if (isset($result['error'])) {
            echo "data: ❌ {$result['error']}\n\n";
            ob_flush(); flush();
            continue;
        }

        $slug = $result['slug'];
        $data = $result['data'];

        $found = false;
        foreach ($existing as $i => $item) {
            if (isset($item['slug']) && $item['slug'] === $slug) {
                $existing[$i] = $data;
                echo "data: 🔁 Diperbarui: $slug\n\n";
                $found = true;
                break;
            }
        }

        if (!$found) {
            $existing[] = $data;
            echo "data: ✅ Ditambahkan: $slug\n\n";
        }

        file_put_contents($outputFile, json_encode($existing, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // Update progress
        $done++;
        $percent = round(($done / $total) * 100);
        echo "event: progress\ndata: $percent\n\n";
        ob_flush(); flush();
    }

    echo "data: 🎉 Semua proses selesai!\n\n";
    echo "event: progress\ndata: 100\n\n";
    ob_flush(); flush();
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Layaranime Generator (Realtime)</title>
  <link rel="stylesheet" href="../assets/style.css">
<style>
body {background:#0f0f10;color:#eee;font-family:'Segoe UI',Arial,sans-serif;}
    main {max-width:900px;margin:40px auto;background:#161616;padding:30px;border-radius:10px;}
    textarea {width:100%;height:180px;padding:12px;border-radius:6px;background:#1f1f1f;color:#eee;border:1px solid #333;}
    button {margin-top:10px;padding:12px;background:#ff6b6b;border:none;border-radius:6px;color:#fff;font-weight:bold;cursor:pointer;}
    button:hover {background:#ff4040;}
    #log {margin-top:20px;background:#000;padding:15px;height:300px;overflow:auto;font-family:monospace;white-space:pre-line;}
    .progress-wrap {margin-top:20px;background:#333;border-radius:6px;overflow:hidden;}
    .progress-bar {height:20px;background:#2ecc71;width:0%;transition:width .3s;}

    body {background:#0f0f10;font-family:'Segoe UI',Arial,sans-serif;color:#eee;margin:0;padding:0;}
    header {background:#161616;padding:15px 0;box-shadow:0 2px 8px rgba(0,0,0,.5);}
    header .container {max-width:1000px;margin:auto;display:flex;align-items:center;justify-content:space-between;padding:0 20px;}
    header .logo {font-size:22px;font-weight:bold;color:#ff6b6b;margin:0;}
    header nav a {margin-left:15px;color:#eee;text-decoration:none;font-size:14px;transition:.3s;}
    header nav a:hover {color:#ff6b6b;}
    main.container {max-width:900px;margin:40px auto;background:#161616;padding:30px;border-radius:10px;box-shadow:0 4px 12px rgba(0,0,0,.6);}
    main h2 {text-align:center;margin-bottom:20px;color:#ff6b6b;}
    form {display:flex;flex-direction:column;gap:16px;}
    form label {font-weight:bold;margin-bottom:4px;display:block;}
    textarea {width:100%;padding:12px;border-radius:6px;border:1px solid #333;background:#1f1f1f;color:#eee;font-size:14px;resize:vertical;}
    input[type="submit"], button {
      padding:12px;background:#ff6b6b;border:none;border-radius:6px;
      color:#fff;font-size:16px;font-weight:bold;cursor:pointer;transition:.3s;align-self:flex-start;
    }
    input[type="submit"]:hover, button:hover {background:#ff4040;}
    #log-output {margin-top:20px;color:#2ecc71;font-family:monospace;white-space:pre-line;}
    footer {margin-top:40px;background:#161616;padding:15px 0;text-align:center;color:#888;font-size:13px;}
    @media(max-width:600px){
      header .container {flex-direction:column;align-items:flex-start;}
      header nav {margin-top:10px;}
      header nav a {margin:0 10px 0 0;}
      main.container {padding:20px;}
      input[type="submit"] {width:100%;text-align:center;}
    }
</style>
</head>
<body>
<header>
  <div class="container">
    <h1 class="logo">Layaranime Generator</h1>
    <nav>
      <a href="/streaming_generator_demo/">Home</a>
      <a href="kotakanime.php">Kotakanime</a>
      <a href="nontonanimeid.php">Nontonanimeid</a>
      <a href="layaranime.php">Layaranime</a>
      <a href="/sitemap.php">Completed</a>
      <a href="#">Bookmark</a>
      <a href="#">Schedule</a>
    </nav>
  </div>
</header>
<main>
  <h2>🚀 Layaranime Generator (Realtime Log)</h2>
  <form id="genForm">
    <textarea name="urls" placeholder="https://layaranime.net/one-piece-episode-1
https://layaranime.net/one-piece-episode-2"></textarea><br>
    <button type="submit">Jalankan</button>
  </form>

  <div class="progress-wrap"><div id="progressBar" class="progress-bar"></div></div>
  <div id="log"></div>
</main>

<script>
document.getElementById('genForm').addEventListener('submit', function(e){
  e.preventDefault();
  const urls = document.querySelector("textarea[name='urls']").value
                .trim().split("\n").map(u => encodeURIComponent(u)).join(",");
  const logBox = document.getElementById('log');
  const progressBar = document.getElementById('progressBar');
  logBox.innerHTML = '';
  progressBar.style.width = "0%";

  // Buka SSE stream
  const evtSource = new EventSource('layaranime.php?stream=1&urls=' + urls);

  evtSource.onmessage = function(e){
    logBox.innerHTML += e.data + "\n";
    logBox.scrollTop = logBox.scrollHeight;
    if (e.data.includes("🎉")) {
      evtSource.close();
    }
  };

  evtSource.addEventListener("progress", function(e){
    progressBar.style.width = e.data + "%";
  });
});
</script>
</body>
</html>
