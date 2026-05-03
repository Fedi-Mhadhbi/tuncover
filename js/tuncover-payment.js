const paymentQuery = new URLSearchParams(window.location.search);
const reservationId = Number.parseInt(paymentQuery.get("reservation_id") || "0", 10);

let paymentData = null;
let testCards = [];

function payEl(id) {
  return document.getElementById(id);
}

function fmtMoney(value) {
  return `${Number(value || 0).toFixed(2)} TND`;
}

function showPayError(message) {
  const box = payEl("payError");
  if (!box) return;
  box.textContent = message || "";
  box.style.display = message ? "block" : "none";
}

function setText(id, value) {
  const el = payEl(id);
  if (el) el.textContent = value;
}

function setValue(id, value) {
  const el = payEl(id);
  if (el) el.value = value;
}

function lockPaymentForm(disabled, buttonLabel) {
  const form = payEl("paymentForm");
  if (!form) return;

  form.querySelectorAll("input, button").forEach((field) => {
    if (field.id === "amountInput") return;
    if (field.type === "submit") return;
    field.disabled = disabled;
  });

  const payBtn = payEl("payBtn");
  if (payBtn) {
    payBtn.disabled = disabled;
    payBtn.textContent = buttonLabel || "Pay now";
  }
}

function fillDemoCard(index) {
  const card = testCards[index];
  if (!card) return;

  setValue("cardHolder", card.card_holder || "");
  setValue("cardNumber", card.card_number || "");
  setValue("expMonth", card.exp_month || "");
  setValue("expYear", String(card.exp_year || "").slice(-2));
  setValue("cvv", card.cvv || "");
}

window.fillDemoCard = fillDemoCard;

function renderDemoCards() {
  const root = payEl("demoCards");
  if (!root) return;

  if (!testCards.length) {
    root.innerHTML = '<p class="demo-empty">No virtual card found for this tourist account.</p>';
    return;
  }

  root.innerHTML = testCards.map((card, index) => {
    const expYear = String(card.exp_year || "").slice(-2);
    return `
      <button type="button" class="demo-card-btn" onclick="fillDemoCard(${index})">
        <strong>${card.card_brand || "CARD"}</strong>
        <span>${card.card_holder || "Tourist card"}</span>
        <code>${card.card_number || ""}</code>
        <small>${card.exp_month || "--"}/${expYear || "--"} | CVV ${card.cvv || "---"}</small>
      </button>
    `;
  }).join("");

  fillDemoCard(0);
}

function setReceiptLinks() {
  const receiptUrl = `php/tourist/receipt.php?format=pdf&reservation_id=${reservationId}`;
  const receiptLink = payEl("downloadReceiptLink");
  const viewLink = payEl("viewBookingLink");
  const dashboardLink = payEl("backToDashboard");

  if (receiptLink) receiptLink.href = receiptUrl;
  if (viewLink) viewLink.href = "tuncover-tourist.html";
  if (dashboardLink) dashboardLink.href = "tuncover-tourist.html";
}

function showPaymentSuccess(message) {
  const form = payEl("paymentForm");
  const box = payEl("successBox");
  const text = payEl("successText");

  if (form) form.style.display = "none";
  if (text) text.textContent = message;
  if (box) box.style.display = "block";

  showPayError("");
  setReceiptLinks();
}

function populateSummary(data) {
  setText("sumTitle", data.activity_title || "-");
  setText("sumArtisan", data.artisan_name || "-");
  setText("sumDate", `${data.date_activite || "-"} ${data.heure_activite || ""}`.trim());
  setText("sumGuests", `${Number(data.participants || 0)} guest(s)`);
  setText("sumAmount", fmtMoney(data.amount));
  setValue("amountInput", fmtMoney(data.amount));
}

function populateExistingCard(data) {
  if (data.card_holder) setValue("cardHolder", data.card_holder);
}

