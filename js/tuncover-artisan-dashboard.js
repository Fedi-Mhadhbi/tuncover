const BOOKINGS_API = "php/artisan/bookings.php";
const LISTINGS_API = "php/artisan/listings.php";
const EARNINGS_API = "php/artisan/earnings.php";
const ME_API = "php/artisan/me.php";
const PROFILE_API = "php/artisan/profile.php";
const PORTFOLIO_API = "php/artisan/portfolio.php";
const NOTIFICATIONS_API = "php/shared/notifications.php";
const SHARED_CHAT_API = "php/shared/chat.php";

let artisanBookingsCache = [];
let artisanListingsCache = [];
let artisanPortfolioItems = [];
let artisanPortfolioAlbums = [];
let currentArtisanUser = null;
let artDirectUserId = 0;
let artDirectLabel = "";
let artNotifPollTimer = null;

function esc(value) {
  return String(value || "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");
}

function el(id) {
  return document.getElementById(id);
}

function setEl(id, value) {
  const node = el(id);
  if (node) node.textContent = value;
}

function setVal(id, value) {
  const node = el(id);
  if (node) node.value = value;
}

function renderAvatarBox(id, initials, avatarUrl) {
  const node = el(id);
  if (!node) return;
  if (avatarUrl) {
    node.innerHTML = `<img src="${esc(avatarUrl)}" alt="Avatar" style="width:100%;height:100%;object-fit:cover;border-radius:50%;" onerror="this.parentElement.textContent='${esc(initials)}'">`;
  } else {
    node.innerHTML = "";
    node.textContent = initials;
  }
}

function showToast(message, isError = false) {
  const toast = el("toast");
  if (!toast) return;
  toast.textContent = message || "Done";
  toast.style.background = isError ? "#a32d2d" : "var(--ink,#1A1410)";
  toast.style.transform = "translateY(0)";
  toast.style.opacity = "1";
  setTimeout(() => {
    toast.style.transform = "translateY(100px)";
    toast.style.opacity = "0";
  }, 3200);
}

function statusBadge(status) {
  const map = {
    en_attente: '<span class="status-badge status-pending">Pending</span>',
    confirmee: '<span class="status-badge status-confirmed">Confirmed</span>',
    terminee: '<span class="status-badge status-completed">Completed</span>',
    annulee: '<span class="status-badge status-cancelled">Cancelled</span>'
  };
  return map[status] || `<span class="status-badge">${esc(status || "-")}</span>`;
}

function listingStatusBadge(status) {
  const map = {
    publie: '<span class="listing-status lst-active">Published</span>',
    brouillon: '<span class="listing-status lst-draft">Draft</span>',
    pause: '<span class="listing-status lst-paused">Paused</span>'
  };
  return map[status] || '<span class="listing-status lst-draft">Draft</span>';
}

function paymentBadge(status) {
  if (status === "completed") {
    return '<span class="status-badge status-confirmed">Paid</span>';
  }
  if (status === "refunded") {
    return '<span class="status-badge" style="background:#e8f1ff;color:#2c5e9b;border:1px solid #c7daf6;">Refunded</span>';
  }
  return '<span class="status-badge status-pending">Unpaid</span>';
}

function money(value) {
  return `${Number(value || 0).toFixed(0)} TND`;
}

function previewImage(item) {
  if (!item) return "https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=900&q=70";
  if (String(item.media_type || "").toLowerCase() === "video") {
    return item.thumbnail_url || "https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=900&q=70";
  }
  return item.media_url || "https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=900&q=70";
}

function groupPortfolioAlbums(items) {
  const map = new Map();
  (items || []).forEach((item) => {
    const explicitKey = String(item.album_key || "").trim();
    const titleKey = String(item.album_title || "").trim().toLowerCase().replace(/[^a-z0-9]+/g, "-");
    const key = explicitKey || titleKey || (`single-${item.id_portfolio || Math.random()}`);
    const title = String(item.album_title || "").trim() || String(item.title || "Portfolio Album").trim() || "Portfolio Album";
    if (!map.has(key)) {
      map.set(key, { album_key: key, album_title: title, items: [] });
    }
    map.get(key).items.push(item);
  });

  return Array.from(map.values()).map((album) => {
    album.items.sort((a, b) => {
      const orderDiff = Number(a.display_order || 0) - Number(b.display_order || 0);
      if (orderDiff !== 0) return orderDiff;
      return Number(a.id_portfolio || 0) - Number(b.id_portfolio || 0);
    });
    return album;
  });
}

const titles = {
  overview: "Overview",
  bookings: "Bookings",
  listings: "My Listings",
  addlisting: "Add New Listing",
  earnings: "Earnings",
  profile: "Public Profile"
};

function showPanel(id, trigger) {
  document.querySelectorAll(".dash-panel").forEach((panel) => panel.classList.remove("active"));
  document.querySelectorAll(".nav-item").forEach((item) => item.classList.remove("active"));

  const panel = el(`panel-${id}`);
  if (panel) panel.classList.add("active");
  if (trigger) trigger.classList.add("active");

  setEl("panelTitle", titles[id] || "Dashboard");

  if (id === "bookings") loadBookings();
  if (id === "listings") loadListings();
  if (id === "earnings") loadEarnings();
  if (id === "profile") loadPortfolioManager();
}

window.showPanel = showPanel;

