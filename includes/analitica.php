<?php
// Read-only reports over the existing order and login history.
function filtrosGraficas(array $in): array {
 $f=['desde'=>(string)($in['desde']??date('Y-m-d',strtotime('-29 days'))),'hasta'=>(string)($in['hasta']??date('Y-m-d')),'usuario'=>(string)($in['usuario']??''),'estado'=>(string)($in['estado']??''),'modo'=>(string)($in['modo']??'todos'),'grupo'=>(string)($in['grupo']??'dia'),'pagina'=>max(1,(int)($in['pagina']??1))];
 foreach(['desde','hasta'] as $key){if($f[$key]!==''){$d=DateTimeImmutable::createFromFormat('!Y-m-d',$f[$key]);if(!$d || $d->format('Y-m-d')!==$f[$key]){throw new RuntimeException('Revisa las fechas seleccionadas.');}}}
 if($f['desde'] && $f['hasta'] && $f['desde']>$f['hasta']){throw new RuntimeException('La fecha inicial no puede ser posterior a la final.');}
 if($f['usuario']!=='' && $f['usuario']!=='desconocidos' && !preg_match('/^(registroUsuarios|registrarAdmins|empleados|login):[1-9][0-9]*$/',$f['usuario'])){throw new RuntimeException('Usuario inválido.');}
 if(!in_array($f['estado'],['','pendiente_efectivo','pendiente_tarjeta','pagado','entregado','cancelado','expirado'],true) || !in_array($f['modo'],['todos','reales','prueba'],true) || !in_array($f['grupo'],['dia','mes'],true)){throw new RuntimeException('Filtro inválido.');}
 return $f;
}
function usuariosGraficas(PDO $db): array {
 $map=[];$sources=['registroUsuarios'=>['nombre','correoElectronico','Cliente'],'registrarAdmins'=>['nombre','correoElectronico','Administrador'],'empleados'=>['nombre','correoElectronico','Empleado'],'login'=>['correo','correo','Cuenta anterior']];
 foreach($sources as $table=>$cols){foreach($db->query('SELECT id,`'.$cols[0].'` AS nombre,`'.$cols[1].'` AS correo FROM `'.$table.'`')->fetchAll() as $r){$key=$table.':'.$r['id'];$map[$key]=['id'=>$key,'nombre'=>$r['nombre']?:$r['correo'],'correo'=>$r['correo'],'tipo'=>$cols[2]];}}
 // Keep historical identities after deletion; do not attribute them to another account with the same email.
 $old=$db->query('SELECT usuario_origen AS origen,usuario_id AS cuenta_id FROM pedidos GROUP BY usuario_origen,usuario_id')->fetchAll();
 $old=array_merge($old,$db->query('SELECT origen,cuenta_id FROM bitacora WHERE cuenta_id IS NOT NULL AND origen IS NOT NULL GROUP BY origen,cuenta_id')->fetchAll());
 foreach($old as $r){if(!isset($sources[$r['origen']]) || (int)$r['cuenta_id']<1)continue;$key=$r['origen'].':'.$r['cuenta_id'];if(!isset($map[$key])){$map[$key]=['id'=>$key,'nombre'=>'Cuenta eliminada #'.$r['cuenta_id'],'correo'=>'','tipo'=>$sources[$r['origen']][2].' · histórico'];}}
 uasort($map,fn($a,$b)=>strcasecmp($a['nombre'],$b['nombre']));return $map;
}
function datosGraficas(PDO $db,array $f): array {
 $users=usuariosGraficas($db);$where=['1=1'];$params=[];$logWhere=['1=1'];$logParams=[];
 if($f['desde']){$where[]='p.creado_en>=?';$params[]=$f['desde'].' 00:00:00';$logWhere[]='fecha>=?';$logParams[]=$f['desde'];}
 if($f['hasta']){$next=(new DateTimeImmutable($f['hasta']))->modify('+1 day')->format('Y-m-d');$where[]='p.creado_en<?';$params[]=$next.' 00:00:00';$logWhere[]='fecha<?';$logParams[]=$next;}
 if($f['usuario']==='desconocidos'){$where[]='1=0';$logWhere[]='(cuenta_id IS NULL OR origen IS NULL)';}
 elseif($f['usuario']){[$origin,$uid]=explode(':',$f['usuario'],2);$where[]='p.usuario_origen=? AND p.usuario_id=?';array_push($params,$origin,(int)$uid);$logWhere[]='origen=? AND cuenta_id=?';array_push($logParams,$origin,(int)$uid);}
 if($f['estado']){$where[]='p.estado=?';$params[]=$f['estado'];}
 if($f['modo']==='reales'){$where[]="(p.stripe_modo IS NULL OR p.stripe_modo<>'test')";}elseif($f['modo']==='prueba'){$where[]="p.stripe_modo='test'";}
 $w=implode(' AND ',$where);$lw=implode(' AND ',$logWhere);
 $fetch=function($sql,$args)use($db){$q=$db->prepare($sql);$q->execute($args);return $q->fetchAll();};
 $paid="p.estado IN ('pagado','entregado') AND (p.stripe_modo IS NULL OR p.stripe_modo<>'test')";
 $stats=$fetch("SELECT COUNT(*) AS pedidos,COALESCE(SUM(CASE WHEN $paid THEN p.total_centavos ELSE 0 END),0) AS cobrado,COALESCE(SUM(CASE WHEN p.estado IN ('pagado','entregado') THEN 1 ELSE 0 END),0) AS pagados FROM pedidos p WHERE $w",$params)[0];
 $stats['clientes']=count($fetch("SELECT p.usuario_origen,p.usuario_id FROM pedidos p WHERE $w GROUP BY p.usuario_origen,p.usuario_id",$params));
 $period=$f['grupo']==='mes'?'SUBSTR(p.creado_en,1,7)':'SUBSTR(p.creado_en,1,10)';
 $timeline=$fetch("SELECT $period AS fecha,COUNT(*) AS pedidos,COALESCE(SUM(CASE WHEN $paid THEN p.total_centavos ELSE 0 END),0) AS cobrado FROM pedidos p WHERE $w GROUP BY $period ORDER BY fecha",$params);
 $products=$fetch("SELECT d.producto_id,d.producto,COALESCE(SUM(d.cantidad),0) AS piezas FROM pedido_detalles d JOIN pedidos p ON p.id=d.pedido_id WHERE $w GROUP BY d.producto_id,d.producto ORDER BY piezas DESC,d.producto",$params);
 $pages=max(1,(int)ceil((int)$stats['pedidos']/12));$page=min($f['pagina'],$pages);$offset=($page-1)*12;
 $orders=$fetch("SELECT p.id,p.folio,p.usuario_origen,p.usuario_id,p.nombre,p.creado_en,p.pagado_en,p.estado,p.metodo,p.stripe_modo,p.total_centavos FROM pedidos p WHERE $w ORDER BY p.creado_en DESC,p.id DESC LIMIT 12 OFFSET $offset",$params);
 $details=[];if($orders){$ids=array_column($orders,'id');$rows=$fetch('SELECT pedido_id,producto,cantidad,precio_centavos FROM pedido_detalles WHERE pedido_id IN ('.implode(',',array_fill(0,count($ids),'?')).') ORDER BY pedido_id,id',$ids);foreach($rows as $d){$details[$d['pedido_id']][]=$d;}}
 foreach($orders as &$o){$key=$o['usuario_origen'].':'.$o['usuario_id'];$o['cuenta']=$users[$key]??['id'=>$key,'nombre'=>'Cuenta histórica','correo'=>'','tipo'=>''];$o['items']=$details[$o['id']]??[];}unset($o);
 $logs=$fetch("SELECT origen,cuenta_id, SUM(CASE WHEN evento='entrada' AND intento='exitoso' THEN 1 ELSE 0 END) AS entradas,SUM(CASE WHEN evento='salida' AND intento='exitoso' THEN 1 ELSE 0 END) AS salidas,SUM(CASE WHEN intento='fallido' THEN 1 ELSE 0 END) AS fallidos, MAX(fecha) AS ultima_fecha FROM bitacora WHERE $lw GROUP BY origen,cuenta_id",$logParams);
 $access=[];$totals=['entradas'=>0,'salidas'=>0,'fallidos'=>0];
 foreach($logs as $l){$key=isset($l['origen'],$l['cuenta_id'])?$l['origen'].':'.$l['cuenta_id']:'desconocidos';if(!isset($access[$key])){$access[$key]=['id'=>$key,'nombre'=>$users[$key]['nombre']??'Cuenta no identificada','tipo'=>$users[$key]['tipo']??'Intentos sin cuenta asociada','entradas'=>0,'salidas'=>0,'fallidos'=>0,'ultima_fecha'=>''];}foreach($totals as $type=>$unused){$access[$key][$type]+=(int)$l[$type];$totals[$type]+=(int)$l[$type];}$access[$key]['ultima_fecha']=max($access[$key]['ultima_fecha'],$l['ultima_fecha']);}
 uasort($access,fn($a,$b)=>($b['entradas']+$b['salidas']+$b['fallidos'])<=>($a['entradas']+$a['salidas']+$a['fallidos']));
 return ['usuarios'=>array_values($users),'resumen'=>$stats,'tiempo'=>$timeline,'productos'=>$products,'pedidos'=>$orders,'pagina'=>$page,'paginas'=>$pages,'accesos'=>array_values($access),'totales_acceso'=>$totals,'filtros'=>$f];
}
