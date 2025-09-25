<?php
session_start();
//var_dump($_SESSION);

//cek apakah user sudah login dan sudah verifikasi OTP
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_verified_otp']) || $_SESSION['is_verified_otp'] !== true) {
  header("Location: loginPage.html");
  exit;
}

// Optional: ambil username untuk ditampilkan
$username = $_SESSION['username'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Manage Password - Modern Password Manager</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="css/style.css">
</head>

<body>

  <header>
    <div class="logo">
      <i class="fas fa-lock"></i>
      <span>ManaPass</span>
    </div>
    <div class="search-container">
      <i class="fas fa-search search-icon"></i>
      <input type="text" class="search-bar" id="searchInput" placeholder="Search passwords..." />
    </div>
    <button class="btn btn-secondary" id="logoutBtn">
      <i class="fas fa-sign-out-alt"></i>
      Logout
    </button>
  </header>

  <div class="welcome-box" id="welcomeMessageBox">
    <div class="welcome-content">
      <img src="img/hello.svg" alt="Welcome Illustration" class="welcome-img" />
      <div class="welcome-text">
        <h2>Welcome back, <span id="welcomeUsername"><?= htmlspecialchars($username) ?></span>!</h2>
        <!--<p>Manage your passwords securely and efficiently with Manage Password.</p> -->
      </div>
    </div>
  </div>

  <div class="view-toggle-container">
    <button id="personalViewBtn" class="view-toggle-btn active" data-view="personal">Personal Passwords</button>
    <button id="sharedViewBtn" class="view-toggle-btn" data-view="shared">Shared Passwords</button>
  </div>

  <div id="passwordBoxContainer">
    <div class="add-btn-wrapper">
      <button class="btn btn-primary" id="addPasswordBtn">
        <i class="fas fa-plus"></i> Add Password
      </button>
    </div>
    <div id="cardContainer"></div>
  </div>

  <div id="viewDetailsModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">Password Details</h3>
        <button class="close-btn" id="closeViewDetailsBtn">&times;</button>
      </div>

      <div class="detail-line">
        <p><strong>Title:</strong> <span id="detailTitle"></span></p>
      </div>
      <div class="detail-line">
        <p><strong>Username:</strong> <span id="detailUsername"></span></p>
      </div>
      <div class="detail-line">
        <p><strong>Password:</strong> <span class="password-revealed" id="detailPassword"></span></p>
      </div>
      <div class="detail-line">
        <p><strong>URL/IP Address:</strong> <span id="detailUrl"></span></p>
      </div>
      <div class="detail-line">
        <p><strong>Notes:</strong> <span id="detailNotes"></span></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" id="closeDetailsBtn">
          Close
        </button>
      </div>
    </div>
  </div>

  <!-- Add Password Modal -->
  <!-- Add Password Modal -->
  <div id="addPasswordModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">Add New Password</h3> <!-- ini akan diubah dinamis di JS -->
        <button class="close-btn" id="closeModalBtn">&times;</button>
      </div>
      <form id="addPasswordForm">
        <input type="hidden" id="passwordType" name="type" value="personal">
        <div class="form-group">
          <label for="title" class="form-label">Title</label>
          <input type="text" id="title" class="form-input" placeholder="e.g. UIS Server" required>
        </div>
        <div class="form-group">
          <label for="username" class="form-label">Username</label>
          <input type="text" id="username" class="form-input" placeholder="Enter username" required>
        </div>
        <div class="form-group">
          <label for="password" class="form-label">Password</label>
          <div class="password-input-container">
            <input type="text" id="password" class="form-input" placeholder="Enter password" required>
          </div>
        </div>
        <div class="form-group">
          <label for="url" class="form-label">URL or IP Address</label>
          <input type="text" id="url" class="form-input" placeholder="e.g. 192.168.1.1 or https://example.com">
        </div>
        <div class="form-group">
          <label for="notes" class="form-label">Notes</label>
          <textarea id="notes" class="form-input" rows="3" placeholder="Any additional notes..."></textarea>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" id="cancelAddBtn">Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i>
            Save Password
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Edit Password Modal -->
  <div id="editPasswordModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">Edit Password</h3>
        <button class="close-btn" id="closeEditModalBtn">&times;</button>
      </div>
      <form id="editPasswordForm">
        <input type="hidden" id="edit_id">
        <input type="hidden" id="edit_passwordType">
        <div class="form-group">
          <label for="editTitle" class="form-label">Title</label>
          <input type="text" id="editTitle" class="form-input" required>
        </div>
        <div class="form-group">
          <label for="editUsername" class="form-label">Username</label>
          <input type="text" id="editUsername" class="form-input" required>
        </div>
        <div class="form-group">
          <label for="editPassword" class="form-label">Password</label>
          <div class="password-input-container" style="position: relative;">
            <input type="text" id="editPassword" class="form-input" required style="padding-right: 2.5rem;">
          </div>
        </div>
        <div class="form-group">
          <label for="editUrl" class="form-label">URL or IP Address</label>
          <input type="text" id="editUrl" class="form-input">
        </div>
        <div class="form-group">
          <label for="editNotes" class="form-label">Notes</label>
          <textarea id="editNotes" class="form-input" rows="3"></textarea>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" id="cancelEditBtn">Cancel</button>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i>
            Save Changes
          </button>
        </div>
      </form>
    </div>
  </div>

  <script src="js/page.js"></script>
  <script>
    const viewToggleBtns = document.querySelectorAll('.view-toggle-btn');

    viewToggleBtns.forEach(btn => {
      btn.addEventListener('click', () => {
        // Remove active class dari semua
        viewToggleBtns.forEach(b => b.classList.remove('active'));

        // Tambah active ke tombol yang diklik
        btn.classList.add('active');

        // Load password berdasarkan view
        const view = btn.getAttribute('data-view');

        // Set type di hidden input form
        document.getElementById('passwordType').value = view;

        loadPasswordsByView(view);
      });
    });

    // Load default view saat page siap
    document.addEventListener('DOMContentLoaded', () => {
      loadPasswordsByView('personal');
      document.getElementById('passwordType').value = 'personal';
    });
  </script>
</body>

</html>