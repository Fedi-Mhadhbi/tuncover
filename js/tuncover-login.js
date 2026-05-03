/* ══ PANEL / UI HELPERS ═══════════════════════════════ */
function showPanel(id,btn){
  document.getElementById('panel-login').style.display='none';
  document.getElementById('panel-register').style.display='none';
  document.getElementById('panel-'+id).style.display='block';
  document.querySelectorAll('.auth-tab').forEach(t=>t.classList.remove('active'));
  btn.classList.add('active');
  clearErrors();
}
// Handles s et ro le logic.
function setRole(el,role){
  selectedRole=role;
  document.querySelectorAll('.role-btn').forEach(b=>b.classList.remove('active'));
  el.classList.add('active');
  document.getElementById('artisanExtra').style.display=role==='artisan'?'block':'none';
}
// Handles t og gl ep wd logic.
function togglePwd(id,btn){
  const inp=document.getElementById(id);
  inp.type=inp.type==='password'?'text':'password';
  btn.textContent=inp.type==='password'?'Show':'Hide';
}
// Handles c he ck st re ng th logic.
function checkStrength(v){
  ['s1','s2','s3','s4'].forEach(id=>document.getElementById(id).style.background='var(--sand2)');
  if(v.length>0) document.getElementById('s1').style.background='#e05252';
  if(v.length>=6) document.getElementById('s2').style.background='#e8a03a';
  if(v.length>=8) document.getElementById('s3').style.background='#d4a843';
  if(v.length>=10&&/[A-Z]/.test(v)&&/[0-9]/.test(v)) document.getElementById('s4').style.background='#6b9e4a';
}
// Handles s ho wt oa st logic.
function showToast(msg,type='success'){
  const t=document.getElementById('toast');
  t.textContent=(type==='success'?'✓ ':'✗ ')+msg;
  t.classList.add('show');
  setTimeout(()=>t.classList.remove('show'),3500);
}
// Handles s et lo ad in g logic.
function setLoading(btnId,loading){
  const btn=document.getElementById(btnId);
  if(!btn)return;
  btn.disabled=loading;
  btn.style.opacity=loading?'0.7':'1';
}
// Handles c le ar er ro rs logic.
function clearErrors(){
  document.querySelectorAll('.field-error').forEach(e=>{e.textContent='';});
  document.querySelectorAll('input').forEach(i=>i.style.borderColor='');
}
// Handles s ho we rr or logic.
function showError(fieldId,msg){
  const e=document.getElementById(fieldId+'Err');
  if(e){e.textContent=msg;}
  const f=document.getElementById(fieldId);
  if(f)f.style.borderColor='#e05252';
}

/* ══ ROLE STATE ═══════════════════════════════════════ */
let selectedRole='touriste';

/* ══ REDIRECT BY ROLE ═════════════════════════════════ */
function redirectByRole(role){
  if(role==='admin')   window.location.href='tuncover-admin.html';
  else if(role==='artisan') window.location.href='tuncover-artisan-dashboard.html';
  else                 window.location.href='tuncover-tourist.html';
}

/* ══ AUTO-REDIRECT IF ALREADY LOGGED IN ══════════════ */
(function(){
  // If already logged in (localStorage has valid user), redirect immediately
  // Do NOT call me.php here — that would clear localStorage on PHP session expiry
  try{
    const u=JSON.parse(localStorage.getItem('tc_user')||'null');
    if(u&&u.id&&u.role){
      redirectByRole(u.role);
    }
  }catch(e){
    // Invalid JSON in localStorage — clear it
    localStorage.removeItem('tc_user');
  }
})();

/* ══ LOGIN ════════════════════════════════════════════ */
async function handleLogin(){
  clearErrors();
  const email=document.getElementById('loginEmail')?.value.trim()||'';
  const pwd=document.getElementById('loginPwd')?.value||'';

  if(!email){ showError('loginEmail','Email is required'); return; }
  if(!pwd)  { showError('loginPwd','Password is required'); return; }

  setLoading('loginBtn',true);
  try{
    const res=await fetch('php/auth/login.php',{
  method:'POST',
  headers:{'Content-Type':'application/json'},
  credentials:'include',
  body:JSON.stringify({email,password:pwd})
    });
    const data=await res.json();
    if(data.success){
      // Save to localStorage
      localStorage.setItem('tc_user',JSON.stringify({
  id:data.user.id, nom:data.user.nom, prenom:data.user.prenom,
  email:data.user.email, role:data.user.role
      }));
      showToast('Welcome back, '+data.user.prenom+'!');
      setTimeout(()=>redirectByRole(data.user.role),900);
    }else{
      if(data.field==='email')    showError('loginEmail',data.error);
      else if(data.field==='password') showError('loginPwd',data.error);
      else showToast(data.error||'Login failed','error');
    }
  }catch(e){
    showToast('Connection error — is XAMPP running?','error');
  }finally{
    setLoading('loginBtn',false);
  }
}

/* ══ REGISTER ═════════════════════════════════════════ */
async function handleRegister(){
  clearErrors();
  const prenom=document.getElementById('regPrenom')?.value.trim()||'';
  const nom=document.getElementById('regNom')?.value.trim()||'';
  const email=document.getElementById('regEmail')?.value.trim()||'';
  const pwd=document.getElementById('regPwd')?.value||'';
  const specialite=document.getElementById('regSpecialite')?.value.trim()||'';
  const ville=document.getElementById('regVille')?.value.trim()||'';

  let ok=true;
  if(!prenom){showError('regPrenom','First name required');ok=false;}
  if(!nom)   {showError('regNom','Last name required');ok=false;}
  if(!email) {showError('regEmail','Email required');ok=false;}
  if(!pwd||pwd.length<8){showError('regPwd','Min 8 characters');ok=false;}
  if(!ok)return;

  setLoading('registerBtn',true);
  try{
    const payload={nom,prenom,email,password:pwd,role:selectedRole};
    if(selectedRole==='artisan'){payload.specialite=specialite;payload.ville=ville;}

    const res=await fetch('php/auth/register.php',{
  method:'POST',
  headers:{'Content-Type':'application/json'},
  credentials:'include',
  body:JSON.stringify(payload)
    });
    const data=await res.json();
    if(data.success){
      localStorage.setItem('tc_user',JSON.stringify({
  id:data.user.id||data.user_id, nom, prenom, email, role:selectedRole
      }));
      showToast('Account created! Welcome 🎉');
      setTimeout(()=>redirectByRole(selectedRole),1000);
    }else{
      if(data.field==='email') showError('regEmail',data.error);
      else showToast(data.error||'Registration failed','error');
    }
  }catch(e){
    showToast('Connection error — is XAMPP running?','error');
  }finally{
    setLoading('registerBtn',false);
  }
}

/* ══ KEYBOARD SUPPORT ════════════════════════════════ */
document.addEventListener('keydown',function(e){
  if(e.key!=='Enter')return;
  const loginVisible=document.getElementById('panel-login').style.display!=='none';
  if(loginVisible) handleLogin();
  else handleRegister();
});

/* ══ MOBILE MENU ══════════════════════════════════════ */
function toggleMenu(){
  const m=document.getElementById('mobileMenu');
  m.classList.toggle('open');
  document.body.style.overflow=m.classList.contains('open')?'hidden':'';
}
// Handles c lo se me nu logic.
function closeMenu(){
  document.getElementById('mobileMenu').classList.remove('open');
  document.body.style.overflow='';
}




