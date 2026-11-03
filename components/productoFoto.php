<?php
require_once __DIR__.'/../includes/comercio.php';
header('X-Content-Type-Options: nosniff');
$id=filter_var($_GET['id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
try{$q=dbTienda()->prepare('SELECT imagen,imagen_tipo FROM productos WHERE id=?');$q->execute([$id?:0]);$p=$q->fetch();
 if(!$p || !in_array($p['imagen_tipo'],['image/jpeg','image/png','image/webp'],true)){http_response_code(404);exit;}
 header('Content-Type: '.$p['imagen_tipo']);header('Cache-Control: private, max-age=60');echo $p['imagen'];
}catch(PDOException $e){http_response_code(503);}
