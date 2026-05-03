let guests = 2;
let activityData = null;
let expDirectUserId = 0;

function toggleMenu() {
  const menu = document.getElementById("mobileMenu");
  if (!menu) return;
  menu.classList.toggle("open");
  document.body.style.overflow = menu.classList.contains("open") ? "hidden" : "";
}

function closeMenu() {
  const menu = document.getElementById("mobileMenu");
  if (!menu) return;
  menu.classList.remove("open");
  document.body.style.overflow = "";
}

function showTab(id, btn) {
  document.querySelectorAll(".tab-content").forEach((el) => el.classList.remove("active"));
  document.querySelectorAll(".d-tab").forEach((el) => el.classList.remove("active"));
  document.getElementById(id)?.classList.add("active");
  btn?.classList.add("active");
}

window.showTab = showTab;

function escapeHtml(value) {
  return String(value || "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");
}

function getActivityId() {
  return parseInt(new URLSearchParams(window.location.search).get("id"), 10) || 0;
}

function getSelectedTime() {
  const timeEl = document.querySelector(".date-row select");
  const raw = (timeEl?.value || "10:00 AM").trim();
  const pm = raw.match(/^(\d+):(\d+)\s*PM$/i);
  const am = raw.match(/^(\d+):(\d+)\s*AM$/i);
  if (pm) {
    const hour = parseInt(pm[1], 10);
    return `${String(hour < 12 ? hour + 12 : hour).padStart(2, "0")}:${pm[2]}`;
  }
  if (am) {
    const hour = parseInt(am[1], 10);
    return `${String(hour === 12 ? 0 : hour).padStart(2, "0")}:${am[2]}`;
  }
  return raw.replace(/\s*(AM|PM)/i, "").trim();
}

function getServiceFee(subtotal) {
  return Math.round(subtotal * 0.1);
}

function setReserveButtonState(disabled, label) {
  const reserveBtn = document.querySelector(".booking-actions .book-btn");
  if (!reserveBtn) return;
  reserveBtn.disabled = disabled;
  reserveBtn.textContent = label;
  reserveBtn.style.opacity = disabled ? "0.72" : "";
  reserveBtn.style.cursor = disabled ? "not-allowed" : "";
}

function renderAvailability(activity) {
  const textEl = document.getElementById("availabilityText");
  const badgeEl = document.getElementById("availabilityBadge");
  if (!textEl || !badgeEl) return;

  const capacity = parseInt(activity.capacite || activity.max_participants || 0, 10) || 0;
  const remaining = parseInt(activity.places_restantes || 0, 10) || 0;
  const isFull = String(activity.availability_status || "").toLowerCase() === "complet" || remaining <= 0;

  window._remainingPlaces = remaining;
  textEl.textContent = `Remaining ${remaining} / ${capacity} places`;
  badgeEl.textContent = isFull ? "Full" : "Available";
  badgeEl.style.background = isFull ? "#fbe8e4" : "#eef6ea";
  badgeEl.style.color = isFull ? "#b5533e" : "#4d7a31";

  if (guests > Math.max(1, remaining || 1)) {
    guests = Math.max(1, remaining || 1);
    const guestCountEl = document.getElementById("guestCount");
    if (guestCountEl) guestCountEl.textContent = guests;
  }

  setReserveButtonState(isFull, isFull ? "Full" : "Reserve Now");
}

function updatePrice() {
  const price = parseFloat(activityData?.prix || window._actPrice || 60);
  const maxGuests = Math.max(1, Math.min(8, parseInt(window._remainingPlaces || 8, 10) || 8));
  guests = Math.max(1, Math.min(maxGuests, guests));

  const subtotal = price * guests;
  const fee = getServiceFee(subtotal);
  const total = subtotal + fee;

  const guestCountEl = document.getElementById("guestCount");
  const labelEl = document.getElementById("priceLabel");
  const subtotalEl = document.getElementById("subtotal");
  const totalEl = document.getElementById("total");
  const feeRow = document.querySelectorAll(".price-row")[1];

  if (guestCountEl) guestCountEl.textContent = guests;
  if (labelEl) labelEl.textContent = `${price.toFixed(0)} TND x ${guests} guest${guests > 1 ? "s" : ""}`;
  if (subtotalEl) subtotalEl.textContent = `${subtotal.toFixed(0)} TND`;
  if (feeRow) {
    const feeValue = feeRow.querySelector("span:last-child");
    if (feeValue) feeValue.textContent = `${fee.toFixed(0)} TND`;
  }
  if (totalEl) totalEl.textContent = `${total.toFixed(0)} TND`;
}

