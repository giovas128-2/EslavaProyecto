/* QR generado localmente: no se envían enlaces privados a servicios externos. */
window.GuitarixtaQr = (() => {
  function paint(node, path) {
    node.replaceChildren();
    const url = new URL(path, window.location.href);
    if (url.origin !== window.location.origin) return;
    const link = document.createElement('a');
    link.href = url.href;
    link.className = 'product-qr-link';
    link.setAttribute('aria-label', 'Abrir detalle privado del producto');
    const qr = qrcode(0, 'M');
    qr.addData(url.href);
    qr.make();
    // La biblioteca solo genera una imagen SVG de la matriz, sin interpolar la URL.
    link.innerHTML = qr.createSvgTag({cellSize: 3, margin: 12, scalable: true});
    link.querySelector('svg').setAttribute('aria-hidden', 'true');
    const label = document.createElement('small');
    label.textContent = 'Escanear o abrir';
    link.append(label);
    node.append(link);
  }
  async function cart(nodes) {
    if (!nodes.length) return;
    try {
      const csrf = document.querySelector('meta[name="csrf-token"]').content;
      const response = await fetch('../components/qrCarrito.php', {method: 'POST', credentials: 'same-origin', body: new URLSearchParams({csrf})});
      const data = await response.json();
      if (!response.ok) throw new Error(data.error || 'QR no disponible.');
      for (const node of nodes) {
        if (!node.isConnected) continue;
        if (data.login_required) {
          const a = document.createElement('a');
          a.href = 'login.php'; a.textContent = 'Inicia sesión para ver el QR';
          node.replaceChildren(a);
        } else if (data.urls[node.dataset.cartQr]) paint(node, data.urls[node.dataset.cartQr]);
        else node.textContent = 'Producto fuera del carrito.';
      }
    } catch (error) {
      for (const node of nodes) if (node.isConnected) node.textContent = 'QR no disponible. Vuelve a abrir el carrito.';
    }
  }
  document.querySelectorAll('[data-product-qr]').forEach(node => paint(node, node.dataset.productQr));
  cart([...document.querySelectorAll('[data-cart-qr]')]);
  return {paint, cart};
})();
