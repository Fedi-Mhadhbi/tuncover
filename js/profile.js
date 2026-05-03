/* ── NAV ─────────────────────────────────────────────────────── */
function toggleMenu(){ const m=document.getElementById('mobileMenu'); m.classList.toggle('open'); document.body.style.overflow=m.classList.contains('open')?'hidden':''; }
// Handles c lo se me nu logic.
function closeMenu(){ document.getElementById('mobileMenu').classList.remove('open'); document.body.style.overflow=''; }

/* ── Smart nav ─────────────────────────────────────────────── */
(function(){
  try {
    const u=JSON.parse(localStorage.getItem('tc_user')||'null');
    if(!u||!u.id) return;
    const dest=u.role==='artisan'?'tuncover-artisan-dashboard.html':u.role==='admin'?'tuncover-admin.html':'tuncover-tourist.html';
    document.querySelectorAll('.nav-cta,.menu-cta').forEach(el=>{
      el.textContent='\u{1F464} '+(u.prenom||'Dashboard'); el.href=dest;
    });
  } catch(e){}
})();

/* ── TABS ───────────────────────────────────────────────────── */
function showTab(id, btn){
  document.querySelectorAll('.p-panel').forEach(p=>p.classList.remove('active'));
  document.querySelectorAll('.p-tab').forEach(t=>t.classList.remove('active'));
  document.getElementById('panel-'+id).classList.add('active');
  if(btn) btn.classList.add('active');
}

/* ── LOAD ARTISAN ─────────────────────────────────────────── */
const params    = new URLSearchParams(window.location.search);
const ARTISAN_ID = parseInt(params.get('id')) || 0;
let portfolioAlbums = [];
let activeAlbum = null;
let activeAlbumIndex = 0;

if (!ARTISAN_ID) {
  document.getElementById('heroName').textContent = 'Artisan not found';
  document.getElementById('bioText').textContent  = 'No artisan ID provided.';
} else {
  loadProfile();
}

async function loadProfile(){
  try {
    const res  = await fetch(`php/public/artisan.php?id=${ARTISAN_ID}`);
    const data = await res.json();
    if(!data.success){ showError(data.error||'Artisan not found'); return; }
    renderProfile(data.artisan);
    renderActivities(data.activities);
    renderPortfolio(data.portfolio || []);
    renderReviews(data.reviews, data.artisan);
  } catch(e){
    showError('Could not load profile. Is XAMPP running?');
  }
}

// Handles r en de rp ro fi le logic.
function renderProfile(a){
  const fullName = (a.prenom||'')+' '+(a.nom||'');
  const initials = ((a.prenom||'?')[0]+(a.nom||'?')[0]).toUpperCase();

  document.title = `TunCover — ${fullName}`;

  // Breadcrumb
  document.getElementById('breadcrumbName').textContent = fullName;

  // Avatar — real photo or initials
  const avEl = document.getElementById('avatarEl');
  if(a.avatar_url){
    const img = document.createElement('img');
    img.src=a.avatar_url; img.alt=fullName; img.className='profile-avatar';
    img.onerror=()=>{ avEl.textContent=initials; };
    avEl.replaceWith(img);
  } else {
    avEl.textContent=initials;
  }

  // Verified badge
  if(a.statut_validation==='valide'){
    document.getElementById('verifiedBadge').style.display='flex';
  }

  // Hero text
  document.getElementById('herocraft').textContent    = a.specialite||'Artisan';
  document.getElementById('heroName').textContent     = fullName;
  document.getElementById('heroLocation').textContent = '📍 '+( a.ville||'Tunisia');

  // Stats
  const rating = parseFloat(a.avg_rating)>0 ? parseFloat(a.avg_rating).toFixed(1) : '—';
  document.getElementById('statActivities').textContent = a.nb_activites||0;
  document.getElementById('statBookings').textContent   = a.nb_reservations||0;
  document.getElementById('statRating').textContent     = rating;
  document.getElementById('statReviews').textContent    = a.nb_reviews||0;

  // About panel
  document.getElementById('bioText').textContent          = a.bio||'This artisan has not added a bio yet.';
  document.getElementById('detailVille').textContent      = a.ville||'—';
  document.getElementById('detailSpecialite').textContent = a.specialite||'—';
  document.getElementById('detailRating').textContent     = rating>0 ? `${rating} ★` : 'No ratings yet';
  document.getElementById('detailActivites').textContent  = `${a.nb_activites||0} published`;

  // Reviews header
  document.getElementById('reviewBig').textContent   = rating;
  document.getElementById('reviewCount').textContent = `${a.nb_reviews||0} review${a.nb_reviews!=1?'s':''}`;

  // Update tab label
  const actTab = document.querySelectorAll('.p-tab')[1];
  if(actTab) actTab.textContent = `Experiences (${a.nb_activites||0})`;
  const portTab = document.querySelectorAll('.p-tab')[2];
  if(portTab) portTab.textContent = `Portfolio`;
  const revTab = document.querySelectorAll('.p-tab')[3];
  if(revTab) revTab.textContent = `Reviews (${a.nb_reviews||0})`;
}

