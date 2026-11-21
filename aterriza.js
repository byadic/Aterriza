// <script src="aterriza.js"></script> al final de aterriza.html (reemplaza el script anterior)
const LOGIN_URL = 'index_modificado.html';   // adonde vuelve si no hay sesión
const $ = id => document.getElementById(id);
let usuario = null, perfil = {}, posts = [], feed = [], friends = [], personas = [], favs = [];
let activeFriend = null, pollTimer = null, ultimosMsgs = 0, feedTipo = '', visor = { lista: [], i: 0 };

const TIPOS = { ayuda: 'Ofrece ayuda', plan: 'Busca plan', recomendacion: 'Recomienda', otro: 'Publicación', foto: 'Foto' };
const LUGARES = [
  ['Paisajes y miradores', 'Miradores, montañas y vistas panorámicas para tomar fotografías.', 'Naturaleza', 'miradores'],
  ['Parques y áreas verdes', 'Espacios para caminar, descansar o compartir una tarde.', 'Aire libre', 'parques'],
  ['Centros históricos y culturales', 'Historia, arquitectura y cultura de la ciudad.', 'Cultura', 'museos y centros históricos'],
  ['Comida local', 'Platos tradicionales, mercados y restaurantes.', 'Gastronomía', 'restaurantes de comida típica'],
  ['Cafeterías', 'Lugares tranquilos para estudiar o conversar.', 'Café', 'cafeterías'],
  ['Deporte y gimnasios', 'Gimnasios y canchas para mantenerte activo.', 'Deporte', 'gimnasios'],
  ['Vida nocturna', 'Bares y peñas para salir con amigos.', 'Salidas', 'bares y peñas']
];
const PIN = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>';

async function api(url, o) {
  const r = await fetch(url, o);
  let d = {};
  try { d = await r.json(); } catch (e) {}
  if (r.status === 401) { location.href = LOGIN_URL; throw new Error('Sesión no iniciada'); }
  if (!r.ok || !d.ok) throw new Error(d.mensaje || 'Error del servidor.');
  return d;
}
const post = (url, datos) => api(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(datos) });
const val = id => $(id).value.trim();
const setval = (id, v) => { $(id).value = v || ''; };
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const inicial = n => esc((n || '?').charAt(0).toUpperCase());
const fecha = f => new Date(String(f).replace(' ', 'T')).toLocaleDateString('es-BO', { day: 'numeric', month: 'short' });
const mapsUrl = q => 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(q);
const rolTxt = r => r === 'residente' ? 'Residente' : 'Foráneo';

function showScreen(id) {
  document.querySelectorAll('.screen').forEach(s => s.classList.remove('active'));
  $(id).classList.add('active');
  document.querySelectorAll('.nav button').forEach(b => b.classList.toggle('active', b.dataset.screen === id));
  if (id !== 'personas') detenerChat();
  if (id === 'inicio') cargarFeed(feedTipo);
  if (id === 'personas') { buscarPersonas(); cargarAmigos(); }
  if (id === 'perfil') cargarPerfil().then(renderProfile);
  if (id === 'publicaciones') renderPosts();
  if (id === 'lugares') cargarFavoritos();
  window.scrollTo(0, 0);
}

/* ── Inicio: feed ── */
function construirInicio() {
  $('inicio').innerHTML = `
    <h1>Inicio</h1><p class="sub">Descubre qué comparte la comunidad: ayuda para foráneos, planes y recomendaciones.</p>
    <div class="card composer">
      <div class="composer-top"><div class="avatar" id="meAvatar"></div>
        <textarea id="postTexto" rows="2" maxlength="500" placeholder="¿Ofreces ayuda a foráneos o buscas plan? Cuéntalo aquí..."></textarea></div>
      <div class="composer-bar">
        <select id="postTipo"><option value="ayuda">Ofrezco ayuda</option><option value="plan">Busco plan</option><option value="recomendacion">Recomiendo un lugar</option><option value="otro">Otro</option></select>
        <label class="filebtn">Añadir foto<input type="file" id="postFoto" accept="image/*" hidden onchange="$('postFotoNombre').textContent=this.files[0]?this.files[0].name:''"></label>
        <button class="primary" type="button" onclick="publicarFeed()">Publicar</button>
      </div>
      <small id="postFotoNombre" class="sub"></small><p class="sub" id="feedStatus" style="margin:6px 0 0"></p>
    </div>
    <div class="chips" id="feedFiltros">
      <button class="chip active" data-t="" onclick="cargarFeed('')">Todo</button>
      <button class="chip" data-t="ayuda" onclick="cargarFeed('ayuda')">Ofrecen ayuda</button>
      <button class="chip" data-t="plan" onclick="cargarFeed('plan')">Buscan plan</button>
      <button class="chip" data-t="recomendacion" onclick="cargarFeed('recomendacion')">Recomendaciones</button>
    </div>
    <div id="feed"></div>`;
}

