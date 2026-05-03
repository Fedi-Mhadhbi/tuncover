const TAPI = {
  me: "php/auth/me.php",
  logout: "php/auth/logout.php",
  bookings: "php/tourist/tourist_bookings.php",
  reviews: "php/tourist/reviews.php"
};

let allBk = [];
let currentBkFilter = "all";
let activeReviewBooking = null;
let activeReviewStars = 0;

function esc(s) {
  return String(s || "").replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
}

function gel(id) {
  return document.getElementById(id);
}

function setEl(id, v) {
  const e = gel(id);
  if (e) e.textContent = v || "";
}

function setVal(id, v) {
  const e = gel(id);
  if (e) e.value = v || "";
}

function toast(msg, ok = true) {
  const t = gel("tcToast");
  if (!t) return;
  t.textContent = (ok ? "OK: " : "Error: ") + msg;
  t.style.transform = "translateY(0)";
  t.style.opacity = "1";
  setTimeout(() => {
    t.style.transform = "translateY(80px)";
    t.style.opacity = "0";
  }, 3000);
}

const PTITLES = {
  overview: "Overview",
  bookings: "My Bookings",
  wishlist: "Wishlist",
  reviews: "Reviews",
  profile: "Profile"
};

function showPanel(id, el) {
  document.querySelectorAll(".dash-panel").forEach((p) => p.classList.remove("active"));
  const panel = gel("panel-" + id);
  if (panel) panel.classList.add("active");

  document.querySelectorAll(".nav-item,.mob-tab").forEach((n) => n.classList.remove("active"));
  if (el) el.classList.add("active");

  setEl("panelTitle", PTITLES[id] || "Dashboard");

  if (id === "bookings") loadBookings();
  if (id === "reviews") loadReviews();
}

window.showPanel = showPanel;

(function bootstrapTouristDashboard() {
  try {
    const u = JSON.parse(localStorage.getItem("tc_user") || "null");
    if (!u || !u.id) {
      window.location.href = "tuncover-login.html";
      return;
    }
    quickInject(u);
    verifyAndLoad();
  } catch (e) {
    localStorage.removeItem("tc_user");
    window.location.href = "tuncover-login.html";
  }
})();

function quickInject(u) {
  const ini = ((u.prenom || "?")[0] + (u.nom || "?")[0]).toUpperCase();
  const fullName = (u.prenom || "") + " " + (u.nom || "");

  setEl("sbAvatar", ini);
  setEl("sbName", fullName);
  setEl("chipAvatar", ini);
  setEl("chipName", u.prenom || "");
  setEl("greetName", u.prenom || "");
  setEl("profAvatar", ini);
  setEl("profName", fullName);
  setVal("pfPrenom", u.prenom || "");
  setVal("pfNom", u.nom || "");
  setVal("pfEmail", u.email || "");
}

async function verifyAndLoad() {
  try {
    const res = await fetch(TAPI.me, { credentials: "include" });
    const text = await res.text();
    const data = JSON.parse(text);
    if (data.success && data.user) {
      const u = data.user;
      localStorage.setItem("tc_user", JSON.stringify({
        id: u.id_utilisateur || u.id,
        nom: u.nom,
        prenom: u.prenom,
        email: u.email,
        role: u.role
      }));
      quickInject({
        prenom: u.prenom,
        nom: u.nom,
        email: u.email,
        role: u.role
      });
    }
  } catch (e) {
    console.warn("Using cached tourist user data");
  }
  loadOverviewData();
}

async function loadOverviewData() {
  try {
    const res = await fetch(TAPI.bookings, { credentials: "include" });
    const data = await res.json();
    const bk = data.bookings || [];
    allBk = bk;
    const upcoming = bk.filter((b) => b.statut === "confirmee" || b.statut === "en_attente");

    setEl("statTotal", String(bk.length));
    setEl("statUpcoming", String(upcoming.length));
    setEl("statWishlist", "0");
    setEl("statReviews", String(bk.filter((b) => Number(b.has_review || 0) === 1).length));

    const box = gel("overviewBookings");
    if (!box) return;

    if (!upcoming.length) {
      box.innerHTML = "<p style=\"color:var(--text-m);font-size:0.85rem;\">No upcoming bookings yet.</p>";
      return;
    }

    box.innerHTML = upcoming.slice(0, 3).map(renderOverviewBooking).join("");
  } catch (e) {
    console.error("loadOverviewData", e);
  }
}

