const DATA = [
  {
    id: "prep", flag: "PREP", title: "Before delivery \u2014 information to gather (blocks Modules C & D)",
    days: [
      { n:"Blocking", t:"Needed before Module C or D can be taught", topics:"These are the company-specific gaps. Most need answers from team leads, not from the trainer — the Skills Module now proposes an industry-standard default for each; confirm them, and escalate anything unresolved to your head of technical support. Ordered by how much they block.",
        items:[
          "Ticketing system: name, required fields, priority definitions, response targets",
          "Team contact map \u2014 names, channels, ownership boundaries \u2014 including who owns layer 7 / relay tickets",
          "Escalation triggers and the out-of-hours path",
          "Does 1st line retain customer contact after escalation?",
          "Approval path for admin actions (new contracts, module activation, tariff changes)",
          "Incident procedure \u2014 who gets alerted when several customers report the same issue",
          "Where known issues / solutions are documented",
          "Investigation time limit before escalation"
        ] },
      { n:"Data protection", t:"Needed before Module D5 can be taught", topics:"Which regime applies depends on where our customers operate \u2014 Ghana (Act 843), EU/UK (GDPR), Gulf states. This needs a real answer, not a placeholder.",
        items:[
          "Which data-protection regimes apply to our contracts; controller vs processor in each",
          "Is admin-panel access logged and reviewed? (Trainees must be told plainly either way)",
          "Where does a data-subject request from a driver get routed?",
          "Policy on ad-hoc surveillance-shaped requests (\u201cwhere was driver X on Saturday?\u201d)",
          "Retention policy, and who authorises deletion of an object",
          "Our answer on private/personal mode for out-of-hours vehicle use"
        ] },
      { n:"Materials", t:"Practical setup", topics:"Without these, the highest-value exercises cannot run at all.",
        items:[
          "10\u201312 anonymized past tickets (routing + queue-sorting exercises)",
          "Breakable test environment with admin-panel visibility",
          "Test user with restricted rights (for the Master-trap drill)",
          "Trainer briefed on our actual data-protection policy before the D5 role-plays"
        ] }
    ]
  },
  {
    id: "support", flag: "TRACK 1", title: "1st-line support — 2 week plan",
    days: [
      { n:"Day 1", t:"Introduction to PILOT and basic concepts", topics:"Object, Sensor, Contract, Account, Alert, Geofence; roles (User / Admin / Super Admin).",
        items:["Define: Object, Sensor, Contract, Account","Explain Personal Account vs Admin Panel","Explain what a Mapping Contract is and its use case"] },
      { n:"Day 2", t:"Interface and navigation", topics:"Top panel, workspace, map, map tools.",
        items:["Log in to the system","Describe each element of the top panel","Change map type to Yandex Sat","Measure distance between two points on the map"] },
      { n:"Day 3", t:"User and rights management", topics:"Creating users, assigning rights to objects/labels, rights templates.",
        items:["Create a new user with role \u201cUser\u201d","Assign rights to 2 test objects","Create a rights template for the role","Block, then unblock, the created user"] },
      { n:"Day 4", t:"Working with objects (part 1)", topics:"Object card, mandatory fields, device IMEI.",
        items:["Manually create a new object (car) with General + Info filled in","Assign a tag and place it in a group","Find \u201cCurrent Track\u201d and \u201cFollow Object\u201d via right-click menu"] },
      { n:"Day 5", t:"Working with objects (part 2) and object list", topics:"Filtering, sorting, columns, groups, color indicators.",
        items:["Configure color status indicators","Create group \u201cTest Vehicles\u201d and move objects into it","Configure columns: Name, Speed, Status, Driver","Filter list to objects \u201cin motion\u201d"] },
      { n:"Day 6", t:"Sensors (part 1)", topics:"Sensor types by purpose and by principle of operation.",
        items:["Add an ignition sensor (two-position); verify via Points tab","Add a fuel level sensor (discrete) and fill main parameters"] },
      { n:"Day 7", t:"Sensors (part 2)", topics:"Calibration tables, formulas, sensor templates.",
        items:["Set up a 3\u20134 point calibration table for the fuel sensor","Apply formula /1000 to the battery voltage sensor","Save sensor config as a template and apply it to another object"] },
      { n:"Day 8", t:"History and reports", topics:"Track/player/events/graph, report types and parameters.",
        items:["Build 24h movement history for an object; review with the player","Create a \u201cMileage and Stops\u201d report for yesterday","Save the report in PDF and Excel formats"] },
      { n:"Day 9", t:"Contract settings and notifications", topics:"Password/email settings, 2FA, notification types.",
        items:["Add a test email in contract settings and send confirmation","Activate \u201cSpeed Limit Exceeded\u201d email notifications","Enable and configure 2FA for the test user (if available)"] },
      { n:"Day 10", t:"Additional modules and tools", topics:"Modular architecture, report scheduler, tokens.",
        items:["Describe the Notifications module and its requirements","Explain what a Token is and when it's used","Describe what a user can do with the Report Scheduler"] },
      { n:"Day 11\u201312", t:"Comprehensive review and call simulation", topics:"Applying knowledge to realistic support scenarios.",
        items:["Scenario: user can't log in \u2014 walk through diagnostic steps","Scenario: object missing from list \u2014 walk through diagnostic steps","Scenario: speed notifications not received \u2014 walk through diagnostic steps","Scenario: give partner temporary access without an account \u2014 explain token solution"] },
      { n:"Day 13\u201314", t:"Final testing and consultation", topics:"Assessment and open Q&A.",
        items:["Complete the final test","Attend error review / Q&A session","Resolve one non-standard case using documentation"] }
    ]
  },
  {
    id: "admin", flag: "TRACK 2", title: "Admin panel — 3 day plan",
    days: [
      { n:"Day 1", t:"Interface familiarization and basic operations", topics:"Panel structure, personal settings, contracts.",
        items:["Log into the Administrative Panel","Update personal settings (name, password, photo)","Create a new contract, activate modules (e.g. Video, Drivers), save","Answer: what is a stock account?","Answer: what account types exist and how do they differ?","Answer: how do you add a configuration to a contract?","Create a Prepayment contract and activate the Drivers module"] },
      { n:"Day 2", t:"Objects, partners, and finances", topics:"Object transfer/configuration, partner setup, payments.",
        items:["Transfer an object into the created contract","Add a tariff to the object","Configure blocking with a block date","Add a speed-control configuration to the object","Create a partner (data, currency, tariff)","Configure SMTP for the partner","Add a payment in the Finances section","Answer: how do you transfer an object between contracts?","Answer: which configurations control speed limits?","Answer: how do you add a manual subscription debit?","Create a partner, transfer a contract to them, configure \u201cAddress in Online Tree\u201d"] },
      { n:"Day 3", t:"Modules, notifications, security, rebranding", topics:"Geofences, email templates, 2FA, white-labeling.",
        items:["Activate the Geofences module for a contract","Configure an email template for low balance notifications","Enable 2FA for a partner (TOTP or email)","Configure rebranding: logo, theme, check login page","Answer: how do you activate the Analytics module?","Answer: how do you set up TOTP login?","Answer: what settings can rebranding change?","Configure and test Notifications; add \u201cLow Balance Emails\u201d config"] },
      { n:"Final test", t:"Combined assessment", topics:"End-to-end tasks covering all three days.",
        items:["Create a prepaid contract, add an object, configure mileage by CAN","Create a partner, assign a contract, configure SMTP","Activate the Video module, configure streaming in a new tab","Write user instructions: how to change the password in the personal account"] }
    ]
  },
  {
    id: "skills", flag: "TRACK 3", title: "Support skills \u2014 communication, troubleshooting, escalation, standards",
    days: [
      { n:"Module A", t:"Communication with customers", topics:"Acknowledge \u2192 Diagnose \u2192 Act \u2192 Close. The question ladder, difficult conversations, ticket notes.",
        items:[
          "Question drill: 5 questions before proposing a cause (\u00d75 symptoms)",
          "Rewrite 5 manual sentences for a non-technical fleet manager",
          "Role-play: angry customer, vehicle dark all weekend \u2014 full cycle",
          "Role-play: deliver a \u201cno\u201d plus the alternative",
          "Write up role-play 3 to the 5-point ticket standard"
        ] },
      { n:"Module B", t:"Troubleshooting methodology", topics:"Three instruments (Sensors tracing, Audit history, Admin reports). The 7-layer model. Master-status trap. Recalculate.",
        items:[
          "Instrument drill: Sensors tracing \u2014 last reception, satellites, sensor value, under a minute",
          "Instrument drill: find an object\u2019s last settings change in Audit history",
          "Layer sorting: 15 symptoms \u2192 layer + first check",
          "Broken sandbox: diagnose 6 planted faults cold \u2b50 highest-value exercise",
          "The Master trap: explain a rights issue you cannot see from your own login",
          "Notification hunt: find the weekday-only time window",
          "Recalculate drill: fix calibration, then make yesterday\u2019s report correct",
          "Write your own diagnostic tree and defend the order of checks"
        ] },
      { n:"Module C", t:"Support workflows and escalation", topics:"Ticket lifecycle, team map, escalation triggers, special cases. Mostly company-specific \u2014 needs filling first.",
        items:[
          "Route the ticket: 10 real past requests \u2192 owning team + justification",
          "Write an escalation handover; 2nd line grades whether they could start cold",
          "Shadowing: listen to live calls",
          "Reverse shadowing: handle live contacts with a senior listening in"
        ] },
      { n:"Module D", t:"Industry standard practice", topics:"ITIL ticket types and priority matrix, KCS knowledge capture, support metrics, driver data as personal data.",
        items:[
          "Sort the queue: 12 tickets \u2192 Incident / Service request / Problem + priority, impact and urgency justified separately",
          "Find the problem: spot the recurring symptom in a month of tickets",
          "Write a KCS article titled in the customer\u2019s words \u2014 then find it by searching as a customer would",
          "Physical clock drill: 6 faults \u2192 \u201cwhat happens in the real world while this stays broken?\u201d",
          "Role-play: fleet manager wants a weekend location report on one named driver",
          "Role-play: a driver calls asking what data is held on him and demands deletion"
        ] }
    ]
  }
];

