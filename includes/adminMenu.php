<link rel="stylesheet" href="../assets/css/admin.css">
<nav class="panel-navigation" aria-label="Administración">
  <details class="panel-dropdown"><summary>☰ Menú de administrador <span>▾</span></summary>
    <div class="panel-options">
      <a href="admin.php">01 · Usuarios y empleados</a>
      <a href="bitacora.php">02 · Bitácora de accesos</a>
      <a href="productos.php">03 · Productos / guitarras</a>
      <a href="pedidosAdmin.php">04 · Pedidos y entregas</a>
      <a href="tienda.php">Visitar tienda ↗</a>
    </div>
  </details>
  <a class="admin-orders-link" href="pedidosAdmin.php"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M5 17h14l-2-4V9a5 5 0 0 0-10 0v4zM10 20h4"/></svg> Pedidos <b id="order-alert" hidden></b></a>
</nav>
<script>
(()=>{const badge=document.getElementById('order-alert');async function load(){if(document.hidden)return;try{const r=await fetch('../components/notificaciones.php');if(!r.ok)return;const d=await r.json();badge.textContent=d.cantidad;badge.hidden=!d.cantidad;}catch(e){}}load();setInterval(load,20000);})();
</script>