<?php
require_once __DIR__.'/comercio.php';

// No se aceptan direcciones de retorno arbitrarias ni datos de precios en la URL.
function destinoProductoQr(array $params): ?string {
    if (isset($params['detalle']) && is_string($params['detalle']) && preg_match('/^[1-9][0-9]{0,17}$/D', $params['detalle'])) {
        return 'productoQr.php?detalle='.$params['detalle'];
    }
    if (isset($params['carrito']) && is_string($params['carrito']) && preg_match('/^[a-f0-9]{64}$/D', $params['carrito'])) {
        return 'productoQr.php?carrito='.$params['carrito'];
    }
    return null;
}
function mismoComprador(array $registro, array $usuario): bool {
    return (int)$registro['usuario_id'] === (int)$usuario['id'] && $registro['usuario_origen'] === $usuario['origen'];
}
function guardarQrCarrito(PDO $db, array $usuario, array $cart): array {
    $urls=[];
    $db->beginTransaction();
    try {
        $q=$db->prepare('DELETE FROM carrito_qr WHERE usuario_origen=? AND usuario_id=? AND expira_en<=?');
        $q->execute([$usuario['origen'],$usuario['id'],date('Y-m-d H:i:s')]);
        $q=$db->prepare('INSERT INTO carrito_qr (token,usuario_origen,usuario_id,producto_id,producto,cantidad,precio_centavos,actualizado_en,expira_en) VALUES (?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE producto=VALUES(producto),cantidad=VALUES(cantidad),precio_centavos=VALUES(precio_centavos),actualizado_en=VALUES(actualizado_en),expira_en=VALUES(expira_en)');
        $read=$db->prepare('SELECT token FROM carrito_qr WHERE usuario_origen=? AND usuario_id=? AND producto_id=?');
        foreach ($cart['items'] as $item) {
            $q->execute([bin2hex(random_bytes(32)),$usuario['origen'],$usuario['id'],$item['id'],$item['producto'],$item['cantidad'],$item['precio_centavos'],date('Y-m-d H:i:s'),date('Y-m-d H:i:s',strtotime('+7 days'))]);
            $read->execute([$usuario['origen'],$usuario['id'],$item['id']]);
            $urls[$item['id']]='productoQr.php?carrito='.$read->fetchColumn();
        }
        $db->commit();
    } catch (Throwable $e) { if($db->inTransaction())$db->rollBack(); throw $e; }
    return $urls;
}
function leerProductoQr(PDO $db, array $usuario, array $params): array {
    if (!destinoProductoQr($params)) throw new RuntimeException('QR no disponible.');
    if (isset($params['detalle'])) {
        $q=$db->prepare('SELECT d.*,p.usuario_id,p.usuario_origen,p.folio,p.estado,p.creado_en,p.iva_porcentaje FROM pedido_detalles d JOIN pedidos p ON p.id=d.pedido_id WHERE d.id=?');
        $q->execute([$params['detalle']]);
    } else {
        $q=$db->prepare('SELECT * FROM carrito_qr WHERE token=? AND expira_en>?');
        $q->execute([$params['carrito'],date('Y-m-d H:i:s')]);
    }
    $row=$q->fetch();
    // Incluso un administrador debe ser el comprador para abrir este QR privado.
    if (!$row || !mismoComprador($row,$usuario)) throw new RuntimeException('QR no disponible.');
    return $row;
}