(function bootstrapArtisanDashboard() {
  try {
    const user = JSON.parse(localStorage.getItem("tc_user") || "null");
    if (!user || !user.id) {
      window.location.href = "tuncover-login.html";
      return;
    }
    if (user.role === "touriste") {
      window.location.href = "tuncover-tourist.html";
      return;
    }
    if (user.role === "admin") {
      window.location.href = "tuncover-admin.html";
      return;
    }
  } catch (error) {
    localStorage.removeItem("tc_user");
    window.location.href = "tuncover-login.html";
    return;
  }

  loadCurrentUser();
})();

async function loadCurrentUser() {
  try {
    const response = await fetch(ME_API, { credentials: "include" });
    const data = await response.json();
    if (!data.success) {
      showToast("Could not load artisan profile", true);
      return;
    }

    const user = data.user || {};
    currentArtisanUser = user;

    const initials = ((user.prenom || "?")[0] + (user.nom || "?")[0]).toUpperCase();
    const greeting =
      new Date().getHours() < 12 ? "Good morning" :
      new Date().getHours() < 18 ? "Good afternoon" :
      "Good evening";

    renderAvatarBox("sidebarAvatar", initials, user.avatar_url || "");
    renderAvatarBox("ppbAvatar", initials, user.avatar_url || "");

    setEl("sidebarName", `${user.prenom || ""} ${user.nom || ""}`.trim());
    setEl("sidebarRole", user.statut_validation === "valide" ? "Verified Artisan" : "Pending Approval");
    setEl("artisanGreeting", `${greeting}, ${user.prenom || ""}`);
    setEl("ppbName", `${user.prenom || ""} ${user.nom || ""}`.trim());

    setVal("pfName", `${user.prenom || ""} ${user.nom || ""}`.trim());
    setVal("pfSpecialite", user.specialite || "");
    setVal("pfVille", user.ville || "");
    setVal("pfBio", user.bio || "");

    const publicLink = document.querySelector(".ppb-link");
    if (publicLink && user.id_artisan) {
      publicLink.href = `profile.html?id=${user.id_artisan}`;
    }

    localStorage.setItem("tc_user", JSON.stringify({
      id: user.id,
      nom: user.nom,
      prenom: user.prenom,
      email: user.email,
      role: user.role,
      avatar_url: user.avatar_url || ""
    }));

    loadOverview();
    loadPortfolioManager();
    initArtisanNotifications();
    initArtisanDeepLinkChat();
  } catch (error) {
    console.error(error);
    showToast("Connection error", true);
  }
}

async function loadOverview() {
  try {
    const response = await fetch(BOOKINGS_API, { credentials: "include" });
    const data = await response.json();
    if (!data.success) return;

    const bookings = data.bookings || [];
    artisanBookingsCache = bookings;
    const pending = bookings.filter((booking) => booking.statut === "en_attente");

    setEl("statTotalBookings", bookings.length);
    setEl("statTotalSub", bookings.length === 0 ? "no bookings yet" : "bookings total");
    setEl("statPending", pending.length);
    setEl("statPendingSub", pending.length > 0 ? "Needs attention" : "All up to date");

    const tbody = el("overviewBookingsTbody");
    if (!tbody) return;

    if (!bookings.length) {
      tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:var(--text-m);padding:1.5rem;font-style:italic;">No bookings yet.</td></tr>';
    } else {
      tbody.innerHTML = bookings.slice(0, 4).map((booking) => `
        <tr>
          <td class="td-name">${esc(`${booking.tourist_prenom || ""} ${booking.tourist_nom || ""}`)}</td>
          <td>${esc(booking.activite_titre || "")}</td>
          <td>${esc(booking.date_activite || "-")}</td>
          <td>${Number(booking.nb_participants || 1)}</td>
          <td>${money(booking.montant_total)}</td>
          <td>${statusBadge(booking.statut)}</td>
          <td>
            ${booking.statut === "en_attente"
              ? `<span class="td-action" style="color:var(--green);cursor:pointer;" onclick="acceptBooking(${Number(booking.id_reservation)})">Accept</span>`
              : `<span class="td-action" style="cursor:pointer;color:var(--clay,#C9703A);" onclick="viewBooking(${Number(booking.id_reservation)})">View</span>`}
          </td>
        </tr>
      `).join("");
    }

    loadEarningsStats();
  } catch (error) {
    console.error(error);
  }
}

async function loadEarningsStats() {
  try {
    const response = await fetch(EARNINGS_API, { credentials: "include" });
    const data = await response.json();
    if (!data.success) return;

    const stats = data.stats || {};
    setEl("statEarned", Number(stats.this_month || 0).toFixed(0));
    setEl("statEarnedSub", "TND this month");
    setEl("statRating", Number(stats.avg_rating || 0) > 0 ? stats.avg_rating : "-");
    setEl("statRatingSub", Number(stats.total_reviews || 0) > 0 ? `From ${stats.total_reviews} reviews` : "no reviews yet");
  } catch (error) {
  }
}

