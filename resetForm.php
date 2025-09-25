<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Reset Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="css/forgotPass.css" />
    <link rel="stylesheet" href="css/registrasi.css" />
    <style>
        .info-box {
            font-size: 0.85em;
            background: #f0f0f0;
            padding: 10px;
            margin-top: 5px;
            border-radius: 5px;
            display: none;
            color: #333;
            text-align: left;
        }

        .error-message {
            font-size: 0.85em;
            color: red;
            margin-top: 5px;
            display: none;
            text-align: left;
        }

        .valid {
            color: green;
        }
    </style>
</head>

<body>
    <div class="auth-container">
        <div class="auth-box">
            <h2 class="auth-title">Reset Password</h2>
            <form id="resetForm">
                <input type="hidden" name="token" value="<?php echo $_GET['token'] ?? ''; ?>" />

                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> New Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" placeholder="Enter new password" required />
                        <i class="fas fa-eye toggle-password" data-target="password"></i>
                    </div>
                    <div id="passwordRules" class="info-box">
                        Password must contain:
                        <ul>
                            <li id="rule-length">Minimum 4 characters</li>
                            <li id="rule-upper">At least one uppercase letter</li>
                            <li id="rule-lower">At least one lowercase letter</li>
                            <li id="rule-number">At least one number</li>
                            <li id="rule-symbol">At least one symbol (!@#$...)</li>
                        </ul>

                    </div>
                </div>

                <div class="form-group">
                    <label for="confirmPassword"><i class="fas fa-lock"></i> Confirm Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirm new password" required />
                        <i class="fas fa-eye toggle-password" data-target="confirmPassword"></i>
                    </div>
                    <div id="confirmError" class="error-message">Passwords do not match</div>
                </div>



                <button type="submit" class="btn btn-primary">Reset Password</button>
                <i class="fas fa-spinner fa-spin" id="loginSpinner" style="display: none; margin-left: 10px;"></i>
            </form>

            <p class="redirect-text">
                Remember your password? <a href="loginPage.html">Login</a>
            </p>
        </div>
        <div class="auth-illustration">
            <img src="img/reset.svg" alt="Reset Illustration" />
        </div>
    </div>

    <script>
        const passwordInput = document.getElementById("password");
        const confirmInput = document.getElementById("confirmPassword");
        const passwordRules = document.getElementById("passwordRules");
        const confirmError = document.getElementById("confirmError");

        function hidePasswordRules() {
            passwordRules.style.display = "none";
        }

        passwordInput.addEventListener("focus", () => {
            passwordRules.style.display = "block";
        });

        confirmInput.addEventListener("focus", hidePasswordRules);

        passwordInput.addEventListener("input", () => {
            const val = passwordInput.value;
            document.getElementById("rule-length").classList.toggle("valid", val.length >= 4);
            document.getElementById("rule-upper").classList.toggle("valid", /[A-Z]/.test(val));
            document.getElementById("rule-lower").classList.toggle("valid", /[a-z]/.test(val));
            document.getElementById("rule-number").classList.toggle("valid", /[0-9]/.test(val));
            document.getElementById("rule-symbol").classList.toggle("valid", /[^A-Za-z0-9]/.test(val));
        });

        confirmInput.addEventListener("input", () => {
            if (confirmInput.value !== passwordInput.value) {
                confirmError.style.display = "block";
            } else {
                confirmError.style.display = "none";
            }
        });

        document.querySelectorAll('.toggle-password').forEach(icon => {
            icon.addEventListener('click', () => {
                const target = document.getElementById(icon.dataset.target);
                target.type = target.type === 'password' ? 'text' : 'password';
                icon.classList.toggle('fa-eye');
                icon.classList.toggle('fa-eye-slash');
            });
        });

        document.getElementById("resetForm").addEventListener("submit", function(e) {
            e.preventDefault();

            if (confirmInput.value !== passwordInput.value) {
                confirmError.style.display = "block";
                return;
            }

            const formData = new FormData(this);

            fetch("php/resetPass.php", {
                    method: "POST",
                    body: formData
                })
                .then(async res => {
                    const text = await res.text();
                    try {
                        const json = JSON.parse(text);
                        if (json.success) {
                            alert(json.message);
                            window.location.href = "loginPage.html";
                        } else {
                            alert(json.message);
                        }
                    } catch (e) {
                        console.error("Respons bukan JSON valid:", text);
                        alert("Terjadi kesalahan pada server.");
                    }
                })
                .catch(err => {
                    console.error("Terjadi kesalahan:", err);
                });
        });
    </script>
</body>

</html>