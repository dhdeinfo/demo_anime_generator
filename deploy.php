<?php
// deploy.php - Deploy ke Netlify via CLI (dengan log file + navigasi + popup notifikasi)

$configFile = __DIR__ . "/deploy_config.json";
$logFile    = __DIR__ . "/deploy_log.txt";

// ====== Simpan konfigurasi ======
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['token'])) {
    $token = trim($_POST['token'] ?? '');
    $siteId = trim($_POST['siteId'] ?? '');

    if (!$token || !$siteId) {
        die("<p style='color:red;'>❌ Token dan Site ID wajib diisi.</p>");
    }

    $config = ['token' => $token, 'siteId' => $siteId];
    file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));
    echo "<p style='color:green;'>✅ Konfigurasi tersimpan.</p>";
    exit;
}

// ====== Jalankan Deploy ======
if (isset($_GET['run'])) {
    if (!file_exists($configFile)) {
        die("❌ Konfigurasi belum ada.");
    }
    $config = json_decode(file_get_contents($configFile), true);

    $token = $config['token'];
    $siteId = $config['siteId'];

    // Kosongkan log file
    file_put_contents($logFile, "⏳ Deploy dimulai...\n");

    // Jalankan deploy di background
    if (stripos(PHP_OS, 'WIN') === 0) {
        // Windows
        $cmd = "set NETLIFY_AUTH_TOKEN=$token && netlify deploy --prod --dir=output --site=$siteId >> \"$logFile\" 2>&1";
        pclose(popen("start /B cmd /C \"$cmd\"", "r"));
    } else {
        // Linux / Mac
        $cmd = "NETLIFY_AUTH_TOKEN=$token netlify deploy --prod --dir=output --site=$siteId >> \"$logFile\" 2>&1 &";
        exec($cmd);
    }
    echo "OK";
    exit;
}

// ====== Ambil Log ======
if (isset($_GET['log'])) {
    if (file_exists($logFile)) {
        header("Content-Type: text/plain");
        readfile($logFile);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>🚀 Deploy ke Netlify</title>
<style>
body {background:#0f0f10;color:#eee;font-family:Segoe UI,Arial;padding:20px;}
.wrap {max-width:700px;margin:auto;background:#161616;padding:20px;border-radius:8px;}
h2 {color:#ff6b6b;}
label {display:block;margin:10px 0 4px;}
input {width:100%;padding:10px;border-radius:6px;border:1px solid #333;background:#1f1f1f;color:#eee;}
button {margin-top:15px;padding:12px;background:#2ecc71;border:none;border-radius:6px;color:#fff;font-size:16px;cursor:pointer;}
button:hover {background:#27ae60;}
pre {background:#000;padding:15px;border-radius:6px;max-height:400px;overflow:auto;color:#0f0;}
.links {margin-bottom:20px;text-align:center;}
.links a {display:inline-block;margin:5px;padding:8px 14px;background:#2c3e50;color:#fff;text-decoration:none;border-radius:6px;font-size:14px;}
.links a:hover {background:#34495e;}
</style>
</head>
<body>
<div class="links">
  <a href="index.php">➕ Tambah Postingan</a>
  <a href="dashboard.php">📋 Dashboard</a>
  <a href="ads.php">📢 Ads Manager</a>
  <a href="deploy.php">🚀 Deploy</a>
</div>

<div class="wrap">
  <h2>⚡ Deploy ke Netlify</h2>
  <form method="post" onsubmit="saveConfig(event)">
    <label>Netlify Auth Token:</label>
    <input type="text" name="token" required>

    <label>Site ID:</label>
    <input type="text" name="siteId" required>

    <button type="submit">💾 Simpan Konfigurasi</button>
  </form>

  <hr>
  <button onclick="runDeploy()">🚀 Jalankan Deploy</button>
  <pre id="logBox">Log akan tampil di sini...</pre>
</div>

<script>
function saveConfig(e){
  e.preventDefault();
  let form=new FormData(e.target);
  fetch("deploy.php",{method:"POST",body:form})
    .then(r=>r.text()).then(t=>alert(t));
}

function runDeploy(){
  const logBox=document.getElementById("logBox");
  logBox.textContent="⏳ Deploy dimulai...\n";
  fetch("deploy.php?run=1").then(()=> {
    // Poll log setiap 1 detik
    let timer=setInterval(()=>{
      fetch("deploy.php?log=1")
      .then(r=>r.text())
      .then(t=>{
        logBox.textContent=t;
        logBox.scrollTop=logBox.scrollHeight;
        if(t.includes("Deploy is live!")){
          clearInterval(timer);
          alert("✅ Deploy berhasil! Situs sudah live.");
        }
        if(t.toLowerCase().includes("error")){
          clearInterval(timer);
          alert("❌ Deploy gagal! Periksa log untuk detail.");
        }
      });
    },1000);
  });
}
</script>
</body>
</html>
