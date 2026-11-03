<?php
require_once __DIR__ . '/../includes/adminGuard.php';
$id=filter_var($_GET['id'] ?? null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
if (!$id) { http_response_code(404); exit('Imagen no encontrada.'); }
try {
 $q=$conexion->prepare('SELECT imagen,imagen_tipo FROM productos WHERE id=?'); $q->execute([$id]);$r=$q->fetch();
 if (!$r) { http_response_code(404); exit('Imagen no encontrada.'); }
 if (!in_array($r['imagen_tipo'],['image/png','image/jpeg','image/webp'],true)) { http_response_code(415); exit; }
 if (isset($_GET['binario'])) {
   header('Content-Type: text/plain; charset=utf-8');
   header('Content-Disposition: attachment; filename="producto-'.$id.'-binario.txt"');
   $len=strlen($r['imagen']);
   for($i=0;$i<$len;$i++) { echo sprintf('%08b',ord($r['imagen'][$i])).(($i+1)%8===0 ? "\n" : ' '); }
 } else {
   header('Content-Type: '.$r['imagen_tipo']); header('Content-Length: '.strlen($r['imagen'])); echo $r['imagen'];
 }
} catch (PDOException $e) { http_response_code(503); echo 'No se pudo leer la imagen.'; }
