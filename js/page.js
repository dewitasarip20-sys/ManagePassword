// ==== Utility: Escape HTML ====
function escapeHTML(str) {
  return String(str)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

// ==== Render Card ====
function addPasswordCard(pass, view = "personal") {
  const lastEditedInfo =
    view === "shared"
      ? `
  <div class="field">
    <div class="field-icon"><i class="fas fa-user-edit"></i></div>
    <div class="field-content">
      <span class="field-label">Last Edited:</span>
      <span class="field-value" data-type="last-edited">
      ${escapeHTML(pass.updated_by_username || "Unknown")} 
      on ${
        pass.updated_at && !isNaN(new Date(pass.updated_at))
        ? new Date(pass.updated_at).toISOString().split("T")[0]
        : "Unknown Date"
      }
      </span>
    </div>
  </div>`
      : "";

  const notesHTML = pass.notes
    ? `
    <div class="field notes-field"><div class="field-icon"><i class="fas fa-sticky-note"></i></div>
      <div class="field-content"><span class="field-label">Notes:</span>
        <span class="field-value" data-type="notes">${escapeHTML(
          pass.notes
        )}</span></div></div>`
    : "";

  const card = document.createElement("div");
  card.className = "card";
  card.setAttribute("data-id", pass.id);
  card.setAttribute("data-view", view);

  card.innerHTML = `
    <div class="card-header">
      <h3 class="card-title">${escapeHTML(pass.title)}</h3>
    </div>
    <div class="field"><div class="field-icon"><i class="fas fa-user"></i></div>
      <div class="field-content"><span class="field-label">Username:</span>
        <span class="field-value" data-type="username">${escapeHTML(
          pass.username
        )}</span></div></div>
    <div class="field password-field"><div class="field-icon"><i class="fas fa-key"></i></div>
      <div class="field-content"><span class="field-label">Password:</span>
        <span class="field-value password-value real-password" style="user-select: text;">${escapeHTML(
          pass.password
        )}</span>
      </div></div>
    <div class="field"><div class="field-icon"><i class="fas fa-link"></i></div>
      <div class="field-content"><span class="field-label">URL/IP Address:</span>
        <span class="field-value" data-type="url">${escapeHTML(
          pass.url || "N/A"
        )}</span></div></div>
    ${notesHTML}
    ${lastEditedInfo}
    <div class="card-footer">
      <button class="view-details-btn" type="button"> View Details</button>
      <div class="action-buttons">
        <button class="edit-btn" type="button"><i class="fas fa-edit"></i> Edit</button>
        <button class="delete-btn" type="button"><i class="fas fa-trash"></i> Delete</button>
      </div>
    </div>
  `;

  document.getElementById("cardContainer").prepend(card);
}

// ==== Load Passwords ====
async function loadPasswordsByView(view) {
  const container = document.getElementById("cardContainer");
  container.innerHTML = "";

  const url =
    view === "shared"
      ? "php/get_shared_password.php"
      : "./php/api/get_password.php";

  try {
    const res = await fetch(url);
    const text = await res.text();
    try {
      const data = JSON.parse(text);
      if (data.success) {
        data.passwords.forEach((pass) => addPasswordCard(pass, view));
      } else {
        alert(`Failed to load password. Please try again.: ${data.message}`);
        if (data.message === "User not logged in")
          window.location.href = "loginPage.html";
      }
    } catch (jsonErr) {
      console.error("JSON parse error:", jsonErr);
      console.error("Raw response:", text);
      alert("Invalid data format from the server.");
    }
  } catch (err) {
    console.error("Load error:", err);
    alert("An error occurred while loading the password. Please try again.");
  }
}

// ==== Toggle View Active Button ====
function setActiveViewButton(view) {
  document.querySelectorAll(".view-toggle-btn").forEach((btn) => {
    btn.classList.toggle("active", btn.dataset.view === view);
  });
}

// ==== Search ====
document.getElementById("searchInput").addEventListener("input", function () {
  const searchValue = this.value.toLowerCase();
  document.querySelectorAll(".card").forEach((card) => {
    const title =
      card.querySelector(".card-title")?.textContent.toLowerCase() || "";
    const username =
      card.querySelector('[data-type="username"]')?.textContent.toLowerCase() ||
      "";
    const url =
      card.querySelector('[data-type="url"]')?.textContent.toLowerCase() || "";
    const lastEdited =
      card
        .querySelector('[data-type="last-edited"]')
        ?.textContent.toLowerCase() || "";

    const isMatch =
      title.includes(searchValue) ||
      username.includes(searchValue) ||
      url.includes(searchValue) ||
      lastEdited.includes(searchValue);

    card.style.display = isMatch ? "" : "none";
  });
});

// ==== Logout ====
function logout() {
  // Jika pakai session, bisa panggil php logout
  fetch("php/logout.php")
    .then(() => {
      window.location.href = "loginPage.html";
    })
    .catch(() => {
      window.location.href = "loginPage.html";
    });
}
document.getElementById("logoutBtn").addEventListener("click", logout);

// ==== Modal Handling ====
const modal = document.getElementById("addPasswordModal");
const editModal = document.getElementById("editPasswordModal");
const viewDetailsModal = document.getElementById("viewDetailsModal");

function hideModal() {
  modal.style.display = "none";
  editModal.style.display = "none";
  viewDetailsModal.style.display = "none";
  document.getElementById("addPasswordForm").reset();
  document.getElementById("editPasswordForm").reset();
}

[...document.querySelectorAll(".close-btn, .btn-secondary")].forEach((btn) =>
  btn.addEventListener("click", hideModal)
);
window.addEventListener("click", (e) => {
  if (e.target.classList.contains("modal")) hideModal();
});

// ==== Add Password Modal Open ====
document.getElementById("addPasswordBtn").addEventListener("click", () => {
  const type =
    document.querySelector(".view-toggle-btn.active")?.dataset.view ||
    "personal";
  document.getElementById("passwordType").value = type;
  document.querySelector("#addPasswordModal .modal-title").textContent =
    type === "personal" ? "Add Personal Password" : "Add Shared Password";

  modal.style.display = "block";
  document.getElementById("title").focus();
});

// ==== Add Password Submit ====
document
  .getElementById("addPasswordForm")
  .addEventListener("submit", async (e) => {
    e.preventDefault();

    const type = document.getElementById("passwordType").value;

    const newPassword = {
      title: document.getElementById("title").value,
      username: document.getElementById("username").value,
      password: document.getElementById("password").value,
      url: document.getElementById("url").value,
      notes: document.getElementById("notes").value,
    };

    const endpoint =
      type === "shared"
        ? "php/add_shared_password.php"
        : "./php/api/add_password.php";

    try {
      const res = await fetch(endpoint, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(newPassword),
      });

      if (!res.ok) {
        const text = await res.text();
        console.error("Server error:", res.status, text);
        alert("Server error: " + res.status);
        return;
      }

      const result = await res.json();
      if (result.success) {
        const fullPassword = result.password || {
          ...newPassword,
          id: result.id,
        };
        const currentView = document.querySelector(".view-toggle-btn.active")
          ?.dataset.view;
        if (type === currentView) addPasswordCard(fullPassword, type);
        e.target.reset();
        hideModal();
      } else {
        alert(`Failed to add ${type} password: ` + result.message);
      }
    } catch (err) {
      console.error("Add error:", err);
      alert("An error occurred while adding the password. Please try again.");
    }
  });

