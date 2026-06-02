<?php
// admin/kotakanime.php

function getMetaContent($name, $xpath) {
    $query = "//meta[@property='" . $name . "']";
    $node = $xpath->query($query)->item(0);
    return $node ? $node->getAttribute('content') : '';
}

function getElementByClass($class, $tag, $xpath) {
    $query = '//' . $tag . '[contains(concat(" ", normalize-space(@class), " "), " ' . htmlspecialchars($class) . ' ")]';
    $nodes = $xpath->query($query);
    return $nodes->length > 0 ? $nodes->item(0) : null;
}

function generateFromURL($url) {
    $html = file_get_contents($url);
    if (!$html) return array('error' => 'Gagal ambil konten dari ' . $url);

    libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    $doc->loadHTML($html);
    $xpath = new DOMXPath($doc);

    // --- Judul ---
    $titleNode = getElementByClass('entry-title', 'h1', $xpath);
    $title = trim($titleNode ? $titleNode->nodeValue : '');
    if (!$title) return array('error' => 'Judul tidak ditemukan di ' . $url);

    // --- Slug ---
    $slugRaw = preg_replace('/[^a-z0-9]+/i', '-', $title);
    $slug = strtolower(trim($slugRaw, '-'));
    $urlPath = "/blog/" . $slug . ".html";

    // --- Tanggal Published ---
    $timeNode = $xpath->query("//time[@datetime]")->item(0);
    $published = $timeNode ? substr($timeNode->getAttribute('datetime'), 0, 10) : date('Y-m-d');

    // --- Thumbnail ---
    $thumbnail = getMetaContent('og:image', $xpath);

    // --- Sinopsis ---
    $sinopsisNode = getElementByClass('tagpst', 'div', $xpath);
    $summary = $sinopsisNode ? trim($sinopsisNode->textContent) : getMetaContent('og:description', $xpath);

    // --- Series ---
    $breadcrumbNode = $xpath->query("//nav[contains(@class,'breadcrumbs')]/a[last()-1]")->item(0);
    $series = $breadcrumbNode ? ucwords(trim($breadcrumbNode->nodeValue)) : '';

    // --- Episode Number ---
    preg_match('/Episode\s+(\d+)/i', $title, $m);
    $episode = $m[1] ?? '';

    // --- Genres (sementara default) ---
    $genres = array("Fantasy", "Isekai");

    // --- Iframe Player ---
    $iframeNode = $xpath->query("//iframe[@title='Server Nonton']")->item(0);
    $iframeSrc = $iframeNode ? $iframeNode->getAttribute('data-src') : '';
    $iframe = $iframeSrc ? "<iframe src=\"$iframeSrc\" width=\"100%\" height=\"480\" frameborder=\"0\" allowfullscreen></iframe>" : '';

    // --- Download Links ---
    $downloadDiv = getElementByClass('listlink', 'div', $xpath);
    $download_links = '';
    if ($downloadDiv) {
        $as = $downloadDiv->getElementsByTagName('a');
        $dl = [];
        foreach ($as as $a) {
            $dl[] = '<li><a href="'.$a->getAttribute('href').'" target="_blank">'.htmlspecialchars($a->nodeValue).'</a></li>';
        }
        $download_links = '<ul>'.implode("", $dl).'</ul>';
    }

    // --- Data Final ---
    $data = array(
        'title' => ucwords($title),
        'slug' => $slug,
        'url' => $urlPath,
        'thumbnail' => $thumbnail,
        'published' => $published,
        'summary' => $summary,
        'type' => 'Anime',
        'season' => date('Y', strtotime($published)),
        'studio' => '',
        'status' => 'Ongoing',
        'genres' => $genres,
        'series' => $series,
        'episode' => $episode,
        'iframe' => $iframe,
        'durasi' => '',
        'country' => 'Japan',
        'network' => '',
        'download_links' => $download_links
    );

    return array('success' => true, 'data' => $data, 'slug' => $slug);
}

// --- Handle POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['urls'])) {
    $urls = explode("\n", $_POST['urls']);
    $urls = array_filter(array_map('trim', $urls));

    $postsPath = dirname(__DIR__) . '/data/posts.json';
    $posts = file_exists($postsPath) ? json_decode(file_get_contents($postsPath), true) : array();
    $log = array();

    foreach ($urls as $url) {
        $result = generateFromURL($url);
        if (isset($result['success'])) {
            $found = false;
            foreach ($posts as $key => $post) {
                if (isset($post['slug']) && $post['slug'] === $result['slug']) {
                    $posts[$key] = $result['data'];
                    $found = true;
                    $log[] = '🔁 ' . $result['slug'] . ' (replaced)';
                    break;
                }
            }
            if (!$found) {
                $posts[] = $result['data'];
                $log[] = '✅ ' . $result['slug'];
            }
        } else {
            $log[] = '❌ ' . $result['error'];
        }
    }

    file_put_contents($postsPath, json_encode($posts, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    echo json_encode(array('log' => $log));
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Generator Kotakanime</title>
<link rel="stylesheet" href="../assets/style.css">
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
<script>
function submitForm(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    const output = document.getElementById('log-output');
    output.innerHTML = '⏳ Memproses...';

    fetch(form.action, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        output.innerHTML = data.log.join('\n');
    })
    .catch(error => {
        output.innerHTML = '❌ Terjadi kesalahan saat memproses.';
    });
}
</script>
</head>
<body>
<header>
  <div class="container">
    <h1 class="logo">Kotakanime Generator</h1>
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

<main class="container">
  <h2>🔗 Generator Kotakanime</h2>
  <form method="post" onsubmit="submitForm(event)">
    <label for="urls">Masukkan Daftar URL (satu per baris):</label>
    <textarea id="urls" name="urls" rows="10" required></textarea>
    <input type="submit" value="🚀 Generate dan Simpan Semua">
  </form>
  <div id="log-output"></div>
</main>

<footer>
  <p>&copy; 2025 Kotakanime Generator. All rights reserved.</p>
</footer>
</body>
</html>