async function loadBookings() {
  try {
    const response = await fetch(BOOKINGS_API, { credentials: "include" });
    const data = await response.json();
    if (!data.success) return;

    const bookings = (data.bookings || []).slice().sort((a, b) => {
      const weight = (value) => value === "en_attente" ? 0 : value === "confirmee" ? 1 : value === "terminee" ? 2 : 3;
      return weight(a.statut) - weight(b.statut);
    });
    artisanBookingsCache = bookings;
    const tbody = el("bookingsTbody");
    if (!tbody) return;

    if (!bookings.length) {
      tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:var(--text-m);padding:2rem;font-style:italic;">No bookings yet.</td></tr>';
      return;
    }

    tbody.innerHTML = bookings.map((booking) => `
      <tr>
        <td class="td-name">${esc(`${booking.tourist_prenom || ""} ${booking.tourist_nom || ""}`)}</td>
        <td>${esc(booking.activite_titre || "")}</td>
        <td>${esc(booking.date_activite || "-")} - ${esc(booking.heure_activite || "-")}</td>
        <td>${Number(booking.nb_participants || 1)}</td>
        <td>${money(booking.montant_total)}</td>
        <td>${statusBadge(booking.statut)}</td>
        <td>${paymentBadge(booking.payment_status || "pending")}</td>
        <td>
          ${booking.statut === "en_attente"
            ? `<span class="td-action" style="color:var(--green);cursor:pointer;margin-right:.6rem;" onclick="acceptBooking(${Number(booking.id_reservation)})">Accept</span><span class="td-action" style="color:#b5533e;cursor:pointer;" onclick="declineBooking(${Number(booking.id_reservation)})">Decline</span>`
            : booking.statut === "confirmee"
              ? `<span class="td-action" style="color:#355f8f;cursor:pointer;margin-right:.6rem;" onclick="completeBooking(${Number(booking.id_reservation)})">Complete</span><span class="td-action" style="cursor:pointer;color:var(--clay,#C9703A);" onclick="viewBooking(${Number(booking.id_reservation)})">View</span>`
              : `<span class="td-action" style="cursor:pointer;color:var(--clay,#C9703A);" onclick="viewBooking(${Number(booking.id_reservation)})">View</span>`}
        </td>
      </tr>
    `).join("");
  } catch (error) {
    console.error(error);
  }
}

window.viewBooking = function viewBooking(id) {
  const booking = artisanBookingsCache.find((item) => Number(item.id_reservation) === Number(id));
  if (!booking) {
    showToast("Booking not found", true);
    return;
  }

  alert(
    `Booking #${booking.id_reservation}\n` +
    `Tourist: ${booking.tourist_prenom || ""} ${booking.tourist_nom || ""}\n` +
    `Activity: ${booking.activite_titre || "-"}\n` +
    `Date: ${booking.date_activite || "-"} ${booking.heure_activite || ""}\n` +
    `Guests: ${booking.nb_participants || 1}\n` +
    `Payment: ${booking.payment_status || "pending"}`
  );
};

window.acceptBooking = async function acceptBooking(id) {
  try {
    const response = await fetch(BOOKINGS_API, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      credentials: "include",
      body: JSON.stringify({ id: Number(id), statut: "confirmee" })
    });
    const data = await response.json();
    if (data.success) {
      showToast("Booking accepted");
      loadBookings();
      loadOverview();
    } else {
      showToast(data.error || "Failed to accept booking", true);
    }
  } catch (error) {
    showToast("Network error", true);
  }
};

window.declineBooking = async function declineBooking(id) {
  try {
    const response = await fetch(BOOKINGS_API, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      credentials: "include",
      body: JSON.stringify({ id: Number(id), statut: "annulee" })
    });
    const data = await response.json();
    if (data.success) {
      showToast("Booking declined");
      loadBookings();
      loadOverview();
    } else {
      showToast(data.error || "Failed to decline booking", true);
    }
  } catch (error) {
    showToast("Network error", true);
  }
};

window.completeBooking = async function completeBooking(id) {
  try {
    const response = await fetch(BOOKINGS_API, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      credentials: "include",
      body: JSON.stringify({ id: Number(id), statut: "terminee" })
    });
    const data = await response.json();
    if (data.success) {
      showToast("Booking marked completed");
      loadBookings();
      loadOverview();
    } else {
      showToast(data.error || "Failed to complete booking", true);
    }
  } catch (error) {
    showToast("Network error", true);
  }
};

function populateListingForm(listing) {
  setVal("editListingId", listing ? listing.id_activite : "");
  setVal("lstTitre", listing ? listing.titre : "");
  setVal("lstCategorie", listing ? listing.id_categorie : "1");
  setVal("lstVille", listing ? listing.ville : "");
  setVal("lstDescription", listing ? listing.description : "");
  setVal("lstPrix", listing ? listing.prix : "");
  setVal("lstMaxP", listing ? (listing.max_participants || listing.capacite || 8) : "8");
  setVal("lstDuree", listing ? listing.duree_heures : "2");
  setVal("lstImageUrl", listing ? (listing.image_url || "") : "");
  setEl("listing-form-title", listing ? "Edit Experience" : "Add New Experience");

  const cancelBtn = el("btnCancelEdit");
  if (cancelBtn) cancelBtn.style.display = listing ? "inline-flex" : "none";
}

function clearListingErrors() {
  ["lstTitreErr", "lstDescErr", "lstPrixErr", "listing-form-error"].forEach((id) => {
    const node = el(id);
    if (!node) return;
    node.textContent = "";
    node.style.display = "none";
  });
}

function validateListingForm() {
  clearListingErrors();

  let ok = true;
  const title = (el("lstTitre")?.value || "").trim();
  const description = (el("lstDescription")?.value || "").trim();
  const price = Number(el("lstPrix")?.value || 0);

  if (!title) {
    const node = el("lstTitreErr");
    if (node) {
      node.textContent = "Title is required";
      node.style.display = "block";
    }
    ok = false;
  }
  if (!description) {
    const node = el("lstDescErr");
    if (node) {
      node.textContent = "Description is required";
      node.style.display = "block";
    }
    ok = false;
  }
  if (price <= 0) {
    const node = el("lstPrixErr");
    if (node) {
      node.textContent = "Price must be greater than 0";
      node.style.display = "block";
    }
    ok = false;
  }

  return ok;
}

