/* standard-defaults index (formerly gaps) */
const fills = [...document.querySelectorAll('.std')];
fills.forEach((f,i)=>{ f.id = 'gap-' + i; });
document.getElementById('gapbtn').textContent = fills.length + ' standard defaults';
document.getElementById('dn').textContent = fills.length + ' total';

function sectionOf(el){
  let n = el;
  while(n && n !== document.body){
    let p = n.previousElementSibling;
    while(p){
      if(/^H[234]$/.test(p.tagName)) return p.textContent.replace(/7\.9 GUIDE/g,'').trim();
      p = p.previousElementSibling;
    }
    n = n.parentElement;
  }
  return 'Document';
}

const list = document.getElementById('dlist');
fills.forEach((f,i)=>{
  const b = document.createElement('button');
  b.className = 'gi';
  let txt = f.textContent.replace(/^\[FILL IN:?\s*/,'').replace(/\]$/,'').trim() || 'unspecified';
  if(txt.length > 110) txt = txt.slice(0, 108).trimEnd() + '…';
  b.innerHTML = '<span class="sec">' + sectionOf(f) + '</span>' + txt;
  b.addEventListener('click', ()=>{
    closeDrawer();
    f.scrollIntoView({block:'center'});
    f.classList.remove('flash'); void f.offsetWidth; f.classList.add('flash');
  });
  list.appendChild(b);
});

const drawer = document.getElementById('drawer');
function openDrawer(){ drawer.classList.add('open'); }
function closeDrawer(){ drawer.classList.remove('open'); }
document.getElementById('gapbtn').addEventListener('click', openDrawer);
document.getElementById('dclose').addEventListener('click', closeDrawer);
drawer.addEventListener('click', e=>{ if(e.target === drawer) closeDrawer(); });
document.addEventListener('keydown', e=>{ if(e.key === 'Escape') closeDrawer(); });

/* mobile contents */
document.getElementById('menub').addEventListener('click', ()=>{
  document.getElementById('toc').classList.toggle('open');
});
document.querySelectorAll('.toc a').forEach(a=>a.addEventListener('click', ()=>{
  if(window.innerWidth <= 1000) document.getElementById('toc').classList.remove('open');
}));

/* toc highlight */
const links = [...document.querySelectorAll('.toc a')];
const targets = links.map(a=>document.querySelector(a.getAttribute('href'))).filter(Boolean);
const obs = new IntersectionObserver(es=>{
  es.forEach(e=>{
    if(!e.isIntersecting) return;
    links.forEach(l=>l.classList.toggle('on', l.getAttribute('href') === '#' + e.target.id));
  });
}, {rootMargin:'-70px 0px -75% 0px'});
targets.forEach(t=>obs.observe(t));