function bookingStatusBadge(status) {
  const map = {
    en_attente: '<span class="status-badge status-pending">Pending</span>',
    confirmee: '<span class="status-badge status-confirmed">Confirmed</span>',
    terminee: '<span class="status-badge status-completed">Completed</span>',
    annulee: '<span class="status-badge status-cancelled">Cancelled</span>'
  };
  return map[status] || `<span class="status-badge">${esc(status || "-")}</span>`;
}

function paymentLabel(status) {
  if (status === "completed") return "Paid";
  if (status === "refunded") return "Refunded";
  return "Unpaid";
}

function renderOverviewBooking(booking) {
  return `
    <div class="booking-item booking-item-compact">
      <div class="booking-main">
        <div class="booking-header-row">
          <div>
            <div class="booking-title">${esc(booking.activite_titre || "Activity")}</div>
            <div class="booking-artisan">Hosted by ${esc([booking.artisan_prenom, booking.artisan_nom].filter(Boolean).join(" ") || "Artisan")}</div>
          </div>
          ${bookingStatusBadge(booking.statut)}
        </div>
        <div class="booking-meta-grid">
          <div class="booking-meta-cell">
            <div class="booking-meta-label">Date</div>
            <strong>${esc(booking.date_activite || "-")}</strong>
          </div>
          <div class="booking-meta-cell">
            <div class="booking-meta-label">Guests</div>
            <strong>${Number(booking.nb_participants || 1)}</strong>
          </div>
          <div class="booking-meta-cell">
            <div class="booking-meta-label">Total</div>
            <strong>${esc(booking.montant_total || 0)} TND</strong>
          </div>
        </div>
      </div>
    </div>
  `;
}

function renderBookings() {
  const box = gel("bkList");
  if (!box) return;

  const rows = (allBk || []).filter((booking) => {
    if (currentBkFilter === "all") return true;
    if (currentBkFilter === "upcoming") return booking.statut === "confirmee" || booking.statut === "en_attente";
    return booking.statut === currentBkFilter;
  });

  if (!rows.length) {
    box.innerHTML = '<p style="color:var(--text-m);font-size:0.85rem;padding:1rem 0;">No bookings in this section.</p>';
    return;
  }

  box.innerHTML = rows.map((booking) => {
    const artisan = [booking.artisan_prenom, booking.artisan_nom].filter(Boolean).join(" ").trim() || "Artisan";
    const paymentStatus = String(booking.payment_status || "pending");
    const reviewDone = Number(booking.has_review || 0) > 0;
    const canCancel = booking.statut === "en_attente" || booking.statut === "confirmee";
    const canPay = booking.statut === "confirmee" && paymentStatus !== "completed" && paymentStatus !== "refunded";
    const canReview = (booking.statut === "confirmee" || booking.statut === "terminee") && paymentStatus === "completed" && !reviewDone;
    const canReceipt = paymentStatus === "completed" || paymentStatus === "refunded";

    return `
      <div class="booking-item">
        <div class="booking-main">
          <div class="booking-header-row">
            <div>
              <div class="booking-title">${esc(booking.activite_titre || "Activity")}</div>
              <div class="booking-artisan">Hosted by ${esc(artisan)}</div>
            </div>
            ${bookingStatusBadge(booking.statut)}
          </div>

          <div class="booking-meta-grid">
            <div class="booking-meta-cell">
              <div class="booking-meta-label">Date</div>
              <strong>${esc(booking.date_activite || "-")} ${esc(booking.heure_activite || "")}</strong>
            </div>
            <div class="booking-meta-cell">
              <div class="booking-meta-label">Guests</div>
              <strong>${Number(booking.nb_participants || 1)}</strong>
            </div>
            <div class="booking-meta-cell">
              <div class="booking-meta-label">Total</div>
              <strong>${esc(booking.montant_total || 0)} TND</strong>
            </div>
            <div class="booking-meta-cell">
              <div class="booking-meta-label">Payment</div>
              <strong>${esc(paymentLabel(paymentStatus))}</strong>
            </div>
          </div>
        </div>

        <div class="booking-actions">
          ${canPay ? `<button class="btn-sm btn-pay" onclick="goPay(${Number(booking.id_reservation)})">Pay Now</button>` : ""}
          ${canReceipt ? `<button class="btn-sm btn-receipt" onclick="openReceipt(${Number(booking.id_reservation)})">Receipt</button>` : ""}
          ${canReview ? `<button class="btn-sm btn-review" onclick="openRvModal(${Number(booking.id_reservation)})">Leave Review</button>` : ""}
          ${reviewDone ? `<button class="btn-sm btn-reviewed" disabled>Reviewed</button>` : ""}
          ${canCancel ? `<button class="btn-sm btn-cancel" onclick="cancelBk(${Number(booking.id_reservation)})">Cancel</button>` : ""}
        </div>
      </div>
    `;
  }).join("");
}