function listingImage(listing) {
  return listing.image_url || "https://images.unsplash.com/photo-1598300042247-d088f8ab3a91?w=900&q=70";
}

async function loadListings() {
  const grid = el("listingsGrid");
  if (!grid) return;

  grid.innerHTML = '<div style="text-align:center;padding:3rem;color:var(--text-m);font-style:italic;">Loading listings...</div>';

  try {
    const response = await fetch(LISTINGS_API, { credentials: "include" });
    const data = await response.json();
    if (!data.success) {
      grid.innerHTML = '<div style="text-align:center;padding:3rem;color:#a33;">Could not load listings.</div>';
      return;
    }

    artisanListingsCache = (data.listings || []).filter((listing) => listing.statut !== "supprime");

    if (!artisanListingsCache.length) {
      grid.innerHTML = '<div style="text-align:center;padding:3rem;color:var(--text-m);font-style:italic;">No listings yet.</div>';
      return;
    }

    grid.innerHTML = artisanListingsCache.map((listing) => `
      <div class="listing-card">
        <div class="listing-img">
          <img src="${esc(listingImage(listing))}" alt="${esc(listing.titre || "Experience")}" onerror="this.src='https://images.unsplash.com/photo-1598300042247-d088f8ab3a91?w=900&q=70'">
          ${listingStatusBadge(listing.statut)}
        </div>
        <div class="listing-body">
          <div class="listing-title">${esc(listing.titre || "Untitled")}</div>
          <div class="listing-meta">
            <span>${esc(listing.ville || "-")}</span>
            <span>${money(listing.prix)}</span>
          </div>
          <div class="listing-meta" style="margin-top:.45rem;">
            <span>${esc(listing.categorie_nom || "Experience")}</span>
            <span>${Number(listing.total_bookings || 0)} bookings</span>
          </div>
          <div class="listing-meta" style="margin-top:.45rem;">
            <span>${Number(listing.avg_rating || 0) > 0 ? `${Number(listing.avg_rating).toFixed(1)} rating` : "New"}</span>
            <span>${Number(listing.places_restantes || 0)} left</span>
          </div>
          <div class="listing-actions">
            <button class="lst-btn" onclick="editListing(${Number(listing.id_activite)})">Edit</button>
            <button class="lst-btn" onclick="toggleListingStatus(${Number(listing.id_activite)}, '${listing.statut === "publie" ? "pause" : "publie"}')">${listing.statut === "publie" ? "Pause" : "Publish"}</button>
            <button class="lst-btn primary" onclick="deleteListing(${Number(listing.id_activite)})">Delete</button>
          </div>
        </div>
      </div>
    `).join("");
  } catch (error) {
    console.error(error);
    grid.innerHTML = '<div style="text-align:center;padding:3rem;color:#a33;">Could not load listings.</div>';
  }
}

window.editListing = function editListing(id) {
  const listing = artisanListingsCache.find((item) => Number(item.id_activite) === Number(id));
  if (!listing) {
    showToast("Listing not found", true);
    return;
  }

  populateListingForm(listing);
  showPanel("addlisting", document.querySelectorAll(".nav-item")[3]);
};

window.cancelEditListing = function cancelEditListing() {
  populateListingForm(null);
  clearListingErrors();
};

window.submitListing = async function submitListing(targetStatus) {
  if (!validateListingForm()) return;

  const editId = Number(el("editListingId")?.value || 0);
  const payload = {
    titre: el("lstTitre")?.value.trim() || "",
    id_categorie: Number(el("lstCategorie")?.value || 0),
    ville: el("lstVille")?.value.trim() || "",
    description: el("lstDescription")?.value.trim() || "",
    prix: Number(el("lstPrix")?.value || 0),
    max_participants: Number(el("lstMaxP")?.value || 8),
    duree_heures: Number(el("lstDuree")?.value || 2),
    image_url: el("lstImageUrl")?.value.trim() || "",
    statut: targetStatus
  };

  const btnPublish = el("btnPublish");
  const btnDraft = el("btnDraft");
  if (btnPublish) btnPublish.disabled = true;
  if (btnDraft) btnDraft.disabled = true;

  try {
    const response = await fetch(LISTINGS_API, {
      method: editId ? "PUT" : "POST",
      headers: { "Content-Type": "application/json" },
      credentials: "include",
      body: JSON.stringify(editId ? { id: editId, ...payload } : payload)
    });
    const data = await response.json();
    if (!data.success) {
      const err = el("listing-form-error");
      if (err) {
        err.textContent = data.error || "Could not save listing";
        err.style.display = "block";
      }
      return;
    }

    showToast(editId ? "Listing updated" : (targetStatus === "publie" ? "Listing published" : "Draft saved"));
    populateListingForm(null);
    loadListings();
    showPanel("listings", document.querySelectorAll(".nav-item")[2]);
  } catch (error) {
    const err = el("listing-form-error");
    if (err) {
      err.textContent = "Network error";
      err.style.display = "block";
    }
  } finally {
    if (btnPublish) btnPublish.disabled = false;
    if (btnDraft) btnDraft.disabled = false;
  }
};

