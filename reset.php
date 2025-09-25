<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Reset Password</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link rel="stylesheet" href="css/reset.css" />
</head>
<body>
  <div class="reset-container">
    <h1>Reset Your Password</h1>
    <form action="/php/resetPass.php" method="POST">
      <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token'] ?? '', ENT_QUOTES); ?>">
      
      <label for="new_password">New Password</label>
      <div class="password-wrapper">
        <input type="password" id="new_password" name="new_password" required/>
        <i class="fas fa-eye toggle-password" toggle="#new_password"></i>
      </div>
      
      <div id="password-rules">
        <strong>Password harus:</strong>
        <ul>
          <li id="rule-length">Minimal 4 karakter</li>
          <li id="rule-uppercase">Mengandung huruf besar</li>
          <li id="rule-number">Mengandung angka</li>
          <li id="rule-symbol">Mengandung simbol</li>
        </ul>
      </div>

      <div id="confirm-password-container">
        <label for="confirm_password">Confirm Password</label>
        <div class="password-wrapper">
          <input type="password" id="confirm_password" name="confirm_password" required />
          <i class="fas fa-eye toggle-password" toggle="#confirm_password"></i>
        </div>
        <div id="confirm-error" class="error-bubble">Password tidak cocok</div>
      </div>

      <div class="g-recaptcha" data-sitekey="6LfUM0YrAAAAAL2-cNGg2cudL82ShWZxcpHnvgkD"></div>

      <button type="submit">Reset Password</button>
    </form>
  </div>

  <script>
    // Toggle password show/hide
    const toggleIcons = document.querySelectorAll('.toggle-password');
    toggleIcons.forEach(icon => {
      icon.addEventListener('click', () => {
        const input = document.querySelector(icon.getAttribute('toggle'));
        const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
        input.setAttribute('type', type);
        icon.classList.toggle('fa-eye');
        icon.classList.toggle('fa-eye-slash');
      });
    });

    // Validasi aturan password dan konfirmasi
    document.addEventListener("DOMContentLoaded", function () {
      const passwordInput = document.getElementById("new_password");
      const rulesBox = document.getElementById("password-rules");
      const confirmInput = document.getElementById("confirm_password");
      const confirmError = document.getElementById("confirm-error");

      passwordInput.addEventListener("focus", function () {
        rulesBox.style.display = "block";
      });

      passwordInput.addEventListener("blur", function () {
        rulesBox.style.display = "none";
      });

      passwordInput.addEventListener("input", function () {
        const val = passwordInput.value;
        document.getElementById("rule-length").style.color = val.length >= 4 ? "green" : "black";
        document.getElementById("rule-uppercase").style.color = /[A-Z]/.test(val) ? "green" : "black";
        document.getElementById("rule-number").style.color = /\d/.test(val) ? "green" : "black";
        document.getElementById("rule-symbol").style.color = /[^A-Za-z0-9]/.test(val) ? "green" : "black";
      });

      confirmInput.addEventListener("input", () => {
        if (confirmInput.value !== passwordInput.value) {
          confirmError.style.display = "block";
        } else {
          confirmError.style.display = "none";
        }
      });

      // Validasi sebelum submit
      const form = document.querySelector("form");
      form.addEventListener("submit", function (e) {
        const val = passwordInput.value;
        const isValid =
          val.length >= 4 &&
          /[A-Z]/.test(val) &&
          /\d/.test(val) &&
          /[^A-Za-z0-9]/.test(val);

        if (!isValid) {
          e.preventDefault();
          alert("Password tidak memenuhi syarat.");
          return;
        }

        if (confirmInput.value !== val) {
          e.preventDefault();
          alert("Konfirmasi password tidak cocok.");
        }
      });
    });
  </script>

  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</body>
</html>
