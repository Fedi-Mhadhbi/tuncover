function toggleMenu(){
    const m=document.getElementById('mobileMenu');
    const open=m.classList.contains('open');
    m.classList.toggle('open',!open);
    document.body.style.overflow=!open?'hidden':'';
  }
// Handles c lo se me nu logic.
  function closeMenu(){
    document.getElementById('mobileMenu').classList.remove('open');
    document.body.style.overflow='';
  }

/* ── Smart nav ── */
(function(){
  try {
    const u = JSON.parse(localStorage.getItem('tc_user') || 'null');
    if (!u || !u.id) return;
    const dest = u.role === 'artisan' ? 'tuncover-artisan-dashboard.html'
              : u.role === 'admin'    ? 'tuncover-admin.html'
              : 'tuncover-tourist.html';
    const label = '\u{1F464} ' + (u.prenom || 'Dashboard');
    document.querySelectorAll('.nav-cta,.menu-cta').forEach(el => {
      el.textContent = label; el.href = dest;
      el.style.background = '#C9703A'; el.style.color = '#fff';
    });
  } catch(e) {}
})();

/* ── Category → fallback image (used when DB has no image_url) ── */
const CAT_IMG = {
  'workshop':    'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=600&q=80',
  'pottery':     'https://images.unsplash.com/photo-1565193566173-7a0ee3dbe261?w=600&q=80',
  'ceramics':    'https://images.unsplash.com/photo-1474649107449-ea4f014b7e9f?w=600&q=80',
  'weaving':     'https://images.unsplash.com/photo-1606722590583-6951b5ea92ad?w=600&q=80',
  'carpet':      'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=600&q=80',
  'jewellery':   'https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?w=600&q=80',
  'jewelry':     'https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?w=600&q=80',
  'calligraphy': 'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?w=600&q=80',
  'leather':     'https://images.unsplash.com/photo-1473496169904-658ba7574b0d?w=600&q=80',
  'cooking':     'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=600&q=80',
  'cuisine':     'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=600&q=80',
  'pastry':      'https://images.unsplash.com/photo-1558961363-fa8fdf82db35?w=600&q=80',
  'heritage':    'https://images.unsplash.com/photo-1548013146-72479768bada?w=600&q=80',
  'tour':        'https://images.unsplash.com/photo-1598300042247-d088f8ab3a91?w=600&q=80',
  'medina':      'https://images.unsplash.com/photo-1598300042247-d088f8ab3a91?w=600&q=80',
  'event':       'https://images.unsplash.com/photo-1516450360452-9312f5e86fc7?w=600&q=80',
  'festival':    'https://images.unsplash.com/photo-1470229722913-7c0e2dbbafd3?w=600&q=80',
  'adventure':   'https://images.unsplash.com/photo-1539020140153-e479b8c22e70?w=600&q=80',
  'default':     'https://images.unsplash.com/photo-1598300042247-d088f8ab3a91?w=600&q=80'
};

// Handles g et im g logic.
function getImg(a) {
  if (a.image_url) return a.image_url;
  const keys = [
    (a.categorie||'').toLowerCase(),
    (a.artisan_specialite||'').toLowerCase(),
    (a.titre||'').toLowerCase()
  ].join(' ');
  for (const k of Object.keys(CAT_IMG)) {
    if (keys.includes(k)) return CAT_IMG[k];
  }
  return CAT_IMG['default'];
}

// Handles g et ba dg e logic.
function getBadge(a) {
  const s = (a.categorie || a.artisan_specialite || 'Experience');
  return s.charAt(0).toUpperCase() + s.slice(1).split(' ')[0];
}