window.toggleListingStatus = async function toggleListingStatus(id, nextStatus) {
  try {
    const response = await fetch(LISTINGS_API, {
      method: "PUT",
      headers: { "Content-Type": "application/json" },
      credentials: "include",
      body: JSON.stringify({ id: Number(id), statut: nextStatus })
    });
    const data = await response.json();
    if (data.success) {
      showToast(nextStatus === "publie" ? "Listing published" : "Listing paused");
      loadListings();
    } else {
      showToast(data.error || "Could not update listing", true);
    }
  } catch (error) {
    showToast("Network error", true);
  }
};

window.deleteListing = async function deleteListing(id) {
  if (!confirm("Delete this listing?")) return;

  try {
    const response = await fetch(LISTINGS_API, {
      method: "DELETE",
      headers: { "Content-Type": "application/json" },
      credentials: "include",
      body: JSON.stringify({ id: Number(id) })
    });
    const data = await response.json();
    if (data.success) {
      showToast("Listing deleted");
      loadListings();
      loadOverview();
    } else {
      showToast(data.error || "Could not delete listing", true);
    }
  } catch (error) {
    showToast("Network error", true);
  }
};

async function loadEarnings() {
  try {
    const response = await fetch(EARNINGS_API, { credentials: "include" });
    const data = await response.json();
    if (!data.success) return;

    const stats = data.stats || {};
    setEl("earnThisMonth", Number(stats.this_month || 0).toFixed(0));
    setEl("earnLastMonth", Number(stats.last_month || 0).toFixed(0));
    setEl("earnTotal", Number(stats.total || 0).toFixed(0));
    setEl("earnPending", Number(stats.pending || 0).toFixed(2));

    const tbody = el("earningsTbody");
    if (!tbody) return;

    const transactions = data.transactions || [];
    if (!transactions.length) {
      tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:var(--text-m);padding:1.5rem;font-style:italic;">No payments yet.</td></tr>';
      return;
    }

    tbody.innerHTML = transactions.map((transaction) => `
      <tr>
        <td>${esc((transaction.date_reservation || "").substring(0, 10))}</td>
        <td>${esc(transaction.activite || "-")}</td>
        <td>${esc(transaction.tourist || "-")}</td>
        <td>${money(transaction.montant_total)}</td>
        <td>${money(transaction.platform_fee)}</td>
        <td>${money(transaction.artisan_amount)}</td>
        <td>${esc(transaction.statut || "completed")}</td>
      </tr>
    `).join("");
  } catch (error) {
    console.error(error);
  }
}

function updateAlbumSelect() {
  const select = el("portExistingAlbum");
  if (!select) return;

  const current = select.value;
  select.innerHTML = '<option value="">Create new album</option>' + artisanPortfolioAlbums.map((album) => `
    <option value="${esc(album.album_key)}">${esc(album.album_title)} (${album.items.length})</option>
  `).join("");

  if (current && artisanPortfolioAlbums.some((album) => album.album_key === current)) {
    select.value = current;
  }
}

function renderPortfolioManager() {
  const list = el("portfolioList");
  if (!list) return;

  artisanPortfolioAlbums = groupPortfolioAlbums(artisanPortfolioItems);
  updateAlbumSelect();

  if (!artisanPortfolioAlbums.length) {
    list.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:2rem;color:var(--text-m);font-style:italic;border:1px dashed var(--sand2);border-radius:1rem;background:#fff;">No portfolio albums yet.</div>';
    return;
  }

  list.innerHTML = artisanPortfolioAlbums.map((album) => {
    const previews = album.items.slice(0, 3).map((item) => previewImage(item));
    return `
      <div class="portfolio-admin-card">
        <div class="portfolio-admin-cover">
          <div class="portfolio-admin-stack">
            <span style="background-image:url('${esc(previews[0] || "")}')"></span>
            <span style="background-image:url('${esc(previews[1] || previews[0] || "")}')"></span>
            <span style="background-image:url('${esc(previews[2] || previews[1] || previews[0] || "")}')"></span>
          </div>
          <div class="portfolio-admin-badge">${album.items.length} ${album.items.length === 1 ? "piece" : "pieces"}</div>
        </div>
        <div class="portfolio-admin-body">
          <div class="portfolio-admin-title">${esc(album.album_title)}</div>
          <div class="portfolio-admin-meta">Album</div>
          <div class="portfolio-admin-actions">
            <span class="portfolio-admin-count">${album.items.length} item${album.items.length === 1 ? "" : "s"}</span>
            <button class="portfolio-admin-remove" onclick="removePortfolioAlbum('${esc(album.album_key)}')">Remove album</button>
          </div>
        </div>
      </div>
    `;
  }).join("");
}

async function loadPortfolioManager() {
  try {
    const response = await fetch(PORTFOLIO_API, { credentials: "include" });
    const data = await response.json();
    if (!data.success) {
      showToast(data.error || "Could not load portfolio", true);
      return;
    }
    artisanPortfolioItems = data.items || [];
    renderPortfolioManager();
  } catch (error) {
    console.error(error);
  }
}

function makeAlbumKey(title) {
  const slug = String(title || "").trim().toLowerCase().replace(/[^a-z0-9]+/g, "-").replace(/^-+|-+$/g, "");
  return slug || (`album-${Date.now()}`);
}

async function createPortfolioEntry(formData) {
  const response = await fetch(PORTFOLIO_API, {
    method: "POST",
    credentials: "include",
    body: formData
  });
  return response.json();
}

