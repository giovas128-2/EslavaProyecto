<?php
function registrarEvento(PDO $db, string $correo, string $evento, string $intento, ?array $usuario = null): void {
    $ahora = new DateTimeImmutable('now', new DateTimeZone('America/Mexico_City'));
    $q = $db->prepare('INSERT INTO bitacora (fecha,hora,intento,usuario,evento,cuenta_id,origen) VALUES (?,?,?,?,?,?,?)');
    $q->execute([$ahora->format('Y-m-d'),$ahora->format('H:i:s'),$intento,substr($correo,0,254),$evento,$usuario['id'] ?? null,$usuario['origen'] ?? null]);
}
