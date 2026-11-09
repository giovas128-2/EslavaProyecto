<?php
// Reporte de solo lectura: nunca modifica pagos, pedidos ni existencias.
function filtrosReporte(array $in): array {
    $f=[];
    foreach (['desde'=>date('Y-m-d',strtotime('-29 days')),'hasta'=>date('Y-m-d'),'estado'=>'','modo'=>'reales'] as $key=>$default) {
        if(isset($in[$key]) && !is_string($in[$key])) throw new InvalidArgumentException('Filtro inválido.');
        $f[$key]=$in[$key]??$default;
    }
    foreach (['desde','hasta'] as $key) {
        $d=DateTimeImmutable::createFromFormat('!Y-m-d',$f[$key]);
        if (!$d || $d->format('Y-m-d')!==$f[$key] || $f[$key]<'1000-01-01' || $f[$key]>'9998-12-31') throw new InvalidArgumentException('Selecciona fechas válidas.');
    }
    if($f['desde']>$f['hasta']) throw new InvalidArgumentException('Desde no puede ser posterior a Hasta.');
    if(!in_array($f['estado'],['','pagado','entregado','pendiente_efectivo','pendiente_tarjeta','cancelado','expirado'],true) || !in_array($f['modo'],['reales','prueba','todos'],true)) throw new InvalidArgumentException('Filtro inválido.');
    $f['pagina']=max(1,min(1000000,(int)($in['pagina']??1)));
    return $f;
}
function datosReporte(PDO $db,array $f): array {
    $where=['p.creado_en>=?','p.creado_en<?'];
    $args=[$f['desde'].' 00:00:00',(new DateTimeImmutable($f['hasta']))->modify('+1 day')->format('Y-m-d').' 00:00:00'];
    if($f['estado']!=='') {$where[]='p.estado=?';$args[]=$f['estado'];}
    if($f['modo']==='reales')$where[]="(p.stripe_modo IS NULL OR p.stripe_modo<>'test')";
    if($f['modo']==='prueba')$where[]="p.stripe_modo='test'";
    $w=implode(' AND ',$where);
    $fetch=function($sql,$params)use($db){$q=$db->prepare($sql);$q->execute($params);return $q->fetchAll();};
    $paid="p.estado IN ('pagado','entregado') AND (p.stripe_modo IS NULL OR p.stripe_modo<>'test')";
    $stats=$fetch("SELECT COUNT(*) pedidos, COALESCE(SUM(CASE WHEN $paid THEN p.total_centavos ELSE 0 END),0) cobrado, COALESCE(SUM(CASE WHEN $paid THEN 1 ELSE 0 END),0) ventas, COALESCE(SUM(CASE WHEN p.estado IN ('pendiente_efectivo','pendiente_tarjeta') THEN 1 ELSE 0 END),0) pendientes FROM pedidos p WHERE $w",$args)[0];
    $pages=max(1,(int)ceil((int)$stats['pedidos']/20));$page=min($f['pagina'],$pages);$offset=($page-1)*20;
    $orders=$fetch("SELECT p.id,p.folio,p.nombre,p.usuario_id,p.usuario_origen,p.creado_en,p.pagado_en,p.estado,p.metodo,p.stripe_modo,p.subtotal_centavos,p.iva_centavos,p.envio_centavos,p.total_centavos FROM pedidos p WHERE $w ORDER BY p.creado_en DESC,p.id DESC LIMIT 20 OFFSET $offset",$args);
    $details=[];
    if($orders){$ids=array_column($orders,'id');$rows=$fetch('SELECT pedido_id,producto,cantidad,precio_centavos FROM pedido_detalles WHERE pedido_id IN ('.implode(',',array_fill(0,count($ids),'?')).') ORDER BY pedido_id,id',$ids);foreach($rows as $r)$details[$r['pedido_id']][]=$r;}
    return ['resumen'=>$stats,'pedidos'=>$orders,'detalles'=>$details,'pagina'=>$page,'paginas'=>$pages];
}
