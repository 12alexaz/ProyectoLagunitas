// Cuadros de diálogo propios (reemplazan alert() y confirm() del navegador).
(function () {
var ICONOS = { info: 'ℹ', error: '⚠', ok: '✔', pregunta: '?' };
function crear(opts) {
return new Promise(function (resolve) {
var previo = document.activeElement;
var fondo = document.createElement('div');
fondo.className = 'lag-overlay';
var caja = document.createElement('div');
caja.className = 'lag-dialogo lag-' + (opts.tipo || 'info');
caja.setAttribute('role', 'alertdialog');
caja.setAttribute('aria-modal', 'true');
var icono = document.createElement('div'); icono.className = 'lag-icono'; icono.textContent = ICONOS[opts.tipo || 'info'];
var titulo = document.createElement('h3'); titulo.textContent = opts.titulo || '';
var msg = document.createElement('p'); msg.textContent = opts.mensaje || '';
var botones = document.createElement('div'); botones.className = 'lag-botones';
function cerrar(valor) { document.removeEventListener('keydown', teclas, true); fondo.remove(); if (previo && previo.focus) previo.focus(); resolve(valor); }
function teclas(e) { if (e.key === 'Escape') { e.preventDefault(); cerrar(false); } }
if (opts.cancelar) {
var bc = document.createElement('button'); bc.type = 'button'; bc.className = 'lag-btn lag-sec'; bc.textContent = opts.cancelar;
bc.onclick = function () { cerrar(false); }; botones.appendChild(bc);
}
var ba = document.createElement('button'); ba.type = 'button'; ba.className = 'lag-btn lag-pri'; ba.textContent = opts.aceptar || 'Aceptar';
ba.onclick = function () { cerrar(true); }; botones.appendChild(ba);
caja.appendChild(icono); if (opts.titulo) caja.appendChild(titulo); caja.appendChild(msg); caja.appendChild(botones);
fondo.appendChild(caja);
fondo.addEventListener('mousedown', function (e) { if (e.target === fondo && opts.cancelar) cerrar(false); });
document.addEventListener('keydown', teclas, true);
document.body.appendChild(fondo);
ba.focus();
});
}
window.lagDialog = {
alerta: function (mensaje, tipo, titulo) { return crear({ mensaje: mensaje, tipo: tipo || 'info', titulo: titulo }); },
error: function (mensaje, titulo) { return crear({ mensaje: mensaje, tipo: 'error', titulo: titulo || 'Atención' }); },
confirmar: function (mensaje, aceptar) { return crear({ mensaje: mensaje, tipo: 'pregunta', titulo: 'Confirmar acción', cancelar: 'Cancelar', aceptar: aceptar || 'Sí, continuar' }); }
};
// Formularios con data-confirm="texto": piden confirmación en un cuadro propio.
document.addEventListener('submit', function (e) {
var f = e.target;
if (!f.getAttribute || !f.hasAttribute('data-confirm') || f.__confirmado) return;
e.preventDefault();
window.lagDialog.confirmar(f.getAttribute('data-confirm')).then(function (ok) {
if (ok) { f.__confirmado = true; f.submit(); }
});
}, true);
})();