function filterBookings(type, btn) {
  currentBkFilter = type || "all";
  document.querySelectorAll("#bkTabs .b-tab").forEach((node) => node.classList.remove("active"));
  if (btn) btn.classList.add("active");
  renderBookings();
}

window.filterBookings = filterBookings;

async function loadBookings() {
  const box = gel("bkList");
  if (box) box.innerHTML = "<p>Loading...</p>";

  try {
    const res = await fetch(TAPI.bookings, { credentials: "include" });
    const data = await res.json();
    if (!data.success) {
      if (box) box.innerHTML = "<p>Failed loading bookings</p>";
      return;
    }

    allBk = data.bookings || [];
    renderBookings();
  } catch (e) {
    console.error(e);
    if (box) box.innerHTML = "<p>Network error</p>";
  }
}

async function cancelBk(id) {
  if (!confirm("Cancel this booking?")) return;

  try {
    const res = await fetch(TAPI.bookings, {
      method: "DELETE",
      headers: { "Content-Type": "application/json" },
      credentials: "include",
      body: JSON.stringify({ id: id })
    });
    const data = await res.json();
    if (data.success) {
      toast("Booking cancelled");
      loadBookings();
      loadOverviewData();
    } else {
      toast(data.error || "Error", false);
    }
  } catch (e) {
    toast("Network error", false);
  }
}

window.cancelBk = cancelBk;

async function loadWishlist() {
  const box = gel("wlGrid");
  if (box) box.innerHTML = "<p style=\"color:var(--text-m);\">Wishlist will be repaired next.</p>";
}

async function loadReviews() {
  const box = gel("rvList");
  if (box) box.innerHTML = '<p style="color:var(--text-m);font-size:0.85rem;padding:1rem 0;">Loading...</p>';

  try {
    const res = await fetch(TAPI.reviews, { credentials: "include" });
    const data = await res.json();
    if (!data.success) {
      if (box) box.innerHTML = '<p style="color:#a33;">Failed loading reviews</p>';
      return;
    }

    const reviews = data.reviews || [];
    setEl("statReviews", String(reviews.length));

    if (!box) return;
    if (!reviews.length) {
      box.innerHTML = '<p style="color:var(--text-m);font-size:0.85rem;padding:1rem 0;">No reviews yet. Open a paid booking and leave one.</p>';
      return;
    }

    box.innerHTML = reviews.map((review) => {
      const stars = "&#9733;".repeat(Math.max(0, Math.round(review.note || 0))) + "&#9734;".repeat(Math.max(0, 5 - Math.round(review.note || 0)));
      const date = review.date_avis ? new Date(review.date_avis).toLocaleDateString("en-GB", { day: "2-digit", month: "short", year: "numeric" }) : "";
      return `
        <div class="review-card">
          <div class="booking-title" style="font-size:1.2rem;">${esc(review.activite_titre || "Activity")}</div>
          <div class="booking-artisan" style="margin-top:0.25rem;">Artisan: ${esc(review.artisan_nom || "Artisan")}</div>
          <div style="color:var(--gold);font-size:1.05rem;margin-top:0.65rem;">${stars}</div>
          <div style="font-size:0.76rem;color:var(--text-m);margin-top:0.35rem;">${esc(date)}</div>
          <p style="margin-top:0.9rem;color:var(--ink);line-height:1.65;">${esc(review.commentaire || "")}</p>
        </div>
      `;
    }).join("");
  } catch (e) {
    console.error(e);
    if (box) box.innerHTML = '<p style="color:#a33;">Network error</p>';
  }
}

