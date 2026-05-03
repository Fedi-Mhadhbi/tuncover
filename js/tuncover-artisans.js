function toggleMenu() {
  const m = document.getElementById("mobileMenu");
  const open = m.classList.contains("open");
  m.classList.toggle("open", !open);
  document.body.style.overflow = !open ? "hidden" : "";
}

function closeMenu() {
  document.getElementById("mobileMenu").classList.remove("open");
  document.body.style.overflow = "";
}

let allArtisans = [];
let currentCraft = "all";
let artisanDirectUserId = 0;

const ARTISAN_CRAFT_KEYWORDS = {
  all: [],
  pottery: ["pottery", "ceramic", "ceramics", "faience"],
  weaving: ["weaving", "woven", "loom", "textile", "carpet", "rug", "mergoum", "tissage"],
  jewellery: ["jewellery", "jewelry", "silver", "gold", "bijou", "bijoux"],
  calligraphy: ["calligraphy", "calligraphe", "script", "arabic writing"],
  leather: ["leather", "cuir", "bag", "sandal"]
};

function esc(s) {
  return String(s || "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");
}

function artisanNorm(v) {
  return String(v || "").toLowerCase().trim();
}

function artisanCover(a) {
  const fallback = {
    pottery: "https://images.unsplash.com/photo-1565193566173-7a0ee3dbe261?w=800&q=80",
    jewellery: "https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?w=800&q=80",
    weaving: "https://images.unsplash.com/photo-1606722590583-6951b5ea92ad?w=800&q=80",
    calligraphy: "https://images.unsplash.com/photo-1548013146-72479768bada?w=800&q=80",
    leather: "https://images.unsplash.com/photo-1598300042247-d088f8ab3a91?w=800&q=80",
    ceramic: "https://images.unsplash.com/photo-1474649107449-ea4f014b7e9f?w=800&q=80",
    default: "https://images.unsplash.com/photo-1598300042247-d088f8ab3a91?w=800&q=80"
  };

  const fullName = `${a.prenom || ""} ${a.nom || ""}`.toLowerCase().trim();
  if (fullName.includes("fatma") && fullName.includes("ben ali")) {
    return "https://images.unsplash.com/photo-1517495306984-f84210f9daa8?w=800&q=80";
  }
  if (fullName.includes("amira") && fullName.includes("khelifi")) {
    return "https://images.unsplash.com/photo-1493106641515-6b5631de4bb9?w=800&q=80";
  }
  if (a.cover_url) return a.cover_url;

  const hay = [a.specialite, a.bio].map(artisanNorm).join(" ");
  for (const [key, value] of Object.entries(fallback)) {
    if (key !== "default" && hay.includes(key)) return value;
  }
  return fallback.default;
}

function artisanMatchesCraftExact(a, craft) {
  if (craft === "all") return true;
  const hay = [a.specialite, a.bio, a.prenom, a.nom].map(artisanNorm).join(" ");
  const keywords = ARTISAN_CRAFT_KEYWORDS[craft] || [craft];
  return keywords.some((keyword) => hay.includes(keyword));
}

function updateArtisanHeroStats() {
  const stats = document.querySelectorAll(".h-stat-num");
  if (stats[0]) stats[0].textContent = allArtisans.length;
  const cities = new Set(allArtisans.map((a) => artisanNorm(a.ville)).filter(Boolean));
  if (stats[1] && cities.size) stats[1].textContent = cities.size;
  const crafts = new Set(allArtisans.map((a) => artisanNorm(a.specialite)).filter(Boolean));
  if (stats[2] && crafts.size) stats[2].textContent = crafts.size;
}

function filterArtisans(craft, el) {
  currentCraft = craft || "all";
  document.querySelectorAll(".craft-pill").forEach((p) => p.classList.remove("active"));
  if (el) el.classList.add("active");
  renderArtisans();
}

window.filterArtisans = filterArtisans;

