// Carrito de compras: un producto solo puede comprarse a su proveedor.
(function () {
var productos = window.PRODUCTOS || [];
var $ = function (id) { return document.getElementById(id); };
var selProv = $('c-prov'); if (!selProv) return;
var selProd = $('c-prod'), cant = $('c-cant'), upp = $('c-upp'), costo = $('c-costo'), venc = $('c-vencimiento'), info = $('c-info');
var tbody = $('c-lineas'), hidden = $('c-hidden'), total = $('c-total'), btn = $('c-confirmar'), provH = $('c-prov-h');
var porId = {}; productos.forEach(function (p) { porId[p.id] = p; });
var carrito = [];
function bs(n) { return 'Bs ' + Number(n).toFixed(2); }
function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
function llenarProductos() {
var prov = parseInt(selProv.value, 10) || 0;
selProd.innerHTML = '';
var lista = productos.filter(function (p) { return Number(p.proveedor_id) === prov; });
if (!prov) { selProd.innerHTML = '<option value="">Primero elige un proveedor</option>'; info.textContent = ''; return; }
if (!lista.length) { selProd.innerHTML = '<option value="">Este proveedor no tiene productos asignados</option>'; return; }
lista.forEach(function (p) {
var o = document.createElement('option');
o.value = p.id; o.textContent = p.nombre + ' (' + p.codigo + ') — stock: ' + p.stock_actual;
selProd.appendChild(o);
});
sugerir();
}
function sugerir() {
var p = porId[selProd.value]; if (!p) return;
upp.value = p.unidades_paquete; costo.value = Number(p.precio_compra).toFixed(2);
info.textContent = 'Costo actual registrado: ' + bs(p.precio_compra) + ' por paquete de ' + p.unidades_paquete + ' unidades.';
}
function dibujar() {
tbody.innerHTML = ''; hidden.innerHTML = '';
var suma = 0;
if (!carrito.length) tbody.innerHTML = '<tr><td colspan="8" class="m-vacio">Aún no agregaste productos.</td></tr>';
carrito.forEach(function (l, i) {
var p = porId[l.id], sub = l.costo * l.cant; suma += sub;
var tr = document.createElement('tr');
tr.innerHTML = '<td>' + esc(p.nombre) + '</td><td class="num">' + l.cant + '</td><td class="num">' + l.upp + '</td>' +
'<td class="num">' + (l.cant * l.upp) + '</td><td class="num">' + bs(l.costo) + '</td><td>' + (l.vencimiento ? esc(l.vencimiento.split('-').reverse().join('/')) : 'Sin vencimiento') + '</td><td class="num">' + bs(sub) + '</td>' +
'<td><button type="button" class="m-btn m-borrar" data-i="' + i + '">Quitar</button></td>';
tbody.appendChild(tr);
hidden.insertAdjacentHTML('beforeend',
'<input type="hidden" name="items[' + i + '][producto_id]" value="' + l.id + '">' +
'<input type="hidden" name="items[' + i + '][unidades_por_paquete]" value="' + l.upp + '">' +
'<input type="hidden" name="items[' + i + '][cantidad]" value="' + l.cant + '">' +
'<input type="hidden" name="items[' + i + '][costo_unitario]" value="' + l.costo.toFixed(2) + '">' +
'<input type="hidden" name="items[' + i + '][fecha_vencimiento]" value="' + esc(l.vencimiento || '') + '">');
});
total.textContent = 'Total: ' + bs(suma);
btn.disabled = !carrito.length;
provH.value = selProv.value;
selProv.disabled = carrito.length > 0;   // no se mezclan proveedores en una compra
}
$('c-agregar').addEventListener('click', function () {
var p = porId[selProd.value];
var n = parseInt(cant.value, 10), u = parseInt(upp.value, 10), c = parseFloat(costo.value), fv = venc.value || '';
if (fv && fv < new Date().toISOString().slice(0, 10)) { lagDialog.error('La fecha de vencimiento no puede ser anterior a hoy.'); return; }
if (!p) { lagDialog.error('Selecciona un producto.'); return; }
if (!(n >= 1) || !(u >= 1) || !(c >= 0)) { lagDialog.error('Revisa cantidad, unidades por paquete y costo.'); return; }
var ex = carrito.filter(function (l) { return l.id === p.id && l.upp === u && l.costo === c && l.vencimiento === fv; })[0];
if (ex) ex.cant += n; else carrito.push({ id: p.id, cant: n, upp: u, costo: c, vencimiento: fv });
cant.value = 1; venc.value = ''; dibujar();
});
tbody.addEventListener('click', function (e) {
var i = e.target.getAttribute && e.target.getAttribute('data-i');
if (i !== null && i !== undefined) { carrito.splice(parseInt(i, 10), 1); dibujar(); }
});
$('c-vaciar').addEventListener('click', function () { carrito = []; dibujar(); });
selProv.addEventListener('change', function () { llenarProductos(); dibujar(); });
selProd.addEventListener('change', sugerir);
$('c-form').addEventListener('submit', function (e) { if (!carrito.length) { e.preventDefault(); return; } selProv.disabled = false; btn.disabled = true; });
llenarProductos();
// Desde el Asistente: prellenar con los productos sugeridos
(window.PREFILL || []).forEach(function (r) {
var p = porId[r.id]; if (!p || Number(p.proveedor_id) !== (parseInt(selProv.value, 10) || 0)) return;
carrito.push({ id: p.id, cant: r.paq, upp: parseInt(p.unidades_paquete, 10), costo: parseFloat(p.precio_compra) });
});
dibujar();
})();
