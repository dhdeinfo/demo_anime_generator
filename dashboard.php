<?php
// dashboard.php
$file = "data.json";
$data = file_exists($file) ? json_decode(file_get_contents($file), true) : [];

// Cek notifikasi
$msg = "";
if (isset($_GET['deleted'])) $msg = "✅ Postingan berhasil dihapus.";
if (isset($_GET['added']))   $msg = "✅ Postingan baru berhasil ditambahkan.";
if (isset($_GET['cleared'])) $msg = "🗑️ Semua postingan berhasil dihapus.";
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Dashboard Streaming Generator</title>
  <link rel="stylesheet" href="assets/style.css">
  <style>
    body {background:#0f0f10;color:#eee;font-family:'Segoe UI',Arial,sans-serif;}
    .wrap {max-width:1000px;margin:40px auto;background:#161616;padding:20px;border-radius:10px;box-shadow:0 4px 12px rgba(0,0,0,.6);}
    h2 {color:#ff6b6b;text-align:center;margin-bottom:20px;}
    table {width:100%;border-collapse:collapse;margin-top:20px;}
    table th, table td {padding:12px 10px;border-bottom:1px solid #333;text-align:left;}
    table th {background:#222;}
    table tr:hover {background:#1f1f1f;}
    a.btn {
      display:inline-block;padding:6px 12px;background:#2ecc71;color:#fff;
      text-decoration:none;border-radius:6px;font-size:13px;transition:.3s;
    }
    a.btn:hover {background:#27ae60;}
    a.btn-del {background:#e74c3c;}
    a.btn-del:hover {background:#c0392b;}
    .add {display:flex;justify-content:space-between;margin-bottom:20px;}
    .add a {
      background:#ff6b6b;padding:10px 16px;border-radius:6px;
      color:#fff;text-decoration:none;font-weight:bold;
    }
    .add a:hover {background:#ff4040;}
    .msg {
      padding:12px;margin-bottom:20px;border-radius:6px;font-weight:bold;text-align:center;
    }
    .msg.success {background:#2ecc71;color:#fff;}
    .msg.error {background:#e74c3c;color:#fff;}
  </style>
</head>
<body>
<div class="wrap">
  <h2>📋 Dashboard Postingan</h2>

  <?php if($msg): ?>
    <div class="msg success"><?= $msg ?></div>
  <?php endif; ?>

  <div class="add">
    <a href="index.php">➕ Tambah Postingan Baru</a>
    <?php if(!empty($data)): ?>
      <a href="delete.php?all=1" onclick="return confirm('Yakin ingin hapus SEMUA postingan?')">🗑️ Hapus Semua</a>
    <?php endif; ?>
  </div>

  <table>
    <tr>
      <th>#</th>
      <th>Judul</th>
      <th>Series</th>
      <th>Published</th>
      <th>Aksi</th>
    </tr>
    <?php if(empty($data)): ?>
      <tr><td colspan="5" style="text-align:center;">Belum ada postingan</td></tr>
    <?php else: ?>
      <?php foreach($data as $i=>$p): ?>
      <tr>
        <td><?= $i+1 ?></td>
        <td><?= htmlspecialchars($p['title']) ?></td>
        <td><?= htmlspecialchars($p['series']) ?></td>
        <td><?= $p['published'] ?? '-' ?></td>
        <td>
          <a class="btn" href="output/posts/<?= $p['slug'] ?>.html" target="_blank">👁️ Preview</a>
          <a class="btn btn-del" href="delete.php?slug=<?= urlencode($p['slug']) ?>" onclick="return confirm('Yakin ingin hapus postingan ini?')">🗑️ Hapus</a>
        </td>
      </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </table>
</div>
</body>
</html>
