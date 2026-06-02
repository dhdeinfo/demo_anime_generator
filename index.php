<?php
// streaming-generator/index.php
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Generator Streaming - Localhost</title>
  <link rel="stylesheet" href="assets/style.css">
  <style>
    body {background:#0f0f10;font-family:'Segoe UI',Arial,sans-serif;color:#eee;margin:0;padding:0;}
    .wrap {max-width:800px;margin:40px auto;background:#161616;padding:30px;border-radius:10px;box-shadow:0 4px 12px rgba(0,0,0,.6);}
    h2 {margin-bottom:20px;color:#ff6b6b;text-align:center;}
    form {display:flex;flex-direction:column;gap:16px;}
    label {font-weight:bold;margin-bottom:4px;display:block;}
    input[type="text"], input[type="file"], textarea, select {
      width:100%;padding:10px;border-radius:6px;border:1px solid #333;
      background:#1f1f1f;color:#eee;font-size:14px;
    }
    input[type="file"] {background:#222;color:#ccc;}
    textarea {resize:vertical;}
    button {
      padding:12px;background:#ff6b6b;border:none;border-radius:6px;
      color:#fff;font-size:16px;font-weight:bold;cursor:pointer;
      transition:.3s;
    }
    button:hover {background:#ff4040;}
    .links {margin:0;padding:15px;text-align:center;background:#161616;box-shadow:0 2px 6px rgba(0,0,0,.5);}
    .links a {
      display:inline-block;margin:6px;padding:8px 14px;
      background:#2ecc71;color:#fff;text-decoration:none;
      border-radius:6px;transition:.3s;font-size:14px;
    }
    .links a:hover {background:#27ae60;}
    #thumbPreview {display:block;margin-top:10px;max-height:150px;border-radius:6px;}
    /* Popup */
    #popup {
      position:fixed;top:0;left:0;right:0;bottom:0;
      background:rgba(0,0,0,.7);display:none;align-items:center;justify-content:center;z-index:999;
    }
    .popup-box {
      background:#1f1f1f;padding:25px 30px;border-radius:10px;text-align:center;
      box-shadow:0 4px 12px rgba(0,0,0,.6);max-width:500px;width:90%;max-height:90%;overflow:auto;
    }
    .popup-box h3 {color:#2ecc71;margin-bottom:15px;}
    .popup-box input {width:100%;margin-bottom:15px;}
    .popup-box pre {
      text-align:left;background:#111;padding:10px;border-radius:6px;
      color:#eee;font-size:13px;max-height:300px;overflow:auto;
    }
    .popup-box button {
      background:#ff6b6b;color:#fff;border:none;padding:10px 20px;border-radius:6px;cursor:pointer;margin-top:15px;
    }
    .popup-box button:hover {background:#ff4040;}
    .success {color:#2ecc71;}
    .error {color:#e74c3c;}
    .update {color:#f39c12;}
    /* animasi loading dots */
    .loading-dots::after {
      content: '...';
      animation: dots 1s steps(3, end) infinite;
    }
    @keyframes dots {
      0%, 20% { content: ''; }
      40% { content: '.'; }
      60% { content: '..'; }
      80%, 100% { content: '...'; }
    }
  </style>
</head>
<body>
 <div class="links">
    <a href="dasboard.php">⚙️ Dashboard</a>
    <a href="admin">⚙️ Dashboard Admin</a>
	<a href="ads.php">⚙️ Dashboard Ads</a>
	<a href="deploy.php">⚙️ Dashboard Deploy</a>
    <a href="#" onclick="openPopup(event)">⚙️ Jalankan Generator</a>
	<a href="indexnow/">⚙️ indexnowy</a>
    <a href="output/index.html" target="_blank">📂 Lihat Output</a>
  </div>

<div class="wrap">
  <h2>Tambah Postingan Streaming</h2>
  <form action="generate.php" method="post" enctype="multipart/form-data">
    <div>
      <label>Judul Anime:</label>
      <input type="text" name="judul" required>
    </div>
    <div>
      <label>Sinopsis:</label>
      <textarea name="sinopsis" rows="5" required></textarea>
    </div>
    <div>
      <label>Genre (pisahkan dengan koma):</label>
      <input type="text" name="genre">
    </div>
    <div>
      <label>Durasi:</label>
      <input type="text" name="durasi">
    </div>
    <div>
      <label>Studio:</label>
      <input type="text" name="studio">
    </div>
    <div>
      <label>Thumbnail (upload gambar):</label>
      <input type="file" name="thumbnail" accept="image/*" onchange="previewThumb(event)">
      <img id="thumbPreview" style="display:none;">
    </div>
    <div>
      <label>Link Video (embed URL):</label>
      <input type="text" name="video" placeholder="https://www.youtube.com/embed/VIDEO_ID" required>
    </div>
    <div>
      <label>Link Download 480p:</label>
      <input type="text" name="dl480">
    </div>
    <div>
      <label>Link Download 720p:</label>
      <input type="text" name="dl720">
    </div>
    <button type="submit">🚀 Generate Postingan</button>
  </form>
</div>

<!-- Popup -->
<div id="popup">
  <div class="popup-box">
    <h3>🚀 Jalankan Generator</h3>
    <div id="inputBox">
      <input type="text" id="netlifyUrl" placeholder="https://contoh.netlify.app">
      <button onclick="runGenerator()">Update & Generate</button>
    </div>
    <div id="logBox" style="display:none;">
      <pre id="logOutput"><span class="loading-dots">⏳ Sedang memproses</span></pre>
      <button onclick="closePopup()">Tutup</button>
    </div>
  </div>
</div>

<script>
function previewThumb(event){
  const img = document.getElementById('thumbPreview');
  img.src = URL.createObjectURL(event.target.files[0]);
  img.style.display = "block";
}

function openPopup(e){
  e.preventDefault();
  document.getElementById("popup").style.display="flex";
  document.getElementById("inputBox").style.display="block";
  document.getElementById("logBox").style.display="none";
}

function runGenerator(){
  const url = document.getElementById("netlifyUrl").value.trim();
  if(!url){ alert("⚠️ Masukkan URL Netlify dulu!"); return; }

  document.getElementById("inputBox").style.display="none";
  document.getElementById("logBox").style.display="block";
  const logOutput = document.getElementById("logOutput");
  logOutput.innerHTML = '<span class="loading-dots">⏳ Sedang memproses</span>';

  fetch("build.php", {
    method: "POST",
    headers: {"Content-Type": "application/x-www-form-urlencoded"},
    body: "netlify="+encodeURIComponent(url)
  })
  .then(res => res.json())
  .then(data => {
    logOutput.innerHTML = data.map(msg => {
      if(msg.includes("✅")) return `<span class="success">${msg}</span>`;
      if(msg.includes("🔁")) return `<span class="update">${msg}</span>`;
      if(msg.includes("❌")) return `<span class="error">${msg}</span>`;
      return msg;
    }).join("\n");
  })
  .catch(err=>{
    logOutput.textContent = "❌ Gagal menjalankan generator.";
  });
}

function closePopup(){
  document.getElementById("popup").style.display="none";
}
</script>
</body>
</html>
