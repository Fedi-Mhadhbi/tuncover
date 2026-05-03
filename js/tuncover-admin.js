/* â•â• API ENDPOINTS â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
const API = {
  me:           'php/auth/me.php',
  logout:       'php/auth/logout.php',
  stats:        'php/admin/stats.php',
  users:        'php/admin/users.php',
  artisans:     'php/admin/artisans.php',
  activities:   'php/admin/activities.php',
  reservations: 'php/admin/reservations.php',
  reservationsExport: 'php/admin/reservations_export.php',
  reviews:      'php/admin/reviews.php',
};

/* â•â• STATE â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
let currentUser    = null;
let currentArtisan = null;
let deleteTarget   = null;
let usersData      = [];
let artisansData   = [];
let activitiesData = [];

/* â•â• PANEL NAVIGATION â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
const panelTitles = {
  dashboard:'Dashboard', users:'All Users', artisans:'Artisans',
  activities:'Activities', reservations:'Reservations',
  reviews:'Reviews', stats:'Statistics'
};

// Handles s ho wp an el logic.
function showPanel(id, el) {
  document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
  document.getElementById('panel-' + id).classList.add('active');
  if (el) el.classList.add('active');
  const titleEl = document.getElementById('panelTitle');
  if (titleEl) titleEl.textContent = panelTitles[id] || 'Admin';
  if (window.innerWidth <= 768) document.getElementById('sidebar').classList.remove('open');

  if (id === 'dashboard')    loadDashboard();
  if (id === 'users')        loadUsers();
  if (id === 'artisans')     loadArtisans();
  if (id === 'activities')   loadActivities();
  if (id === 'reservations') loadReservations();
  if (id === 'reviews')      loadReviews();
  if (id === 'stats')        loadStats();
}

/* â•â• HELPERS â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
function esc(s) {
  return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
// Handles s et st at logic.
function setStat(id, val) {
  const el = document.getElementById(id);
  if (el) el.textContent = val !== null && val !== undefined ? val : '0';
}
// Handles s et tx t logic.
function setTxt(id, val) {
  const el = document.getElementById(id);
  if (el) el.textContent = val || '';
}

function setDashboardSubtext(index, value) {
  const node = document.querySelectorAll('#panel-dashboard .stats-grid .stat-sub')[index];
  if (node) node.textContent = value || '';
}

// Handles r ol eb ad ge logic.
function roleBadge(role) {
  const normalized = String(role || '').trim().toLowerCase();
  const safeRole = normalized || 'touriste';
  const map = {touriste:['badge-blue','Tourist'],artisan:['badge-amber','Artisan'],admin:['badge-red','Admin']};
  const [cls, label] = map[safeRole] || ['badge-gray', safeRole || 'Unknown'];
  return `<span class="badge ${cls}">${label}</span>`;
}
// Handles s ta tu sb ad ge logic.
function statusBadge(s) {
  return s === 'actif' ?
     `<span class="badge badge-green">Active</span>`
   : `<span class="badge badge-red">Banned</span>`;
}
// Handles v al id at io nb ad ge logic.
function validationBadge(s) {
  const map = {en_attente:['badge-amber','Pending'],valide:['badge-green','Validated'],rejete:['badge-red','Rejected']};
  const [cls, label] = map[s] || ['badge-gray', s];
  return `<span class="badge ${cls}">${label}</span>`;
}
// Handles a ct iv it yb ad ge logic.
function activityBadge(s) {
  const map = {publie:['badge-green','Published'],brouillon:['badge-gray','Draft'],pause:['badge-amber','Paused']};
  const [cls, label] = map[s] || ['badge-gray', s];
  return `<span class="badge ${cls}">${label}</span>`;
}
// Handles r es er va ti on ba dg e logic.
function reservationBadge(s) {
  const map = {confirmee:['badge-green','Confirmed'],en_attente:['badge-amber','Pending'],annulee:['badge-red','Cancelled'],terminee:['badge-blue','Completed']};
  const [cls, label] = map[s] || ['badge-gray', s];
  return `<span class="badge ${cls}">${label}</span>`;
}

/* â•â• TOAST â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
function showToast(msg, type = 'success') {
  const t = document.getElementById('toast');
  if (!t) return;
  const icons = { success:'âœ“', error:'âœ—', info:'â„¹' };
  t.innerHTML = `<span>${icons[type]||'âœ“'}</span> ${msg}`;
  t.className = 'toast show ' + type;
  setTimeout(() => { t.className = 'toast'; }, 3000);
}

/* â•â• CHECK ADMIN AUTH â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
async function checkAuth() {
  try {
    const res  = await fetch(API.me);
    const data = await res.json();
    if (!data.success || data.user.role !== 'admin') {
      window.location.href = 'tuncover-login.html';
      return false;
    }
    const u = data.user;
    const initials = ((u.prenom||'A')[0] + (u.nom||'D')[0]).toUpperCase();
    const av = document.querySelector('.s-avatar');
    if (av) av.textContent = initials;
    const nm = document.querySelector('.s-name');
    if (nm) nm.textContent = u.prenom + ' ' + u.nom;
    return true;
  } catch(e) {
    console.error('Auth check failed:', e);
    document.body.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100vh;font-family:sans-serif;flex-direction:column;gap:1rem;background:#0f1117;color:#c4cad6;"><p style="color:#e05252;">Ã¢Å¡Â  Could not verify session. Is XAMPP running?</p><a href="tuncover-login.html" style="color:#C9703A;">Go to Login</a></div>';
    return false;
  }
}

/* â•â• DASHBOARD â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
async function loadDashboard() {
  try {
    const res  = await fetch(API.stats);
    const data = await res.json();
    if (!data.success) return;

    // Main stat cards â€” REAL data from DB
    setStat('totalUsers',        data.users        || 0);
    setStat('totalArtisans',     data.artisans      || 0);
    setStat('totalReservations', data.reservations  || 0);
    setStat('totalActivities',   data.activities    || 0);

    // Stat subtexts
    setTxt('subUsers',        (data.users||0) + ' total registered');
    setTxt('subArtisans',     (data.artisans||0) + ' artisans Â· ' + (data.pending_artisans||0) + ' pending');
    setTxt('subReservations', (data.month_reservations||0) + ' this month');
    setTxt('subActivities',   (data.activities||0) + ' published Â· ' + (data.pending_activities||0) + ' drafts');
    setDashboardSubtext(0, (data.users||0) + ' total registered');
    setDashboardSubtext(1, (data.artisans||0) + ' artisans · ' + (data.pending_artisans||0) + ' pending');
    setDashboardSubtext(2, (data.month_reservations||0) + ' this month');
    setDashboardSubtext(3, (data.activities||0) + ' published · ' + (data.pending_activities||0) + ' drafts');

    // Pending badge
    const pb = document.getElementById('pendingBadge');
    if (pb) {
      pb.textContent  = data.pending_artisans || '0';
      pb.style.display = (data.pending_artisans > 0) ? '' : 'none';
    }

    // Pending artisans mini table
    const mini = document.getElementById('pendingArtisansMini');
    if (mini) {
      if (!data.pending_list || !data.pending_list.length) {
        mini.innerHTML = '<tr><td colspan="4" style="text-align:center;color:var(--text-m);padding:1.5rem;">No pending artisans - all validated &#10003;</td></tr>';
      } else {
        mini.innerHTML = data.pending_list.map(a => `
          <tr> <td class="td-name">${esc(a.nom||'')}</td> <td>${esc(a.specialite||'â€”')}</td> <td>${esc(a.ville||'â€”')}</td> <td><div class="action-group"> <button class="btn-action btn-approve" onclick="quickApprove(${a.id_artisan})">Approve</button> <button class="btn-action btn-reject"  onclick="quickReject(${a.id_artisan})">Reject</button> </div></td> </tr>`).join('');
      }
    }

    // Recent users table
    const ru = document.getElementById('recentUsers');
    if (ru) {
      if (!data.recent_users || !data.recent_users.length) {
        ru.innerHTML = '<tr><td colspan="5" style="text-align:center;color:var(--text-m);padding:1.5rem;">No users yet</td></tr>';
      } else {
        ru.innerHTML = data.recent_users.map(u => {
          const ban = u.statut === 'banni' ? 'actif' : 'banni';
          const d   = (u.date_inscription||'').substring(0,10);
          return `<tr> <td><div class="td-name">${esc((u.prenom||'')+' '+(u.nom||''))}</div><div class="td-email">${esc(u.email||'')}</div></td> <td>${roleBadge(u.role)}</td> <td style="color:var(--text-m);font-size:0.78rem;">${d}</td> <td>${statusBadge(u.statut)}</td> <td><div class="action-group"> <button class="btn-action btn-view" onclick="viewUser(${u.id_utilisateur})">View</button> <button class="btn-action btn-ban" onclick="banUser(${u.id_utilisateur},'${ban}')">${u.statut==='banni'?'Unban':'Ban'}</button> </div></td> </tr>`;
        }).join('');
      }
    }

    // Region chart â€” REAL data, falls back gracefully if empty
    const rc = document.getElementById('regionChart');
    if (rc) {
      if (data.regions && data.regions.length) {
        const maxR   = Math.max(...data.regions.map(r => parseInt(r.cnt)||0), 1);
        const colors = ['var(--clay)','var(--blue)','var(--green)','var(--amber)','var(--gold)','#9b59b6'];
        rc.innerHTML = data.regions.map((r, i) => `
          <div class="chart-row"> <span class="chart-label">${esc(r.ville||'?')}</span> <div class="chart-bar-bg"><div class="chart-bar-fill" style="width:${Math.round((parseInt(r.cnt)||0)/maxR*100)}%;background:${colors[i%colors.length]};"></div></div> <span class="chart-value">${r.cnt}</span> </div>`).join('');
      } else {
        rc.innerHTML = '<div style="text-align:center;color:var(--text-m);padding:1.5rem;font-style:italic;font-size:0.82rem;">No bookings yet â€” chart will appear after first reservations.</div>';
      }
    }

  } catch(e) { console.error('loadDashboard error:', e); }
}

/* â•â• USERS â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
async function loadUsers() {
  try {
    const q      = document.getElementById('userSearch')?.value || '';
    const role   = document.getElementById('userRoleFilter')?.value || '';
    const statut = document.getElementById('userStatusFilter')?.value || '';
    const res    = await fetch(`${API.users}?q=${encodeURIComponent(q)}&role=${role}&statut=${statut}`);
    const data   = await res.json();
    if (!data.success) return;
    usersData = data.users;
    renderUsersTable(usersData);
  } catch(e) { console.error('loadUsers:', e); }
}

// Called from oninput/onchange in HTML
function renderUsers() { loadUsers(); }

// Handles r en de ru se rs ta bl e logic.
function renderUsersTable(list) {
  setTxt('userCount', list.length + ' users');
  const tbody = document.getElementById('usersTable');
  if (!tbody) return;
  if (!list.length) {
    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:var(--text-m);padding:1.5rem;">No users found</td></tr>';
    return;
  }
  tbody.innerHTML = list.map(u => `
    <tr> <td><div class="td-name">${esc((u.prenom||'')+' '+(u.nom||''))}</div><div class="td-email">${esc(u.email||'')}</div></td> <td>${roleBadge(u.role)}</td> <td style="color:var(--text-m);font-size:0.78rem;">${(u.date_inscription||'').substring(0,10)}</td> <td>${statusBadge(u.statut)}</td> <td><div class="action-group"> <button class="btn-action btn-view" onclick="viewUser(${u.id_utilisateur})">View</button> <button class="btn-action btn-ban" onclick="banUser(${u.id_utilisateur},'${u.statut==='banni'?'actif':'banni'}')">${u.statut==='banni'?'Unban':'Ban'}</button> <button class="btn-action btn-delete" onclick="openDelete('user',${u.id_utilisateur})">Delete</button> </div></td> </tr>`).join('');
}

// Handles v ie wu se r logic.
function viewUser(id) {
  const u = usersData.find(x => x.id_utilisateur == id);
  if (!u) return;
  currentUser = u;
  document.getElementById('userModalTitle').textContent = (u.prenom||'') + ' ' + (u.nom||'');
  document.getElementById('userModalBody').innerHTML = `
    <div class="detail-row"><span class="detail-label">Full Name</span><span class="detail-value">${esc((u.prenom||'')+' '+(u.nom||''))}</span></div> <div class="detail-row"><span class="detail-label">Email</span><span class="detail-value">${esc(u.email||'')}</span></div> <div class="detail-row"><span class="detail-label">Phone</span><span class="detail-value">${esc(u.telephone||'â€”')}</span></div> <div class="detail-row"><span class="detail-label">Role</span>${roleBadge(u.role)}</div> <div class="detail-row"><span class="detail-label">Status</span>${statusBadge(u.statut)}</div> <div class="detail-row"><span class="detail-label">Joined</span><span class="detail-value">${(u.date_inscription||'').substring(0,10)}</span></div>`;
  const banBtn = document.getElementById('banBtn');
  if (banBtn) {
    banBtn.textContent = u.statut==='banni' ? 'Unban User' : 'Ban User';
    banBtn.onclick = () => { banUser(u.id_utilisateur, u.statut==='banni'?'actif':'banni'); closeModal('userModal'); };
  }
  openModal('userModal');
}

async function banUser(id, newStatut) {
  try {
    const res  = await fetch(API.users, {
  method: 'POST',
  headers: {'Content-Type':'application/json'},
  body: JSON.stringify({id, statut: newStatut})
    });
    const data = await res.json();
    if (data.success) {
      showToast(`User ${newStatut==='banni'?'banned':'unbanned'} âœ“`, newStatut==='banni'?'error':'success');
      loadUsers();
      loadDashboard();
    } else showToast(data.error||'Error','error');
  } catch(e) { showToast('Network error','error'); }
}

/* â•â• ARTISANS â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
async function loadArtisans() {
  try {
    const q      = document.getElementById('artisanSearch')?.value || '';
    const statut = document.getElementById('artisanStatusFilter')?.value || '';
    const res    = await fetch(`${API.artisans}?q=${encodeURIComponent(q)}&statut=${statut}`);
    const data   = await res.json();
    if (!data.success) return;
    artisansData = data.artisans;
    renderArtisansTable(artisansData);
  } catch(e) { console.error('loadArtisans:', e); }
}

// Handles r en de ra rt is an s logic.
function renderArtisans() { loadArtisans(); }

// Handles r en de ra rt is an st ab le logic.
function renderArtisansTable(list) {
  setTxt('artisanCount', list.length + ' artisans');
  const tbody = document.getElementById('artisansTable');
  if (!tbody) return;
  if (!list.length) {
    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:var(--text-m);padding:1.5rem;">No artisans found</td></tr>';
    return;
  }
  tbody.innerHTML = list.map(a => `
    <tr> <td><div class="td-name">${esc((a.prenom||'')+' '+(a.nom||''))}</div><div class="td-email">${esc(a.email||'')}</div></td> <td>${esc(a.specialite||'â€”')}</td> <td>${esc(a.ville||'â€”')}</td> <td>${a.note_moyenne > 0 ? `<span style="color:var(--gold)">â˜…</span> ${a.note_moyenne}` : '<span style="color:var(--text-m)">â€”</span>'}</td> <td>${a.nb_activites||0}</td> <td>${validationBadge(a.statut_validation)}</td> <td><div class="action-group"> <button class="btn-action btn-view" onclick="viewArtisan(${a.id_artisan})">View</button> ${a.statut_validation==='en_attente' ? `
          <button class="btn-action btn-approve" onclick="quickApprove(${a.id_artisan})">Approve</button> <button class="btn-action btn-reject"  onclick="quickReject(${a.id_artisan})">Reject</button>` : ''}
        <button class="btn-action btn-delete" onclick="openDelete('user',${a.id_utilisateur})">Delete</button> </div></td> </tr>`).join('');
}

// Handles v ie wa rt is an logic.
function viewArtisan(id) {
  const a = artisansData.find(x => x.id_artisan == id);
  if (!a) return;
  currentArtisan = a;
  document.getElementById('artisanModalTitle').textContent = (a.prenom||'') + ' ' + (a.nom||'');
  document.getElementById('artisanModalBody').innerHTML = `
    <div class="detail-row"><span class="detail-label">Name</span><span class="detail-value">${esc((a.prenom||'')+' '+(a.nom||''))}</span></div> <div class="detail-row"><span class="detail-label">Email</span><span class="detail-value">${esc(a.email||'')}</span></div> <div class="detail-row"><span class="detail-label">Craft</span><span class="detail-value">${esc(a.specialite||'â€”')}</span></div> <div class="detail-row"><span class="detail-label">City</span><span class="detail-value">${esc(a.ville||'â€”')}</span></div> <div class="detail-row"><span class="detail-label">Rating</span><span class="detail-value">${a.note_moyenne > 0 ? 'â˜… ' + a.note_moyenne : 'Not rated yet'}</span></div> <div class="detail-row"><span class="detail-label">Listings</span><span class="detail-value">${a.nb_activites||0} published</span></div> <div class="detail-row"><span class="detail-label">Bookings</span><span class="detail-value">${a.nb_reservations||0} total</span></div> <div class="detail-row"><span class="detail-label">Validation</span>${validationBadge(a.statut_validation)}</div>`;
  openModal('artisanModal');
}

async function quickApprove(id) {
  try {
    const res  = await fetch(API.artisans, {
  method: 'POST',
  headers: {'Content-Type':'application/json'},
  body: JSON.stringify({id, statut:'valide'})
    });
    const data = await res.json();
    if (data.success) {
      showToast('Artisan approved âœ“','success');
      closeModal('artisanModal');
      loadArtisans(); loadDashboard();
    } else showToast(data.error||'Error','error');
  } catch(e) { showToast('Network error','error'); }
}

async function quickReject(id) {
  try {
    const res  = await fetch(API.artisans, {
  method: 'POST',
  headers: {'Content-Type':'application/json'},
  body: JSON.stringify({id, statut:'rejete'})
    });
    const data = await res.json();
    if (data.success) {
      showToast('Artisan rejected','error');
      closeModal('artisanModal');
      loadArtisans(); loadDashboard();
    } else showToast(data.error||'Error','error');
  } catch(e) { showToast('Network error','error'); }
}

// Handles a pp ro ve ar ti sa n logic.
function approveArtisan() { if (currentArtisan) quickApprove(currentArtisan.id_artisan); }
// Handles r ej ec ta rt is an logic.
function rejectArtisan()  { if (currentArtisan) quickReject(currentArtisan.id_artisan);  }

/* â•â• ACTIVITIES â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
async function loadActivities() {
  try {
    const q      = document.getElementById('actSearch')?.value || '';
    const statut = document.getElementById('actStatusFilter')?.value || '';
    const res    = await fetch(`${API.activities}?q=${encodeURIComponent(q)}&statut=${statut}`);
    const data   = await res.json();
    if (!data.success) return;
    activitiesData = data.activities;
    renderActivitiesTable(activitiesData);
  } catch(e) { console.error('loadActivities:', e); }
}

// Handles r en de ra ct iv it ie s logic.
function renderActivities() { loadActivities(); }

// Handles r en de ra ct iv it ie st ab le logic.
function renderActivitiesTable(list) {
  setTxt('actCount', list.length + ' activities');
  const tbody = document.getElementById('activitiesTable');
  if (!tbody) return;
  if (!list.length) {
    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:var(--text-m);padding:1.5rem;">No activities found</td></tr>';
    return;
  }
  tbody.innerHTML = list.map(a => `
    <tr> <td class="td-name">${esc(a.titre||'')}</td> <td style="color:var(--text-m);">${esc(a.artisan_nom||'')}</td> <td>${esc(a.ville||'â€”')}</td> <td style="color:var(--green);">${a.prix||0} TND</td> <td>${a.nb_reservations||0}</td> <td>${activityBadge(a.statut)}</td> <td><div class="action-group"> ${a.statut==='publie' ?
           `<button class="btn-action btn-ban"     onclick="toggleActivity(${a.id_activite},'pause')">Pause</button>`
         : `<button class="btn-action btn-approve" onclick="toggleActivity(${a.id_activite},'publie')">Publish</button>`}
        <button class="btn-action btn-delete" onclick="openDelete('activity',${a.id_activite})">Delete</button> </div></td> </tr>`).join('');
}

async function toggleActivity(id, newStatus) {
  try {
    const res  = await fetch(API.activities, {
  method: 'PUT',
  headers: {'Content-Type':'application/json'},
  body: JSON.stringify({id, statut: newStatus})
    });
    const data = await res.json();
    if (data.success) {
      showToast(`Activity ${newStatus==='publie'?'published âœ“':'paused'}`, newStatus==='publie'?'success':'info');
      loadActivities();
    } else showToast(data.error||'Error','error');
  } catch(e) { showToast('Network error','error'); }
}

/* â•â• RESERVATIONS â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
function buildReservationFilters(includeFormat = false, format = 'csv') {
  const params = new URLSearchParams();
  const statut = document.getElementById('resStatusFilter')?.value || '';
  const payment = document.getElementById('resPaymentFilter')?.value || '';
  const dateFrom = document.getElementById('resDateFrom')?.value || '';
  const dateTo = document.getElementById('resDateTo')?.value || '';
  if (statut) params.set('statut', statut);
  if (payment) params.set('payment', payment);
  if (dateFrom) params.set('date_from', dateFrom);
  if (dateTo) params.set('date_to', dateTo);
  if (includeFormat) params.set('format', format);
  return params.toString();
}

async function loadReservations() {
  try {
    const query = buildReservationFilters();
    const res = await fetch(`${API.reservations}${query ? ('?' + query) : ''}`);
    const data   = await res.json();
    if (!data.success) return;
    setTxt('resCount', data.reservations.length + ' reservations');
    const tbody = document.getElementById('reservationsTable');
    if (!tbody) return;
    if (!data.reservations.length) {
      tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;color:var(--text-m);padding:1.5rem;">No reservations found</td></tr>';
      return;
    }
    tbody.innerHTML = data.reservations.map(r => `
      <tr> <td><div class="td-name">${esc(r.tourist_nom||'')}</div><div class="td-email">${esc(r.tourist_email||'')}</div></td> <td style="color:var(--text-m);">${esc(r.activite_titre||'')}</td> <td style="font-size:0.78rem;color:var(--text-m);">${r.date_activite||''}</td> <td>${r.nb_participants||1}</td> <td style="color:var(--green);">${r.montant_total||0} TND</td> <td>${reservationBadge(r.statut)}</td> <td style="font-size:0.75rem;color:var(--text-m);">${esc(r.artisan_nom||'')}</td> <td style="font-size:0.75rem;color:var(--text-m);"> ${r.payment_status === 'completed' ?
             '<span class="badge badge-green">Paid</span>'
           : r.payment_status === 'refunded' ?
               '<span class="badge badge-blue">Refunded</span>'
              : '<span class="badge badge-amber">Unpaid</span>'}
          ${r.transaction_ref ? `<span style="display:block;margin-top:0.2rem;color:var(--text-m);font-size:0.72rem;">${esc(r.transaction_ref)}</span>` : ''}
        </td> <td> <div class="action-group"> ${r.statut==='en_attente' ? `<button class="btn-action btn-approve" onclick="adminReservationAction(${r.id_reservation},'confirm')">Confirm</button>` : ''}
            ${r.statut==='confirmee' ? `<button class="btn-action btn-approve" onclick="adminReservationAction(${r.id_reservation},'complete')">Complete</button>` : ''}
            ${r.statut!=='annulee' && r.statut!=='terminee' ? `<button class="btn-action btn-ban" onclick="adminReservationAction(${r.id_reservation},'cancel')">Cancel</button>` : ''}
            ${r.payment_status==='completed' ? `<button class="btn-action btn-delete" onclick="adminReservationAction(${r.id_reservation},'refund')">Refund</button>` : ''}
          </div> </td> </tr>`).join('');
  } catch(e) { console.error('loadReservations:', e); }
}

// Handles r en de rr es er va ti on s logic.
function renderReservations() { loadReservations(); }

// Handles e xp or tr es er va ti on s logic.
function exportReservations(format){
  const query = buildReservationFilters(true, format === 'pdf' ? 'pdf' : 'csv');
  const url = `${API.reservationsExport}${query ? ('?' + query) : ''}`;
  window.open(url, '_blank');
}

async function adminReservationAction(id, action){
  const labels = {
  confirm: 'confirm this reservation',
  complete: 'mark this reservation as completed',
  cancel: 'cancel this reservation',
  refund: 'refund this paid reservation'
  };
  if (!confirm(`Do you want to ${labels[action] || action}?`)) return;
  try {
    const res = await fetch(API.reservations, {
  method: 'POST',
  headers: {'Content-Type':'application/json'},
  body: JSON.stringify({id, action})
    });
    const data = await res.json();
    if (!data.success) {
      showToast(data.error || 'Action failed','error');
      return;
    }
    showToast('Reservation updated','success');
    loadReservations();
    loadDashboard();
    loadStats();
  } catch(e) {
    showToast('Network error','error');
  }
}

/* â•â• REVIEWS â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
async function loadReviews() {
  try {
    const res  = await fetch(API.reviews);
    const data = await res.json();
    if (!data.success) return;
    const tbody = document.getElementById('reviewsTable');
    if (!tbody) return;
    if (!data.reviews.length) {
      tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:var(--text-m);padding:1.5rem;">No reviews yet</td></tr>';
      return;
    }
      tbody.innerHTML = data.reviews.map(r => {
        const note = Math.max(0, Math.min(5, parseInt(r.note || 0, 10) || 0));
        const stars = '&#9733;'.repeat(note) + '&#9734;'.repeat(5 - note);
        return `
      <tr> <td class="td-name">${esc(r.tourist_nom||'')}</td> <td style="color:var(--text-m);">${esc(r.activite_titre||'')}</td> <td><span style="color:var(--gold);">${stars}</span></td> <td style="font-size:0.78rem;color:var(--text-m);max-width:200px;">${esc(r.commentaire||'')}</td> <td style="font-size:0.78rem;color:var(--text-m);">${(r.date_avis||'').substring(0,10)}</td> <td><button class="btn-action btn-delete" onclick="openDelete('review',${r.id_avis})">Remove</button></td> </tr>`;
      }).join('');
  } catch(e) { console.error('loadReviews:', e); }
}

/* â•â• STATISTICS â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
async function loadStats() {
  try {
    const res  = await fetch(API.stats);
    const data = await res.json();
    if (!data.success) return;

    setStat('statsMonthRevenue', parseFloat(data.month_revenue||0).toFixed(0));
    setStat('statsAvgBooking',   data.avg_booking || 0);
    setStat('statsAvgRating',    data.avg_rating > 0 ? data.avg_rating : 'â€”');
    setTxt('statsRatingSub',    data.total_reviews > 0 ? 'From ' + data.total_reviews + ' reviews' : 'No reviews yet');
    setStat('statsCancRate',    (data.cancellation_rate||0) + '%');
    setTxt('statsCancSub',      'of ' + (data.reservations||0) + ' total bookings');

    // Category chart
    const catData = (data.categories && data.categories.length) ? data.categories : (data.activities_by_category || []);
    const catChart = document.getElementById('categoryChart') || document.querySelector('#panel-stats .card:first-child > div:last-child');
    if (catChart) {
      if (catData.length) {
        const maxC   = Math.max(...catData.map(c => parseInt(c.cnt)||0), 1);
        const colors = ['var(--clay)','var(--blue)','var(--green)','var(--amber)','var(--gold)','#9b59b6'];
        catChart.innerHTML = catData.map((c, i) => `
          <div class="chart-row"> <span class="chart-label">${esc(c.nom||'Other')}</span> <div class="chart-bar-bg"><div class="chart-bar-fill" style="width:${Math.round((parseInt(c.cnt)||0)/maxC*100)}%;background:${colors[i%colors.length]};"></div></div> <span class="chart-value">${c.cnt}</span> </div>`).join('');
      } else {
        catChart.innerHTML = '<div style="text-align:center;color:var(--text-m);padding:1.5rem;font-style:italic;">No data yet</div>';
      }
    }

    // Top artisans table
    const tbody = document.getElementById('statsTopArtisans');
    if (tbody) {
      if (data.top_artisans && data.top_artisans.length) {
        tbody.innerHTML = data.top_artisans.map(a => `
          <tr> <td class="td-name">${esc(a.nom||'')}</td> <td>${a.bookings||0}</td> <td style="color:var(--green);">${parseFloat(a.revenue||0).toFixed(0)} TND</td> </tr>`).join('');
      } else {
        tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;color:var(--text-m);padding:1.5rem;font-style:italic;">No data yet</td></tr>';
      }
    }
  } catch(e) { console.error('loadStats:', e); }
}

/* â•â• DELETE â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
function openDelete(type, id) {
  deleteTarget = { type, id };
  const msgs = {
  user:     'this user account',
  activity: 'this activity listing',
  review:   'this review'
  };
  const msg = document.getElementById('deleteModalMsg');
  if (msg) msg.textContent = `Are you sure you want to delete ${msgs[type]||'this item'} This action cannot be undone.`;
  openModal('deleteModal');
}

async function confirmDelete() {
  if (!deleteTarget) return;
  const { type, id } = deleteTarget;
  const endpoints = { user: API.users, activity: API.activities, review: API.reviews };
  const endpoint  = endpoints[type];
  if (!endpoint) { closeModal('deleteModal'); return; }

  try {
    const res  = await fetch(endpoint, {
  method: 'DELETE',
  headers: {'Content-Type':'application/json'},
  body: JSON.stringify({id})
    });
    const data = await res.json();
    if (data.success) {
      showToast('Deleted successfully âœ“','success');
      closeModal('deleteModal');
      if (type === 'user')     { loadUsers(); loadDashboard(); }
      if (type === 'activity') loadActivities();
      if (type === 'review')   loadReviews();
    } else showToast(data.error||'Delete failed','error');
  } catch(e) { showToast('Network error','error'); }
  deleteTarget = null;
}

/* â•â• MODALS â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
function openModal(id)  { document.getElementById(id).classList.add('open'); }
// Handles c lo se mo da l logic.
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-overlay').forEach(o => {
  o.addEventListener('click', e => { if (e.target === o) o.classList.remove('open'); });
});

/* â•â• SIDEBAR MOBILE â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
function toggleSidebar() { document.getElementById('sidebar').classList.toggle('open'); }

/* â•â• LOGOUT â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
async function handleLogout() {
  try { await fetch(API.logout, { method:'POST' }); } catch(e) {}
  localStorage.removeItem('tc_user');
  window.location.href = 'tuncover-login.html';
}

/* â•â• INIT â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• */
// Fast redirect if clearly not admin (before server check)
(function() {
  try {
    const u = JSON.parse(localStorage.getItem('tc_user') || 'null');
    if (!u || !u.id || u.role !== 'admin') {
      window.location.href = 'tuncover-login.html';
    }
  } catch(e) {
    window.location.href = 'tuncover-login.html';
  }
})();

// Then verify with server and load data
checkAuth().then(ok => { if (ok) loadDashboard(); });

document.addEventListener('DOMContentLoaded', () => {
  const viewAllBtn = document.querySelector("#panel-dashboard .btn-primary-sm");
  if (viewAllBtn) {
    viewAllBtn.onclick = () => showPanel('artisans', document.querySelectorAll('.nav-item')[2]);
  }
});