/* ── ACTIVITIES ─────────────────────────────────────────────── */
const FALLBACK_IMAGES = {
  'pottery':     'https://images.unsplash.com/photo-1565193566173-7a0ee3dbe261?w=600&q=80',
  'jewellery':   'https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?w=600&q=80',
  'leather':     'https://images.unsplash.com/photo-1473496169904-658ba7574b0d?w=600&q=80',
  'calligraphy': 'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?w=600&q=80',
  'weaving':     'https://images.unsplash.com/photo-1606722590583-6951b5ea92ad?w=600&q=80',
  'mergoum':     'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=600&q=80',
  'cooking':     'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=600&q=80',
  'heritage':    'https://images.unsplash.com/photo-1548013146-72479768bada?w=600&q=80',
  'ceramic':     'https://images.unsplash.com/photo-1474649107449-ea4f014b7e9f?w=600&q=80',
  'tour':        'https://images.unsplash.com/photo-1598300042247-d088f8ab3a91?w=600&q=80',
  'default':     'https://images.unsplash.com/photo-1598300042247-d088f8ab3a91?w=600&q=80'
};

// Handles g et ac ti vi ty im ag e logic.
function getActivityImage(a){
  if(a.image_url) return a.image_url;
  const t=(a.titre||'').toLowerCase(), c=(a.categorie||'').toLowerCase();
  for(const [k,v] of Object.entries(FALLBACK_IMAGES)){
    if(k!=='default'&&(t.includes(k)||c.includes(k))) return v;
  }
  return FALLBACK_IMAGES.default;
}

// Handles r en de ra ct iv it ie s logic.
function renderActivities(activities){
  const grid = document.getElementById('activitiesGrid');
  if(!activities||!activities.length){
    grid.innerHTML='<div style="grid-column:1/-1;text-align:center;padding:3rem;color:var(--text-m);font-style:italic;">No experiences available yet.</div>';
    return;
  }
  grid.innerHTML = activities.map(a=>{
    const img    = getActivityImage(a);
    const badge  = a.categorie||'Experience';
    const rating = parseFloat(a.avg_rating)>0 ? parseFloat(a.avg_rating).toFixed(1) : 'New';
    const reviews= parseInt(a.nb_reviews)||0;
    return `<div class="act-card" onclick="window.location='tuncover-experience.html?id=${a.id_activite}'"> <div class="act-card-img"> <img src="${esc(img)}" alt="${esc(a.titre)}" loading="lazy" onerror="this.src='${FALLBACK_IMAGES.default}'"/> <span class="act-badge">${esc(badge)}</span> </div> <div class="act-card-body"> <h3>${esc(a.titre)}</h3> <p>${esc((a.description||'').substring(0,100))}…</p> <div class="act-details"> <span class="act-detail">⏱ ${a.duree_heures}h</span> <span class="act-detail">👥 Max ${a.max_participants}</span> <span class="act-detail">📍 ${esc(a.ville)}</span> </div> <div class="act-meta"> <div class="act-price">From ${parseFloat(a.prix).toFixed(0)} TND</div> <div class="act-rating"><span class="star">★</span> ${rating}${reviews>0?' ('+reviews+')':''}</div> </div> </div> </div>`;
  }).join('');
}