let STATE = {};

function allItemKeys(){
  const keys = [];
  DATA.forEach(sec => sec.days.forEach((d,di) => d.items.forEach((_,ii) => {
    keys.push(sec.id + ":" + di + ":" + ii);
  })));
  return keys;
}

async function loadState(){
  try{
    const res = await window.storage.get('pilot-tracker-state', false);
    STATE = res && res.value ? JSON.parse(res.value) : {};
  }catch(e){
    STATE = {};
  }
}

async function saveState(){
  try{
    await window.storage.set('pilot-tracker-state', JSON.stringify(STATE), false);
    document.getElementById('lastSaved').textContent = 'saved ' + new Date().toLocaleTimeString();
  }catch(e){
    document.getElementById('lastSaved').textContent = 'save failed \u2014 retry';
  }
}

function render(){
  const container = document.getElementById('sections');
  container.innerHTML = '';

  DATA.forEach(sec => {
    const secTotal = sec.days.reduce((a,d)=>a+d.items.length,0);
    const secDone = sec.days.reduce((a,d,di)=>a + d.items.reduce((b,_,ii)=> b + (STATE[sec.id+':'+di+':'+ii] ? 1:0),0),0);

    const secEl = document.createElement('div');
    secEl.className = 'section';
    secEl.innerHTML = `
      <div class="section-head" data-sec="${sec.id}">
        <span class="flag">${sec.flag}</span>
        <h2>${sec.title}</h2>
        <span class="count">${secDone}/${secTotal}</span>
        <span class="chev">\u25BE</span>
      </div>
      <div class="section-bar-track"><div class="section-bar-fill" style="width:${secTotal? (secDone/secTotal*100):0}%"></div></div>
      <div class="days" id="days-${sec.id}"></div>
    `;
    container.appendChild(secEl);

    const daysEl = secEl.querySelector('#days-' + sec.id);
    sec.days.forEach((d, di) => {
      const total = d.items.length;
      const done = d.items.reduce((a,_,ii)=> a + (STATE[sec.id+':'+di+':'+ii] ? 1:0), 0);
      const complete = done === total;

      const card = document.createElement('div');
      card.className = 'day-card' + (complete ? ' complete' : '');
      card.innerHTML = `
        <div class="day-top">
          <span class="daynum">${d.n}</span>
          <h3>${d.t}</h3>
          <span class="dcount">${done}/${total}</span>
        </div>
        <p class="topics">${d.topics}</p>
        <div class="items">
          ${d.items.map((it, ii) => {
            const key = sec.id + ':' + di + ':' + ii;
            const checked = !!STATE[key];
            return `<div class="item${checked?' checked':''}" data-key="${key}">
              <div class="switch"></div><span>${it}</span>
            </div>`;
          }).join('')}
        </div>
      `;
      daysEl.appendChild(card);
    });
  });

  container.querySelectorAll('.item').forEach(el => {
    el.addEventListener('click', async () => {
      const key = el.getAttribute('data-key');
      STATE[key] = !STATE[key];
      await saveState();
      render();
      updateHeader();
    });
  });

  container.querySelectorAll('.section-head').forEach(el => {
    el.addEventListener('click', () => {
      const days = el.parentElement.querySelector('.days');
      const barTrack = el.parentElement.querySelector('.section-bar-track');
      const collapsed = days.style.display === 'none';
      days.style.display = collapsed ? 'flex' : 'none';
      barTrack.style.display = collapsed ? 'block' : 'none';
      el.classList.toggle('collapsed', !collapsed);
    });
  });
}

function updateHeader(){
  const keys = allItemKeys();
  const total = keys.length;
  const done = keys.filter(k => STATE[k]).length;
  const pct = total ? Math.round(done/total*100) : 0;

  document.getElementById('doneCount').textContent = done;
  document.getElementById('totalCount').textContent = total;
  document.getElementById('gaugeNum').textContent = pct + '%';

  const circumference = 264;
  const offset = circumference - (pct/100)*circumference;
  document.getElementById('gaugeArc').setAttribute('stroke-dashoffset', offset);
}

document.getElementById('resetBtn').addEventListener('click', async () => {
  STATE = {};
  await saveState();
  render();
  updateHeader();
});

(async function init(){
  await loadState();
  render();
  updateHeader();
})();