// Handles e sc logic.
function esc(s) {
  return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

let allActivities = [];
let currentType   = 'all';
let searchTimer   = null;

async function loadActivities() {
  const grid = document.getElementById('cardsGrid');
  grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:3rem;color:var(--text-m);font-style:italic;">Loading experiences…</div>';
  try {
    const res  = await fetch('php/public/activities.php');
    const data = await res.json();
    if (!data.success || !data.activities.length) {
      grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:3rem;color:var(--text-m);">No experiences available yet.</div>';
      return;
    }
    allActivities = data.activities;
    filterCards();
  } catch(e) {
    grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:3rem;color:#a33;">Could not load. Is XAMPP running?</div>';
  }
}

// Handles r en de rc ar ds logic.
function renderCards(data) {
  const grid = document.getElementById('cardsGrid');
  const countEl = document.getElementById('resultCount');
  if (countEl) countEl.textContent = data.length;

  if (!data.length) {
    grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:3rem;color:var(--text-m);font-style:italic;">No experiences match your search.</div>';
    return;
  }

  grid.innerHTML = data.map(a => {
    const img     = getImg(a);
    const badge   = getBadge(a);
    const rating  = parseFloat(a.avg_rating) > 0 ? parseFloat(a.avg_rating).toFixed(1) : null;
    const reviews = parseInt(a.nb_reviews)   || 0;
    const rStr    = rating ? `${rating} (${reviews})` : 'New';
    const artisan = esc((a.artisan_prenom||'')+' '+(a.artisan_nom||''));
    const desc    = (a.description||'').substring(0, 90) + ((a.description||'').length > 90 ? '…' : '');

    return `<div class="card" onclick="window.location='tuncover-experience.html?id=${a.id_activite}'" style="cursor:pointer;"> <div class="card-img"> <img src="${img}" alt="${esc(a.titre)}" loading="lazy" onerror="this.src='${CAT_IMG['default']}'"/> <span class="card-badge">${esc(badge)}</span> <button class="card-fav" onclick="event.stopPropagation()">♡</button> </div> <div class="card-body"> <div class="card-region">${esc(a.ville)}</div> <h3>${esc(a.titre)}</h3> <p>${esc(desc)}</p> <div style="font-size:0.72rem;color:var(--text-m);margin:.45rem 0 .35rem;">by ${artisan}</div> <div class="card-meta"> <div class="card-price">From ${parseFloat(a.prix).toFixed(0)} TND</div> <div class="card-rating"><span class="star">★</span>${rStr}</div> </div> </div> </div>`;
  }).join('');
}

/* ── Filters ── */
const TYPE_KEYWORDS = {
  'all':      null,
  'heritage': ['heritage','tour','guided tour','medina','souk'],
  'workshop': ['workshop','pottery','ceramics','cooking','cuisine','pastry','leather','craft'],
  'event':    ['event','festival','cultural'],
  'tour':     ['tour','guided','medina','souk','heritage'],
  'festival': ['festival']
};

// Handles s et ty pe logic.
function setType(btn, type) {
  document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  currentType = type;
  filterCards();
}
// Handles s et ta b logic.
function setTab(btn) {
  document.querySelectorAll('.type-tab').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
}
// Handles f il te rc ar ds logic.
function filterCards() {
  const q = (document.getElementById('searchInput')?.value || '').toLowerCase().trim();
  const allowed = TYPE_KEYWORDS[currentType] || null;

  const filtered = allActivities.filter(a => {
    const cat   = (a.categorie || '').toLowerCase();
    const title = (a.titre || '').toLowerCase();
    const ville = (a.ville || '').toLowerCase();
    const spec  = (a.artisan_specialite || '').toLowerCase();
    const desc  = (a.description || '').toLowerCase();
    const art   = ((a.artisan_prenom||'')+' '+(a.artisan_nom||'')).toLowerCase();

    const matchType = !allowed || allowed.some(k => cat.includes(k) || title.includes(k) || spec.includes(k));
    const matchQ    = !q || title.includes(q) || ville.includes(q) || desc.includes(q) || art.includes(q) || cat.includes(q) || spec.includes(q);
    return matchType && matchQ;
  });
  renderCards(filtered);
}

let exploreDirectUserId = 0;

// Handles e ns ur ee xp lo re di re ct ch at ui logic.
function ensureExploreDirectChatUI() {
  if (document.getElementById('exploreDirectChatModal')) return;
  const host = document.createElement('div');
  host.innerHTML = `
    <div id="exploreDirectChatModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:350;align-items:center;justify-content:center;"> <div style="width:min(760px,96vw);max-height:86vh;background:var(--cream);border:1px solid var(--sand2);border-radius:1rem;overflow:hidden;display:flex;flex-direction:column;"> <div style="padding:1rem 1.2rem;border-bottom:1px solid var(--sand2);display:flex;justify-content:space-between;align-items:center;"> <div> <strong id="exploreDirectTitle">Chat with artisan</strong> <div id="exploreDirectSub" style="font-size:.75rem;color:var(--text-m);"></div> </div> <button style="background:none;border:none;cursor:pointer;" onclick="closeExploreDirectChat()">Close</button> </div> <div id="exploreDirectMessages" style="overflow:auto;padding:1rem;display:grid;gap:.6rem;background:#fff9f3;"></div> <div style="padding:.9rem;border-top:1px solid var(--sand2);display:flex;gap:.6rem;"> <input id="exploreDirectInput" placeholder="Write a message..." style="flex:1;border:1px solid var(--sand2);border-radius:.7rem;padding:.7rem;background:#fff;"/> <button class="search-btn" style="padding:0.7rem 1.2rem;" onclick="sendExploreDirectMessage()">Send</button> </div> </div> </div>`;
  document.body.appendChild(host);
}

// Handles o pe nd ir ec tc ha tf ro mc ar d logic.
function openDirectChatFromCard(event, userId, artisanName) {
  if (event) event.stopPropagation();
  const u = JSON.parse(localStorage.getItem('tc_user') || 'null');
  if (!u || !u.id) {
    window.location.href = 'tuncover-login.html';
    return;
  }
  if (!userId || parseInt(userId,10) <= 0) {
    alert('Artisan chat is not available for this card.');
    return;
  }
  ensureExploreDirectChatUI();
  exploreDirectUserId = parseInt(userId, 10);
  document.getElementById('exploreDirectTitle').textContent = 'Chat with ' + (artisanName || 'artisan');
  document.getElementById('exploreDirectSub').textContent = 'Direct chat before reservation';
  const m = document.getElementById('exploreDirectChatModal');
  if (m) m.style.display = 'flex';
  loadExploreDirectMessages();
}

// Handles c lo se ex pl or ed ir ec tc ha t logic.
function closeExploreDirectChat() {
  const m = document.getElementById('exploreDirectChatModal');
  if (m) m.style.display = 'none';
  exploreDirectUserId = 0;
}

async function loadExploreDirectMessages() {
  if (!exploreDirectUserId) return;
  const box = document.getElementById('exploreDirectMessages');
  if (box) box.innerHTML = '<p style="color:var(--text-m);">Loading chat...</p>';
  try {
    const res = await fetch(`php/shared/chat.php?direct_user_id=${exploreDirectUserId}`, { credentials: 'include' });
    const data = await res.json();
    if (!data.success) {
      if (box) box.innerHTML = `<p style="color:#a33;">${esc(data.error || 'Could not load chat')}</p>`;
      return;
    }
    const me = parseInt((JSON.parse(localStorage.getItem('tc_user') || '{}').id || 0), 10);
    const msgs = data.messages || [];
    if (!msgs.length) {
      if (box) box.innerHTML = '<p style="color:var(--text-m);">Start the conversation with this artisan.</p>';
      return;
    }
    box.innerHTML = msgs.map(msg => {
      const mine = parseInt(msg.id_sender, 10) === me;
      return `<div style="display:flex;justify-content:${mine ? 'flex-end' : 'flex-start'};"> <div style="max-width:74%;padding:.62rem .75rem;border-radius:.75rem;border:1px solid ${mine ? '#d8b59a' : 'var(--sand2)'};background:${mine ? '#f7dfcf' : '#fff'};"> <div style="font-size:.78rem;line-height:1.45;">${esc(msg.message || '')}</div> <div style="font-size:.68rem;color:var(--text-m);margin-top:.25rem;">${esc(msg.sender_name || '')} - ${(msg.created_at || '').substring(0,16)}</div> </div> </div>`;
    }).join('');
    box.scrollTop = box.scrollHeight;
  } catch (e) {
    if (box) box.innerHTML = '<p style="color:#a33;">Network error</p>';
  }
}

async function sendExploreDirectMessage() {
  if (!exploreDirectUserId) return;
  const inp = document.getElementById('exploreDirectInput');
  const message = (inp?.value || '').trim();
  if (!message) return;
  try {
    const res = await fetch('php/shared/chat.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  credentials: 'include',
  body: JSON.stringify({ direct_user_id: exploreDirectUserId, message })
    });
    const data = await res.json();
    if (!data.success) {
      alert(data.error || 'Could not send message');
      return;
    }
    inp.value = '';
    loadExploreDirectMessages();
  } catch (e) {
    alert('Network error');
  }
}

