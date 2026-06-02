<?php
// --- Helper Functions ---
function slugify($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    return strtolower(trim($text, '-')) ?: 'post-' . time();
}

function fetch_url($url) {
    if (ini_get('allow_url_fopen')) {
        $context = stream_context_create([
            'http' => ['header' => "User-Agent: Mozilla/5.0\r\n"]
        ]);
        $data = @file_get_contents($url, false, $context);
        if ($data !== false) return $data;
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT => "Mozilla/5.0"
    ]);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data ?: false;
}

function extract_info($html, $label) {
    $pattern = '/<span><b>' . preg_quote($label, '/') . ':<\/b>\s*(.*?)<\/span>/i';
    preg_match($pattern, $html, $matches);
    return trim(strip_tags($matches[1] ?? ''));
}

function extract_between($html, $start, $end) {
    $s = strpos($html, $start);
    if ($s === false) return '';
    $s += strlen($start);
    $e = strpos($html, $end, $s);
    if ($e === false) return '';
    return trim(strip_tags(substr($html, $s, $e - $s)));
}

function extract_download_links($html) {
    $links = '';
    preg_match_all('/<div class="soraurlx">(.*?)<\/div>/is', $html, $blocks);
    foreach ($blocks[1] as $block) {
        preg_match('/<strong>(.*?)<\/strong>/i', $block, $resMatch);
        $res = $resMatch[1] ?? 'Unknown';
        preg_match_all('/<a[^>]+href="([^"]+)"[^>]*>(.*?)<\/a>/is', $block, $aTags);
        $linkItems = [];
        for ($i = 0; $i < count($aTags[0]); $i++) {
            $url = htmlspecialchars($aTags[1][$i]);
            $text = strip_tags($aTags[2][$i]);
            $linkItems[] = "<a href=\"$url\" target=\"_blank\">$text</a>";
        }
        $joinedLinks = implode(' | ', $linkItems);
        $links .= "<li><strong>$res:</strong> $joinedLinks</li>\n";
    }
    return $links ?: '<li>Tidak tersedia</li>';
}