async function cargarFeed(tipo) {
  feedTipo = tipo || '';
  document.querySelectorAll('#feedFiltros .chip').forEach(c => c.classList.toggle('active', c.dataset.t === feedTipo));
  try { feed = (await api('api/feed.php?tipo=' + feedTipo)).feed; } catch (e) { return; }
  const box = $('feed');
  if (!feed.length) { box.innerHTML = '<div class="empty">Aún no hay publicaciones aquí. ¡Sé la primera persona en compartir algo!</div>'; return; }
  box.innerHTML = feed.map(p => `
    <article class="card post">
      <div class="post-head"><div class="avatar">${inicial(p.nombre)}</div>
        <div class="info"><b>${esc(p.nombre)}</b><small>${rolTxt(p.rol)} · ${fecha(p.fecha)}</small></div>
        <span class="tag">${TIPOS[p.tipo]}</span></div>
      ${p.texto ? `<p class="post-text">${esc(p.texto)}</p>` : ''}
      ${p.src ? `<img class="post-img" src="${esc(p.src)}" alt="Publicación de ${esc(p.nombre)}" onclick="abrirFoto('feed',${p.id})">` : ''}
      ${p.usuario_id !== usuario.id ? `<div class="actions"><button class="smallbtn ${p.sigo ? 'on' : ''}" onclick="toggleSeguir(${p.usuario_id},${p.sigo})">${p.sigo ? 'Siguiendo' : 'Seguir'}</button></div>` : ''}
    </article>`).join('');
}

async function publicarFeed() {
  const texto = val('postTexto'), foto = $('postFoto').files[0], st = $('feedStatus');
  if (!texto && !foto) { st.textContent = 'Escribe algo o añade una foto.'; return; }
  const fd = new FormData();
  fd.append('tipo', foto && !texto ? 'foto' : $('postTipo').value);
  fd.append('texto', texto);
  if (foto) fd.append('fotos[]', foto);
  try {
    await api('api/publicaciones.php', { method: 'POST', body: fd });
    setval('postTexto', ''); $('postFoto').value = ''; $('postFotoNombre').textContent = ''; st.textContent = '';
    await Promise.all([cargarFeed(feedTipo), cargarPosts()]);
    renderProfile();
  } catch (e) { st.textContent = e.message; }
}

/* ── Seguir ── */
async function toggleSeguir(id, sigo) {
  try {
    await post('api/seguir.php', { id, accion: sigo ? 'dejar' : 'seguir' });
    await Promise.all([cargarPerfil(), cargarFeed(feedTipo), $('personas').classList.contains('active') ? buscarPersonas() : 0]);
    renderProfile();
  } catch (e) { alert(e.message); }
}

/* ── Personas ── */
function construirPersonas() {
  $('personas').querySelector('p.sub').insertAdjacentHTML('afterend', `
    <div class="card filtros"><div class="grid3">
      <div><label for="fInteres">Intereses</label><input id="fInteres" placeholder="Ej. música, fútbol, cine"></div>
      <div><label for="fCiudad">Ciudad de origen</label><input id="fCiudad" placeholder="Ej. Oruro"></div>
      <div><label for="fRol">Ver</label><select id="fRol"><option value="">Todos</option><option value="foraneo">Foráneos</option><option value="residente">Residentes</option></select></div>
    </div><div class="actions"><button class="primary" type="button" onclick="buscarPersonas()">Filtrar</button>
      <button class="secondary" type="button" onclick="['fInteres','fCiudad','fRol'].forEach(i=>setval(i,''));buscarPersonas()">Limpiar</button></div></div>
    <h2>Descubre personas</h2><div class="people-grid" id="personasGrid"></div>`);
  ['fInteres', 'fCiudad'].forEach(i => $(i).addEventListener('keydown', e => { if (e.key === 'Enter') buscarPersonas(); }));
}

