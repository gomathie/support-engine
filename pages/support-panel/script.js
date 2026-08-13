const TREES = [
  { id:"login", q:"I can\u2019t log in", lay:"1\u20132", steps:[
    {t:"Admin \u2192 Failed Login Attempts. Are their attempts arriving at all?", l:1,
     fix:"No attempts logged = they aren\u2019t reaching the login they think they are. Wrong URL, wrong portal, or a cached page."},
    {t:"Blocked Users and Disabled Users.", l:1, fix:"Unblock, or find out who blocked them and why."},
    {t:"Password Reset history \u2014 someone may have reset it already.", l:1},
    {t:"Is the contract itself blocked for non-payment?", l:2, fix:"Layer 2. Route to finance rather than fixing the login."},
    {t:"Is it just them, or their whole company?", l:1, fix:"Everyone = layer 2. One person = layer 1."}
  ]},
  { id:"gone", q:"An object disappeared from my list", lay:"1\u20134", steps:[
    {t:"Does it exist in the admin panel at all?", l:4},
    {t:"Does this user have rights to it? Check their rights, not your view.", l:1,
     fix:"Master trap. It may never have been theirs to see."},
    {t:"Is a filter, group selection or column config hiding it?", l:3},
    {t:"Is Temporary blocking switched on?", l:4,
     fix:"Not a fault. A colleague may have suspended it to stop the subscription charge \u2014 seasonal equipment does this."},
    {t:"Was it moved to another contract, or deleted?", l:4,
     fix:"Deleting removes all data and history. Restoring needs a system administrator."}
  ]},
  { id:"numbers", q:"The fuel / mileage numbers are wrong", lay:"5\u20136", steps:[
    {t:"Open Sensors tracing. Is data arriving, and what is the raw value?", l:6,
     fix:"This one step decides everything below it."},
    {t:"Raw value wrong or absent?", l:6, fix:"Layer 6 \u2014 the device. Stop tuning formulas."},
    {t:"Raw value fine but display wrong \u2192 check the field mapping first.", l:5},
    {t:"Then the conversion formula \u2014 before the calibration table.", l:5,
     fix:"The formula runs the moment data arrives, before calibration. Debugging the table while a formula mangles its input is wasted time. Use the Test button rather than reasoning about it."},
    {t:"Then the calibration table: Select nearest value, and Calibration first.", l:5,
     fix:"These two switches quietly change results \u2014 a common cause of \u201cclose but wrong\u201d."},
    {t:"Numbers intermittently missing rather than wrong? Look for a division in the formula.", l:5,
     fix:"If the divisor can arrive as 0 or undefined the calculation errors. Guard it with a conditional."},
    {t:"Was it ever right? Check Audit history for a change on that date.", l:5,
     fix:"Never right = configuration. Recently wrong = a change, or the device."},
    {t:"After fixing: run Recalculate for the affected period.", l:5,
     fix:"Otherwise the customer\u2019s historical reports stay wrong and they call back."}
  ]},
  { id:"notif", q:"I\u2019m not getting notifications", lay:"2", steps:[
    {t:"Is the Notifications module active on the contract?", l:2,
     fix:"Without it there\u2019s no Notifications tab at all."},
    {t:"Does the notification actually exist?", l:2},
    {t:"Is the object or tag selected in it?", l:2,
     fix:"A vehicle added later isn\u2019t covered automatically."},
    {t:"Check the time window \u2014 time zone, days, intervals.", l:2,
     fix:"A rule set for weekdays 09:00\u201318:00 is silent on a Saturday and looks broken. The most-missed cause."},
    {t:"Check the geofence scope: anywhere / in selected zones / outside them.", l:2},
    {t:"Check the \u201cfor all users\u201d flag.", l:2,
     fix:"If off, colleagues don\u2019t get it \u2014 exactly the shape of \u201cmy manager gets them and I don\u2019t\u201d."},
    {t:"Check the delivery method: SMS, push, email, alert, control room, webhook, command, Telegram.", l:2},
    {t:"For email: is the address verified?", l:2,
     fix:"Unverified addresses silently receive nothing."},
    {t:"Spam folder.", l:2}
  ]},
  { id:"nodata", q:"No data from a vehicle", lay:"4\u20136", steps:[
    {t:"Sensors tracing \u2014 when was the last reception? What\u2019s the satellite count?", l:6},
    {t:"Connection Lost report \u2014 when, how long, and at what address?", l:6,
     fix:"Repeated drops at the same location is an installation or coverage story, not a platform one."},
    {t:"Is Temporary blocking on?", l:4, fix:"Free to check, and not a fault."},
    {t:"Does the device ID / IMEI match the physical unit? Is the device type right?", l:4},
    {t:"Has it ever reported?", l:6, fix:"Never = configuration or installation. Stopped = power, connection, or hardware."}
  ]},
  { id:"relay", q:"Data\u2019s fine in PILOT but missing in our own system", lay:"7", steps:[
    {t:"Is the endpoint enabled, and is object relay enabled?", l:7},
    {t:"External ID, Host, Port, Format, Path, credentials \u2014 all correct?", l:7},
    {t:"Statistics: is Queue growing?", l:7,
     fix:"Packets are accumulating and can\u2019t be delivered. The external server is unavailable, settings are wrong, or the far side is refusing the connection."},
    {t:"Statistics: is Err climbing?", l:7,
     fix:"Check availability, authentication, protocol, schedule, and any restrictions on the receiving side."},
    {t:"Statistics: Send rising but Ack stuck at zero?", l:7,
     fix:"We\u2019re transmitting and they never confirm. Protocol, External ID, JSON config, credentials."},
    {t:"New object not relaying?", l:7,
     fix:"Is an Auto add rule enabled for that contract, and was the object created after the rule?"},
    {t:"No statistics at all?", l:7,
     fix:"Usually benign \u2014 nothing sent yet, period too short, or the endpoint is new."}
  ]}
];

