const money=c=>new Intl.NumberFormat('es-MX',{style:'currency',currency:'MXN'}).format(c/100)+' MXN';
const csrf=document.querySelector('meta[name="csrf-token"]').content;
let toastTimer;
function notify(message){const e=document.getElementById('toast');e.textContent=message;e.hidden=false;clearTimeout(toastTimer);toastTimer=setTimeout(()=>e.hidden=true,4500);}
async function api(data){const response=await fetch('../components/tiendaApi.php',data?{method:'POST',body:new URLSearchParams({...data,csrf}),credentials:'same-origin'}:{credentials:'same-origin'});let result;try{result=await response.json();}catch(e){throw new Error('La tienda no respondió. Inténtalo de nuevo.');}if(!response.ok||result.error)throw new Error(result.error||'No se pudo completar la solicitud.');return result;}
const drawer=document.getElementById('cart-drawer'),toggle=document.getElementById('cart-toggle');
function closeCart(){drawer.close();toggle.setAttribute('aria-expanded','false');toggle.focus();}
toggle.addEventListener('click',async()=>{drawer.showModal();toggle.setAttribute('aria-expanded','true');try{renderCart(await api());}catch(e){notify(e.message);}});
document.getElementById('cart-close').onclick=closeCart;document.getElementById('keep-shopping').onclick=closeCart;drawer.addEventListener('close',()=>toggle.setAttribute('aria-expanded','false'));
drawer.addEventListener('click',e=>{if(e.target===drawer){const r=drawer.getBoundingClientRect();if(e.clientX<r.left)closeCart();}});
function el(tag,cls,text){const n=document.createElement(tag);if(cls)n.className=cls;if(text!==undefined)n.textContent=text;return n;}
function renderCart(cart){const count=document.getElementById('cart-count');count.textContent=cart.cantidad;count.hidden=!cart.cantidad;toggle.setAttribute('aria-label',`Abrir carrito, ${cart.cantidad} piezas`);const root=document.getElementById('cart-items');root.replaceChildren();
 if(!cart.items.length){const empty=el('div','empty-state');empty.append(el('h3','','Tu carrito está vacío'),el('p','','Encuentra tu próximo sonido en el catálogo.'));root.append(empty);}
 if(cart.items.length){
 const scroll=el('div','purchase-table-scroll'),table=el('table','purchase-table');
 table.append(el('caption','','Cantidades seleccionadas. Desliza la tabla para ver todas las columnas.'));
 const head=el('thead'),tr=el('tr');
 for(const title of ['Nombre del producto','Stock (cantidad seleccionada)','Imagen del producto','Código QR','Precio total']){const th=el('th','',title);th.scope='col';tr.append(th);}
 head.append(tr);table.append(head);const body=el('tbody');
 for(const item of cart.items){
  const row=el('tr'),name=el('td','',item.producto);name.append(el('small','',money(item.precio_centavos)+' por pieza'));
  const quantity=el('td'),controls=el('div','cart-item-controls'),qty=el('input');qty.type='number';qty.min=1;qty.max=Math.max(1,Math.min(99,item.stock));qty.value=item.cantidad;qty.setAttribute('aria-label','Cantidad de '+item.producto);
  qty.onchange=async()=>{qty.disabled=true;try{renderCart(await api({accion:'cantidad',id:item.id,cantidad:qty.value}));}catch(e){qty.value=item.cantidad;qty.disabled=false;notify(e.message);}};
  const remove=el('button','','Eliminar');remove.type='button';remove.setAttribute('aria-label','Eliminar '+item.producto);remove.onclick=async()=>{try{renderCart(await api({accion:'quitar',id:item.id}));}catch(e){notify(e.message);}};
  controls.append(qty,remove);quantity.append(controls);
  const photo=el('td'),img=el('img','product-thumb');img.src='../components/productoFoto.php?id='+item.id;img.alt=item.producto;photo.append(img);
  const qr=el('td','product-qr-cell','Generando QR…');qr.dataset.cartQr=item.id;
  row.append(name,quantity,photo,qr,el('td','',money(item.precio_centavos*item.cantidad)));body.append(row);
 }
 table.append(body);scroll.append(table);root.append(scroll,el('p','cart-table-note','Precio total por producto antes de IVA y envío. El QR requiere iniciar sesión con la misma cuenta en el celular.'));
 window.GuitarixtaQr.cart([...root.querySelectorAll('[data-cart-qr]')]);
 }
 document.getElementById('cart-total').textContent=money(cart.subtotal_centavos);document.getElementById('checkout-link').hidden=!cart.items.length;
}
document.querySelectorAll('.add-cart').forEach(form=>form.addEventListener('submit',async e=>{e.preventDefault();const button=form.querySelector('button');button.disabled=true;try{renderCart(await api({accion:'agregar',id:form.dataset.id,cantidad:new FormData(form).get('cantidad')}));notify('Guitarra agregada a tu carrito.');}catch(error){notify(error.message);}finally{button.disabled=false;}}));
api().then(renderCart).catch(()=>{});