function renderArtisans() {
  const q = artisanNorm(document.getElementById("artisanSearch")?.value);
  const region = artisanNorm(document.getElementById("regionFilter")?.value);
  const sortValue = document.getElementById("sortFilter")?.value || "Sort: Featured";

  let filtered = (allArtisans || []).filter((a) => {
    const name = artisanNorm(`${a.prenom || ""} ${a.nom || ""}`);
    const spec = artisanNorm(a.specialite);
    const ville = artisanNorm(a.ville);
    const bio = artisanNorm(a.bio);
    const matchCraft = artisanMatchesCraftExact(a, currentCraft);
    const matchSearch = !q || name.includes(q) || spec.includes(q) || ville.includes(q) || bio.includes(q);
    const matchRegion = !region || ville === region;
    return matchCraft && matchSearch && matchRegion;
  });

  filtered.sort((a, b) => {
    const aRating = parseFloat(a.note_moyenne || 0);
    const bRating = parseFloat(b.note_moyenne || 0);
    const aReviews = parseInt(a.nb_reviews || 0, 10);
    const bReviews = parseInt(b.nb_reviews || 0, 10);
    const aActs = parseInt(a.nb_activites || 0, 10);
    const bActs = parseInt(b.nb_activites || 0, 10);

    if (sortValue === "Most Reviews") return bReviews - aReviews || bRating - aRating || bActs - aActs;
    if (sortValue === "Highest Rated") return bRating - aRating || bReviews - aReviews || bActs - aActs;
    return bActs - aActs || bReviews - aReviews || bRating - aRating;
  });

  const count = document.getElementById("aCount");
  if (count) count.textContent = `(${filtered.length} shown)`;

  const grid = document.getElementById("artisansGrid");
  if (!grid) return;

  if (!filtered.length) {
    grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:3rem;color:var(--text-m);font-style:italic;">No artisans match this filter.</div>';
    return;
  }

  grid.innerHTML = filtered.map((a) => {
    const fullName = `${a.prenom || ""} ${a.nom || ""}`.trim();
    const rating = parseFloat(a.note_moyenne) > 0 ? parseFloat(a.note_moyenne).toFixed(1) : "New";
    const nbActs = parseInt(a.nb_activites || 0, 10);
    const nbReviews = parseInt(a.nb_reviews || 0, 10);
    const avatar = a.avatar_url || "https://images.unsplash.com/photo-1607799279861-4dd421887fb3?w=100&q=70";
    const bio = String(a.bio || "Traditional artisan from Tunisia.");
    const shortBio = bio.length > 120 ? bio.slice(0, 120).trim() + "..." : bio;

    return `
      <div class="artisan-card" onclick="window.location='profile.html?id=${a.id_artisan}'">
        <div class="artisan-card-cover">
          <img src="${artisanCover(a)}" alt="${esc(fullName)}" loading="lazy" onerror="this.src='https://images.unsplash.com/photo-1598300042247-d088f8ab3a91?w=400&q=70'"/>
          <span class="craft-tag">${esc(a.specialite || "Artisan")}</span>
        </div>
        <div class="artisan-card-body">
          <div class="artisan-avatar-row">
            <img src="${avatar}" class="artisan-avatar" alt="${esc(fullName)}" onerror="this.style.display='none'"/>
            <span class="artisan-verified" title="Verified Artisan">&#10003;</span>
          </div>
          <div class="artisan-name">${esc(fullName)}</div>
          <div class="artisan-craft">${esc(a.specialite || "Craft Artisan")}</div>
          <div class="artisan-location">Location: ${esc(a.ville || "Tunisia")}</div>
          <p class="artisan-bio">${esc(shortBio)}</p>
          <div class="artisan-footer">
            <div>
              <div class="artisan-rating"><span class="star">&#9733;</span>${rating}${nbReviews ? ` (${nbReviews})` : ""}</div>
              <div class="artisan-workshops">${nbActs} experience${nbActs !== 1 ? "s" : ""} available</div>
            </div>
            <div class="artisan-actions">
              <button class="btn-chat" onclick="openArtisanDirectChat(event, ${parseInt(a.artisan_user_id || 0, 10)}, '${esc(fullName)}')">Chat</button>
              <a href="profile.html?id=${a.id_artisan}" class="btn-visit" onclick="event.stopPropagation()">View Profile</a>
            </div>
          </div>
        </div>
      </div>`;
  }).join("");
}