function changeGuests(delta) {
  guests += delta;
  updatePrice();
}

window.changeGuests = changeGuests;

async function reserveNow() {
  const currentUser = JSON.parse(localStorage.getItem("tc_user") || "null");
  if (!currentUser?.id) {
    window.location.href = "tuncover-login.html";
    return;
  }

  if (currentUser.role === "artisan" || currentUser.role === "admin") {
    alert("Only tourist accounts can make reservations.");
    return;
  }

  const dateEl = document.getElementById("bookDate");
  const date = dateEl?.value || "";
  if (!date) {
    alert("Please select a date first.");
    dateEl?.focus();
    return;
  }

  const activityId = getActivityId();
  if (!activityId) {
    alert("Activity not found.");
    return;
  }

  setReserveButtonState(true, "Booking...");

  try {
    const response = await fetch("php/tourist/reserve.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      credentials: "include",
      body: JSON.stringify({
        id_activite: activityId,
        date_activite: date,
        heure_activite: getSelectedTime(),
        nb_participants: guests
      })
    });

    const data = await response.json();
    if (!data.success) {
      alert(`Booking failed: ${data.error || "Unknown error"}`);
      renderAvailability(activityData || {});
      return;
    }

    setReserveButtonState(true, "Booked!");
    const nextUrl = data.payment_url ? data.payment_url.replace("../../", "") : "tuncover-tourist.html";
    setTimeout(() => {
      window.location.href = nextUrl;
    }, 700);
  } catch (error) {
    alert("Connection error while booking.");
    renderAvailability(activityData || {});
  }
}

window.reserveNow = reserveNow;

function applyLoggedInNav() {
  const nav = document.getElementById("nav");
  if (nav) {
    nav.classList.add("scrolled");
    window.addEventListener("scroll", () => nav.classList.toggle("scrolled", window.scrollY > 30));
  }

  try {
    const user = JSON.parse(localStorage.getItem("tc_user") || "null");
    if (!user?.id) return;

    const dest =
      user.role === "admin" ? "tuncover-admin.html" : user.role === "artisan" ? "tuncover-artisan-dashboard.html" : "tuncover-tourist.html";

    const initials = ((user.prenom || "?")[0] + (user.nom || "?")[0]).toUpperCase();
    const name = user.prenom || "Account";
    const links = document.getElementById("navLinks");
    const cta = links?.querySelector(".nav-cta");

    if (cta) {
      cta.outerHTML =
        `<a href="${dest}" style="font-size:0.82rem;font-weight:500;letter-spacing:0.06em;text-transform:uppercase;text-decoration:none;color:var(--text-m,#6B5C4E);display:flex;align-items:center;gap:0.5rem;">` +
        `<span style="width:28px;height:28px;border-radius:50%;background:var(--clay,#C9703A);color:#fff;display:flex;align-items:center;justify-content:center;font-size:0.7rem;font-weight:600;">${initials}</span>${escapeHtml(name)}</a>` +
        `<a href="#" onclick="navLogout(event)" style="font-size:0.78rem;color:var(--text-m,#6B5C4E);text-decoration:none;letter-spacing:0.05em;text-transform:uppercase;margin-left:0.5rem;">Sign Out</a>`;
    }

    const mobileCta = document.querySelector("#mobileMenu .menu-cta");
    if (mobileCta) {
      mobileCta.href = dest;
      mobileCta.textContent = name;
    }
  } catch (_) {}
}

async function navLogout(event) {
  if (event) event.preventDefault();
  try {
    await fetch("php/auth/logout.php", { method: "POST" });
  } catch (_) {}
  localStorage.removeItem("tc_user");
  window.location.href = "tuncover-login.html";
}

window.navLogout = navLogout;

function ensureExperienceDirectChatUI() {
  if (document.getElementById("expDirectChatModal")) return;

  const host = document.createElement("div");
  host.innerHTML = `
    <div id="expDirectChatModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:360;align-items:center;justify-content:center;">
      <div style="width:min(760px,96vw);max-height:86vh;background:var(--cream);border:1px solid var(--sand2);border-radius:1rem;overflow:hidden;display:flex;flex-direction:column;">
        <div style="padding:1rem 1.2rem;border-bottom:1px solid var(--sand2);display:flex;justify-content:space-between;align-items:center;">
          <div>
            <strong id="expDirectTitle">Chat with artisan</strong>
            <div style="font-size:.75rem;color:var(--text-m);">Ask questions before reservation</div>
          </div>
          <button style="background:none;border:none;cursor:pointer;" onclick="closeExperienceDirectChat()">Close</button>
        </div>
        <div id="expDirectMessages" style="overflow:auto;padding:1rem;display:grid;gap:.6rem;background:#fff9f3;"></div>
        <div style="padding:.9rem;border-top:1px solid var(--sand2);display:flex;gap:.6rem;">
          <input id="expDirectInput" placeholder="Write a message..." style="flex:1;border:1px solid var(--sand2);border-radius:.7rem;padding:.7rem;background:#fff;">
          <button class="book-btn" type="button" style="padding:0.65rem 1.1rem;" onclick="sendExperienceDirectMessage()">Send</button>
        </div>
      </div>
    </div>`;
  document.body.appendChild(host);
}