window.addPortfolioItem = async function addPortfolioItem() {
  const existingAlbumKey = el("portExistingAlbum")?.value || "";
  const existingAlbum = artisanPortfolioAlbums.find((album) => album.album_key === existingAlbumKey);
  const albumTitleInput = (el("portAlbumTitle")?.value || "").trim();
  const albumTitle = existingAlbum ? existingAlbum.album_title : albumTitleInput;
  const albumKey = existingAlbum ? existingAlbum.album_key : makeAlbumKey(albumTitle);
  const caption = (el("portTitle")?.value || "").trim();
  const type = el("portType")?.value || "image";
  const thumb = (el("portThumbUrl")?.value || "").trim();
  const mediaUrl = (el("portMediaUrl")?.value || "").trim();
  const files = Array.from(el("portFiles")?.files || []);

  if (!albumTitle) {
    showToast("Album name is required", true);
    return;
  }
  if (!files.length && !mediaUrl) {
    showToast("Add files or a media URL", true);
    return;
  }

  try {
    const entries = [];
    if (files.length) {
      files.forEach((file, index) => {
        const data = new FormData();
        data.append("album_key", albumKey);
        data.append("album_title", albumTitle);
        data.append("title", caption || file.name.replace(/\.[^.]+$/, "") || `${albumTitle} ${index + 1}`);
        data.append("media_type", file.type.startsWith("video/") ? "video" : "image");
        data.append("thumbnail_url", thumb);
        data.append("is_public", "1");
        data.append("media_file", file);
        entries.push(data);
      });
    } else {
      const data = new FormData();
      data.append("album_key", albumKey);
      data.append("album_title", albumTitle);
      data.append("title", caption || albumTitle);
      data.append("media_type", type);
      data.append("media_url", mediaUrl);
      data.append("thumbnail_url", thumb);
      data.append("is_public", "1");
      entries.push(data);
    }

    for (const entry of entries) {
      const result = await createPortfolioEntry(entry);
      if (!result.success) {
        showToast(result.error || "Could not save portfolio item", true);
        return;
      }
    }

    setVal("portAlbumTitle", "");
    setVal("portTitle", "");
    setVal("portThumbUrl", "");
    setVal("portMediaUrl", "");
    setVal("portExistingAlbum", "");
    const filesInput = el("portFiles");
    if (filesInput) filesInput.value = "";

    showToast(existingAlbum ? "Album updated" : "Album created");
    loadPortfolioManager();
  } catch (error) {
    console.error(error);
    showToast("Network error", true);
  }
};

window.removePortfolioAlbum = async function removePortfolioAlbum(albumKey) {
  const album = artisanPortfolioAlbums.find((item) => item.album_key === albumKey);
  if (!album) {
    showToast("Album not found", true);
    return;
  }
  if (!confirm(`Delete album "${album.album_title}" and all its items?`)) return;

  try {
    for (const item of album.items) {
      const response = await fetch(PORTFOLIO_API, {
        method: "DELETE",
        headers: { "Content-Type": "application/json" },
        credentials: "include",
        body: JSON.stringify({ id_portfolio: Number(item.id_portfolio) })
      });
      const data = await response.json();
      if (!data.success) {
        showToast(data.error || "Could not delete album", true);
        return;
      }
    }
    showToast("Album removed");
    loadPortfolioManager();
  } catch (error) {
    showToast("Network error", true);
  }
};

function ensureArtCommsUI() {
  if (!document.getElementById("artNotifModal")) {
    document.body.insertAdjacentHTML("beforeend", `
      <div id="artNotifModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:360;align-items:center;justify-content:center;">
        <div style="width:min(760px,96vw);max-height:86vh;background:var(--cream);border:1px solid var(--sand2);border-radius:1rem;overflow:hidden;display:flex;flex-direction:column;">
          <div style="padding:1rem 1.2rem;border-bottom:1px solid var(--sand2);display:flex;justify-content:space-between;align-items:center;">
            <div>
              <strong>Notifications & Messages</strong>
              <div style="font-size:.75rem;color:var(--text-m);">See unread alerts and direct chats</div>
            </div>
            <div style="display:flex;gap:.55rem;align-items:center;">
              <button onclick="markAllArtNotificationsRead()" style="background:#fff;border:1px solid var(--sand2);border-radius:.7rem;padding:.45rem .8rem;cursor:pointer;">Mark all read</button>
              <button style="background:none;border:none;cursor:pointer;" onclick="closeArtNotifModal()">Close</button>
            </div>
          </div>
          <div style="padding:1rem 1.2rem;display:grid;gap:1.1rem;overflow:auto;">
            <div>
              <div style="font-size:.78rem;letter-spacing:.08em;text-transform:uppercase;color:var(--clay);margin-bottom:.65rem;">Notifications</div>
              <div id="artNotifList" style="display:grid;gap:.65rem;"></div>
            </div>
            <div>
              <div style="font-size:.78rem;letter-spacing:.08em;text-transform:uppercase;color:var(--clay);margin-bottom:.65rem;">Direct Messages</div>
              <div id="artThreadList" style="display:grid;gap:.65rem;"></div>
            </div>
          </div>
        </div>
      </div>
    `);
  }

  if (!document.getElementById("artDirectChatModal")) {
    document.body.insertAdjacentHTML("beforeend", `
      <div id="artDirectChatModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:370;align-items:center;justify-content:center;">
        <div style="width:min(760px,96vw);max-height:86vh;background:var(--cream);border:1px solid var(--sand2);border-radius:1rem;overflow:hidden;display:flex;flex-direction:column;">
          <div style="padding:1rem 1.2rem;border-bottom:1px solid var(--sand2);display:flex;justify-content:space-between;align-items:center;">
            <div>
              <strong id="artDirectTitle">Chat with tourist</strong>
              <div id="artDirectSub" style="font-size:.75rem;color:var(--text-m);">Direct chat before reservation</div>
            </div>
            <button style="background:none;border:none;cursor:pointer;" onclick="closeArtDirectChat()">Close</button>
          </div>
          <div id="artDirectMessages" style="overflow:auto;padding:1rem;display:grid;gap:.6rem;background:#fff9f3;"></div>
          <div style="padding:.9rem;border-top:1px solid var(--sand2);display:flex;gap:.6rem;">
            <input id="artDirectInput" placeholder="Write a message..." style="flex:1;border:1px solid var(--sand2);border-radius:.7rem;padding:.7rem;background:#fff;"/>
            <button class="add-btn" style="padding:.75rem 1.1rem;" onclick="sendArtDirectMessage()">Send</button>
          </div>
        </div>
      </div>
    `);
  }
}