async function loadArtisans() {
  const grid = document.getElementById("artisansGrid");
  if (grid) grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:3rem;color:var(--text-m);font-style:italic;">Loading artisans...</div>';
  try {
    const res = await fetch("php/public/artisans.php");
    const data = await res.json();
    allArtisans = Array.isArray(data.artisans) ? data.artisans : [];
    updateArtisanHeroStats();
    renderArtisans();
  } catch (e) {
    if (grid) grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:3rem;color:#a33;">Could not load artisans. Is XAMPP running?</div>';
  }
}

function ensureArtisansDirectChatUI() {
  if (document.getElementById("artisansDirectChatModal")) return;
  const host = document.createElement("div");
  host.innerHTML = `
    <div id="artisansDirectChatModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:360;align-items:center;justify-content:center;">
      <div style="width:min(760px,96vw);max-height:86vh;background:var(--cream);border:1px solid var(--sand2);border-radius:1rem;overflow:hidden;display:flex;flex-direction:column;">
        <div style="padding:1rem 1.2rem;border-bottom:1px solid var(--sand2);display:flex;justify-content:space-between;align-items:center;">
          <div>
            <strong id="artisansDirectTitle">Chat with artisan</strong>
            <div style="font-size:.75rem;color:var(--text-m);">Direct chat before reservation</div>
          </div>
          <button style="background:none;border:none;cursor:pointer;" onclick="closeArtisansDirectChat()">Close</button>
        </div>
        <div id="artisansDirectMessages" style="overflow:auto;padding:1rem;display:grid;gap:.6rem;background:#fff9f3;"></div>
        <div style="padding:.9rem;border-top:1px solid var(--sand2);display:flex;gap:.6rem;">
          <input id="artisansDirectInput" placeholder="Write a message..." style="flex:1;border:1px solid var(--sand2);border-radius:.7rem;padding:.7rem;background:#fff;"/>
          <button class="btn-visit" onclick="sendArtisansDirectMessage()">Send</button>
        </div>
      </div>
    </div>`;
  document.body.appendChild(host);
}

function openArtisanDirectChat(event, userId, artisanName) {
  if (event) event.stopPropagation();
  const u = JSON.parse(localStorage.getItem("tc_user") || "null");
  if (!u || !u.id) {
    window.location.href = "tuncover-login.html";
    return;
  }
  if (!userId || parseInt(userId, 10) <= 0) {
    alert("Chat is unavailable for this artisan.");
    return;
  }
  ensureArtisansDirectChatUI();
  artisanDirectUserId = parseInt(userId, 10);
  document.getElementById("artisansDirectTitle").textContent = "Chat with " + (artisanName || "artisan");
  const m = document.getElementById("artisansDirectChatModal");
  if (m) m.style.display = "flex";
  loadArtisansDirectMessages();
}

window.openArtisanDirectChat = openArtisanDirectChat;

function closeArtisansDirectChat() {
  const m = document.getElementById("artisansDirectChatModal");
  if (m) m.style.display = "none";
  artisanDirectUserId = 0;
}

window.closeArtisansDirectChat = closeArtisansDirectChat;

