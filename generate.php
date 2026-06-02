<?php
// streaming-generator/generate.php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php'); exit;
}

$judul  = trim($_POST['judul']);
$slug   = strtolower(preg_replace('/[^a-z0-9]+/','-', $judul));
$data = [
    'judul'=>$judul,
    'slug'=>$slug,
    'sinopsis'=>$_POST['sinopsis'],
    'genre'=>$_POST['genre'],
    'durasi'=>$_POST['durasi'],
    'studio'=>$_POST['studio'],
    'video'=>$_POST['video'],
    'dl480'=>$_POST['dl480'],
    'dl720'=>$_POST['dl720']
];

// load existing
$file = 'data.json';
$posts = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
$posts[] = $data;
file_put_contents($file, json_encode($posts, JSON_PRETTY_PRINT));

// rebuild site
include 'build.php';

echo "✅ Postingan berhasil dibuat. <a href='output/index.html' target='_blank'>Lihat Website</a>";
?>