const MX = [
  { r:"Impact: high", sub:"whole contract / many customers", cells:[
    {p:"P1", ex:"Nobody at a contract can log in"},
    {p:"P2", ex:"Reports wrong fleet-wide, fleet still runs"},
    {p:"P3", ex:"Cosmetic, affects everyone"}]},
  { r:"Impact: medium", sub:"a group, or a VIP", cells:[
    {p:"P2", ex:"Reefer temperature alerts not firing"},
    {p:"P3", ex:"One depot\u2019s geofence reports wrong"},
    {p:"P4", ex:"A group wants a column added"}]},
  { r:"Impact: low", sub:"one user / one object", cells:[
    {p:"P3", ex:"One vehicle dark during a theft"},
    {p:"P4", ex:"One sensor mis-calibrated"},
    {p:"P5", ex:"How-to question"}]}
];

let S = { sym:null, state:{}, cust:"", obj:"", pri:null };

/* ---------- render ---------- */
const symsEl = document.getElementById('syms');
TREES.forEach(t => {
  const b = document.createElement('button');
  b.className = 'sym'; b.setAttribute('aria-pressed','false'); b.dataset.id = t.id;
  b.innerHTML = `<span class="q">\u201c${t.q}\u201d</span><span class="lay">layer ${t.lay}</span>`;
  b.addEventListener('click', () => { S.sym = S.sym === t.id ? null : t.id; S.state = {}; S.pri = S.pri; renderTree(); save(); });
  symsEl.appendChild(b);
});

function renderTree(){
  document.querySelectorAll('.sym').forEach(el =>
    el.setAttribute('aria-pressed', String(el.dataset.id === S.sym)));
  const box = document.getElementById('treebox');
  box.innerHTML = '';
  if(!S.sym){ note(); return; }
  const t = TREES.find(x => x.id === S.sym);
  const wrap = document.createElement('div');
  wrap.className = 'stepwrap';
  t.steps.forEach((st, i) => {
    const k = S.sym + ':' + i;
    const v = S.state[k] || '';
    const row = document.createElement('div');
    row.className = 'step' + (v === 'out' ? ' ruled' : v === 'found' ? ' found' : '');
    row.innerHTML = `
      <div class="num">${i+1}</div>
      <div class="body">
        <div class="t">${st.t}<span class="tag">L${st.l}</span></div>
        ${st.fix ? `<div class="fix">${st.fix}</div>` : ''}
        <div class="acts">
          <button class="mini${v==='out'?' on-out':''}" data-k="${k}" data-v="out">Ruled out</button>
          ${st.fix ? `<button class="mini${v==='found'?' on-found':''}" data-k="${k}" data-v="found">Cause found</button>` : ''}
        </div>
      </div>
    `;
    wrap.appendChild(row);
  });
  box.appendChild(wrap);
  wrap.querySelectorAll('.mini').forEach(b => {
    b.addEventListener('click', e => {
      e.stopPropagation();
      const tk = b.dataset.k, tv = b.dataset.v;
      S.state[tk] = S.state[tk] === tv ? null : tv;
      renderTree(); save();
    });
  });
  note();
}

