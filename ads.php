<?php
// ads.php - Halaman Dashboard Ads Setting
$adsFile = "ads.json";
$ads = ["header"=>"", "content"=>"", "player"=>"", "footer"=>"", "sticky"=>""];

// load ads.json jika ada
if (file_exists($adsFile)) {
    $loaded = json_decode(file_get_contents($adsFile), true);
    if (is_array($loaded)) $ads = array_merge($ads, $loaded);
}

// simpan update
$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ads['header']  = $_POST['header'] ?? "";
    $ads['content'] = $_POST['content'] ?? "";
    $ads['player']  = $_POST['player'] ?? "";
    $ads['footer']  = $_POST['footer'] ?? "";
    $ads['sticky']  = $_POST['sticky'] ?? "";

    file_put_contents($adsFile, json_encode($ads, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
    $msg = "✅ Iklan berhasil disimpan!";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Ads Setting</title>
  <style>
    body {background:#0f0f10;color:#eee;font-family:'Segoe UI',Arial,sans-serif;margin:0;padding:0;}
    header {background:#161616;padding:12px 0;box-shadow:0 2px 6px rgba(0,0,0,.5);}
    header .container {max-width:1000px;margin:auto;display:flex;align-items:center;justify-content:space-between;padding:0 20px;}
    header .logo {font-size:20px;font-weight:bold;color:#2ecc71;}
    nav a {margin-left:15px;color:#eee;text-decoration:none;font-size:14px;transition:.3s;}
    nav a:hover {color:#ff6b6b;}
    .wrap {max-width:1000px;margin:30px auto;background:#161616;padding:25px;border-radius:10px;box-shadow:0 4px 12px rgba(0,0,0,.6);}
    h2 {text-align:center;color:#2ecc71;margin-bottom:20px;}
    form {display:flex;flex-direction:column;gap:22px;}
    label {font-weight:bold;margin-bottom:5px;color:#ff6b6b;}
    textarea {
      width:100%;min-height:100px;background:#1f1f1f;border:1px solid #333;color:#eee;
      border-radius:6px;padding:10px;font-family:monospace;font-size:13px;resize:vertical;
    }
    button {
      padding:12px;background:#2ecc71;border:none;border-radius:6px;
      color:#fff;font-size:16px;font-weight:bold;cursor:pointer;transition:.3s;
    }
    button:hover {background:#27ae60;}
    .msg {padding:12px;margin-bottom:15px;text-align:center;border-radius:6px;font-weight:bold;}
    .success {background:#2ecc71;color:#fff;}
    .preview {
      margin-top:10px;padding:12px;background:#222;border:1px dashed #444;border-radius:6px;
    }
    .preview h4 {margin:0 0 8px;color:#ccc;font-size:14px;}
    iframe {border:none;}
    @media(max-width:600px){
      header .container {flex-direction:column;align-items:flex-start;}
      nav {margin-top:10px;}
      nav a {margin:0 10px 0 0;}
    }
  </style>
</head>
<body>
<header>
  <div class="container">
    <div class="logo">⚙️ Ads Dashboard</div>
    <nav>
      <a href="dashboard.php">Dashboard</a>
      <a href="ads.php">Ads Setting</a>
      <a href="..">Home</a>
    </nav>
  </div>
</header>

<div class="wrap">
  <h2>⚙️ Ads Slot Setting + Preview</h2>

  <?php if($msg): ?>
    <div class="msg success"><?= $msg ?></div>
  <?php endif; ?>

  <form method="post">
    <div>
      <label>Header Slot (atas halaman):</label>
      <textarea name="header" oninput="updatePreview('header')"><?= htmlspecialchars($ads['header']) ?></textarea>
      <div class="preview"><h4>Preview Header</h4><div id="preview-header"><?= $ads['header'] ?></div></div>
    </div>

    <div>
      <label>Content Slot (tengah halaman / list):</label>
      <textarea name="content" oninput="updatePreview('content')"><?= htmlspecialchars($ads['content']) ?></textarea>
      <div class="preview"><h4>Preview Content</h4><div id="preview-content"><?= $ads['content'] ?></div></div>
    </div>

    <div>
      <label>Player Slot (atas sebelum video player):</label>
      <textarea name="player" oninput="updatePreview('player')"><?= htmlspecialchars($ads['player']) ?></textarea>
      <div class="preview"><h4>Preview Player</h4><div id="preview-player"><?= $ads['player'] ?></div></div>
    </div>

    <div>
      <label>Footer Slot (bawah halaman):</label>
      <textarea name="footer" oninput="updatePreview('footer')"><?= htmlspecialchars($ads['footer']) ?></textarea>
      <div class="preview"><h4>Preview Footer</h4><div id="preview-footer"><?= $ads['footer'] ?></div></div>
    </div>

    <div>
      <label>Sticky Slot (mobile bawah layar):</label>
      <textarea name="sticky" oninput="updatePreview('sticky')"><?= htmlspecialchars($ads['sticky']) ?></textarea>
      <div class="preview"><h4>Preview Sticky</h4><div id="preview-sticky"><?= $ads['sticky'] ?></div></div>
      <small style="color:#bbb;">Iklan ini hanya tampil di mobile (fixed di bawah layar).</small>
    </div>

    <button type="submit">💾 Simpan Iklan</button>
  </form>
</div>

<script>
function updatePreview(slot){
  const textarea = document.querySelector("textarea[name='"+slot+"']");
  document.getElementById("preview-"+slot).innerHTML = textarea.value;
}
</script>
</body>
</html>
