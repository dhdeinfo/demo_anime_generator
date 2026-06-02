<?php
// ======================================================
// BUILD.PHP FINAL — FIX TOTAL + DISCOVER READY
// ======================================================

date_default_timezone_set('Asia/Jakarta');

// ---------------- ADS ----------------
function wrapAd($code){
    if(trim($code)==="") return "";
    return '<div class="ad-container">'.$code.'</div>';
}

$ads=["header"=>"","content"=>"","player"=>"","footer"=>"","sticky"=>""];
if(file_exists("ads.json")){
    $tmp=json_decode(file_get_contents("ads.json"),true);
    if(is_array($tmp)) $ads=array_merge($ads,$tmp);
}

// ---------------- HELPERS ----------------
function extractEpisodeNumber($title){
    if(preg_match('/(\d{1,4})/',$title,$m)) return (int)$m[1];
    return 0;
}
function esc($s){
    return htmlspecialchars($s,ENT_QUOTES,'UTF-8');
}

// ---------------- BUILD ----------------
if($_SERVER['REQUEST_METHOD']==='POST'){

    if(!file_exists("data.json")){
        die(json_encode(["❌ data.json tidak ditemukan"]));
    }

    $posts=json_decode(file_get_contents("data.json"),true);

    // SORT TERBARU
    usort($posts,function($a,$b){
        return strtotime($b['published']) <=> strtotime($a['published'])
            ?: extractEpisodeNumber($b['title']) <=> extractEpisodeNumber($a['title']);
    });

    $baseUrl=rtrim($_POST['netlify'] ?? "https://example.com","/");

    @mkdir("output/posts",0777,true);
    @mkdir("output/assets",0777,true);
    if(file_exists("assets/style.css")){
        copy("assets/style.css","output/assets/style.css");
    }

    // ======================================================
    // GROUP EPISODE PER SERIES
    // ======================================================
    $seriesEpisodes=[];
    foreach($posts as $p){
        $seriesEpisodes[$p['series']][]=$p;
    }
    foreach($seriesEpisodes as &$eps){
        usort($eps,function($a,$b){
            return extractEpisodeNumber($a['title']) <=> extractEpisodeNumber($b['title']);
        });
    }
    unset($eps);

    // ======================================================
    // EPISODE PAGES
    // ======================================================
    foreach($posts as $p){

        $episodes=$seriesEpisodes[$p['series']];
        $index=0;
        foreach($episodes as $i=>$epItem){
            if($epItem['slug']===$p['slug']){
                $index=$i; break;
            }
        }

        $prev=$index>0?$episodes[$index-1]:null;
        $next=$index<count($episodes)-1?$episodes[$index+1]:null;

        $ep=extractEpisodeNumber($p['title']);
        $publish=date('Y-m-d',strtotime($p['published']??date('Y-m-d')));
        $schemaImg=esc($p['thumbnail'] ?: $baseUrl.'/assets/cover-default.jpg');
        $genres=is_array($p['genres'])?implode(", ",$p['genres']):$p['genres'];

        // ================= AUTO RELATED EPISODE (MAX 5)
        $related=[];
        for($i=$index-1;$i>=0 && count($related)<2;$i--){
            $related[]=$episodes[$i];
        }
        for($i=$index+1;$i<count($episodes) && count($related)<5;$i++){
            $related[]=$episodes[$i];
        }

        $episodeList='<ul class="episode-list related">';
        foreach($related as $e){
            $episodeList.='<li>
            <a href="'.$baseUrl.'/posts/'.$e['slug'].'.html" title="Nonton '.esc($e['title']).'">
            ▶ '.esc($e['title']).'
            </a></li>';
        }
        $episodeList.='</ul>';

        ob_start(); ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= esc($p['title']) ?> Sub Indo | NontonanimesubIndo</title>
<meta name="description" content="Nonton <?= esc($p['title']) ?> sub Indo kualitas HD.">
<meta name="robots" content="index, follow, max-image-preview:large">
<link rel="canonical" href="<?= $baseUrl ?>/posts/<?= esc($p['slug']) ?>.html">

<meta property="og:type" content="video.episode">
<meta property="og:title" content="<?= esc($p['title']) ?>">
<meta property="og:description" content="Streaming <?= esc($p['title']) ?> sub Indo">
<meta property="og:image" content="<?= $schemaImg ?>">
<meta property="og:url" content="<?= $baseUrl ?>/posts/<?= esc($p['slug']) ?>.html">

<script type="application/ld+json">
{
 "@context":"https://schema.org",
 "@type":"TVEpisode",
 "name":"<?= esc($p['title']) ?> Sub Indo",
 "episodeNumber":<?= $ep ?>,
 "datePublished":"<?= $publish ?>",
 "url":"<?= $baseUrl ?>/posts/<?= esc($p['slug']) ?>.html",
 "image":{
   "@type":"ImageObject",
   "url":"<?= $schemaImg ?>",
   "caption":"<?= esc($p['title']) ?> Sub Indo"
 },
 "partOfSeries":{
   "@type":"TVSeries",
   "name":"<?= esc($p['series']) ?>"
 },
 "publisher":{
   "@type":"Organization",
   "name":"NontonanimesubIndo",
   "logo":{
     "@type":"ImageObject",
     "url":"<?= $baseUrl ?>/assets/logo.png"
   }
 }
}
</script>

<link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<header><div class="wrap">
<h1 class="logo">🎬 NontonanimesubIndo</h1>
<nav class="nav">
<a href="../index.html">Home</a> |
<a href="../all.html">All Anime</a>
</nav>
</div></header>




<?= wrapAd($ads['header']) ?>

<main class="container">
<h1><?= esc($p['title']) ?></h1>

<nav class="episode-nav">
<?php if($prev): ?><a href="<?= $prev['slug'] ?>.html">⬅ Episode Sebelumnya</a><?php endif; ?>
<?php if($next): ?><a href="<?= $next['slug'] ?>.html">Episode Selanjutnya ➡</a><?php endif; ?>
</nav>

<?= wrapAd($ads['player']) ?>
<div class="player"><?= html_entity_decode($p['iframe']) ?></div>

<ul class="meta">
<li><b>Series:</b> <?= esc($p['series']) ?></li>
<li><b>Genre:</b> <?= esc($genres) ?></li>
<li><b>Durasi:</b> <?= esc($p['durasi']) ?></li>
</ul>

<section>
<h3>📺 Episode Terkait <?= esc($p['series']) ?></h3>
<?= $episodeList ?>
</section>

<?= wrapAd($ads['content']) ?>
</main>

<?= wrapAd($ads['footer']) ?>
<footer>© <?= date('Y') ?> NontonanimesubIndo</footer>
</body>
</html>
<?php
file_put_contents("output/posts/".$p['slug'].".html",ob_get_clean());
    }

    echo json_encode(["✅ BUILD SELESAI TOTAL","🎯 Episode + Internal Link OK"]);
}
?>