async function refreshArtNotifDot() {
  try {
    const res = await fetch(`${NOTIFICATIONS_API}?limit=20`, { credentials: "include" });
    const data = await res.json();
    const dot = el("artNotifDot");
    if (dot) dot.style.display = data.success && Number(data.unread_count || 0) > 0 ? "" : "none";
  } catch (error) {
  }
}

async function loadArtNotificationsModal() {
  ensureArtCommsUI();
  const notifList = el("artNotifList");
  const threadList = el("artThreadList");
  if (notifList) notifList.innerHTML = '<div style="color:var(--text-m);font-style:italic;">Loading notifications...</div>';
  if (threadList) threadList.innerHTML = '<div style="color:var(--text-m);font-style:italic;">Loading messages...</div>';

  try {
    const [notifRes, threadRes] = await Promise.all([
      fetch(`${NOTIFICATIONS_API}?limit=30`, { credentials: "include" }),
      fetch(`${SHARED_CHAT_API}?mode=direct_threads`, { credentials: "include" })
    ]);
    const notifData = await notifRes.json();
    const threadData = await threadRes.json();

    if (notifList) {
      const notifications = notifData.success ? (notifData.notifications || []) : [];
      notifList.innerHTML = notifications.length ? notifications.map((item) => `
        <button onclick="openArtNotification(${Number(item.id_notification)}, '${esc(item.link_url || "")}')" style="text-align:left;background:#fff;border:1px solid var(--sand2);border-radius:.85rem;padding:.85rem .95rem;cursor:pointer;">
          <div style="display:flex;justify-content:space-between;gap:.75rem;align-items:flex-start;">
            <div>
              <div style="font-weight:600;color:var(--ink);">${esc(item.title || "Notification")}</div>
              <div style="font-size:.78rem;color:var(--text-m);margin-top:.2rem;">${esc(item.message || "")}</div>
            </div>
            ${Number(item.is_read || 0) === 0 ? '<span style="width:10px;height:10px;border-radius:50%;background:#e05252;flex-shrink:0;margin-top:.2rem;"></span>' : ''}
          </div>
        </button>
      `).join("") : '<div style="color:var(--text-m);font-style:italic;">No notifications yet.</div>';
    }

    if (threadList) {
      const threads = threadData.success ? (threadData.threads || []) : [];
      threadList.innerHTML = threads.length ? threads.map((thread) => `
        <button onclick="openArtDirectChatModal(${Number(thread.counterpart_user_id)}, '${esc(thread.counterpart_name || "Tourist")}')" style="text-align:left;background:#fff;border:1px solid var(--sand2);border-radius:.85rem;padding:.85rem .95rem;cursor:pointer;">
          <div style="display:flex;justify-content:space-between;gap:.75rem;align-items:flex-start;">
            <div>
              <div style="font-weight:600;color:var(--ink);">${esc(thread.counterpart_name || "Tourist")}</div>
              <div style="font-size:.78rem;color:var(--text-m);margin-top:.2rem;">${esc(thread.last_message || "Open chat")}</div>
            </div>
            ${Number(thread.unread_count || 0) > 0 ? `<span style="min-width:22px;height:22px;border-radius:999px;background:#e05252;color:#fff;font-size:.72rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;">${Number(thread.unread_count)}</span>` : ''}
          </div>
        </button>
      `).join("") : '<div style="color:var(--text-m);font-style:italic;">No direct messages yet.</div>';
    }
    refreshArtNotifDot();
  } catch (error) {
    if (notifList) notifList.innerHTML = '<div style="color:#a33;">Could not load notifications.</div>';
    if (threadList) threadList.innerHTML = '<div style="color:#a33;">Could not load messages.</div>';
  }
}

function initArtisanNotifications() {
  ensureArtCommsUI();
  refreshArtNotifDot();
  if (artNotifPollTimer) clearInterval(artNotifPollTimer);
  artNotifPollTimer = setInterval(refreshArtNotifDot, 15000);
}

function initArtisanDeepLinkChat() {
  const params = new URLSearchParams(window.location.search);
  const directUser = Number(params.get("direct_user") || 0);
  const directName = params.get("direct_name") || "";
  if (directUser > 0) {
    setTimeout(() => openArtDirectChatModal(directUser, directName || "Tourist"), 250);
  }
}

