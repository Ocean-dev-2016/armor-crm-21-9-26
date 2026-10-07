</main>
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
<footer class="footer">
  <p class="mb-0">&copy; <span id="year"></span> Ocean Infotech. All Rights Reserved. | Developed by <a href="https://www.oceaninfotech.co.in" target="_blank">Ocean Infotech</a></p>
  <script>
    document.getElementById('year').textContent = new Date().getFullYear();
    document.addEventListener('DOMContentLoaded', function() {

      const toggleButton = document.getElementById('sidebar-toggler');
      const mobileMenu = document.getElementById('mobileMenu');

      if (toggleButton && mobileMenu) {

        const offcanvas = new bootstrap.Offcanvas(mobileMenu);

        toggleButton.addEventListener('click', function() {
          offcanvas.toggle();
        });

      }

    });
  </script>
</footer>
</div>
</div>
<?php
include BASE_PATH . '/include/js.php';

?>