async function buscarPersonas() {
  const qs = new URLSearchParams({ q: val('fInteres'), ciudad: val('fCiudad'), rol: $('fRol').value });
  try { personas = (await api('api/personas.php?' + qs)).personas; } catch (e) { return; }
  const box = $('personasGrid');
  if (!personas.length) { box.innerHTML = '<div class="empty">No encontramos personas con esos filtros.</div>'; return; }
  box.innerHTML = personas.map(p => `
    <div class="card">
      <div class="person" style="border:0;padding:0 0 10px"><div class="avatar">${inicial(p.nombre)}</div>
        <div class="info"><b>${esc(p.nombre)}</b><small>${rolTxt(p.rol)}${p.ciudad ? ' · ' + esc(p.ciudad) : ''}</small></div></div>
      ${p.comunes ? `<span class="tag">${p.comunes} interés(es) en común</span>` : ''}
      ${p.carrera ? `<p class="sub" style="margin:8px 0 0">${esc(p.carrera)}${p.universidad ? ' · ' + esc(p.universidad) : ''}</p>` : ''}
      <div>${p.intereses.map(t => `<span class="tag">${esc(t)}</span>`).join('')}</div>
      <p class="sub" style="margin:8px 0 0">${p.seguidores} seguidor(es)</p>
      <div class="actions">
        <button class="smallbtn ${p.sigo ? 'on' : ''}" onclick="toggleSeguir(${p.id},${p.sigo})">${p.sigo ? 'Siguiendo' : 'Seguir'}</button>
        ${p.amigo ? `<button class="smallbtn" onclick="openChat(${p.id})">Mensaje</button>`
                  : `<button class="smallbtn" onclick="agregarAmigo(${p.id})">Añadir amigo</button>`}
      </div>
    </div>`).join('');
}

async function agregarAmigo(id) {
  try { await post('api/amigos.php', { id }); await Promise.all([cargarAmigos(), buscarPersonas()]); } catch (e) { alert(e.message); }
}

/* ── Amigos y chat ── */
function openAddFriend() { $('friendModal').classList.add('open'); $('friendName').focus(); }
function closeAddFriend() { $('friendModal').classList.remove('open'); }

async function addFriend() {
  const busqueda = val('friendName');
  if (!busqueda) { alert('Escribe el nombre o correo de la persona.'); return; }
  try {
    await post('api/amigos.php', { busqueda, mensaje: val('friendNote') });
    setval('friendName', ''); setval('friendNote', '');
    closeAddFriend();
    await Promise.all([cargarAmigos(), buscarPersonas()]);
  } catch (e) { alert(e.message); }
}

async function cargarAmigos() {
  try { friends = (await api('api/amigos.php')).amigos; } catch (e) { return; }
  const box = $('friendsList');
  if (!friends.length) { box.innerHTML = '<div class="empty">Todavía no tienes amigos añadidos.</div>'; return; }
  box.innerHTML = friends.map(f => `
    <div class="person"><div class="avatar">${inicial(f.nombre)}</div>
      <div class="info"><b>${esc(f.nombre)}</b><small>${esc(f.ultimo || 'Sin mensajes todavía')}</small></div>
      <button onclick="openChat(${f.id})">Mensaje</button></div>`).join('');
}

function detenerChat() { clearInterval(pollTimer); pollTimer = null; }

