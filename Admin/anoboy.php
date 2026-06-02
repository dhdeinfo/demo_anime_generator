<?php
// Generator Nontonanimeid.my.id
// Ambil data anime: title, published, poster, iframe, summary, genres, info (status, studio, season, type, durasi), series

function slugify($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    return strtolower(trim($text, '-')) ?: 'post-' . time();
}

function fetch_url($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
    ]);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

function clean_text($txt) {
    $txt = strip_tags($txt);
    $txt = html_entity_decode($txt, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $txt = preg_replace('/\s+/', ' ', $txt);
    return trim($txt);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    ob_start();
    $urls = explode("\n", trim($_POST['urls'] ?? ''));
    foreach ($urls as $url) {
        $url = trim($url);
        if ($url === '') continue;

        $html = fetch_url($url);
        if (!$html) {
            echo "<div class='notif notif-error'>❌ Gagal ambil: $url</div>";
            continue;
        }

        // Title
        $title = "Tanpa Judul";
        if (preg_match('/<h1[^>]*class=["\']entry-title["\'][^>]*>(.*?)<\/h1>/is', $html, $m)) {
            $title = clean_text($m[1]);
        }
        $slug = slugify($title);

        // Published
        $published = date("Y-m-d");
        if (preg_match('/<time[^>]*class=["\']entry-date published["\'][^>]*datetime=["\']([^"\']+)["\']/i', $html, $m)) {
            $published = substr($m[1], 0, 10);
        }

        // Thumbnail
        $poster = "https://via.placeholder.com/225x303.png?text=Poster";
        if (preg_match('/<img[^>]+class=["\']wp-post-image["\'][^>]+src=["\']([^"\']+)/i', $html, $m)) {
            $poster = $m[1];
        }

        // Iframe
        $iframe = "";
        if (preg_match('/<div[^>]*class=["\']player-embed["\'][^>]*>.*?<iframe[^>]+src=["\']([^"\']+)["\']/is', $html, $m)) {
            $iframe = '<iframe src="' . htmlspecialchars($m[1]) . '" width="100%" height="480" frameborder="0" allowfullscreen></iframe>';
        }

        // Summary
        $summary = "";
        if (preg_match('/<div[^>]*class=["\']desc mindes["\'][^>]*>(.*?)<\/div>/is', $html, $m)) {
            $summary = clean_text($m[1]);
        }

        // Genres
        $genres = [];
        if (preg_match('/<div[^>]*class=["\']genxed["\'][^>]*>(.*?)<\/div>/is', $html, $m)) {
            preg_match_all('/<a[^>]*>(.*?)<\/a>/i', $m[1], $gm);
            if (!empty($gm[1])) $genres = array_map('trim', $gm[1]);
        }

        // Info (status, studio, season, type, durasi)
        $status = $studio = $season = $type = $durasi = "";
        if (preg_match('/<div[^>]*class=["\']spe["\'][^>]*>(.*?)<\/div>/is', $html, $m)) {
            $spe = $m[1];

            if (preg_match('/<b>\s*Status:\s*<\/b>\s*([^<]+)/i', $spe, $x)) $status = clean_text($x[1]);
            if (preg_match('/<b>\s*Studio:\s*<\/b>\s*(?:<a[^>]*>)?([^<]+)/i', $spe, $x)) $studio = clean_text($x[1]);
            if (preg_match('/<b>\s*Season:\s*<\/b>\s*(?:<a[^>]*>)?([^<]+)/i', $spe, $x)) $season = clean_text($x[1]);
            if (preg_match('/<b>\s*Type:\s*<\/b>\s*([^<]+)/i', $spe, $x)) $type = clean_text($x[1]);
            if (preg_match('/<b>\s*Duration:\s*<\/b>\s*([^<]+)/i', $spe, $x)) $durasi = clean_text($x[1]);
        }

        // Series
        $series = "";
        if (preg_match('/series\s*<a[^>]*>(.*?)<\/a>/i', $html, $m)) {
            $series = clean_text($m[1]);
        }

        // Download links (opsional: nontonanimeid biasanya tidak ada format soraurlx)
        $download_links = "<li>Tidak tersedia</li>";

        // Simpan file HTML placeholder
        if (!is_dir("../blog")) mkdir("../blog", 0777, true);
        file_put_contents("../blog/$slug.html", "<!-- content generated -->");

        // Notifikasi
        echo "<div class='notif notif-success'>✅ Berhasil dibuat: <a href='../blog/$slug.html' target='_blank'>$slug.html</a></div>";

        // Update data.json
        $jsonFile = __DIR__ . '/../data.json';
        $posts = file_exists($jsonFile) ? json_decode(file_get_contents($jsonFile), true) : [];
        $posts = array_filter($posts, fn($p) => ($p['slug'] ?? '') !== $slug);

        $newPost = [
            "title" => $title,
            "slug" => $slug,
            "url" => "/blog/$slug.html",
            "thumbnail" => $poster,
            "published" => $published,
            "summary" => $summary,
            "type" => $type,
            "season" => $season,
            "studio" => $studio,
            "status" => $status,
            "genres" => $genres,
            "series" => $series,
            "iframe" => $iframe,
            "durasi" => $durasi,
            "download_links" => $download_links
        ];

        array_unshift($posts, $newPost);
        file_put_contents($jsonFile, json_encode(array_values($posts), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo "<div class='notif notif-info'>📝 data.json diperbarui</div>";
    }
    $notif = ob_get_clean();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <title>Generator Nontonanimeid.my.id</title>
  <style>
    body {background:#0f0f10;font-family:'Segoe UI',Arial,sans-serif;color:#eee;}
    main.container {max-width:900px;margin:40px auto;background:#161616;padding:30px;border-radius:10px;}
    .notif {padding:12px;margin-top:20px;border-radius:6px;font-weight:bold;text-align:center;}
    .notif-success {background:#2ecc71;color:#fff;}
    .notif-info {background:#3498db;color:#fff;}
    .notif-error {background:#e74c3c;color:#fff;}
  </style>
</head>
<body>
  <main class="container">
    <h2>🔗 Generator Nontonanimeid.my.id</h2>
    <form method="post">
      <label>Masukkan Banyak Link (satu per baris):</label><br>
      <textarea name="urls" rows="10" style="width:100%;padding:12px;border-radius:6px;background:#1f1f1f;color:#eee;"></textarea><br>
      <button type="submit" style="padding:12px;background:#ff6b6b;border:none;border-radius:6px;color:#fff;">🚀 Generate Semua</button>
    </form>
    <div><?= $notif ?? '' ?></div>
  </main>
</body>
</html>
