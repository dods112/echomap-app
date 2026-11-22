<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Echo-Map</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.3/MarkerCluster.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.3/MarkerCluster.Default.css">
<link rel="stylesheet" href="{{ asset('css/echomap.css') }}">
<style>
[hidden]{display:none!important}
a.brand,a.primary,a.secondary{text-decoration:none}
.osm-dark{filter:invert(1) hue-rotate(180deg) brightness(.85) contrast(.9) saturate(.7)}
.leaflet-control-attribution{display:block!important}
.leaflet-control-layers{background:#202321!important;border:1px solid #3a3e3a!important;color:#d6d8d4!important}
.heatmap-toggle{left:58px!important;top:10px!important}
.pick-banner{position:absolute;z-index:600;top:14px;left:50%;transform:translateX(-50%);background:#252825;border:1px solid var(--accent);border-radius:12px;padding:9px 14px;font-size:12px;display:flex;gap:12px;align-items:center;box-shadow:0 10px 30px #0008}
.pick-banner button{border:1px solid #444844;background:#1b1d1c;color:#ccc;border-radius:8px;padding:5px 10px;font-size:11px}
.reactions button.on{border-color:var(--accent);background:#342825}
.panel-actions{justify-content:flex-start}
.auth-error{color:#e38484;font-size:11px;margin-top:12px}
</style>
</head>
<body>
<div class="app-shell">
  <header class="map-topbar">
    <a class="brand" href="/map"><span class="brand-mark"><i data-ic="pin" data-s="18"></i></span><span>Echo<span>&mdash;</span>Map</span></a>
    <div class="searchbox"><i data-ic="search" data-s="18"></i><input id="search" aria-label="Search memories" placeholder="Search places, memories..."></div>
    <button class="filter-btn" id="filterBtn"><i data-ic="tune" data-s="17"></i> Filters <span id="fcount" hidden></span></button>
    <button class="primary add-btn" id="addBtn"><i data-ic="plus" data-s="18"></i> Add Pin</button>
    @auth
      <button class="top-avatar" title="{{ auth()->user()->name }}" onclick="location='{{ route('profile.edit') }}'">{{ auth()->user()->initials }}</button>
      <form method="POST" action="{{ route('logout') }}" style="margin:0">@csrf<button class="secondary" style="height:40px;padding:0 14px;font-size:13px">Log out</button></form>
    @else
      <a class="secondary" style="height:40px;padding:0 14px;font-size:13px" href="{{ route('login') }}">Log in</a>
      <a class="primary small" href="{{ route('register') }}">Sign up</a>
    @endauth
  </header>

  <aside class="left-rail">
    <button class="active" title="Map"><i data-ic="map"></i></button>
    @auth<button title="Profile" onclick="location='{{ route('profile.edit') }}'"><i data-ic="user"></i></button>@endauth
    <span></span>
  </aside>

  <main class="map-canvas">
    <div id="map" class="leaflet-surface"></div>
    <button class="heatmap-toggle" id="heatBtn"><i data-ic="flame" data-s="17"></i><span id="heatLabel">Heatmap</span></button>
    <div class="pick-banner" id="banner" hidden><span>Click inside the dashed area to place your pin</span><button id="cancelPick">Cancel</button></div>
    <div class="map-empty" id="empty" hidden><i data-ic="search" data-s="26"></i><h3>No memories found</h3><p>Try changing your search, year, theme, or visibility filters.</p><button id="clearF">Clear all filters</button></div>
    <div class="timeline"><div class="timeline-head"><span>Timeline</span><b id="yearLabel"></b></div><div class="range-wrap"><input type="range" id="year"><div class="year-marks" id="marks"></div></div></div>
    <div class="filter-popover" id="filterPop" hidden>
      <div><b>Filter memories</b><button id="filterClose"><i data-ic="close" data-s="16"></i></button></div>
      <label>THEME &middot; SELECT MULTIPLE</label><div class="chips" id="themeFilter"></div>
      <label>VISIBILITY</label><div id="visFilter"></div>
      <button class="primary apply" id="applyF"></button>
    </div>
  </main>

  <aside class="memory-panel" id="panel" hidden></aside>
</div>

<div class="modal-backdrop" id="modal" hidden>
  <form class="add-modal" id="pinForm">
    <div class="modal-head"><div><span>NEW MEMORY</span><h2>Add a pin</h2></div><button type="button" id="closeModal"><i data-ic="close"></i></button></div>
    <div class="details-step">
      <div class="coordinate-readout" id="coords"></div>
      <label>PLACE NAME</label><input class="field-input" name="place" maxlength="255" required placeholder="e.g. Near the main gate">
      <label>MEMORY TITLE</label><input class="field-input" name="title" maxlength="70" required placeholder="Give this moment a title">
      <label>YOUR MEMORY</label><textarea name="body" id="bodyTxt" maxlength="500" required placeholder="What happened here? Tell the story..."></textarea><div class="char-count" id="cc">0 / 500</div>
      <label>ADD A PHOTO <span>OPTIONAL</span></label>
      <input type="file" id="photo" name="photo" accept="image/*" hidden>
      <button type="button" class="photo-upload" id="photoBtn"><i data-ic="upload"></i><b>Choose a photo</b><small>JPG, PNG or WEBP</small></button>
      <div class="upload-preview" id="prev" hidden><img id="prevImg" alt="Upload preview"><button type="button" id="prevX"><i data-ic="close" data-s="16"></i></button></div>
      <label>THEME</label><div class="theme-chips" id="themeChips"></div>
      <label>WHO CAN SEE THIS?</label><div class="privacy" id="privChips"></div>
      <div class="auth-error" id="errs" hidden></div>
      <div class="modal-foot"><button type="button" class="secondary" id="cancelForm">Cancel</button><button class="primary" type="submit"><i data-ic="pin" data-s="17"></i> Drop pin</button></div>
    </div>
  </form>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.3/leaflet.markercluster.js"></script>
<script>
const AUTH = @json(auth()->check());
const ME = @json(auth()->user()?->initials);
const CAMPUS = @json(config('campus'));
const csrf = document.querySelector('meta[name="csrf-token"]').content;
const THEMES = ['Friendship', 'Campus Life', 'History'];
const VIS = ['Public', 'Friends', 'Private'];
const VIS_HINT = { Private: 'Only you', Friends: 'People you follow', Public: 'Everyone' };
const COLORS = { 'Campus Life': '#efb85d', 'Friendship': '#f08a68', 'History': '#82a999' };
const SYMBOLS = ['\u2665', '\u2726', '\u263B'];
const IC = {
  map: '<path d="m3 6 5-3 8 3 5-3v15l-5 3-8-3-5 3Z"/><path d="M8 3v15M16 6v15"/>',
  search: '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
  plus: '<path d="M12 5v14M5 12h14"/>',
  tune: '<path d="M4 7h10M18 7h2M4 17h2M10 17h10"/><circle cx="16" cy="7" r="2"/><circle cx="8" cy="17" r="2"/>',
  flame: '<path d="M12 22c4.4 0 8-3.6 8-8 0-3.2-1.5-5.8-4.6-8.5.1 2-1 3.8-2.4 4.5.2-3.3-1.7-6.5-4.4-8C9 5.3 7 7.4 5.8 9.3 2.8 14 6.3 22 12 22Z"/>',
  user: '<circle cx="12" cy="8" r="4"/><path d="M4 22c.5-5 3-7 8-7s7.5 2 8 7"/>',
  close: '<path d="m6 6 12 12M18 6 6 18"/>',
  upload: '<path d="M12 16V3M7 8l5-5 5 5"/><path d="M4 15v5h16v-5"/>',
  lock: '<rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
  pin: '<path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>'
};
const ic = (n, s = 20) => `<svg class="icon" width="${s}" height="${s}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${IC[n] || ''}</svg>`;
const hydrate = root => root.querySelectorAll('[data-ic]').forEach(el => { el.outerHTML = ic(el.dataset.ic, +el.dataset.s || 20); });
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const $ = id => document.getElementById(id);
hydrate(document);

/* ---------- map (locked to the campus) ---------- */
const campusBounds = L.latLngBounds([CAMPUS.bounds.south, CAMPUS.bounds.west], [CAMPUS.bounds.north, CAMPUS.bounds.east]);
const map = L.map('map', { center: [CAMPUS.center.lat, CAMPUS.center.lng], zoom: CAMPUS.zoom, minZoom: CAMPUS.minZoom, maxZoom: 19, maxBounds: campusBounds, maxBoundsViscosity: 1.0 });
const osm = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, className: 'osm-dark', attribution: '&copy; OpenStreetMap contributors' }).addTo(map);
const sat = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { maxZoom: 19, attribution: 'Tiles &copy; Esri' });
L.control.layers({ 'Street': osm, 'Satellite': sat }, null, { position: 'topleft' }).addTo(map);
L.rectangle(campusBounds, { color: '#f08a68', weight: 1, fill: false, dashArray: '6 6', interactive: false }).addTo(map);

let cluster = null, heatLayer = null, pickMarker = null;
let pins = [], current = null, pickMode = false, picked = null, heat = false;
let selThemes = [], selVis = [...VIS], theme = 'Campus Life', privacy = 'Public';
const thisYear = new Date().getFullYear();
let year = thisYear, minYear = 2018;

const pinIcon = color => L.divIcon({ html: `<span style="--pin:${color}"><i></i></span>`, className: 'echo-pin', iconSize: L.point(36, 44), iconAnchor: L.point(18, 42) });

async function post(url, data, isForm) {
  const opts = { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' } };
  if (isForm) { opts.body = data; } else { opts.headers['Content-Type'] = 'application/json'; opts.body = JSON.stringify(data); }
  return fetch(url, opts);
}

async function load() {
  const r = await fetch('/api/pins', { headers: { Accept: 'application/json' } });
  pins = await r.json();
  minYear = Math.min(2018, ...pins.map(p => p.year));
  const slider = $('year');
  slider.min = minYear; slider.max = thisYear; slider.value = year;
  const mid = Math.round((minYear + thisYear) / 2);
  $('marks').innerHTML = `<span>${minYear}</span><span>${mid}</span><span>${thisYear}</span>`;
  render();
}

function visible() {
  const q = $('search').value.toLowerCase();
  return pins.filter(p => p.year <= year
    && `${p.title} ${p.place} ${p.theme}`.toLowerCase().includes(q)
    && (!selThemes.length || selThemes.includes(p.theme))
    && selVis.includes(p.visibility));
}

function render() {
  if (cluster) map.removeLayer(cluster);
  if (heatLayer) { map.removeLayer(heatLayer); heatLayer = null; }
  const list = visible();
  cluster = L.markerClusterGroup({
    showCoverageOnHover: false, maxClusterRadius: 55,
    iconCreateFunction: c => L.divIcon({ html: `<div><strong>${c.getChildCount()}</strong><span>memories</span></div>`, className: 'echo-cluster', iconSize: L.point(58, 58) })
  });
  list.forEach(p => {
    const m = L.marker([p.lat, p.lng], { icon: pinIcon(COLORS[p.theme] || '#f08a68'), title: p.title });
    m.bindTooltip(esc(p.title), { direction: 'top', offset: L.point(0, -35), className: 'echo-tooltip' });
    m.on('click', () => openPin(p.id));
    cluster.addLayer(m);
  });
  map.addLayer(cluster);
  if (heat) {
    heatLayer = L.layerGroup(list.map(p => L.circle([p.lat, p.lng], { radius: 85, stroke: false, fillColor: COLORS[p.theme] || '#f08a68', fillOpacity: .22, className: 'memory-heat' }))).addTo(map);
  }
  $('empty').hidden = list.length > 0;
  $('yearLabel').textContent = 'Up to ' + year;
  const n = selThemes.length + (selVis.length === VIS.length ? 0 : 1);
  $('fcount').hidden = n === 0; $('fcount').textContent = n;
  $('applyF').textContent = `Show ${list.length} ${list.length === 1 ? 'memory' : 'memories'}`;
}

/* ---------- filters ---------- */
function renderFilters() {
  $('themeFilter').innerHTML = `<button class="${selThemes.length ? '' : 'on'}" data-t="">All</button>` +
    THEMES.map(t => `<button class="${selThemes.includes(t) ? 'on' : ''}" data-t="${t}">${t}</button>`).join('');
  $('visFilter').innerHTML = VIS.map(v => `<div class="check-row"><button data-v="${v}" class="${selVis.includes(v) ? 'checked' : ''}">${selVis.includes(v) ? '\u2713' : ''}</button> ${v} memories</div>`).join('');
}
$('themeFilter').onclick = e => {
  const b = e.target.closest('button'); if (!b) return;
  const t = b.dataset.t;
  selThemes = t === '' ? [] : (selThemes.includes(t) ? selThemes.filter(x => x !== t) : [...selThemes, t]);
  renderFilters(); render();
};
$('visFilter').onclick = e => {
  const b = e.target.closest('button'); if (!b) return;
  const v = b.dataset.v;
  selVis = selVis.includes(v) ? selVis.filter(x => x !== v) : [...selVis, v];
  renderFilters(); render();
};
$('filterBtn').onclick = () => { $('filterPop').hidden = !$('filterPop').hidden; };
$('filterClose').onclick = $('applyF').onclick = () => { $('filterPop').hidden = true; };
$('clearF').onclick = () => { $('search').value = ''; selThemes = []; selVis = [...VIS]; year = thisYear; $('year').value = year; renderFilters(); render(); };
$('search').addEventListener('input', render);
$('year').addEventListener('input', e => { year = +e.target.value; render(); });
$('heatBtn').onclick = () => { heat = !heat; $('heatBtn').classList.toggle('active', heat); $('heatLabel').textContent = heat ? 'Heatmap on' : 'Heatmap'; render(); };

/* ---------- memory panel ---------- */
async function openPin(id) {
  const r = await fetch('/api/pins/' + id, { headers: { Accept: 'application/json' } });
  if (!r.ok) return;
  const p = await r.json();
  current = p;
  const color = COLORS[p.theme] || '#f08a68';
  const rx = SYMBOLS.map(s => `<button class="${(p.mine || []).includes(s) ? 'on' : ''}" onclick="react('${s}')">${s} <span>${(p.reactions || {})[s] || 0}</span></button>`).join('');
  const cm = p.comments.map(c => `<div class="comment"><span>${esc(c.initials)}</span><div><b>${esc(c.author)} <small>${esc(c.when)}</small></b><p>${esc(c.body)}</p></div></div>`).join('')
    || '<p class="no-comments">No comments yet. Add the first one.</p>';
  const form = AUTH
    ? `<form class="comment-form" onsubmit="sendComment(event)"><span>${esc(ME)}</span><input id="cbody" maxlength="240" placeholder="Add a comment..." required><button type="submit">Send</button></form>`
    : `<p class="no-comments"><a href="/login" style="color:var(--accent)">Log in</a> to comment and react.</p>`;
  const media = p.photo_url
    ? `<img class="memory-photo" src="${esc(p.photo_url)}" alt="${esc(p.title)}">`
    : `<div class="memory-placeholder">${ic('pin', 30)}<span>${esc(p.place)}</span></div>`;
  $('panel').hidden = false;
  $('panel').innerHTML =
    `<div class="panel-grab"></div><div class="panel-actions"><button onclick="closePanel()">${ic('close')}</button></div>` + media +
    `<div class="memory-body">` +
    `<div class="memory-meta"><span style="color:${color}">${esc(p.theme)}</span><i></i><span>${esc(p.date)}</span><i></i><span>${esc(p.visibility)}</span></div>` +
    `<h2>${esc(p.title)}</h2><div class="place">${ic('pin', 15)}${esc(p.place)}</div>` +
    `<p class="memory-text">${esc(p.body)}</p>` +
    `<div class="author"><span>${esc(p.initials)}</span><div><b>${esc(p.author)}</b><small>Shared ${esc(p.visibility.toLowerCase())}</small></div></div>` +
    `<div class="reactions">${rx}</div>` +
    `<div class="comments-head"><b>Comments</b><span>${p.comments.length}</span></div>${cm}${form}</div>`;
  setTimeout(() => map.invalidateSize(), 50);
  map.panTo([p.lat, p.lng]);
}
function closePanel() { $('panel').hidden = true; setTimeout(() => map.invalidateSize(), 50); }
async function react(symbol) {
  if (!AUTH) { location = '/login'; return; }
  const r = await post(`/api/pins/${current.id}/react`, { symbol });
  if (r.ok) openPin(current.id);
}
async function sendComment(e) {
  e.preventDefault();
  const body = $('cbody').value.trim(); if (!body) return;
  const r = await post(`/api/pins/${current.id}/comments`, { body });
  if (r.ok) openPin(current.id);
}

/* ---------- add pin ---------- */
function renderChoices() {
  $('themeChips').innerHTML = THEMES.map(t => `<button type="button" class="${theme === t ? 'active' : ''}" data-t="${t}">${t}</button>`).join('');
  $('privChips').innerHTML = VIS.map(v => `<button type="button" class="${privacy === v ? 'active' : ''}" data-v="${v}">${ic(v === 'Private' ? 'lock' : v === 'Friends' ? 'user' : 'map', 17)}<span><b>${v}</b><small>${VIS_HINT[v]}</small></span></button>`).join('');
}
$('themeChips').onclick = e => { const b = e.target.closest('button'); if (b) { theme = b.dataset.t; renderChoices(); } };
$('privChips').onclick = e => { const b = e.target.closest('button'); if (b) { privacy = b.dataset.v; renderChoices(); } };
$('bodyTxt').addEventListener('input', e => { $('cc').textContent = e.target.value.length + ' / 500'; });
$('photoBtn').onclick = () => $('photo').click();
$('photo').onchange = e => {
  const f = e.target.files[0];
  if (f && f.type.startsWith('image/')) { $('prevImg').src = URL.createObjectURL(f); $('prev').hidden = false; $('photoBtn').hidden = true; }
};
$('prevX').onclick = () => { $('photo').value = ''; $('prev').hidden = true; $('photoBtn').hidden = false; };

function stopPick() {
  pickMode = false; $('banner').hidden = true; map.getContainer().style.cursor = '';
  if (pickMarker) { map.removeLayer(pickMarker); pickMarker = null; }
}
function closeModal() { $('modal').hidden = true; $('pinForm').reset(); $('prev').hidden = true; $('photoBtn').hidden = false; $('cc').textContent = '0 / 500'; $('errs').hidden = true; stopPick(); }

$('addBtn').onclick = () => {
  if (!AUTH) { location = '/login'; return; }
  pickMode = true; $('banner').hidden = false; map.getContainer().style.cursor = 'crosshair';
};
$('cancelPick').onclick = stopPick;
$('closeModal').onclick = $('cancelForm').onclick = closeModal;
$('modal').onmousedown = e => { if (e.target === $('modal')) closeModal(); };

map.on('click', e => {
  if (!pickMode) return;
  if (!campusBounds.contains(e.latlng)) return;
  picked = e.latlng;
  if (pickMarker) map.removeLayer(pickMarker);
  pickMarker = L.marker(picked, { icon: L.divIcon({ html: '<span><i></i></span>', className: 'echo-pin picked', iconSize: L.point(40, 48), iconAnchor: L.point(20, 46) }) }).addTo(map);
  $('banner').hidden = true;
  $('coords').innerHTML = `<span>LAT ${picked.lat.toFixed(6)}</span><span>LNG ${picked.lng.toFixed(6)}</span>`;
  renderChoices();
  $('modal').hidden = false;
});

$('pinForm').onsubmit = async e => {
  e.preventDefault();
  $('errs').hidden = true;
  const fd = new FormData($('pinForm'));
  fd.append('lat', picked.lat); fd.append('lng', picked.lng);
  fd.append('theme', theme); fd.append('visibility', privacy);
  const r = await post('/api/pins', fd, true);
  if (r.ok) {
    const p = await r.json();
    closeModal();
    await load();
    openPin(p.id);
  } else {
    $('errs').hidden = false;
    if (r.status === 422) { const j = await r.json(); $('errs').textContent = Object.values(j.errors).flat().join(' '); }
    else { $('errs').textContent = 'Something went wrong (' + r.status + ').'; }
  }
};

renderFilters();
load();
</script>
</body>
</html>