window.openArtNotifModal = function openArtNotifModal() {
  ensureArtCommsUI();
  const modal = el("artNotifModal");
  if (modal) modal.style.display = "flex";
  loadArtNotificationsModal();
};

window.closeArtNotifModal = function closeArtNotifModal() {
  const modal = el("artNotifModal");
  if (modal) modal.style.display = "none";
};

window.markAllArtNotificationsRead = async function markAllArtNotificationsRead() {
  try {
    await fetch(NOTIFICATIONS_API, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      credentials: "include",
      body: JSON.stringify({ mode: "all" })
    });
    loadArtNotificationsModal();
  } catch (error) {
    showToast("Could not mark notifications", true);
  }
};

window.openArtNotification = async function openArtNotification(id, linkUrl) {
  try {
    await fetch(NOTIFICATIONS_API, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      credentials: "include",
      body: JSON.stringify({ id_notification: Number(id) })
    });
  } catch (error) {
  }
  refreshArtNotifDot();
  if (linkUrl) {
    window.location.href = linkUrl;
  }
};

window.openArtDirectChatModal = async function openArtDirectChatModal(userId, label) {
  ensureArtCommsUI();
  artDirectUserId = Number(userId) || 0;
  artDirectLabel = label || "Tourist";
  if (!artDirectUserId) {
    showToast("Chat user not found", true);
    return;
  }
  const title = el("artDirectTitle");
  const sub = el("artDirectSub");
  if (title) title.textContent = `Chat with ${artDirectLabel}`;
  if (sub) sub.textContent = "Direct chat before reservation";
  const modal = el("artDirectChatModal");
  if (modal) modal.style.display = "flex";
  await loadArtDirectMessages();
};

window.closeArtDirectChat = function closeArtDirectChat() {
  const modal = el("artDirectChatModal");
  if (modal) modal.style.display = "none";
  artDirectUserId = 0;
  artDirectLabel = "";
};

async function loadArtDirectMessages() {
  const box = el("artDirectMessages");
  if (!box || !artDirectUserId) return;
  box.innerHTML = '<p style="color:var(--text-m);">Loading chat...</p>';
  try {
    const res = await fetch(`${SHARED_CHAT_API}?direct_user_id=${artDirectUserId}`, { credentials: "include" });
    const data = await res.json();
    if (!data.success) {
      box.innerHTML = `<p style="color:#a33;">${esc(data.error || "Could not load chat")}</p>`;
      return;
    }
    const messages = data.messages || [];
    if (!messages.length) {
      box.innerHTML = '<p style="color:var(--text-m);">Start chatting with this tourist.</p>';
    } else {
      box.innerHTML = messages.map((msg) => {
        const mine = Number(msg.id_sender) === Number(currentArtisanUser?.id || 0);
        return `
          <div style="display:flex;justify-content:${mine ? "flex-end" : "flex-start"};">
            <div style="max-width:78%;background:${mine ? "var(--clay)" : "#fff"};color:${mine ? "#fff" : "var(--ink)"};border:${mine ? "none" : "1px solid var(--sand2)"};padding:.7rem .85rem;border-radius:.9rem;">
              <div style="font-size:.72rem;opacity:.75;margin-bottom:.2rem;">${esc(msg.sender_name || "")}</div>
              <div style="line-height:1.5;">${esc(msg.message || "")}</div>
            </div>
          </div>
        `;
      }).join("");
    }
    box.scrollTop = box.scrollHeight;
    refreshArtNotifDot();
    loadArtNotificationsModal();
  } catch (error) {
    box.innerHTML = '<p style="color:#a33;">Network error while loading chat.</p>';
  }
}

window.sendArtDirectMessage = async function sendArtDirectMessage() {
  const input = el("artDirectInput");
  const message = (input?.value || "").trim();
  if (!message || !artDirectUserId) return;
  try {
    const res = await fetch(SHARED_CHAT_API, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      credentials: "include",
      body: JSON.stringify({ direct_user_id: artDirectUserId, message })
    });
    const data = await res.json();
    if (!data.success) {
      showToast(data.error || "Could not send message", true);
      return;
    }
    if (input) input.value = "";
    loadArtDirectMessages();
  } catch (error) {
    showToast("Network error", true);
  }
};

window.handleLogout = async function handleLogout() {
  try {
    await fetch("php/auth/logout.php", { method: "POST", credentials: "include" });
  } catch (error) {
  }
  localStorage.removeItem("tc_user");
  window.location.href = "tuncover-login.html";
};

window.saveProfile = async function saveProfile() {
  const fullName = (el("pfName")?.value || "").trim().split(/\s+/).filter(Boolean);
  const formData = new FormData();
  formData.append("prenom", fullName[0] || "");
  formData.append("nom", fullName.slice(1).join(" ") || "");
  formData.append("specialite", el("pfSpecialite")?.value || "");
  formData.append("ville", el("pfVille")?.value || "");
  formData.append("bio", el("pfBio")?.value || "");

  const avatarFile = el("pfAvatarFile")?.files?.[0];
  if (avatarFile) {
    formData.append("avatar_file", avatarFile);
  }

  try {
    const response = await fetch(PROFILE_API, {
      method: "POST",
      credentials: "include",
      body: formData
    });
    const data = await response.json();
    if (data.success) {
      showToast("Profile saved");
      loadCurrentUser();
    } else {
      showToast(data.error || "Failed to save profile", true);
    }
  } catch (error) {
    showToast("Network error", true);
  }
};