function openRvModal(id) {
  const booking = (allBk || []).find((item) => Number(item.id_reservation) === Number(id));
  if (!booking) {
    toast("Booking not found", false);
    return;
  }

  activeReviewBooking = booking;
  activeReviewStars = 0;
  setStars(0);
  if (gel("rvComment")) gel("rvComment").value = "";
  if (gel("rvError")) {
    gel("rvError").style.display = "none";
    gel("rvError").textContent = "";
  }
  setEl("rvActTitle", `${booking.activite_titre || "Activity"} with ${[booking.artisan_prenom, booking.artisan_nom].filter(Boolean).join(" ") || "Artisan"}`);
  const modal = gel("rvModal");
  if (modal) modal.style.display = "flex";
}

window.openRvModal = openRvModal;

function closeRvModal() {
  const modal = gel("rvModal");
  if (modal) modal.style.display = "none";
  activeReviewBooking = null;
  activeReviewStars = 0;
  setStars(0);
}

window.closeRvModal = closeRvModal;

function setStars(value) {
  activeReviewStars = value;
  document.querySelectorAll("#rvStars .rv-star").forEach((star, index) => {
    star.innerHTML = index < value ? "&#9733;" : "&#9734;";
  });
}

window.setStars = setStars;

async function submitReview() {
  if (!activeReviewBooking) {
    toast("Select a booking first", false);
    return;
  }

  const comment = (gel("rvComment")?.value || "").trim();
  const errorNode = gel("rvError");
  const submitBtn = gel("rvSubmitBtn");

  if (activeReviewStars < 1) {
    if (errorNode) {
      errorNode.textContent = "Please choose a rating";
      errorNode.style.display = "block";
    }
    return;
  }
  if (comment.length < 10) {
    if (errorNode) {
      errorNode.textContent = "Please write at least 10 characters";
      errorNode.style.display = "block";
    }
    return;
  }
  if (errorNode) {
    errorNode.style.display = "none";
    errorNode.textContent = "";
  }

  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.textContent = "Submitting...";
  }

  try {
    const res = await fetch(TAPI.reviews, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      credentials: "include",
      body: JSON.stringify({
        id_reservation: Number(activeReviewBooking.id_reservation),
        note: activeReviewStars,
        commentaire: comment
      })
    });
    const data = await res.json();
    if (!data.success) {
      if (errorNode) {
        errorNode.textContent = data.error || "Could not save review";
        errorNode.style.display = "block";
      }
      return;
    }

    toast("Review submitted");
    closeRvModal();
    await loadBookings();
    await loadReviews();
    await loadOverviewData();
  } catch (e) {
    if (errorNode) {
      errorNode.textContent = "Network error";
      errorNode.style.display = "block";
    }
  } finally {
    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.textContent = "Submit";
    }
  }
}

window.submitReview = submitReview;

function goPay(reservationId) {
  window.location.href = `tuncover-payment.html?reservation_id=${Number(reservationId)}`;
}

window.goPay = goPay;

function openReceipt(reservationId) {
  window.open(`php/tourist/receipt.php?format=pdf&reservation_id=${Number(reservationId)}`, "_blank");
}

async function saveProfile() {
  toast("Profile save UI is preserved. Backend profile save will be repaired next if needed.");
}

window.saveProfile = saveProfile;

async function logoutTourist(e) {
  if (e) e.preventDefault();
  try {
    await fetch(TAPI.logout, { method: "POST", credentials: "include" });
  } catch (err) {}
  localStorage.removeItem("tc_user");
  window.location.href = "tuncover-login.html";
}

window.handleLogout = logoutTourist;