async function openChat(id) {
  if (!friends.find(f => f.id === id)) await cargarAmigos();
  activeFriend = friends.find(f => f.id === id);
  if (!activeFriend) return;
  ultimosMsgs = 0;
  $('chatBox').style.display = 'block';
  $('chatTitle').textContent = 'Conversación con ' + activeFriend.nombre;
  await cargarMensajes();
  detenerChat();
  pollTimer = setInterval(cargarMensajes, 5000);
  $('chatBox').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

async function cargarMensajes() {
  if (!activeFriend) return;
  try {
    const { mensajes } = await api('api/mensajes.php?amigo_id=' + activeFriend.id);
    const box = $('messages');
    box.innerHTML = mensajes.map(m => `<div class="bubble ${m.yo ? 'me' : ''}">${esc(m.texto)}</div>`).join('');
    if (mensajes.length !== ultimosMsgs) { box.scrollTop = box.scrollHeight; ultimosMsgs = mensajes.length; }
  } catch (e) {}
}

async function sendMessage() {
  if (!activeFriend) return;
  const texto = $('chatInput').value.trim();
  if (!texto) return;
  try {
    await post('api/mensajes.php', { amigo_id: activeFriend.id, texto });
    $('chatInput').value = '';
    await cargarMensajes(); cargarAmigos();
  } catch (e) { alert(e.message); }
}

function contactRoom(name) {
  alert('Has seleccionado: ' + name + '. Aquí puedes conectar un formulario o chat con quien publica el cuarto.');
}

/* ── Lugares (Google Maps) ── */
function construirLugares() {
  $('lugares').innerHTML = `<h1>Lugares recomendados</h1>
    <p class="sub">Toca «Abrir en Google Maps» para ver opciones cerca de ti. Confirma horarios antes de visitar.</p>`
    + LUGARES.map(l => `<div class="card place"><div class="placepic">${PIN}</div><div>
        <b>${l[0]}</b><small>${l[1]}</small><div><span class="tag">${l[2]}</span></div>
        <a class="smallbtn" style="margin-top:10px" target="_blank" rel="noopener" href="${mapsUrl(l[3] + ' cerca de mí')}">Abrir en Google Maps</a></div></div>`).join('')
    + `<h2>Mis lugares favoritos</h2><p class="sub">Guarda los lugares que ya visitaste y te gustaron.</p>
      <div class="card"><label for="favNombre">Nombre del lugar</label><input id="favNombre" maxlength="120" placeholder="Ej. Mirador de la Cruz">
        <div class="grid2"><div><label for="favZona">Zona o ciudad</label><input id="favZona" maxlength="120" placeholder="Ej. Oruro"></div>
        <div><label for="favNota">Nota (opcional)</label><input id="favNota" maxlength="255" placeholder="¿Por qué te gustó?"></div></div>
        <div class="actions"><button class="primary" type="button" onclick="guardarFavorito()">Guardar lugar</button></div>
        <p class="sub" id="favStatus" style="margin:8px 0 0"></p></div><div id="favList"></div>`;
}

async function cargarFavoritos() {
  try { favs = (await api('api/lugares.php')).lugares; } catch (e) { return; }
  $('favList').innerHTML = favs.length ? favs.map(f => `
    <div class="card fav"><div><b>${esc(f.nombre)}</b><small style="display:block;color:var(--muted)">${esc([f.zona, f.nota].filter(Boolean).join(' · '))}</small>
      <a class="smallbtn" style="margin-top:10px" target="_blank" rel="noopener" href="${mapsUrl(f.nombre + ' ' + (f.zona || ''))}">Ver en Google Maps</a></div>
      <button class="secondary smallbtn" onclick="eliminarFavorito(${f.id})">Quitar</button></div>`).join('')
    : '<div class="empty">Aún no guardaste lugares favoritos.</div>';
}

async function guardarFavorito() {
  try {
    await post('api/lugares.php', { accion: 'agregar', nombre: val('favNombre'), zona: val('favZona'), nota: val('favNota') });
    ['favNombre', 'favZona', 'favNota'].forEach(i => setval(i, '')); $('favStatus').textContent = '';
    await cargarFavoritos();
  } catch (e) { $('favStatus').textContent = e.message; }
}
async function eliminarFavorito(id) {
  try { await post('api/lugares.php', { accion: 'eliminar', id }); await cargarFavoritos(); } catch (e) { alert(e.message); }
}

/* ── Visor de fotos ── */
function construirVisor() {
  document.body.insertAdjacentHTML('beforeend', `
    <div class="modal" id="visorModal" onclick="if(event.target===this)cerrarVisor()"><div class="modalbox photo-box">
      <button class="modal-close" type="button" aria-label="Cerrar" onclick="cerrarVisor()">×</button>
      <img id="visorImg" alt=""><div id="visorInfo" style="margin-top:12px"></div>
      <div class="photo-nav"><button class="secondary" type="button" onclick="navVisor(-1)">Anterior</button><button class="secondary" type="button" onclick="navVisor(1)">Siguiente</button></div>
    </div></div>`);
  document.addEventListener('keydown', e => {
    if (!$('visorModal').classList.contains('open')) return;
    if (e.key === 'Escape') cerrarVisor(); if (e.key === 'ArrowLeft') navVisor(-1); if (e.key === 'ArrowRight') navVisor(1);
  });
}
function abrirFoto(origen, id) {
  visor.lista = (origen === 'feed' ? feed : posts).filter(p => p.src);
  visor.i = Math.max(0, visor.lista.findIndex(p => p.id === id));
  mostrarVisor();
  $('visorModal').classList.add('open');
}
function mostrarVisor() {
  const p = visor.lista[visor.i];
  if (!p) return;
  $('visorImg').src = p.src;
  $('visorInfo').innerHTML = `<b>${esc(p.nombre || perfil.name || usuario.nombre)}</b> <small style="color:var(--muted)">· ${fecha(p.fecha)}</small>
    <p class="post-text" style="margin:6px 0 0">${esc(p.texto || p.caption || '')}</p>`;
}
function navVisor(d) { visor.i = (visor.i + d + visor.lista.length) % visor.lista.length; mostrarVisor(); }
function cerrarVisor() { $('visorModal').classList.remove('open'); }

/* ── Perfil y publicaciones ── */
async function cargarPerfil() { perfil = (await api('api/perfil.php')).perfil; }
async function cargarPosts() { posts = (await api('api/publicaciones.php')).posts; }

function openEditProfile() {
  ['name', 'age', 'city', 'bio', 'music', 'hobbies', 'food', 'plan'].forEach(k => setval('profile' + k.charAt(0).toUpperCase() + k.slice(1), perfil[k]));
  $('profileStatus').textContent = '';
  $('editProfileModal').classList.add('open');
}
function closeEditProfile() { $('editProfileModal').classList.remove('open'); }
function openAddPost() {
  setval('newPostCaption', ''); $('newPostPhotos').value = ''; $('newPostStatus').textContent = '';
  $('addPostModal').classList.add('open');
}
function closeAddPost() { $('addPostModal').classList.remove('open'); }

async function subirFotos(files, texto) {
  const fd = new FormData();
  files.forEach(f => fd.append('fotos[]', f));
  fd.append('tipo', 'foto'); fd.append('texto', texto || '');
  await api('api/publicaciones.php', { method: 'POST', body: fd });
  await cargarPosts();
}

async function publishPost() {
  const files = [...$('newPostPhotos').files].filter(f => f.type.startsWith('image/'));
  if (!files.length) { $('newPostStatus').textContent = 'Selecciona al menos una foto para publicar.'; return; }
  try { await subirFotos(files, val('newPostCaption')); renderProfile(); closeAddPost(); }
  catch (e) { $('newPostStatus').textContent = e.message; }
}

async function saveProfile() {
  try {
    perfil = (await post('api/perfil.php', {
      name: val('profileName'), age: val('profileAge'), city: val('profileCity'), bio: val('profileBio'),
      music: val('profileMusic'), hobbies: val('profileHobbies'), food: val('profileFood'), plan: val('profilePlan')
    })).perfil;
    const files = [...$('profilePhotos').files].filter(f => f.type.startsWith('image/'));
    if (files.length) await subirFotos(files, '');
    $('profilePhotos').value = '';
    await cargarPerfil(); renderProfile();
    $('profileStatus').textContent = 'Perfil guardado.';
    setTimeout(closeEditProfile, 650);
  } catch (e) { $('profileStatus').textContent = e.message; }
}

function tiles() {
  return posts.filter(p => p.src).map(p => `<div class="post-tile" onclick="abrirFoto('mias',${p.id})"><img src="${esc(p.src)}" alt="Publicación"></div>`).join('');
}
function renderPosts() {
  $('allPostsGrid').innerHTML = tiles();
  $('allPostsEmpty').style.display = posts.some(p => p.src) ? 'none' : 'block';
}
function renderProfile() {
  const nombre = perfil.name || usuario.nombre;
  $('publicName').textContent = nombre;
  $('publicBio').textContent = perfil.bio || 'Añade una descripción para que otras personas te conozcan.';
  $('profileAvatar').textContent = nombre.charAt(0).toUpperCase();
  if ($('meAvatar')) $('meAvatar').textContent = nombre.charAt(0).toUpperCase();
  $('publicTags').innerHTML = [perfil.city, perfil.music, perfil.hobbies, perfil.food, perfil.plan].filter(Boolean).map(t => `<span class="tag">${esc(t)}</span>`).join('');
  const st = document.querySelectorAll('.profile-stats b');
  st[0].textContent = posts.length; st[1].textContent = perfil.seguidores || 0; st[2].textContent = perfil.seguidos || 0;
  $('postsGrid').innerHTML = tiles();
  $('postsEmpty').style.display = posts.some(p => p.src) ? 'none' : 'block';
  renderPosts();
}

/* ── Arranque: verifica la sesión antes de mostrar nada ── */
(async function iniciar() {
  try { usuario = (await api('api/sesion.php')).usuario; } catch (e) { return; }

  const header = document.querySelector('header');
  const perfilBtn = header.querySelector('button');
  const grupo = document.createElement('div');
  grupo.style.cssText = 'display:flex;gap:8px;align-items:center';
  const salir = document.createElement('button');
  salir.type = 'button'; salir.textContent = 'Salir';
  salir.onclick = () => { location.href = 'api/logout.php'; };
  header.appendChild(grupo); grupo.append(perfilBtn, salir);

  construirInicio(); construirPersonas(); construirLugares(); construirVisor();
  $('friendName').placeholder = 'Nombre o correo de la persona';
  document.querySelector('#friendModal .sub').textContent = 'Busca a otra persona registrada en Aterriza.';

  try { await Promise.all([cargarPerfil(), cargarPosts()]); } catch (e) {}
  renderProfile();
  cargarFeed('');
  cargarAmigos();
})();