// ==== View Details Modal ====
document.getElementById("cardContainer").addEventListener("click", (e) => {
  const btn = e.target.closest(".view-details-btn");
  if (!btn) return;
  const card = btn.closest(".card");

  document.getElementById("detailTitle").textContent =
    card.querySelector(".card-title").textContent;
  document.getElementById("detailUsername").textContent = card.querySelector(
    '[data-type="username"]'
  ).textContent;
  document.getElementById("detailPassword").textContent =
    card.querySelector(".real-password")?.textContent || "—";
  document.getElementById("detailUrl").textContent =
    card.querySelector('[data-type="url"]').textContent || "N/A";
  document.getElementById("detailNotes").textContent =
    card.querySelector('[data-type="notes"]')?.textContent || "—";

  viewDetailsModal.style.display = "block";
});
document
  .getElementById("closeViewDetailsBtn")
  .addEventListener("click", () => (viewDetailsModal.style.display = "none"));

// ==== Delete Password ====
document
  .getElementById("cardContainer")
  .addEventListener("click", async (e) => {
    const deleteBtn = e.target.closest(".delete-btn");
    if (!deleteBtn) return;

    const card = deleteBtn.closest(".card");
    const id = card.getAttribute("data-id");
    const view = card.getAttribute("data-view"); // 'personal' atau 'shared'

    if (!confirm("Yakin ingin menghapus password ini?")) return;

    const endpoint =
      view === "shared"
        ? "php/delete_shared_password.php"
        : "./php/api/delete_password.php";

    try {
      const res = await fetch(endpoint, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ id }),
      });

      const result = await res.json();
      if (result.success) {
        card.remove();
      } else {
        alert(`Failed to delete password.: ${result.message}`);
      }
    } catch (err) {
      console.error("Delete error:", err);
      alert("An error occurred while deleting the password. Please try again.");
    }
  });

// ==== Edit Password ====
document.getElementById("cardContainer").addEventListener("click", (e) => {
  const editBtn = e.target.closest(".edit-btn");
  if (!editBtn) return;

  const card = editBtn.closest(".card");
  const id = card.getAttribute("data-id");
  const view = card.getAttribute("data-view");

  // Isi form edit dengan data dari kartu
  document.getElementById("edit_id").value = id;
  document.getElementById("edit_passwordType").value = view;
  document.getElementById("editTitle").value =
    card.querySelector(".card-title")?.textContent || "";
  document.getElementById("editUsername").value =
    card.querySelector('[data-type="username"]')?.textContent || "";
  document.getElementById("editPassword").value =
    card.querySelector(".real-password")?.textContent || "";
  document.getElementById("editUrl").value =
    card.querySelector('[data-type="url"]')?.textContent || "";
  document.getElementById("editNotes").value =
    card.querySelector('[data-type="notes"]')?.textContent || "";

  // Tampilkan modal edit
  editModal.style.display = "block";
});

// ==== Submit Edit Password ====
document
  .getElementById("editPasswordForm")
  .addEventListener("submit", async (e) => {
    e.preventDefault();

    const updatedPassword = {
      id: document.getElementById("edit_id").value,
      title: document.getElementById("editTitle").value,
      username: document.getElementById("editUsername").value,
      password: document.getElementById("editPassword").value,
      url: document.getElementById("editUrl").value,
      notes: document.getElementById("editNotes").value,
    };

    const type = document.getElementById("edit_passwordType").value;
    const endpoint =
      type === "shared"
        ? "php/edit_shared_password.php"
        : "./php/api/edit_password.php";

    try {
      const res = await fetch(endpoint, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(updatedPassword),
      });

      const result = await res.json();
      if (result.success) {
        hideModal();
        loadPasswordsByView(type); // Refresh ulang tampilan
      } else {
        alert("Failed to edit password: " + result.message);
      }
    } catch (err) {
      console.error("Edit error:", err);
      alert("Error updating password.");
    }
  });
