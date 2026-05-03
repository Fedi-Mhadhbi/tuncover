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

// Nav scroll effect
  const nav = document.getElementById('nav');
  window.addEventListener('scroll', () => {
    nav.classList.toggle('scrolled', window.scrollY > 60);
  });

  // Scroll reveal
  const reveals = document.querySelectorAll('.reveal');
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        e.target.classList.add('visible');
        observer.unobserve(e.target);
      }
    });
  }, { threshold: 0.12 });
  reveals.forEach(el => observer.observe(el));

  // Keep featured home cards in sync with real published activities.
  (async function loadFeaturedActivities(){
    try{
      const res = await fetch('php/public/activities.php');
      const data = await res.json();
      if(!data.success || !Array.isArray(data.activities) || data.activities.length < 1) return;

      const cards = document.querySelectorAll('[data-feature-card]');
      cards.forEach((card, index) => {
        const activity = data.activities[index];
        if(!activity) return;

        card.setAttribute('onclick', `window.location='tuncover-experience.html?id=${activity.id_activite}'`);

        const img = card.querySelector('.exp-card-img img');
        if(img && activity.image_url){
          img.src = activity.image_url;
          img.alt = activity.titre || 'Experience';
        }

        const badge = card.querySelector('.exp-badge');
        if(badge && activity.categorie) badge.textContent = activity.categorie;

        const region = card.querySelector('.exp-region');
        if(region && activity.ville) region.textContent = activity.ville;

        const title = card.querySelector('h3');
        if(title && activity.titre) title.textContent = activity.titre;

        const desc = card.querySelector('p');
        if(desc && activity.description){
          const shortDesc = activity.description.length > 110
            ? activity.description.slice(0, 110).trim() + '...'
            : activity.description;
          desc.textContent = shortDesc;
        }

        const price = card.querySelector('.exp-price');
        if(price){
          const amount = Number(activity.prix || 0);
          price.innerHTML = `From ${amount.toFixed(0)} TND <span>/ person</span>`;
        }
      });
    }catch(e){
      console.warn('Could not load featured activities', e);
    }
  })();

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