// Handles t ov id eo em be d logic.
function toVideoEmbed(url){
  const u = String(url || '').trim();
  if (!u) return null;
  const yt = u.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([A-Za-z0-9_-]{6,})/i);
  if (yt && yt[1]) return `https://www.youtube.com/embed/${yt[1]}`;
  const vm = u.match(/vimeo\.com\/(\d+)/i);
  if (vm && vm[1]) return `https://player.vimeo.com/video/${vm[1]}`;
  return null;
}

// Handles g ro up po rt fo li oa lb um s logic.
function groupPortfolioAlbums(items){
  const map = new Map();
  (items || []).forEach((it) => {
    const explicitKey = String(it.album_key || '').trim();
    const titleKey = String(it.album_title || '').trim().toLowerCase().replace(/[^a-z0-9]+/g, '-');
    const albumKey = explicitKey || titleKey || ('single-' + (it.id_portfolio || Math.random()));
    const albumTitle = String(it.album_title || '').trim() || String(it.title || 'Portfolio Album').trim() || 'Portfolio Album';
    if (!map.has(albumKey)) {
      map.set(albumKey, { album_key: albumKey, album_title: albumTitle, items: [] });
    }
    map.get(albumKey).items.push(it);
  });
  return Array.from(map.values()).map((album) => {
    album.items.sort((a, b) => (parseInt(a.display_order || 0, 10) - parseInt(b.display_order || 0, 10)) || (parseInt(a.id_portfolio || 0, 10) - parseInt(b.id_portfolio || 0, 10)));
    return album;
  });
}

// Handles p or tf ol io pr ev ie wi ma ge logic.
function portfolioPreviewImage(item){
  if(!item) return 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=600&q=70';
  if((item.media_type || '').toLowerCase() === 'video'){
    return item.thumbnail_url || 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=600&q=70';
  }
  return item.media_url || 'https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=600&q=70';
}

// Handles r en de rp or tf ol io logic.
function renderPortfolio(items){
  const grid = document.getElementById('portfolioGrid');
  if(!grid) return;
  if(!items || !items.length){
    grid.innerHTML = '<div class="no-portfolio">No portfolio items yet.</div>';
    return;
  }
  portfolioAlbums = groupPortfolioAlbums(items);
  grid.innerHTML = portfolioAlbums.map((album, idx) => {
    const previews = album.items.slice(0, 3).map((it) => portfolioPreviewImage(it));
    const count = album.items.length;
    const typeLabel = count > 1 ? `${count} items` : ((album.items[0]?.media_type || 'image').toLowerCase());
    const coverStyle = previews.map((src) => `style="background-image:url('${esc(src)}')"`).join('');
    return `<div class="portfolio-card album-card" onclick="openAlbumModal(${idx})"> <div class="portfolio-media"> <div class="album-stack"> <span style="background-image:url('${esc(previews[0] || '')}')"></span> <span style="background-image:url('${esc(previews[1] || previews[0] || '')}')"></span> <span style="background-image:url('${esc(previews[2] || previews[1] || previews[0] || '')}')"></span> </div> <div class="album-badge">${count} ${count === 1 ? 'piece' : 'pieces'}</div> </div> <div class="portfolio-body"> <div class="portfolio-title">${esc(album.album_title)}</div> <div class="portfolio-meta"> <span class="portfolio-type">Album</span> <span>${esc(typeLabel)}</span> </div> </div> </div>`;
  }).join('');
}

