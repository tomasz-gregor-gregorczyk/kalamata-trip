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
  .hint{font-size:11.5px;color:var(--muted);line-height:1.45;margin:8px 0 0}
  .btn-add{background:var(--accent);color:#04202e;border-color:var(--accent)}
  .post{display:flex;gap:10px;padding:9px 0;border-bottom:1px solid var(--border);align-items:flex-start}
  .post:last-child{border-bottom:none}
  .post img{width:56px;height:56px;object-fit:cover;border-radius:8px;flex:none;background:#0d1526}
  .post .meta{flex:1;min-width:0;font-size:12px;line-height:1.45}
  .post .meta b{color:var(--text);font-weight:600}
  .post .meta span{color:var(--muted);font-size:11px}
  .post .del{width:auto;margin:0;padding:5px 9px;font-size:11px;background:#2a1414;
    border-color:#6b1f1f;color:#fca5a5;flex:none}
  .thumbwrap{margin-top:10px;display:none}
  .thumbwrap img{max-width:100%;border-radius:8px;display:block}
</style>
</head>
<body>
<div class="box">
  <h1>📍 Gdzie jesteśmy</h1>
  <p class="sub">Zaktualizuj pozycję — znajomi zobaczą ją na <a href="index.html">mapie planu</a>.<br>
  Jeśli działa OwnTracks, współrzędne lecą same; panel przydaje się do <b>notatki</b> albo do nadpisania pozycji ręcznie.</p>

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

  <h1 style="font-size:17px;margin:24px 0 4px">📷 Dziennik z trasy</h1>
  <p class="sub">Wpisy i zdjęcia pojawiają się na mapie jako znaczniki. Widzą je wszyscy,
  dodawać i kasować możesz tylko Ty.</p>

  <div class="card">
    <label>Zdjęcie (opcjonalnie)</label>
    <input type="file" id="photo" accept="image/*" onchange="pickPhoto()">
    <div class="thumbwrap" id="thumbwrap"><img id="thumb" alt=""></div>
    <p class="hint" id="photoinfo"></p>

    <label>Opis</label>
    <textarea id="ptext" placeholder="np. Meteory o zachodzie — warto było wstać" maxlength="600"></textarea>

    <div class="row2" style="margin-top:12px">
      <div><label>Lat</label><input id="plat" inputmode="decimal" placeholder="z EXIF lub bieżąca"></div>
      <div><label>Lng</label><input id="plng" inputmode="decimal" placeholder="z EXIF lub bieżąca"></div>
    </div>
    <button type="button" class="btn-gps" onclick="usePhotoGPS()">📡 Wstaw moją bieżącą pozycję</button>

    <button type="button" class="btn-add" onclick="addPost()">Dodaj wpis na mapę</button>
    <div class="status" id="pstatus"></div>
  </div>

  <div class="card">
    <div class="now" style="margin-bottom:6px">Wpisy na mapie</div>
    <div id="postlist" class="now">Ładowanie…</div>
  </div>
</div>

<script>
// przystanki (te same co na mapie)
const PRESETS = [
  ["Gdynia", 54.5189, 18.5305],
  ["MOP Woźniki Zachód (A1)", 50.5980, 19.0000],
  ["Granica Horgoš–Röszke", 46.16, 19.98],
  ["Belgrad — Camp Dunav", 44.858, 20.330],
  ["Granica Preševo–Tabanovce", 42.24, 21.68],
  ["Granica Bogorodica–Evzoni", 41.11, 22.53],
  ["Neos Marmaras — Christos House", 40.0850, 23.7930],
  ["Kalamata — Camping Fare", 37.0213, 22.1434],
  ["Ateny", 37.9838, 23.7275],
  ["Skopje", 41.9981, 21.4254],
  ["Budapeszt", 47.4979, 19.0402],
  ["Częstochowa", 50.8118, 19.1203],
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
    const src = d.src==='auto' ? ' · 📡 automat (OwnTracks)' : (d.src==='manual' ? ' · ✍️ ręcznie' : '');
    const place=esc(d.place||'');
    const what = esc(d.label||'') || (place ? 'w okolicy: '+place : 'w drodze');
    el.innerHTML='Teraz: <b>'+what+'</b><br>'+d.lat+', '+d.lng+' · '+rel(d.updated)+src+(d.note?'<br>„'+d.note+'”':'');
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

// ---------------------------------------------------------------- dziennik
// EXIF czytamy w przeglądarce, PRZED zmniejszeniem zdjęcia — canvas i tak by go wyrzucił.
// Dzięki temu nie potrzebujemy rozszerzenia exif po stronie PHP.
function parseExif(buf){
  try{
    const dv=new DataView(buf);
    if(dv.getUint16(0)!==0xFFD8) return null;              // nie JPEG
    let off=2, tiff=-1;
    while(off+4<dv.byteLength){
      const m=dv.getUint16(off);
      if((m&0xFF00)!==0xFF00) break;
      if(m===0xFFE1 && dv.getUint32(off+4)===0x45786966){ tiff=off+10; break; }
      off+=2+dv.getUint16(off+2);
    }
    if(tiff<0) return null;
    const le=dv.getUint16(tiff)===0x4949;
    const U16=o=>dv.getUint16(o,le), U32=o=>dv.getUint32(o,le);
    if(U16(tiff+2)!==42) return null;
    const rat=p=>{const d=U32(p+4); return d?U32(p)/d:0;};
    let gps=0, exif=0;
    const ifd0=tiff+U32(tiff+4), n0=U16(ifd0);
    for(let i=0;i<n0;i++){
      const e=ifd0+2+i*12, tag=U16(e);
      if(tag===0x8825) gps=tiff+U32(e+8);
      if(tag===0x8769) exif=tiff+U32(e+8);
    }
    const res={lat:null,lng:null,taken:''};
    if(exif){
      const n=U16(exif);
      for(let i=0;i<n;i++){
        const e=exif+2+i*12;
        if(U16(e)===0x9003){                                // DateTimeOriginal
          const p=tiff+U32(e+8); let t='';
          for(let k=0;k<19;k++) t+=String.fromCharCode(dv.getUint8(p+k));
          res.taken=t;
        }
      }
    }
    if(gps){
      const g={}, n=U16(gps);
      for(let i=0;i<n;i++){
        const e=gps+2+i*12, tag=U16(e);
        if(tag===1||tag===3) g[tag]=String.fromCharCode(dv.getUint8(e+8));
        if(tag===2||tag===4){
          const p=tiff+U32(e+8);
          g[tag]=[rat(p),rat(p+8),rat(p+16)];
        }
      }
      if(g[2]&&g[4]){
        const dms=v=>v[0]+v[1]/60+v[2]/3600;
        res.lat=dms(g[2])*(g[1]==='S'?-1:1);
        res.lng=dms(g[4])*(g[3]==='W'?-1:1);
      }
    }
    return res;
  }catch(e){ return null; }
}

// Zmniejszamy przed wysyłką: 4 MB z aparatu w roamingu to wieczność, a i tak
// pokazujemy to w dymku na mapie.
function downscale(file,maxPx,q){
  return new Promise((resolve,reject)=>{
    const img=new Image(), url=URL.createObjectURL(file);
    img.onload=()=>{
      URL.revokeObjectURL(url);
      let w=img.naturalWidth, h=img.naturalHeight;
      const s=Math.min(1,maxPx/Math.max(w,h));
      w=Math.max(1,Math.round(w*s)); h=Math.max(1,Math.round(h*s));
      const c=document.createElement('canvas'); c.width=w; c.height=h;
      c.getContext('2d').drawImage(img,0,0,w,h);
      c.toBlob(b=>b?resolve(b):reject(new Error('Nie mogę przetworzyć obrazu')),'image/jpeg',q);
    };
    img.onerror=()=>{URL.revokeObjectURL(url); reject(new Error('Nie mogę odczytać obrazu'));};
    img.src=url;
  });
}

let photoBlob=null, photoTaken='';
function pickPhoto(){
  const f=document.getElementById('photo').files[0];
  const info=document.getElementById('photoinfo');
  const wrap=document.getElementById('thumbwrap');
  photoBlob=null; photoTaken=''; wrap.style.display='none';
  if(!f){ info.textContent=''; return; }
  info.textContent='Przetwarzam…';
  f.arrayBuffer().then(buf=>{
    const ex=parseExif(buf);
    if(ex&&ex.lat!=null){
      document.getElementById('plat').value=ex.lat.toFixed(5);
      document.getElementById('plng').value=ex.lng.toFixed(5);
    }
    if(ex&&ex.taken) photoTaken=ex.taken;
    return downscale(f,1600,0.82);
  }).then(blob=>{
    photoBlob=blob;
    document.getElementById('thumb').src=URL.createObjectURL(blob);
    wrap.style.display='block';
    const kb=Math.round(blob.size/1024), org=Math.round(f.size/1024);
    const hasGps=document.getElementById('plat').value!=='';
    info.innerHTML=org+' KB → <b>'+kb+' KB</b>'+
      (hasGps?' · 📍 pozycja z EXIF' : ' · brak GPS w zdjęciu — użyję bieżącej pozycji')+
      (photoTaken?' · 🕐 '+photoTaken:'');
  }).catch(e=>{ info.textContent='Błąd: '+e.message; });
}

function usePhotoGPS(){
  if(!navigator.geolocation){ alert('Brak GPS w przeglądarce'); return; }
  navigator.geolocation.getCurrentPosition(pos=>{
    document.getElementById('plat').value=pos.coords.latitude.toFixed(5);
    document.getElementById('plng').value=pos.coords.longitude.toFixed(5);
  }, err=>alert('Nie udało się pobrać GPS: '+err.message), {enableHighAccuracy:true,timeout:10000});
}

function addPost(){
  const st=document.getElementById('pstatus');
  const fd=new FormData();
  fd.append('action','post');
  fd.append('token',document.getElementById('token').value);
  fd.append('text',document.getElementById('ptext').value);
  fd.append('lat',document.getElementById('plat').value);
  fd.append('lng',document.getElementById('plng').value);
  fd.append('taken',photoTaken);
  if(photoBlob) fd.append('photo',photoBlob,'photo.jpg');
  st.className='status'; st.style.display='block'; st.textContent='Wysyłam…';
  fetch('api.php',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
    if(d.ok){
      st.className='status ok'; st.textContent='✔ Dodano — znacznik jest już na mapie.';
      document.getElementById('ptext').value='';
      document.getElementById('photo').value='';
      document.getElementById('thumbwrap').style.display='none';
      document.getElementById('photoinfo').textContent='';
      photoBlob=null; photoTaken='';
      loadPosts();
    } else { st.className='status err'; st.textContent='✖ '+(d.error||'Błąd zapisu'); }
  }).catch(e=>{ st.className='status err'; st.textContent='✖ Błąd sieci: '+e.message; });
}

function esc(t){const d=document.createElement('div');d.textContent=t||'';return d.innerHTML;}
function loadPosts(){
  fetch('api.php?action=posts&t='+Date.now()).then(r=>r.json()).then(list=>{
    const el=document.getElementById('postlist');
    if(!Array.isArray(list)||!list.length){ el.textContent='Jeszcze nic tu nie ma.'; return; }
    el.innerHTML=list.slice().reverse().map(p=>
      '<div class="post">'+
      (p.photo?'<img src="'+esc(p.photo)+'" alt="">':'')+
      '<div class="meta"><b>'+(esc(p.text)||'(bez opisu)')+'</b><br>'+
      '<span>'+p.lat+', '+p.lng+' · '+esc(p.taken||p.created||'')+'</span></div>'+
      '<button type="button" class="del" onclick="delPost(\''+esc(p.id)+'\')">Usuń</button>'+
      '</div>').join('');
  }).catch(()=>{ document.getElementById('postlist').textContent='Nie mogę pobrać wpisów.'; });
}
function delPost(id){
  if(!confirm('Usunąć ten wpis razem ze zdjęciem?')) return;
  const body=new URLSearchParams({action:'post_delete',token:document.getElementById('token').value,id:id});
  fetch('api.php',{method:'POST',body}).then(r=>r.json()).then(d=>{
    const st=document.getElementById('pstatus'); st.style.display='block';
    if(d.ok){ st.className='status ok'; st.textContent='✔ Usunięto.'; loadPosts(); }
    else { st.className='status err'; st.textContent='✖ '+(d.error||'Błąd'); }
  });
}
loadPosts();
</script>
</body>
</html>
