// Carrito de ventas. Los precios mostrados son solo informativos:
// el servidor vuelve a calcular todo con los datos de la base de datos.
(function () {
var catalogo = window.CATALOGO || [];
if (!catalogo.length) return;
var porId = {};
catalogo.forEach(function (c) { porId[c.id] = c; });
var $ = function (id) { return document.getElementById(id); };
var selPres = $('v-pres'), buscar = $('v-buscar'), cant = $('v-cant'), info = $('v-info');
var tbody = $('v-lineas'), hidden = $('v-hidden'), total = $('v-total');
var btnConf = $('v-confirmar');
var carrito = []; // [{id, cantidad}]
function aviso(msg, titulo) {
if (window.lagDialog) window.lagDialog.error(msg, titulo); else info.textContent = msg;
}
function bs(n) { return 'Bs ' + Number(n).toFixed(2); }
function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
function unidadesUsadas(productoId) {
return carrito.reduce(function (acc, l) {
var c = porId[l.id];
return acc + (c.producto_id === productoId ? c.cantidad_unidades * l.cantidad : 0);
}, 0);
}
function llenarSelect() {
var q = buscar.value.trim().toLowerCase();
var actual = selPres.value;
selPres.innerHTML = '';
catalogo.forEach(function (c) {
var txt = (c.producto + ' ' + c.codigo).toLowerCase();
if (q && txt.indexOf(q) === -1) return;
var o = document.createElement('option');
o.value = c.id;
o.textContent = c.producto + ' — ' + c.presentacion + ' (' + c.cantidad_unidades + ' u.) — ' + bs(c.precio_venta) + ' — stock: ' + c.stock_actual;
selPres.appendChild(o);
});
if (actual) selPres.value = actual;
if (!selPres.options.length) {
var o2 = document.createElement('option');
o2.textContent = 'Sin resultados'; o2.value = '';
selPres.appendChild(o2);
}
mostrarInfo();
}
function mostrarInfo() {
var c = porId[selPres.value];
if (!c) { info.textContent = ''; return; }
var libres = c.stock_actual - unidadesUsadas(c.producto_id);
info.textContent = 'Disponible: ' + libres + ' unidades (≈ ' + Math.floor(libres / c.cantidad_unidades) + ' de esta presentación).';
}
function dibujar() {
tbody.innerHTML = '';
hidden.innerHTML = '';
var suma = 0;
if (!carrito.length) {
tbody.innerHTML = '<tr><td colspan="6" class="v-vacio">Aún no agregaste productos.</td></tr>';
}
carrito.forEach(function (l, i) {
var c = porId[l.id], sub = c.precio_venta * l.cantidad;
suma += sub;
var tr = document.createElement('tr');
tr.innerHTML = '<td>' + esc(c.producto) + '</td><td>' + esc(c.presentacion) + '</td>' +
'<td class="num">' + l.cantidad + '</td><td class="num">' + bs(c.precio_venta) + '</td>' +
'<td class="num">' + bs(sub) + '</td>' +
'<td><button type="button" class="v-btn v-borrar" data-i="' + i + '">Quitar</button></td>';
tbody.appendChild(tr);
hidden.insertAdjacentHTML('beforeend',
'<input type="hidden" name="items[' + i + '][presentacion_id]" value="' + l.id + '">' +
'<input type="hidden" name="items[' + i + '][cantidad]" value="' + l.cantidad + '">');
});
total.textContent = 'Total: ' + bs(suma);
btnConf.disabled = !carrito.length;
mostrarInfo();
}
$('v-agregar').addEventListener('click', function () {
var c = porId[selPres.value];
var n = parseInt(cant.value, 10);
if (!c) { aviso('Selecciona un producto.'); return; }
if (!(n >= 1)) { aviso('La cantidad debe ser al menos 1.'); return; }
var requeridas = c.cantidad_unidades * n;
var libres = c.stock_actual - unidadesUsadas(c.producto_id);
if (requeridas > libres) {
var maxPres = Math.floor(libres / c.cantidad_unidades);
aviso('Stock insuficiente de «' + c.producto + '».\nPediste ' + n + ' × ' + c.presentacion + ' (' + requeridas + ' unidades) y solo hay ' + libres + ' unidades disponibles' +
(maxPres > 0 ? ' (máximo ' + maxPres + ' de esta presentación).' : '.'), 'Stock insuficiente');
cant.value = maxPres > 0 ? maxPres : 1;
return;
}
var existente = carrito.filter(function (l) { return l.id === c.id; })[0];
if (existente) existente.cantidad += n; else carrito.push({ id: c.id, cantidad: n });
cant.value = 1;
dibujar();
});
tbody.addEventListener('click', function (e) {
var i = e.target.getAttribute && e.target.getAttribute('data-i');
if (i !== null && i !== undefined) { carrito.splice(parseInt(i, 10), 1); dibujar(); }
});
$('v-vaciar').addEventListener('click', function () { carrito = []; dibujar(); });
buscar.addEventListener('input', llenarSelect);
selPres.addEventListener('change', mostrarInfo);
$('v-form').addEventListener('submit', function (e) {
if (!carrito.length) { e.preventDefault(); return; }
btnConf.disabled = true; // evita doble envío
});
llenarSelect();
dibujar();
})();