async function loadArtisansDirectMessages() {
  if (!artisanDirectUserId) return;
  const box = document.getElementById("artisansDirectMessages");
  if (box) box.innerHTML = '<p style="color:var(--text-m);">Loading chat...</p>';
  try {
    const res = await fetch(`php/shared/chat.php?direct_user_id=${artisanDirectUserId}`, { credentials: "include" });
    const data = await res.json();
    if (!data.success) {
      if (box) box.innerHTML = `<p style="color:#a33;">${esc(data.error || "Could not load chat")}</p>`;
      return;
    }
    const me = parseInt((JSON.parse(localStorage.getItem("tc_user") || "{}").id || 0), 10);
    const msgs = data.messages || [];
    if (!msgs.length) {
      if (box) box.innerHTML = '<p style="color:var(--text-m);">Start the conversation with this artisan.</p>';
      return;
    }
    box.innerHTML = msgs.map((msg) => {
      const mine = parseInt(msg.id_sender, 10) === me;
      return `<div style="display:flex;justify-content:${mine ? "flex-end" : "flex-start"};">
        <div style="max-width:74%;padding:.62rem .75rem;border-radius:.75rem;border:1px solid ${mine ? "#d8b59a" : "var(--sand2)"};background:${mine ? "#f7dfcf" : "#fff"};">
          <div style="font-size:.78rem;line-height:1.45;">${esc(msg.message || "")}</div>
          <div style="font-size:.68rem;color:var(--text-m);margin-top:.25rem;">${esc(msg.sender_name || "")} - ${(msg.created_at || "").substring(0, 16)}</div>
        </div>
      </div>`;
    }).join("");
    box.scrollTop = box.scrollHeight;
  } catch (e) {
    if (box) box.innerHTML = '<p style="color:#a33;">Network error</p>';
  }
}

async function sendArtisansDirectMessage() {
  if (!artisanDirectUserId) return;
  const inp = document.getElementById("artisansDirectInput");
  const message = (inp?.value || "").trim();
  if (!message) return;
  try {
    const res = await fetch("php/shared/chat.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      credentials: "include",
      body: JSON.stringify({ direct_user_id: artisanDirectUserId, message })
    });
    const data = await res.json();
    if (!data.success) {
      alert(data.error || "Could not send message");
      return;
    }
    inp.value = "";
    loadArtisansDirectMessages();
  } catch (e) {
    alert("Network error");
  }
}

window.sendArtisansDirectMessage = sendArtisansDirectMessage;

(function initArtisansPage() {
  const nav = document.getElementById("nav");
  if (nav) {
    nav.classList.add("scrolled");
    window.addEventListener("scroll", function() {
      nav.classList.toggle("scrolled", window.scrollY > 30);
    });
  }

  try {
    const u = JSON.parse(localStorage.getItem("tc_user") || "null");
    if (u && u.id) {
      const dest = u.role === "admin" ? "tuncover-admin.html" : u.role === "artisan" ? "tuncover-artisan-dashboard.html" : "tuncover-tourist.html";
      const ini = ((u.prenom || "?")[0] + (u.nom || "?")[0]).toUpperCase();
      const name = u.prenom || "Account";
      const links = document.getElementById("navLinks");
      if (links) {
        const cta = links.querySelector(".nav-cta");
        if (cta) {
          cta.outerHTML = '<a href="' + dest + '" style="font-size:0.82rem;font-weight:500;letter-spacing:0.06em;text-transform:uppercase;text-decoration:none;color:var(--text-m,#6B5C4E);display:flex;align-items:center;gap:0.5rem;"><span style="width:28px;height:28px;border-radius:50%;background:var(--clay,#C9703A);color:#fff;display:flex;align-items:center;justify-content:center;font-size:0.7rem;font-weight:600;">' + ini + '</span>' + name + '</a><a href="#" onclick="navLogout(event)" style="font-size:0.78rem;color:var(--text-m,#6B5C4E);text-decoration:none;letter-spacing:0.05em;text-transform:uppercase;margin-left:0.5rem;">Sign Out</a>';
        }
      }
      const mm = document.getElementById("mobileMenu");
      if (mm) {
        const mc = mm.querySelector(".menu-cta");
        if (mc) {
          mc.href = dest;
          mc.textContent = name;
        }
      }
    }
  } catch (e) {}

  document.getElementById("regionFilter")?.addEventListener("change", renderArtisans);
  document.getElementById("sortFilter")?.addEventListener("change", renderArtisans);
  loadArtisans();
})();

async function navLogout(e) {
  if (e) e.preventDefault();
  try {
    await fetch("php/auth/logout.php", { method: "POST" });
  } catch (err) {}
  localStorage.removeItem("tc_user");
  window.location.href = "tuncover-login.html";
}

window.navLogout = navLogout;
