<?php
// delete.php
$file = "data.json";
$data = file_exists($file) ? json_decode(file_get_contents($file), true) : [];

if (isset($_GET['all'])) {
    // Hapus semua
    file_put_contents($file, json_encode([], JSON_PRETTY_PRINT));
    header("Location: dashboard.php?cleared=1");
    exit;
}

if (isset($_GET['slug'])) {
    $slug = $_GET['slug'];
    $newData = array_values(array_filter($data, fn($p) => $p['slug'] !== $slug));
    file_put_contents($file, json_encode($newData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    header("Location: dashboard.php?deleted=1");
    exit;
}

header("Location: dashboard.php");