function closeExperienceDirectChat() {
  const modal = document.getElementById("expDirectChatModal");
  if (modal) modal.style.display = "none";
}

window.closeExperienceDirectChat = closeExperienceDirectChat;

async function loadExperienceDirectMessages() {
  if (!expDirectUserId) return;

  const box = document.getElementById("expDirectMessages");
  if (box) box.innerHTML = '<p style="color:var(--text-m);">Loading chat...</p>';

  try {
    const response = await fetch(`php/shared/chat.php?direct_user_id=${expDirectUserId}`, {
      credentials: "include"
    });
    const data = await response.json();
    if (!data.success) {
      if (box) box.innerHTML = `<p style="color:#a33;">${escapeHtml(data.error || "Could not load chat")}</p>`;
      return;
    }

    const me = parseInt((JSON.parse(localStorage.getItem("tc_user") || "{}").id || 0), 10);
    const messages = data.messages || [];
    if (!messages.length) {
      if (box) box.innerHTML = '<p style="color:var(--text-m);">Start chatting with this artisan.</p>';
      return;
    }

    if (box) {
      box.innerHTML = messages
        .map((msg) => {
          const mine = parseInt(msg.id_sender, 10) === me;
          return `
            <div style="display:flex;justify-content:${mine ? "flex-end" : "flex-start"};">
              <div style="max-width:74%;padding:.62rem .75rem;border-radius:.75rem;border:1px solid ${mine ? "#d8b59a" : "var(--sand2)"};background:${mine ? "#f7dfcf" : "#fff"};">
                <div style="font-size:.78rem;line-height:1.45;">${escapeHtml(msg.message || "")}</div>
                <div style="font-size:.68rem;color:var(--text-m);margin-top:.25rem;">${escapeHtml(msg.sender_name || "")} - ${(msg.created_at || "").substring(0, 16)}</div>
              </div>
            </div>`;
        })
        .join("");
      box.scrollTop = box.scrollHeight;
    }
  } catch (_) {
    if (box) box.innerHTML = '<p style="color:#a33;">Network error</p>';
  }
}

function openExperienceDirectChat() {
  const user = JSON.parse(localStorage.getItem("tc_user") || "null");
  if (!user?.id) {
    window.location.href = "tuncover-login.html";
    return;
  }
  if (!expDirectUserId) {
    alert("Chat is not available for this artisan yet.");
    return;
  }

  ensureExperienceDirectChatUI();
  const title = document.getElementById("artisanName")?.textContent || "artisan";
  const modal = document.getElementById("expDirectChatModal");
  const titleEl = document.getElementById("expDirectTitle");
  if (titleEl) titleEl.textContent = `Chat with ${title}`;
  if (modal) modal.style.display = "flex";
  loadExperienceDirectMessages();
}

window.openExperienceDirectChat = openExperienceDirectChat;

async function sendExperienceDirectMessage() {
  if (!expDirectUserId) return;

  const input = document.getElementById("expDirectInput");
  const message = (input?.value || "").trim();
  if (!message) return;

  try {
    const response = await fetch("php/shared/chat.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      credentials: "include",
      body: JSON.stringify({
        direct_user_id: expDirectUserId,
        message
      })
    });
    const data = await response.json();
    if (!data.success) {
      alert(data.error || "Could not send message");
      return;
    }
    input.value = "";
    loadExperienceDirectMessages();
  } catch (_) {
    alert("Network error");
  }
}

window.sendExperienceDirectMessage = sendExperienceDirectMessage;