// Handles r en de ra lb um vi ew er logic.
function renderAlbumViewer(){
  if(!activeAlbum) return;
  const viewer = document.getElementById('albumViewer');
  const strip = document.getElementById('albumStrip');
  const title = document.getElementById('albumModalTitle');
  const count = document.getElementById('albumModalCount');
  if(!viewer || !strip || !title || !count) return;

  const current = activeAlbum.items[activeAlbumIndex] || activeAlbum.items[0];
  title.textContent = activeAlbum.album_title || 'Portfolio Album';
  count.textContent = `${activeAlbum.items.length} ${activeAlbum.items.length === 1 ? 'item' : 'items'}`;

  if((current.media_type || '').toLowerCase() === 'video'){
    const embed = toVideoEmbed(current.media_url);
    viewer.innerHTML = embed ? `<iframe src="${esc(embed)}" allowfullscreen loading="lazy"></iframe>` : `<video controls autoplay preload="metadata" src="${esc(current.media_url || '')}"></video>`;
  } else {
    viewer.innerHTML = `<img src="${esc(current.media_url || '')}" alt="${esc(current.title || activeAlbum.album_title || 'Portfolio image')}" loading="lazy" onerror="this.src='https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=900&q=70'"/>`;
  }

  strip.innerHTML = activeAlbum.items.map((it, idx) => `
    <div class="album-thumb ${idx === activeAlbumIndex ? 'active' : ''}" onclick="setAlbumSlide(${idx})"> <div class="album-thumb-media"> ${(it.media_type || '').toLowerCase() === 'video' ?
           `<img src="${esc(it.thumbnail_url || portfolioPreviewImage(it))}" alt="${esc(it.title || 'Video')}" loading="lazy"/>`
         : `<img src="${esc(it.media_url || '')}" alt="${esc(it.title || 'Image')}" loading="lazy"/>`}
      </div> <div class="album-thumb-caption">${esc(it.title || activeAlbum.album_title || 'Portfolio item')}</div> </div> `).join('');
}

// Handles o pe na lb um mo da l logic.
function openAlbumModal(index){
  activeAlbum = portfolioAlbums[index] || null;
  activeAlbumIndex = 0;
  const modal = document.getElementById('albumModal');
  if(!activeAlbum || !modal) return;
  modal.classList.add('open');
  document.body.style.overflow = 'hidden';
  renderAlbumViewer();
}

// Handles s et al bu ms li de logic.
function setAlbumSlide(index){
  activeAlbumIndex = index;
  renderAlbumViewer();
}

// Handles c lo se al bu mm od al logic.
function closeAlbumModal(){
  const modal = document.getElementById('albumModal');
  if(modal) modal.classList.remove('open');
  document.body.style.overflow = '';
  activeAlbum = null;
  activeAlbumIndex = 0;
}

document.addEventListener('click', (e) => {
  const modal = document.getElementById('albumModal');
  if(!modal || !modal.classList.contains('open')) return;
  if(e.target === modal){
    closeAlbumModal();
  }
});

/* ── REVIEWS ────────────────────────────────────────────────── */
function renderReviews(reviews, artisan){
  const grid = document.getElementById('reviewsGrid');
  if(!reviews||!reviews.length){
    grid.innerHTML='<div class="no-reviews">No reviews yet — be the first to book and review!</div>';
    return;
  }
  grid.innerHTML = reviews.map(r=>{
    const initials=((r.tourist_prenom||'?')[0]+(r.tourist_nom||'?')[0]).toUpperCase();
    const stars='★'.repeat(Math.max(0,Math.round(r.note)))+'☆'.repeat(Math.max(0,5-Math.round(r.note)));
    const date=r.date_avis?new Date(r.date_avis).toLocaleDateString('en-GB',{month:'long',year:'numeric'}):'';
    return `<div class="review-card"> <div class="review-top"> <div class="reviewer"> <div class="reviewer-av">${initials}</div> <div> <div class="reviewer-name">${esc(r.tourist_prenom)} ${esc((r.tourist_nom||'').charAt(0))}.</div> <div class="reviewer-date">${date}</div> </div> </div> <span style="color:var(--gold);font-size:0.9rem;">${stars}</span> </div> <div class="review-activity">re: ${esc(r.activite_titre||'')}</div> <p class="review-text">${esc(r.commentaire||'')}</p> </div>`;
  }).join('');
}

/* ── UTILS ──────────────────────────────────────────────────── */
function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
// Handles s ho we rr or logic.
function showError(msg){
  document.getElementById('heroName').textContent='Error';
  document.getElementById('bioText').textContent=msg;
  document.getElementById('activitiesGrid').innerHTML=`<div style="padding:2rem;color:#a33;">${esc(msg)}</div>`;
}