async function loadCheckout() {
  if (!reservationId) {
    showPayError("Missing reservation ID.");
    lockPaymentForm(true, "Reservation missing");
    return;
  }

  try {
    const response = await fetch(`php/tourist/payment.php?reservation_id=${reservationId}`, {
      credentials: "include"
    });

    const raw = await response.text();
    let data;

    try {
      data = JSON.parse(raw);
    } catch (parseError) {
      console.error("Payment JSON parse failed:", raw);
      showPayError("The payment page returned an invalid response.");
      lockPaymentForm(true, "Unavailable");
      return;
    }

    if (!response.ok || !data.success || !data.payment) {
      showPayError(data && data.error ? data.error : "Could not load payment details.");
      lockPaymentForm(true, "Unavailable");
      return;
    }

    paymentData = data.payment;
    testCards = Array.isArray(data.test_cards) ? data.test_cards : [];

    populateSummary(paymentData);
    populateExistingCard(paymentData);
    renderDemoCards();
    setReceiptLinks();

    if (paymentData.payment_status === "completed") {
      showPaymentSuccess(`This booking was already paid with transaction ${paymentData.transaction_ref || ""}.`);
      return;
    }

    if (paymentData.reservation_status !== "confirmee") {
      lockPaymentForm(true, "Waiting for artisan confirmation");
      showPayError("You can pay after the artisan confirms this reservation.");
      return;
    }

    lockPaymentForm(false, "Pay now");
    showPayError("");
  } catch (error) {
    console.error("Payment load failed:", error);
    showPayError("Network error while loading payment details.");
    lockPaymentForm(true, "Unavailable");
  }
}

async function submitPayment(event) {
  event.preventDefault();

  if (!paymentData) {
    showPayError("Payment details are not loaded yet.");
    return;
  }

  if (paymentData.reservation_status !== "confirmee") {
    showPayError("You can pay after the artisan confirms this reservation.");
    return;
  }

  const payBtn = payEl("payBtn");
  if (payBtn) {
    payBtn.disabled = true;
    payBtn.textContent = "Processing...";
  }

  showPayError("");

  try {
    const response = await fetch("php/tourist/payment.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json"
      },
      credentials: "include",
      body: JSON.stringify({
        reservation_id: reservationId,
        card_holder: payEl("cardHolder") ? payEl("cardHolder").value.trim() : "",
        card_number: payEl("cardNumber") ? payEl("cardNumber").value.trim() : "",
        exp_month: payEl("expMonth") ? payEl("expMonth").value.trim() : "",
        exp_year: payEl("expYear") ? payEl("expYear").value.trim() : "",
        cvv: payEl("cvv") ? payEl("cvv").value.trim() : "",
        amount: Number(paymentData.amount || 0)
      })
    });

    const raw = await response.text();
    let data;

    try {
      data = JSON.parse(raw);
    } catch (parseError) {
      console.error("Payment submit parse failed:", raw);
      showPayError("The payment response was invalid.");
      return;
    }

    if (!response.ok || !data.success) {
      showPayError(data && data.error ? data.error : "Payment failed.");
      return;
    }

    paymentData.payment_status = "completed";
    paymentData.transaction_ref = data.payment ? data.payment.transaction_ref : "";

    showPaymentSuccess(
      `Transaction ${paymentData.transaction_ref || ""} was saved. The artisan received ${fmtMoney(data.payment ? data.payment.artisan_amount : 0)}.`
    );
  } catch (error) {
    console.error("Payment submit failed:", error);
    showPayError("Network error while processing payment.");
  } finally {
    if (payBtn && paymentData && paymentData.payment_status !== "completed") {
      payBtn.disabled = false;
      payBtn.textContent = "Pay now";
    }
  }
}

document.addEventListener("DOMContentLoaded", () => {
  const form = payEl("paymentForm");
  if (form) {
    form.addEventListener("submit", submitPayment);
  }

  loadCheckout();
});