async function loadActivity() {
  const activityId = getActivityId();
  if (!activityId) {
    const availabilityText = document.getElementById("availabilityText");
    const availabilityBadge = document.getElementById("availabilityBadge");
    if (availabilityText) availabilityText.textContent = "Could not load activity.";
    if (availabilityBadge) availabilityBadge.textContent = "Error";
    setReserveButtonState(true, "Unavailable");
    return;
  }

  try {
    const response = await fetch(`php/public/activity.php?id=${activityId}`);
    const data = await response.json();
    if (!data.success || !data.activity) {
      const availabilityText = document.getElementById("availabilityText");
      const availabilityBadge = document.getElementById("availabilityBadge");
      if (availabilityText) availabilityText.textContent = data.error || "Activity unavailable.";
      if (availabilityBadge) availabilityBadge.textContent = "Unavailable";
      setReserveButtonState(true, "Unavailable");
      return;
    }

    activityData = data.activity;
    expDirectUserId = parseInt(activityData.artisan_user_id || 0, 10) || 0;
    window._actPrice = parseFloat(activityData.prix || 60);

    document.title = `TunCover - ${activityData.titre}`;

    const titleEl = document.getElementById("expTitle");
    if (titleEl) titleEl.innerHTML = escapeHtml(activityData.titre).replace(" - ", "<br/>").replace(" — ", "<br/>");

    const mainImg = document.querySelector(".gallery-main img");
    if (mainImg && activityData.image_url) {
      mainImg.src = activityData.image_url;
      mainImg.alt = activityData.titre;
    }
    document.querySelectorAll(".gallery-thumb img").forEach((img) => {
      if (activityData.image_url) {
        img.src = activityData.image_url;
        img.alt = activityData.titre;
      }
    });

    const fullName = [activityData.artisan_prenom, activityData.artisan_nom].filter(Boolean).join(" ").trim();
    const reviewCount = parseInt(activityData.nb_reviews || 0, 10) || 0;
    const rating = parseFloat(activityData.avg_rating || 0) > 0 ? parseFloat(activityData.avg_rating).toFixed(1) : "New";

    const metaRow = document.querySelector(".exp-meta-row");
    if (metaRow) {
      metaRow.innerHTML =
        `<div class="meta-item"><span class="rating-stars">&#9733;&#9733;&#9733;&#9733;&#9733;</span>&nbsp;<strong>${rating}</strong>${reviewCount ? `&nbsp;(${reviewCount} reviews)` : ""}</div>` +
        `<div class="meta-item">&#128205; <strong>${escapeHtml(activityData.ville)}</strong>, Tunisia</div>` +
        `<div class="meta-item">&#9201; <strong>${activityData.duree_heures} hour${parseInt(activityData.duree_heures, 10) !== 1 ? "s" : ""}</strong></div>` +
        `<div class="meta-item">&#128101; <strong>Max ${activityData.max_participants}</strong> guests</div>`;
    }

    const artisanNameEl = document.getElementById("artisanName");
    if (artisanNameEl) artisanNameEl.textContent = fullName || "Artisan";

    const artisanSubEl = document.getElementById("artisanSub");
    if (artisanSubEl) artisanSubEl.textContent = `${activityData.specialite || activityData.categorie || "Artisan"} - ${activityData.ville || "Tunisia"}`;

    const artisanAvatarEl = document.getElementById("artisanAvatar");
    if (artisanAvatarEl) {
      if (activityData.avatar_url) {
        artisanAvatarEl.src = activityData.avatar_url;
      } else {
        artisanAvatarEl.src = "https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=200&q=80";
      }
      artisanAvatarEl.alt = fullName || "Artisan";
    }

    const artisanProfileLink = document.getElementById("artisanProfileLink");
    if (artisanProfileLink && activityData.id_artisan) {
      artisanProfileLink.href = `profile.html?id=${activityData.id_artisan}`;
      artisanProfileLink.onclick = null;
      artisanProfileLink.style.pointerEvents = "auto";
      artisanProfileLink.style.opacity = "1";
    }

    const descriptionEls = document.querySelectorAll("#about .desc-text");
    if (descriptionEls[0] && activityData.description) descriptionEls[0].textContent = activityData.description;
    for (let i = 1; i < descriptionEls.length; i++) descriptionEls[i].style.display = "none";

    const priceEl = document.getElementById("bookingPrice");
    if (priceEl) priceEl.innerHTML = `${parseFloat(activityData.prix).toFixed(0)} TND <span>/ person</span>`;

    renderAvailability(activityData);
    updatePrice();
  } catch (error) {
    console.error(error);
    const availabilityText = document.getElementById("availabilityText");
    const availabilityBadge = document.getElementById("availabilityBadge");
    if (availabilityText) availabilityText.textContent = "Could not load activity.";
    if (availabilityBadge) availabilityBadge.textContent = "Error";
    setReserveButtonState(true, "Unavailable");
  }
}

document.addEventListener("DOMContentLoaded", () => {
  applyLoggedInNav();
  loadActivity();
  updatePrice();
});