document.addEventListener('DOMContentLoaded', () => {
  const inp = document.getElementById('searchInput');
  if (inp) inp.addEventListener('input', () => { clearTimeout(searchTimer); searchTimer = setTimeout(filterCards, 250); });
  ensureExploreDirectChatUI();
  loadActivities();
});

// Handles t og gl ef il te rs logic.
function toggleFilters(){
  const d=document.getElementById('filterDrawer');
  const inner=document.getElementById('drawerInner');
  const isOpen=d.style.display==='block' && inner.style.transform==='translateY(0%)';
  if(!isOpen){
    d.style.display='block';
    requestAnimationFrame(()=>{ inner.style.transform='translateY(0%)'; });
  } else {
    inner.style.transform='translateY(100%)';
    setTimeout(()=>{ d.style.display='none'; },350);
  }
}

(function(){
  const nav=document.getElementById('nav');
  if(nav){
    nav.classList.add('scrolled');
    window.addEventListener('scroll',function(){nav.classList.toggle('scrolled',window.scrollY>30);});
  }
  try{
    const u=JSON.parse(localStorage.getItem('tc_user')||'null');
    if(!u||!u.id)return;
    const dest=u.role==='admin'?'tuncover-admin.html':u.role==='artisan'?'tuncover-artisan-dashboard.html':'tuncover-tourist.html';
    const ini=((u.prenom||'?')[0]+(u.nom||'?')[0]).toUpperCase();
    const name=u.prenom||'Account';
    const links=document.getElementById('navLinks');
    if(links){
      const cta=links.querySelector('.nav-cta');
      if(cta){
        cta.outerHTML='<a href="'+dest+'" style="font-size:0.82rem;font-weight:500;letter-spacing:0.06em;text-transform:uppercase;text-decoration:none;color:var(--text-m,#6B5C4E);display:flex;align-items:center;gap:0.5rem;"><span style="width:28px;height:28px;border-radius:50%;background:var(--clay,#C9703A);color:#fff;display:flex;align-items:center;justify-content:center;font-size:0.7rem;font-weight:600;">'+ini+'</span>'+name+'</a><a href="#" onclick="navLogout(event)" style="font-size:0.78rem;color:var(--text-m,#6B5C4E);text-decoration:none;letter-spacing:0.05em;text-transform:uppercase;margin-left:0.5rem;">Sign Out</a>';
      }
    }
    const mm=document.getElementById('mobileMenu');
    if(mm){const mc=mm.querySelector('.menu-cta');if(mc){mc.href=dest;mc.textContent=name;}}
  }catch(e){}
})();
async function navLogout(e){
  if(e)e.preventDefault();
  try{await fetch('php/auth/logout.php',{method:'POST'});}catch(err){}
  localStorage.removeItem('tc_user');
  window.location.href='tuncover-login.html';
}