function renderMx(){
  const b = document.getElementById('mx');
  b.innerHTML = '<div class="h"></div><div class="h">Urgency: high<br>now / blocking</div><div class="h">Urgency: medium<br>today / workaround</div><div class="h">Urgency: low<br>next week</div>';
  MX.forEach(r => {
    b.innerHTML += `<div class="rh"><b>${r.r}</b><br>${r.sub}</div>`;
    r.cells.forEach((c, i) => {
      const is = S.pri && S.pri.p === c.p && S.pri.ex === c.ex;
      b.innerHTML += `<div class="cell" aria-pressed="${is}" data-r="${r.r}" data-c="${i}" data-p="${c.p}" data-ex="${c.ex}">
        <div class="${c.p.toLowerCase()} p">${c.p}</div><div class="ex">${c.ex}</div>
      </div>`;
    });
  });
  b.querySelectorAll('.cell').forEach(c => {
    c.addEventListener('click', () => {
      const match = S.pri && S.pri.p === c.dataset.p && S.pri.ex === c.dataset.ex;
      S.pri = match ? null : { p: c.dataset.p, ex: c.dataset.ex, why: c.dataset.r + ', ' + ['high','medium','low'][c.dataset.c] + ' urgency' };
      renderMx(); note(); save();
    });
  });
}

function note(){
  const el = document.getElementById('out');
  if(!S.sym && !S.pri){ el.innerHTML = '<span class="hd">No symptom selected yet.</span>'; return; }
  const L = [];
  L.push('Customer : ' + (S.cust || '\u2014'));
  L.push('Object   : ' + (S.obj || '\u2014'));
  if(S.sym) L.push('Reported : "' + TREES.find(t => t.id === S.sym).q + '"');
  if(S.pri) L.push('Priority : ' + S.pri.p + '  (' + S.pri.why + ')');
  if(S.sym){
    const t = TREES.find(x => x.id === S.sym);
    const out = [], found = [];
    t.steps.forEach((st, i) => {
      const v = S.state[S.sym + ':' + i];
      if(v === 'out') out.push(st.t);
      if(v === 'found') found.push(st);
    });
    if(out.length){ L.push(''); L.push('Checked and ruled out:'); out.forEach(o => L.push('  - ' + o)); }
    if(found.length){
      L.push(''); L.push('Cause found:');
      found.forEach(f => { L.push('  * ' + f.t); if(f.fix) L.push('    > ' + f.fix); });
    }
    const done = out.length + found.length;
    if(done < t.steps.length){
      L.push(''); L.push('Still to check: ' + (t.steps.length - done) + ' of ' + t.steps.length);
    }
    if(!found.length && done === t.steps.length){
      L.push(''); L.push('All layers worked, no cause found \u2192 escalate.');
    }
  }
  L.push(''); L.push('Customer has been told:');
  L.push('  ');
  el.textContent = L.join('\n');
}

/* ---------- persistence ---------- */
async function save(){
  try{
    await window.storage.set('pilot-panel-case', JSON.stringify(S), false);
    stamp('saved ' + new Date().toLocaleTimeString());
  }catch(e){ stamp('not saved'); }
}
function stamp(t){ document.getElementById('saved').textContent = t; }

async function load(){
  try{
    const r = await window.storage.get('pilot-panel-case', false);
    if(r && r.value) S = Object.assign(S, JSON.parse(r.value));
  }catch(e){}
  document.getElementById('f-cust').value = S.cust || '';
  document.getElementById('f-obj').value = S.obj || '';
}

/* ---------- wiring ---------- */
document.querySelectorAll('.tab').forEach(t => t.addEventListener('click', () => {
  document.querySelectorAll('.tab').forEach(x => x.setAttribute('aria-selected','false'));
  t.setAttribute('aria-selected','true');
  document.querySelectorAll('.view').forEach(v => v.classList.remove('on'));
  document.getElementById('v-' + t.dataset.v).classList.add('on');
}));

['cust','obj'].forEach(f => document.getElementById('f-' + f).addEventListener('input', e => {
  S[f] = e.target.value; note(); save();
}));

document.getElementById('copy').addEventListener('click', async () => {
  const txt = document.getElementById('out').textContent;
  try{ await navigator.clipboard.writeText(txt); stamp('copied to clipboard'); }
  catch(e){
    const ta = document.createElement('textarea');
    ta.value = txt; document.body.appendChild(ta); ta.select();
    try{ document.execCommand('copy'); stamp('copied to clipboard'); }
    catch(_){ stamp('select the text above to copy'); }
    ta.remove();
  }
});

document.getElementById('fresh').addEventListener('click', () => {
  S = { sym:null, state:{}, cust:'', obj:'', pri:null };
  document.getElementById('f-cust').value = '';
  document.getElementById('f-obj').value = '';
  renderTree(); renderMx(); note(); save();
  stamp('new case started');
});

(async function(){
  await load();
  renderTree(); renderMx(); note();
})();
