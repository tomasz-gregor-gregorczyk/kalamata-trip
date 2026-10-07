<?php header('Content-Type: text/html; charset=utf-8'); ?><!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex">
<title>Wydatki — Kalamata</title>
<style>
  :root{--bg:#0f172a;--card:#1c2842;--border:#2b3a5c;--text:#e2e8f0;--muted:#94a3b8;--accent:#38bdf8;--green:#34d399;--red:#f87171;--amber:#fbbf24}
  *{box-sizing:border-box}
  body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;background:var(--bg);color:var(--text);
    min-height:100vh;display:flex;justify-content:center;padding:20px 16px}
  .box{width:100%;max-width:760px}
  h1{font-size:19px;margin:0 0 4px}
  h2{font-size:15px;margin:0 0 10px}
  p.sub{color:var(--muted);font-size:13px;margin:0 0 18px}
  .card{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:16px;margin-bottom:14px}
  label{display:block;font-size:12px;color:var(--muted);margin:10px 0 4px;text-transform:uppercase;letter-spacing:.4px}
  input,select,button{width:100%;background:#0d1526;border:1px solid var(--border);color:var(--text);
    border-radius:8px;padding:10px;font-size:14px;font-family:inherit}
  .row2{display:flex;gap:8px}.row2>div{flex:1;min-width:0}
  button{cursor:pointer;font-weight:700;margin-top:14px}
  .btn-add{background:var(--green);color:#04231a;border-color:var(--green)}
  .btn-sec{background:#22304d}
  .btn-sec:hover{background:#2a3a60}
  .status{font-size:13px;margin-top:12px;padding:10px;border-radius:8px;display:none;line-height:1.45}
  .ok{background:#0e2a1e;border:1px solid #1f6b45;color:#6ee7b7;display:block}
  .err{background:#2a1414;border:1px solid #6b1f1f;color:#fca5a5;display:block}
  .back{display:inline-flex;align-items:center;gap:7px;margin-bottom:14px;
    padding:8px 14px 8px 11px;border-radius:999px;text-decoration:none;
    background:var(--card);border:1px solid var(--border);color:var(--text);
    font-size:13px;font-weight:700}
  .back:hover{background:#22304d;border-color:var(--accent)}
  .back span{font-size:16px;line-height:1;color:var(--accent)}
  .hint{font-size:11.5px;color:var(--muted);line-height:1.45;margin:8px 0 0}

  .total{display:flex;justify-content:space-between;align-items:baseline;gap:10px;flex-wrap:wrap}
  .total b{font-size:26px;color:var(--green)}
  .total span{font-size:13px;color:var(--muted)}
  .cat{display:grid;grid-template-columns:1fr auto;gap:2px 10px;font-size:13px;padding:7px 0;border-bottom:1px solid var(--border)}
  .cat:last-child{border-bottom:none}
  .cat .v{text-align:right;font-variant-numeric:tabular-nums}
  .cat .v small{color:var(--muted)}
  .bar{grid-column:1/-1;height:5px;background:#0d1526;border-radius:3px;overflow:hidden}
  .bar i{display:block;height:100%;background:var(--accent)}
  .bar i.over{background:var(--red)}

  /* arkusz */
  .sheetwrap{overflow-x:auto;margin:0 -16px;padding:0 16px}
  table{width:100%;border-collapse:collapse;font-size:13px;min-width:620px}
  th{text-align:left;font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.4px;
    padding:6px 4px;border-bottom:1px solid var(--border);font-weight:600}
  td{padding:3px 2px;border-bottom:1px solid var(--border);vertical-align:middle}
  td input,td select{padding:6px 6px;font-size:13px;border-color:transparent;background:transparent;border-radius:6px}
  td input:hover,td select:hover{border-color:var(--border)}
  td input:focus,td select:focus{border-color:var(--accent);background:#0d1526;outline:none}
  td.num input{text-align:right;font-variant-numeric:tabular-nums}
  td.pln{text-align:right;white-space:nowrap;font-variant-numeric:tabular-nums;padding-right:6px;color:var(--muted)}
  td.x button{width:auto;margin:0;padding:4px 8px;font-size:12px;background:transparent;border-color:transparent;color:var(--muted)}
  td.x button:hover{color:var(--red);border-color:#6b1f1f}
  tr.saving td{opacity:.6}
  tr.failed td.pln{color:var(--red)}
  tr.day td{padding:10px 4px 4px;border-bottom:none;font-size:11px;color:var(--accent);font-weight:700;
    text-transform:uppercase;letter-spacing:.4px}
  tr.day td span{float:right;color:var(--muted);font-weight:600}
  tr.day td button{width:auto;margin:0 0 0 8px;padding:1px 7px;font-size:11px;font-weight:600;background:transparent;
    border-color:var(--border);color:var(--muted);text-transform:none;letter-spacing:0}
  tr.day td button:hover{color:var(--red);border-color:#6b1f1f}
  .empty{color:var(--muted);font-size:13px;padding:12px 0}
  .rates{display:grid;grid-template-columns:repeat(auto-fill,minmax(110px,1fr));gap:8px}
  .rates label{margin-top:0}
  #lock{display:none}
  #imp-list td{font-size:12.5px;padding:5px 4px}
  #imp-list td input[type=checkbox]{width:auto;margin:0 4px}
  #imp-list td select{padding:4px;font-size:12.5px}
  #imp-list tr.dup td,#imp-list tr.off td{opacity:.45}
  #imp-list td.amt{text-align:right;white-space:nowrap;font-variant-numeric:tabular-nums}
  .impbar{display:flex;gap:8px;align-items:end;flex-wrap:wrap}
  .impbar>div{flex:1;min-width:140px}
  .impbar button{width:auto;flex:0 0 auto;padding:10px 14px}
  input.past{border-color:var(--amber);color:var(--amber)}
</style>
</head>
<body>
<div class="box">
  <a class="back" href="index.html"><span>&lsaquo;</span> Wróć do mapy i planu</a>
  <h1>💶 Wydatki</h1>
  <p class="sub">Wpisuj kwotę w walucie, w której płacisz. Arkusz przelicza na złotówki
  i porównuje z kalkulatorem z planu. Widzisz to tylko Ty, z tokenem.</p>

  <div class="card" id="lock">
    <label>Token (ten sam co do panelu)</label>
    <input type="password" id="token" autocomplete="current-password" placeholder="wpisz token z config.php">
    <button type="button" class="btn-add" onclick="unlock()">Otwórz arkusz</button>
    <div class="status" id="lstatus"></div>
  </div>

  <div id="app" style="display:none">
    <div class="card">
      <h2>Nowy wydatek</h2>
      <label>Co</label>
      <input id="n-name" placeholder="np. Tankowanie Orlen Woźniki" maxlength="120" enterkeyhint="next">
      <div class="row2">
        <div><label>Kwota</label><input id="n-amount" inputmode="decimal" placeholder="0,00" enterkeyhint="done"></div>
        <div><label>Waluta</label><select id="n-cur"></select></div>
      </div>
      <div class="row2">
        <div><label>Kategoria</label><select id="n-cat"></select></div>
        <div><label>Data</label><input id="n-date" type="date"></div>
      </div>
      <button type="button" class="btn-add" onclick="addItem()">Dodaj</button>
      <div class="status" id="nstatus"></div>
    </div>

    <div class="card">
      <div class="total"><b id="sum">0 zł</b><span id="sumplan"></span></div>
      <div id="cats" style="margin-top:10px"></div>
    </div>

    <div class="card">
      <h2>Arkusz</h2>
      <p class="hint" style="margin:-4px 0 8px">Kliknij komórkę (także datę), żeby poprawić. Zapis leci sam po wyjściu z pola.</p>
      <div class="sheetwrap">
        <table>
          <thead><tr><th style="width:128px">Data</th><th>Co</th><th style="width:130px">Kategoria</th>
            <th style="width:90px;text-align:right">Kwota</th><th style="width:70px">Wal.</th>
            <th style="width:90px;text-align:right">zł</th><th style="width:34px"></th></tr></thead>
          <tbody id="rows"></tbody>
        </table>
      </div>
      <div class="empty" id="empty">Jeszcze nic. Pierwszy wpis to pewnie winiety albo Amber One.</div>
      <button type="button" class="btn-sec" onclick="exportCsv()">⤓ Eksport CSV (Excel / Numbers)</button>
      <button type="button" class="btn-sec" style="color:var(--red)" onclick="clearAll()">🗑 Wyczyść wszystkie wydatki</button>
      <button type="button" class="btn-sec" id="restore" style="display:none" onclick="restoreBackup()"></button>
    </div>

    <div class="card">
      <h2>Import z Revoluta</h2>
      <p class="hint" style="margin:-4px 0 4px">W aplikacji: konto → ⋯ → Wyciąg → format Excel (CSV), okres wyjazdu.
      Wgraj plik, sprawdź listę i kategorie, zaimportuj. Te same transakcje drugi raz się nie dodadzą,
      więc możesz wgrywać cały wyciąg co kilka dni.</p>
      <input type="file" id="imp-file" accept=".csv,text/csv" style="margin-top:8px">
      <div id="imp-box" style="display:none">
        <div class="impbar">
          <div><label>Tylko od dnia</label><input type="date" id="imp-from"></div>
          <button type="button" class="btn-add" id="imp-go" onclick="runImport()">Importuj</button>
        </div>
        <div class="sheetwrap" style="margin-top:10px">
          <table style="min-width:520px"><thead><tr><th style="width:30px"></th><th style="width:88px">Data</th><th>Opis</th>
            <th style="width:130px">Kategoria</th><th style="width:110px;text-align:right">Kwota</th></tr></thead>
            <tbody id="imp-list"></tbody></table>
        </div>
      </div>
      <div class="status" id="istatus"></div>
    </div>

    <div class="card">
      <h2>Kursy (ile zł za 1 jednostkę)</h2>
      <div class="rates" id="rates"></div>
      <button type="button" class="btn-sec" onclick="saveRates()">Zapisz kursy</button>
      <p class="hint">Zmiana kursu przelicza wszystkie wpisy w tej walucie. Jeśli płacisz kartą,
      możesz po powrocie wstawić kurs z wyciągu.</p>
      <div class="status" id="rstatus"></div>
    </div>

    <button type="button" class="btn-sec" style="margin-bottom:30px" onclick="lock()">🔒 Zamknij arkusz na tym urządzeniu</button>
  </div>
</div>

<script>
const CURS = ['PLN','EUR','CZK','HUF','RSD','MKD'];
// Plan = domyślne wartości kalkulatora z index.html (paliwo 5 956 km × 10 l × 9,20 zł,
// opłaty 770 zł × 2, noclegi w drodze: Camp Dunav 2 × 130 zł + Ateny 300 zł, MOP i Larisa za darmo, + Christos House 2 × 36,25 € + 30 €
// + kemping Stavros 3 × 35 € + Kalamata 18 × 25 €, kurs 4,40).
// Po zmianie założeń w kalkulatorze popraw też te liczby.
const CATS = [
  ['Paliwo',           5480],
  ['Opłaty i winiety', 1540],
  ['Noclegi',          3453],
  ['Jedzenie',         null],
  ['Zakupy',           null],
  ['Atrakcje',         null],
  ['Internet',         null],
  ['Inne',             null],
];
const PLAN_TOTAL = CATS.reduce((s,c)=>s+(c[1]||0),0);

let TOKEN='', ITEMS=[], RATES={PLN:1}, BACKUP=0;
const $=id=>document.getElementById(id);
const esc=s=>String(s).replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
const zl=n=>Math.round(n).toLocaleString('pl-PL')+' zł';
const num=s=>parseFloat(String(s).replace(/\s/g,'').replace(',','.'));
const pln=it=>(+it.amount||0)*(RATES[it.cur]||0);
function today(){ const d=new Date(); d.setMinutes(d.getMinutes()-d.getTimezoneOffset()); return d.toISOString().slice(0,10); }
function ls(k,v){ try{ if(v===undefined) return localStorage.getItem(k); if(v===null) localStorage.removeItem(k); else localStorage.setItem(k,v); }catch(e){ return null; } }
function msg(id,text,ok){ const e=$(id); e.className='status '+(ok?'ok':'err'); e.textContent=text; if(ok) setTimeout(()=>{ e.className='status'; },2500); }

function opts(list,sel){ return list.map(v=>'<option'+(v===sel?' selected':'')+'>'+esc(v)+'</option>').join(''); }
const CAT_NAMES=CATS.map(c=>c[0]);

async function api(params){
  const body=new URLSearchParams(Object.assign({token:TOKEN},params));
  const r=await fetch('api.php',{method:'POST',body});
  const d=await r.json().catch(()=>({error:'Serwer zwrócił coś dziwnego ('+r.status+')'}));
  if(!r.ok||d.error){ const e=new Error(d.error||('HTTP '+r.status)); e.status=r.status; throw e; }
  return d;
}

// ---- token ----
async function unlock(){
  TOKEN=$('token').value.trim();
  if(!TOKEN) return;
  try{ await load(); ls('kalamata_admin',TOKEN); }
  catch(e){ msg('lstatus',e.status===403?'Zły token.':e.message,false); }
}
function lock(){ ls('kalamata_admin',null); location.reload(); }

async function load(){
  const d=await api({action:'costs'});
  ITEMS=d.items||[]; RATES=Object.assign({PLN:1},d.rates||{}); BACKUP=d.backup||0;
  $('lock').style.display='none'; $('app').style.display='block';
  renderRates(); render();
}

// ---- formularz ----
function initForm(){
  $('n-cur').innerHTML=opts(CURS,ls('costs_cur')||'PLN');
  $('n-cat').innerHTML=opts(CAT_NAMES,ls('costs_cat')||'Paliwo');
  $('n-date').value=today();
  $('n-date').addEventListener('change',markDate);
  markDate();
  $('n-name').addEventListener('keydown',e=>{ if(e.key==='Enter'){ e.preventDefault(); $('n-amount').focus(); } });
  $('n-amount').addEventListener('keydown',e=>{ if(e.key==='Enter'){ e.preventDefault(); addItem(); } });
}
// Wpis z innego dnia niż dziś świeci się na żółto, żeby nie dopisać dzisiejszych wydatków pod wczoraj.
function markDate(){
  const v=$('n-date').value;
  $('n-date').classList.toggle('past',!!v&&v!==today());
}
async function addItem(){
  const name=$('n-name').value.trim(), amount=num($('n-amount').value);
  if(!name){ msg('nstatus','Wpisz, co to było.',false); $('n-name').focus(); return; }
  if(!(amount>0)){ msg('nstatus','Wpisz kwotę.',false); $('n-amount').focus(); return; }
  const it={name,amount,cur:$('n-cur').value,cat:$('n-cat').value,date:$('n-date').value||today()};
  try{
    const d=await api(Object.assign({action:'cost'},it));
    ITEMS.push(d.item);
    ls('costs_cur',it.cur); ls('costs_cat',it.cat);
    $('n-name').value=''; $('n-amount').value='';
    msg('nstatus','Dodane'+(it.date!==today()?' ('+it.date+')':'')+': '+name+' — '+zl(pln(d.item)),true);
    render(); $('n-name').focus();
  }catch(e){ msg('nstatus',e.message,false); }
}

// ---- podsumowanie ----
function renderSummary(){
  const by={}; let total=0;
  ITEMS.forEach(it=>{ const v=pln(it); total+=v; const c=CAT_NAMES.includes(it.cat)?it.cat:'Inne'; by[c]=(by[c]||0)+v; });
  $('sum').textContent=zl(total);
  $('sumplan').textContent='plan przejazdu i noclegów: '+zl(PLAN_TOTAL);
  $('cats').innerHTML=CATS.filter(([c,p])=>p||by[c]).map(([c,p])=>{
    const v=by[c]||0, pct=p?Math.min(100,v/p*100):0;
    return '<div class="cat"><span>'+esc(c)+'</span><span class="v">'+zl(v)+
      (p?' <small>/ '+zl(p)+'</small>':'')+'</span>'+
      (p?'<div class="bar"><i class="'+(v>p?'over':'')+'" style="width:'+pct+'%"></i></div>':'')+'</div>';
  }).join('');
}

// ---- arkusz ----
function render(){
  renderSummary();
  const sorted=ITEMS.slice().sort((a,b)=>(b.date||'').localeCompare(a.date||'')||(b.id||'').localeCompare(a.id||''));
  $('empty').style.display=sorted.length?'none':'block';
  let html='', lastDay=null;
  sorted.forEach(it=>{
    if(it.date!==lastDay){
      lastDay=it.date;
      const dayTotal=sorted.filter(x=>x.date===it.date).reduce((s,x)=>s+pln(x),0);
      const label=new Date(it.date+'T12:00').toLocaleDateString('pl-PL',{weekday:'short',day:'numeric',month:'numeric'});
      html+='<tr class="day"><td colspan="7">'+esc(label)+'<span><b>'+zl(dayTotal)+'</b>'+
        '<button type="button" data-clearday="'+esc(it.date)+'" title="Usuń wszystkie wydatki z tego dnia">wyczyść dzień</button></span></td></tr>';
    }
    html+='<tr data-id="'+esc(it.id)+'">'+
      '<td><input type="date" data-f="date" value="'+esc(it.date)+'"></td>'+
      '<td><input data-f="name" maxlength="120" value="'+esc(it.name)+'"></td>'+
      '<td><select data-f="cat">'+opts(CAT_NAMES.includes(it.cat)?CAT_NAMES:CAT_NAMES.concat([it.cat]),it.cat)+'</select></td>'+
      '<td class="num"><input data-f="amount" inputmode="decimal" value="'+esc(String(it.amount).replace('.',','))+'"></td>'+
      '<td><select data-f="cur">'+opts(CURS,it.cur)+'</select></td>'+
      '<td class="pln">'+zl(pln(it))+'</td>'+
      '<td class="x"><button type="button" title="Usuń" data-del>✕</button></td></tr>';
  });
  $('rows').innerHTML=html;
  $('restore').style.display=BACKUP?'block':'none';
  $('restore').textContent='↺ Przywróć stan sprzed ostatniego czyszczenia ('+BACKUP+' wpisów)';
}

$('rows').addEventListener('change',async e=>{
  const f=e.target.dataset.f; if(!f) return;
  const tr=e.target.closest('tr'), it=ITEMS.find(x=>x.id===tr.dataset.id); if(!it) return;
  const next=Object.assign({},it,{[f]:f==='amount'?num(e.target.value):e.target.value});
  if(f==='amount'&&!(next.amount>0)){ e.target.value=String(it.amount).replace('.',','); return; }
  if(f==='name'&&!next.name.trim()){ e.target.value=it.name; return; }
  tr.classList.add('saving');
  try{
    const d=await api({action:'cost',id:it.id,date:next.date,name:next.name,cat:next.cat,amount:next.amount,cur:next.cur});
    Object.assign(it,d.item);
    // Zmiana daty przestawia wiersz, a kwoty/waluty — sumy; reszta nie wymaga przerysowania,
    // dzięki czemu Tab przechodzi do następnej komórki bez gubienia fokusu.
    if(f==='date') render();
    else { tr.classList.remove('saving','failed'); tr.querySelector('.pln').textContent=zl(pln(it)); renderSummary(); renderDayTotals(); }
  }catch(err){ tr.classList.remove('saving'); tr.classList.add('failed'); tr.querySelector('.pln').textContent='nie zapisano'; alert(err.message); }
});
function renderDayTotals(){
  document.querySelectorAll('#rows tr.day').forEach(tr=>{
    let s=0, n=tr.nextElementSibling;
    while(n&&!n.classList.contains('day')){ const it=ITEMS.find(x=>x.id===n.dataset.id); if(it) s+=pln(it); n=n.nextElementSibling; }
    tr.querySelector('span b').textContent=zl(s);
  });
}
$('rows').addEventListener('click',async e=>{
  const day=e.target.dataset.clearday;
  if(day){
    const list=ITEMS.filter(x=>x.date===day);
    if(!confirm('Usunąć wszystkie wydatki z '+day+' ('+list.length+' wpisów, '+zl(list.reduce((s,x)=>s+pln(x),0))+')?')) return;
    try{ const d=await api({action:'cost_clear',date:day}); ITEMS=ITEMS.filter(x=>x.date!==day); BACKUP=d.backup||BACKUP; afterClear(); }
    catch(err){ alert(err.message); }
    return;
  }
  if(!e.target.hasAttribute('data-del')) return;
  const tr=e.target.closest('tr'), it=ITEMS.find(x=>x.id===tr.dataset.id); if(!it) return;
  if(!confirm('Usunąć „'+it.name+'” ('+zl(pln(it))+')?')) return;
  try{ await api({action:'cost_delete',id:it.id}); ITEMS=ITEMS.filter(x=>x!==it); render(); }
  catch(err){ alert(err.message); }
});

async function clearAll(){
  if(!ITEMS.length) return;
  if(!confirm('Usunąć wszystkie wydatki ('+ITEMS.length+' wpisów, '+zl(ITEMS.reduce((s,x)=>s+pln(x),0))+')? Kursy zostają.')) return;
  try{ const d=await api({action:'cost_clear'}); ITEMS=[]; BACKUP=d.backup||BACKUP; afterClear(); }
  catch(err){ alert(err.message); }
}
async function restoreBackup(){
  if(!confirm('Przywrócić '+BACKUP+' wpisów z kopii? Obecne wpisy ('+ITEMS.length+') trafią do kopii, więc to też da się cofnąć.')) return;
  try{ const d=await api({action:'cost_restore'}); ITEMS=d.items||[]; BACKUP=d.backup||0; afterClear(); }
  catch(err){ alert(err.message); }
}
// Po zmianie zawartości arkusza podgląd importu musi na nowo wiedzieć, co już jest zaimportowane.
function afterClear(){
  const known=new Set(ITEMS.map(x=>x.src).filter(Boolean));
  IMP.forEach(x=>{ const dup=known.has(x.src); if(x.dup&&!dup) x.on=x.okCur&&!REV_SKIP.test(x.type)&&!REV_SKIP.test(x.name); x.dup=dup; });
  render(); if(IMP.length) renderImport();
}

// ---- kursy ----
function renderRates(){
  $('rates').innerHTML=CURS.filter(c=>c!=='PLN').map(c=>
    '<div><label>'+c+'</label><input inputmode="decimal" data-rate="'+c+'" value="'+String(RATES[c]||'').replace('.',',')+'"></div>').join('');
}
async function saveRates(){
  const r={}; document.querySelectorAll('[data-rate]').forEach(i=>{ const v=num(i.value); if(v>0) r[i.dataset.rate]=v; });
  try{ const d=await api({action:'cost_rates',rates:JSON.stringify(r)}); RATES=Object.assign({PLN:1},d.rates); renderRates(); render(); msg('rstatus','Kursy zapisane.',true); }
  catch(e){ msg('rstatus',e.message,false); }
}

// ---- import z Revoluta ----
// Wyciąg CSV z Revoluta: Type, Product, Started Date, Completed Date, Description, Amount, Fee,
// Currency, State, Balance (w polskiej wersji aplikacji nagłówki mogą być po polsku —
// stąd kilka nazw na kolumnę). Kwota wydatku jest ujemna, opłata dodatnia.
const REV_COLS = {
  type:  ['type','typ'],
  date:  ['started date','data rozpoczęcia','completed date','data zakończenia','date','data'],
  desc:  ['description','opis'],
  amount:['amount','kwota'],
  fee:   ['fee','opłata','prowizja'],
  cur:   ['currency','waluta'],
  state: ['state','stan','status'],
};
// Przelewy, doładowania i wymiany walut to przesuwanie pieniędzy, nie wydatek — domyślnie odznaczone.
const REV_SKIP = /transfer|topup|top-up|exchange|przelew|doładowanie|wymiana/i;
const REV_BAD_STATE = /revert|declin|fail|cofni|odrzuc|anulow/i;
// Zgadywanie kategorii po nazwie sprzedawcy; i tak da się poprawić przed importem.
const CAT_GUESS = [
  ['Paliwo',           /orlen|shell|\bbp\b|omv|\bmol\b|lukoil|circle ?k|\beko\b|avin|aegean|revoil|elin|petrol|benzin|nis |makpetrol|\bina\b|tankstel|fuel|gas station|stacja/i],
  ['Opłaty i winiety', /toll|vinet|winiet|matrica|znamk|e-?vignette|putevi|autoput|autocest|nea odos|egnatia|olympia odos|moreas|attiki|kentriki|aodos|motorway|autostrad|parking|ferry|prom/i],
  ['Internet',         /e-?sim|airalo|holafly|nomad|ubigi|saily|yesim|roaming|cosmote|vodafone|\bwind\b|nova\b|t-?mobile|orange|\bplay\b|plus ?gsm|starlink|wi-?fi/i],
  ['Noclegi',          /booking|airbnb|hotel|camping|kemping|apartment|apartament|rooms|studios|villa|hostel|guest ?house|pension/i],
  ['Jedzenie',         /restaur|taverna|tavern|cafe|caf[eé]|coffee|bakery|piekar|pizz|grill|gyros|souvlaki|bistro|bar\b|mcdonald|kfc|burger|lidl|carrefour|sklavenitis|masoutis|\bab\b|kritikos|my market|bazaar|spar|billa|tesco|penny|kaufland|biedronka|żabka|zabka|market|food/i],
  ['Atrakcje',         /museum|muzeum|ticket|bilet|tour|beach|archaeolog|castle|zamek|park/i],
];
function guessCat(desc){ const g=CAT_GUESS.find(([,re])=>re.test(desc)); return g?g[0]:'Inne'; }

function parseCsv(text){
  text=text.replace(/^\uFEFF/,'');
  const first=text.split(/\r?\n/)[0]||'';
  const sep=(first.match(/;/g)||[]).length>(first.match(/,/g)||[]).length?';':',';
  const rows=[]; let row=[], cell='', q=false;
  for(let i=0;i<text.length;i++){
    const c=text[i];
    if(q){ if(c==='"'){ if(text[i+1]==='"'){ cell+='"'; i++; } else q=false; } else cell+=c; }
    else if(c==='"') q=true;
    else if(c===sep){ row.push(cell); cell=''; }
    else if(c==='\n'||c==='\r'){ if(c==='\r'&&text[i+1]==='\n') i++; row.push(cell); rows.push(row); row=[]; cell=''; }
    else cell+=c;
  }
  if(cell!==''||row.length){ row.push(cell); rows.push(row); }
  return rows.filter(r=>r.some(x=>x.trim()!==''));
}
const revNum=s=>{ s=String(s||'').replace(/\s/g,''); if(/,\d{1,2}$/.test(s)) s=s.replace(/\./g,'').replace(',','.'); else s=s.replace(/,/g,''); return parseFloat(s)||0; };
function revDate(s){
  s=String(s||'').trim(); let m;
  if((m=s.match(/^(\d{4})-(\d{2})-(\d{2})/))) return m[1]+'-'+m[2]+'-'+m[3];
  if((m=s.match(/^(\d{1,2})[./](\d{1,2})[./](\d{4})/))) return m[3]+'-'+m[2].padStart(2,'0')+'-'+m[1].padStart(2,'0');
  return '';
}

let IMP=[];
$('imp-file').addEventListener('change',async e=>{
  const f=e.target.files[0]; if(!f) return;
  $('imp-box').style.display='none'; IMP=[];
  try{
    const rows=parseCsv(await f.text());
    const head=(rows.shift()||[]).map(h=>h.trim().toLowerCase());
    const col={}; for(const k in REV_COLS){ col[k]=REV_COLS[k].map(n=>head.indexOf(n)).find(i=>i>=0); }
    if(col.date===undefined||col.amount===undefined||col.desc===undefined)
      throw new Error('Nie rozpoznaję kolumn. Nagłówek pliku: '+head.join(' | '));
    const known=new Set(ITEMS.map(x=>x.src).filter(Boolean));
    rows.forEach(r=>{
      const raw=revNum(r[col.amount]); if(!(raw<0)) return;           // tylko obciążenia
      const cur=(col.cur!==undefined?r[col.cur]:'PLN').trim().toUpperCase();
      const desc=(r[col.desc]||'').trim(), date=revDate(r[col.date]);
      const type=col.type!==undefined?r[col.type]||'':'', state=col.state!==undefined?r[col.state]||'':'';
      if(!date||REV_BAD_STATE.test(state)) return;
      const fee=col.fee!==undefined?Math.abs(revNum(r[col.fee])):0;
      const amount=Math.round((-raw+fee)*100)/100;
      const src='rev|'+String(r[col.date]).trim()+'|'+raw+'|'+cur+'|'+desc;
      const dup=known.has(src), okCur=CURS.includes(cur);
      IMP.push({date,name:desc||type||'Revolut',amount,cur,cat:guessCat(desc),src,dup,okCur,
                on:!dup&&okCur&&!REV_SKIP.test(type)&&!REV_SKIP.test(desc),type});
    });
    if(!IMP.length) throw new Error('W pliku nie ma żadnych obciążeń.');
    IMP.sort((a,b)=>a.date.localeCompare(b.date));
    const firstItem=ITEMS.map(x=>x.date).sort()[0];
    $('imp-from').value=firstItem||IMP[0].date;
    $('imp-box').style.display='block'; renderImport();
  }catch(err){ msg('istatus',err.message,false); }
  e.target.value='';
});
$('imp-from').addEventListener('change',renderImport);
function impVisible(){ const from=$('imp-from').value; return IMP.filter(x=>!from||x.date>=from); }
function renderImport(){
  const vis=impVisible();
  $('imp-list').innerHTML=vis.map(x=>{
    const i=IMP.indexOf(x), note=x.dup?' <small>(już jest)</small>':!x.okCur?' <small>(waluta spoza arkusza)</small>':'';
    return '<tr class="'+(x.dup?'dup':x.on?'':'off')+'" data-i="'+i+'">'+
      '<td><input type="checkbox" data-k="on"'+(x.on?' checked':'')+(x.dup||!x.okCur?' disabled':'')+'></td>'+
      '<td>'+esc(x.date.slice(5).split('-').reverse().join('.'))+'</td>'+
      '<td>'+esc(x.name)+(x.type?' <small style="color:var(--muted)">'+esc(x.type)+'</small>':'')+note+'</td>'+
      '<td><select data-k="cat">'+opts(CAT_NAMES,x.cat)+'</select></td>'+
      '<td class="amt">'+esc(String(x.amount).replace('.',','))+' '+esc(x.cur)+'</td></tr>';
  }).join('')||'<tr><td colspan="5" class="empty">Nic od tego dnia.</td></tr>';
  const n=vis.filter(x=>x.on&&!x.dup).length;
  $('imp-go').textContent='Importuj ('+n+')'; $('imp-go').disabled=!n;
}
$('imp-list').addEventListener('change',e=>{
  const k=e.target.dataset.k, tr=e.target.closest('tr'); if(!k||!tr) return;
  const x=IMP[+tr.dataset.i]; x[k]=k==='on'?e.target.checked:e.target.value;
  if(k==='on') renderImport();
});
async function runImport(){
  const sel=impVisible().filter(x=>x.on&&!x.dup);
  if(!sel.length) return;
  $('imp-go').disabled=true;
  try{
    const d=await api({action:'cost_import',items:JSON.stringify(sel.map(({date,name,cat,amount,cur,src})=>({date,name,cat,amount,cur,src})))});
    ITEMS=ITEMS.concat(d.added);
    const srcs=new Set(d.added.map(x=>x.src)); IMP.forEach(x=>{ if(srcs.has(x.src)) x.dup=true; });
    render(); renderImport();
    msg('istatus','Zaimportowane: '+d.added.length+(d.skipped?' (pominięte: '+d.skipped+')':'')+'. Popraw w arkuszu, jeśli coś trzeba.',true);
  }catch(err){ msg('istatus',err.message,false); $('imp-go').disabled=false; }
}

// ---- CSV ----
function exportCsv(){
  const q=s=>'"'+String(s).replace(/"/g,'""')+'"', n=v=>String(Math.round(v*100)/100).replace('.',',');
  const lines=[['Data','Co','Kategoria','Kwota','Waluta','Kurs','PLN'].join(';')];
  ITEMS.slice().sort((a,b)=>(a.date||'').localeCompare(b.date||'')).forEach(it=>{
    lines.push([it.date,q(it.name),q(it.cat),n(it.amount),it.cur,String(RATES[it.cur]||0).replace('.',','),n(pln(it))].join(';'));
  });
  // BOM, żeby Excel nie zrobił krzaków z polskich znaków; średnik, bo polski Excel tak dzieli kolumny.
  const blob=new Blob(['﻿'+lines.join('\r\n')],{type:'text/csv;charset=utf-8'});
  const a=document.createElement('a'); a.href=URL.createObjectURL(blob); a.download='wydatki-kalamata.csv'; a.click();
  setTimeout(()=>URL.revokeObjectURL(a.href),1000);
}

initForm();
TOKEN=ls('kalamata_admin')||'';
if(TOKEN) load().catch(e=>{ TOKEN=''; $('lock').style.display='block'; if(e.status===403) ls('kalamata_admin',null); msg('lstatus',e.status===403?'Token wygasł albo się zmienił — wpisz go ponownie.':e.message,false); });
else $('lock').style.display='block';
$('token').addEventListener('keydown',e=>{ if(e.key==='Enter') unlock(); });
</script>
</body>
</html>
