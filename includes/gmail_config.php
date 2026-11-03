<?php
$c=['usuario'=>getenv('GX_GMAIL_USUARIO')?:'', 'password_aplicacion'=>getenv('GX_GMAIL_APP_PASSWORD')?:'', 'nombre'=>'Guitarixta'];
if(is_file(__DIR__.'/gmail_local.php')){$c=array_replace($c,require __DIR__.'/gmail_local.php');}
return $c;