// --- MAIN PROCESS ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    ob_implicit_flush(true);
    ob_end_flush();
    echo "<script>document.getElementById('log-box').innerHTML='';</script>";
    echo "<div class='notif notif-info'>⏳ Mulai proses scraping...</div>";
    echo "<div id='log-box' class='log-box'>";

    $urls = explode("\n", trim($_POST['urls'] ?? ''));
    $total = count($urls);
    $count = 0;

    foreach ($urls as $url) {
        $url = trim($url);
        if (empty($url)) continue;
        $count++;

        echo "<div class='notif notif-info'>[$count/$total] 🔗 Proses: $url</div>";
        flush();

        $html = fetch_url($url);
        if (!$html) {
            echo "<div class='notif notif-error'>❌ Gagal ambil: $url</div>";
            flush();
            continue;
        }

        // Title
        preg_match('/<h1[^>]*>(.*?)<\/h1>/i', $html, $m);
        $title = trim(strip_tags($m[1] ?? ''));
        if (!$title) {
            preg_match('/<title>(.*?)<\/title>/i', $html, $mt);
            $title = str_replace('- Nontonanimeindo.web.id', '', $mt[1] ?? 'Tanpa Judul');
        }
        $slug = slugify($title);

        // Release
        preg_match('/<span[^>]*class=["\']updated["\'][^>]*>([^<]+)<\/span>/i', $html, $releaseMatch);
        $dateString = $releaseMatch[1] ?? '';
        $release = $dateString ? date('Y-m-d', strtotime($dateString)) : date('Y-m-d');

        // Poster
        preg_match('/<div class="thumb">.*?<img[^>]+src="([^"]+)/is', $html, $mPoster);
        $poster = $mPoster[1] ?? 'https://via.placeholder.com/150x220.png?text=Poster';

        // Iframe (dibungkus element <iframe>)
        preg_match('/<iframe[^>]+src="([^"]+)"/i', $html, $mIframe);
        $iframeSrc = $mIframe[1] ?? '';
        $iframe = $iframeSrc
            ? '<iframe src="' . htmlspecialchars($iframeSrc) . '" width="100%" height="480" frameborder="0" allowfullscreen></iframe>'
            : '';

        // Sinopsis
        $sinopsis = extract_between($html, '<strong>Sinopsis:</strong>', '</p>');

        // Series
        preg_match('/<h2[^>]*itemprop="partOfSeries"[^>]*>(.*?)<\/h2>/i', $html, $mSeries);
        $series = trim(strip_tags($mSeries[1] ?? ''));

        // Genres
        preg_match('/<div class="genxed">(.*?)<\/div>/is', $html, $matches);
        $genres = [];
        if ($matches) {
            preg_match_all('/<a[^>]+>(.*?)<\/a>/', $matches[1], $gm);
            $genres = $gm[1] ?? [];
        }

        // Info
        $status   = extract_info($html, 'Status');
        $studio   = extract_info($html, 'Studio');
        $durasi   = extract_info($html, 'Duration');
        $country  = extract_info($html, 'Country');
        $network  = extract_info($html, 'Network');
        $released = extract_info($html, 'Released');
        $season   = extract_info($html, 'Season');
        $type     = extract_info($html, 'Type');
        $download_links = extract_download_links($html);

        // Save file placeholder
        if (!is_dir("../blog")) mkdir("../blog", 0777, true);
        file_put_contents("../blog/$slug.html", "<!-- content generated -->");

        // Update JSON
        $jsonFile = __DIR__ . '/../data.json';
        $posts = file_exists($jsonFile) ? json_decode(file_get_contents($jsonFile), true) : [];
        $newPost = [
            'title' => $title,
            'slug' => $slug,
            'url'  => "/blog/$slug.html",
            'thumbnail' => $poster,
            'published' => $release,
            'summary'   => $sinopsis,
            'type'      => $type,
            'season'    => $season,
            'studio'    => $studio,
            'status'    => $status,
            'genres'    => $genres,
            'series'    => $series,
            'iframe'    => $iframe,
            'durasi'    => $durasi,
            'country'   => $country,
            'network'   => $network,
            'download_links' => $download_links
        ];
        $posts = array_filter($posts, fn($p) => $p['slug'] !== $slug);
        array_unshift($posts, $newPost);
        file_put_contents($jsonFile, json_encode(array_values($posts), JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));

        echo "<div class='notif notif-success'>✅ Selesai: $title</div>";
        flush();
    }
    echo "</div>";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Generator Nontonanimeindo.web.id</title>
  <style>
    body {background:#0f0f10;font-family:'Segoe UI',Arial,sans-serif;color:#eee;margin:0;padding:0;}
    header {background:#161616;padding:15px 0;box-shadow:0 2px 8px rgba(0,0,0,.5);}
    header .container {max-width:1000px;margin:auto;display:flex;align-items:center;justify-content:space-between;padding:0 20px;}
    header .logo {font-size:22px;font-weight:bold;color:#ff6b6b;margin:0;}
    header nav a {margin-left:15px;color:#eee;text-decoration:none;font-size:14px;transition:.3s;}
    header nav a:hover {color:#ff6b6b;}
    main.container {max-width:900px;margin:40px auto;background:#161616;padding:30px;border-radius:10px;box-shadow:0 4px 12px rgba(0,0,0,.6);}
    main h2 {text-align:center;margin-bottom:20px;color:#ff6b6b;}
    form {display:flex;flex-direction:column;gap:16px;}
    textarea {width:100%;padding:12px;border-radius:6px;border:1px solid #333;background:#1f1f1f;color:#eee;}
    button {padding:12px;background:#ff6b6b;border:none;border-radius:6px;color:#fff;font-size:16px;font-weight:bold;cursor:pointer;}
    .notif {padding:8px;margin-top:8px;border-radius:6px;font-weight:bold;font-size:14px;}
    .notif-success {background:#2ecc71;color:#fff;}
    .notif-info {background:#3498db;color:#fff;}
    .notif-error {background:#e74c3c;color:#fff;}
    .log-box {background:#1f1f1f;border:1px solid #333;border-radius:6px;padding:12px;margin-top:20px;max-height:400px;overflow:auto;}
  </style>
  <script>
    // auto-scroll log
    const observer = new MutationObserver(() => {
      const logBox = document.getElementById('log-box');
      if (logBox) logBox.scrollTop = logBox.scrollHeight;
    });
    window.addEventListener('DOMContentLoaded', () => {
      const logBox = document.getElementById('log-box');
      if (logBox) observer.observe(logBox, { childList: true });
    });
  </script>
</head>
<body>
  <header>
    <div class="container">
      <h1 class="logo">Nontonanimeindo.web.id</h1>
      <nav>
        <a href="/streaming_generator_demo/">Home</a>
        <a href="nontonanimeid.php">nontonanimeid</a>
        <a href="layaranime.php">layaranime</a>
        <a href="Nontonanimeidmyid.php">Nontonanimeid.my.id</a>
        <a href="/sitemap.php">Completed</a>
        <a href="#">Bookmark</a>
        <a href="#">Schedule</a>
      </nav>
    </div>
  </header>

  <main class="container">
    <h2>🔗 Generator Anichy</h2>
    <form method="post">
      <label>Masukkan Banyak Link (satu per baris):</label>
      <textarea name="urls" rows="10" required></textarea>
      <button type="submit">🚀 Generate Semua</button>
    </form>
    <div id="log-box" class="log-box"></div>
  </main>
</body>
</html>
