<?php
include 'include/css.php';
include 'component/spinner.php';
include 'component/alert.php';
?>

<body class="bg-light d-flex align-items-center justify-content-center h-100vh">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-8 col-lg-6 col-xl-5">
        <div class="card border-0 shadow-lg p-4">
          <div class="card-body">
            <div class="text-center mb-4">
              <img src="<?= SITE_URL ?>assets/image/crm_logo.png" alt="Logo" class="logo">

              <p class="text-muted">Login to your account.</p>
            </div>

            <form id="loginForm">
              <div class="mb-3">
                <label class="form-label fw-medium">Email Address</label>
                <div class="input-group">
                  <span class="input-group-text">
                    <i data-lucide="mail" class="fs-18"></i></span>
                  <input type="text" name="login" id="login" class="form-control" placeholder="Enter Username" value="" required>
                </div>
                <span id="loginError" class="text-danger"></span>
              </div>

              <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <label class="form-label fw-medium mb-0">Password</label>
                  <!-- <a class='text-decoration-none small' href='forgot-password.html'>Forgot password?</a> -->
                </div>
                <div class="input-group">
                  <span class="input-group-text">
                    <i data-lucide="lock" class="fs-18"></i></span>
                  <input type="password" name="password" class="form-control" id="password" placeholder="Enter Password" value="" required>
                </div>
                <span id="passwordError" class="text-danger"></span>
              </div>

              <div class="d-grid mb-4">
                <button
                  type="submit"
                  id="loginBtn"
                  class="btn btn-primary w-100">
                  <span id="loginText">LOGIN</span>

                  <span
                    id="loginLoader"
                    class="spinner-border spinner-border-sm d-none"></span>
                </button>
              </div>
            </form>
          </div>
        </div>
        <div class="text-center mt-4">
          <p class="mb-0">&copy; <span><?php echo date('Y') ?></span> Developed by <a href="https://www.oceaninfotech.co.in/" target="_blank">Ocean Infotech</a></p>
        </div>
      </div>
    </div>
  </div>
  <div class="toast-container position-fixed top-0 end-0 p-3">
    <div id="commonToast"
      class="toast border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
      <div id="commonToastHeader" class="toast-header border-0 text-white">
        <i id="commonToastIcon"
          data-lucide="check-circle"
          class="me-2"
          style="width:16px;">
        </i>
        <strong id="commonToastTitle" class="me-auto">
          Success
        </strong>
        <button type="button"
          class="btn-close btn-close-white"
          data-bs-dismiss="toast"
          aria-label="Close">
        </button>
      </div>
      <div id="commonToastBody" class="toast-body" style="font-size:16px;">
        Your action has been completed successfully!
      </div>
    </div>
  </div>
  <?php
  include 'include/js.php';
  ?>
  <script>
    document.getElementById("loginForm").addEventListener("submit", function(e) {

      e.preventDefault();

      const form = this;

      const login = document.getElementById("login");
      const password = document.getElementById("password");

      const loginError = document.getElementById("loginError");
      const passwordError = document.getElementById("passwordError");

      const button = document.getElementById("loginBtn");
      const text = document.getElementById("loginText");
      const loader = document.getElementById("loginLoader");

      loginError.textContent = "";
      passwordError.textContent = "";
      login.classList.remove("is-invalid");
      password.classList.remove("is-invalid");

      let isValid = true;
      if (login.value.trim() === "") {
        loginError.textContent =
          "Please enter your email or username.";
        login.classList.add("is-invalid");
        isValid = false;
      }
      if (password.value.trim() === "") {
        passwordError.textContent =
          "Please enter your password.";
        password.classList.add("is-invalid");
        isValid = false;
      }

      if (!isValid) {
        return;
      }

      const formData = new FormData(form);

      button.disabled = true;
      text.textContent = "LOGGING IN...";
      loader.classList.remove("d-none");
      fetch("<?= SITE_URL ?>loginaction.php", {
          method: "POST",
          body: formData
        })
        .then(response => response.json())
        .then(response => {
          if (response.status === true) {
            showToast(response.message, "success");
            setTimeout(() => {
              window.location.href = response.redirect;
            }, 500);
          } else {
            showToast(response.message, "error");
          }
        })
        .catch(error => {
          console.error(error);
          showToast("Something went wrong. Please try again.", "error");
        })
        .finally(() => {
          button.disabled = false;
          text.textContent = "LOGIN";
          loader.classList.add("d-none");
        });
    });
  </script>

</body>

</html>