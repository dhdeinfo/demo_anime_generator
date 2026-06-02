<?php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
ob_implicit_flush(true);
set_time_limit(0);

function sendLog($msg) {
    echo "data: " . trim($msg) . "\n\n";
    @ob_flush(); flush();
}

$token = $_GET['token'] ?? '';
$site  = $_GET['site'] ?? '';

if (!$token || !$site) {
    sendLog("❌ Token dan Site ID wajib diisi");
    echo "data: [DONE]\n\n"; exit;
}

$dir = realpath(__DIR__."/../output");
if (!$dir) {
    sendLog("❌ Folder output tidak ditemukan!");
    echo "data: [DONE]\n\n"; exit;
}

if (!function_exists('proc_open')) {
    sendLog("❌ Server tidak support proc_open, tidak bisa deploy realtime.");
    echo "data: [DONE]\n\n"; exit;
}

// gunakan path absolut netlify
$netlifyPath = trim(shell_exec("which netlify"));
if (!$netlifyPath) {
    sendLog("❌ Netlify CLI tidak ditemukan. Install dulu dengan: npm install -g netlify-cli");
    echo "data: [DONE]\n\n"; exit;
}

$cmd = "cd $dir && NETLIFY_AUTH_TOKEN=$token $netlifyPath deploy --dir=$dir --prod --site=$site";
sendLog("▶️ Menjalankan: $cmd");

$descriptorspec = [
   0 => ["pipe", "r"],
   1 => ["pipe", "w"],
   2 => ["pipe", "w"]
];
$process = proc_open($cmd, $descriptorspec, $pipes);

if (is_resource($process)) {
    fclose($pipes[0]);
    while (!feof($pipes[1])) {
        $line = fgets($pipes[1]);
        if ($line) sendLog("📦 ".$line);
    }
    fclose($pipes[1]);

    while (!feof($pipes[2])) {
        $line = fgets($pipes[2]);
        if ($line) sendLog("⚠️ ".$line);
    }
    fclose($pipes[2]);

    proc_close($process);
    sendLog("✅ Deploy selesai ke Netlify");
}
echo "data: [DONE]\n\n";
