<?php header('Content-Type: text/html; charset=utf-8'); ?><!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Panel — gdzie jesteśmy</title>
<style>
  :root{--bg:#0f172a;--card:#1c2842;--border:#2b3a5c;--text:#e2e8f0;--muted:#94a3b8;--accent:#38bdf8;--green:#34d399}
  *{box-sizing:border-box}
  body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;background:var(--bg);color:var(--text);
    min-height:100vh;display:flex;justify-content:center;padding:20px}
  .box{width:100%;max-width:460px}
  h1{font-size:19px;margin:0 0 4px}
  p.sub{color:var(--muted);font-size:13px;margin:0 0 18px}
  .card{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:16px;margin-bottom:14px}
  label{display:block;font-size:12px;color:var(--muted);margin:10px 0 4px;text-transform:uppercase;letter-spacing:.4px}
  input,select,textarea,button{width:100%;background:#0d1526;border:1px solid var(--border);color:var(--text);
    border-radius:8px;padding:10px;font-size:14px;font-family:inherit}
  textarea{resize:vertical;min-height:48px}
  .row2{display:flex;gap:8px}.row2>div{flex:1}
  button{cursor:pointer;font-weight:700;margin-top:14px}
  .btn-gps{background:#22304d;margin-top:8px}
  .btn-send{background:var(--green);color:#04231a;border-color:var(--green)}
  .btn-gps:hover{background:#2a3a60}
  .status{font-size:13px;margin-top:12px;padding:10px;border-radius:8px;display:none;line-height:1.45}
  .ok{background:#0e2a1e;border:1px solid #1f6b45;color:#6ee7b7;display:block}
  .err{background:#2a1414;border:1px solid #6b1f1f;color:#fca5a5;display:block}
  .now{font-size:13px;color:var(--muted);line-height:1.5}
  .now b{color:var(--accent)}
  a{color:var(--accent)}
</style>
</head>
<body>
<div class="box">
  <h1>📍 Gdzie jesteśmy</h1>
  <p class="sub">Zaktualizuj pozycję — znajomi zobaczą ją na <a href="index.html">mapie planu</a>.</p>

  <div class="card">
    <div class="now" id="current">Ładowanie aktualnej pozycji…</div>
  </div>

  <div class="card">
    <label>Token (Twoje hasło)</label>
    <input type="password" id="token" placeholder="wpisz token z config.php" autocomplete="current-password">

    <label>Szybki wybór przystanku</label>
    <select id="preset">
      <option value="">— wybierz albo użyj GPS / wpisz ręcznie —</option>
    </select>

    <button type="button" class="btn-gps" onclick="useGPS()">📡 Użyj GPS mojego telefonu</button>

    <div class="row2" style="margin-top:12px">
      <div><label>Szerokość (lat)</label><input id="lat" inputmode="decimal" placeholder="np. 44.858"></div>
      <div><label>Długość (lng)</label><input id="lng" inputmode="decimal" placeholder="np. 20.330"></div>
    </div>

    <label>Etykieta (krótko)</label>
    <input id="label" placeholder="np. Belgrad — Camp Dunav" maxlength="80">

    <label>Notatka (opcjonalnie)</label>
    <textarea id="note" placeholder="np. Postój na kawę, wszystko OK 🙂" maxlength="200"></textarea>

    <button type="button" class="btn-send" onclick="send()">Zapisz pozycję</button>
    <div class="status" id="status"></div>
  </div>
</div>

<script>
// przystanki (te same co na mapie)
const PRESETS = [
  ["Gdynia", 54.5189, 18.5305],
  ["Częstochowa", 50.8118, 19.1203],
  ["Granica Horgoš–Röszke", 46.16, 19.98],
  ["Belgrad — Camp Dunav", 44.858, 20.330],
  ["Granica Preševo–Tabanovce", 42.24, 21.68],
  ["Granica Bogorodica–Evzoni", 41.11, 22.53],
  ["Kalambaka / Meteory", 39.718, 21.623],
  ["Ateny", 37.9838, 23.7275],
  ["Kalamata — Camping Fare", 37.0213, 22.1434],
];
const sel = document.getElementById('preset');
PRESETS.forEach((p,i)=>{ const o=document.createElement('option'); o.value=i; o.textContent=p[0]; sel.appendChild(o); });
sel.addEventListener('change', ()=>{
  if(sel.value==='') return;
  const p=PRESETS[sel.value];
  document.getElementById('lat').value=p[1];
  document.getElementById('lng').value=p[2];
  document.getElementById('label').value=p[0];
});

function useGPS(){
  if(!navigator.geolocation){ alert('Brak GPS w przeglądarce'); return; }
  navigator.geolocation.getCurrentPosition(pos=>{
    document.getElementById('lat').value = pos.coords.latitude.toFixed(5);
    document.getElementById('lng').value = pos.coords.longitude.toFixed(5);
    if(!document.getElementById('label').value) document.getElementById('label').value='W drodze';
  }, err=>alert('Nie udało się pobrać GPS: '+err.message), {enableHighAccuracy:true, timeout:10000});
}

function rel(iso){
  const d=new Date(iso), s=(Date.now()-d)/1000;
  if(s<90) return 'przed chwilą';
  if(s<3600) return Math.round(s/60)+' min temu';
  if(s<86400) return Math.round(s/3600)+' h temu';
  return d.toLocaleString('pl-PL');
}
function loadCurrent(){
  fetch('where.php?t='+Date.now()).then(r=>r.json()).then(d=>{
    const el=document.getElementById('current');
    if(!d){ el.textContent='Jeszcze nie ustawiono pozycji.'; return; }
    el.innerHTML='Teraz: <b>'+(d.label||'w drodze')+'</b><br>'+d.lat+', '+d.lng+' · '+rel(d.updated)+(d.note?'<br>„'+d.note+'”':'');
  }).catch(()=>{ document.getElementById('current').textContent='Nie mogę pobrać pozycji.'; });
}
loadCurrent();

function send(){
  const st=document.getElementById('status');
  const body=new URLSearchParams({
    token: document.getElementById('token').value,
    lat: document.getElementById('lat').value,
    lng: document.getElementById('lng').value,
    label: document.getElementById('label').value,
    note: document.getElementById('note').value,
  });
  fetch('where.php', {method:'POST', body}).then(r=>r.json()).then(d=>{
    if(d.ok){ st.className='status ok'; st.textContent='✔ Zapisano! Znajomi już to widzą na mapie.'; loadCurrent(); }
    else { st.className='status err'; st.textContent='✖ '+(d.error||'Błąd zapisu'); }
  }).catch(e=>{ st.className='status err'; st.textContent='✖ Błąd sieci: '+e.message; });
}
</script>
</body>
</html>
