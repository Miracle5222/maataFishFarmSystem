    <!-- [ Layout footer ] Start -->
    <!-- <nav class="layout-footer footer bg-white">
        <div class="container-fluid d-flex flex-wrap justify-content-between text-center container-p-x pb-3">
            <div class="pt-3">
                <span class="footer-text font-weight-semibold">&copy; <a href="https://srthemesvilla.com" class="footer-link" target="_blank">Srthemesvilla</a></span>
            </div>
            <div>
                <a href="javascript:" class="footer-link pt-3">About Us</a>
                <a href="javascript:" class="footer-link pt-3 ml-4">Help</a>
                <a href="javascript:" class="footer-link pt-3 ml-4">Contact</a>
                <a href="javascript:" class="footer-link pt-3 ml-4">Terms &amp; Conditions</a>
            </div>
        </div>
    </nav> -->
    <!-- [ Layout footer ] End -->
    </div>
    <!-- [ Layout content ] Start -->
    </div>
    <!-- [ Layout container ] End -->
    </div>
    <!-- Overlay -->
    <div class="layout-overlay layout-sidenav-toggle"></div>
    </div>
    <!-- [ Layout wrapper] End -->

    <!-- Core scripts -->
    <script src="assets/js/pace.js"></script>
    <script src="assets/js/jquery-3.3.1.min.js"></script>
    <script src="assets/libs/popper/popper.js"></script>
    <script src="assets/js/bootstrap.js"></script>
    <script src="assets/js/sidenav.js"></script>
    <script src="assets/js/layout-helpers.js"></script>
    <script src="assets/js/material-ripple.js"></script>

    <!-- Libs -->
    <script src="assets/libs/perfect-scrollbar/perfect-scrollbar.js"></script>
    <script src="assets/libs/eve/eve.js"></script>
    <script src="assets/libs/flot/flot.js"></script>
    <script src="assets/libs/flot/curvedLines.js"></script>
    <script src="assets/libs/chart-am4/core.js"></script>
    <script src="assets/libs/chart-am4/charts.js"></script>
    <script src="assets/libs/chart-am4/animated.js"></script>

    <!-- Demo -->
    <script src="assets/js/demo.js"></script><script src="assets/js/analytics.js"></script>
    <script src="assets/js/pages/dashboards_index.js"></script>
    
    <!-- Fix for navbar dropdown clicks -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Ensure dropdown toggles work
        var dropdownToggles = document.querySelectorAll('[data-toggle="dropdown"]');
        dropdownToggles.forEach(function(toggle) {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                
                // Get the dropdown menu
                var menu = this.nextElementSibling;
                if (menu && menu.classList.contains('dropdown-menu')) {
                    menu.classList.toggle('show');
                    this.setAttribute('aria-expanded', menu.classList.contains('show'));
                }
            });
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            dropdownToggles.forEach(function(toggle) {
                var menu = toggle.nextElementSibling;
                if (menu && menu.classList.contains('dropdown-menu')) {
                    if (!toggle.contains(e.target) && !menu.contains(e.target)) {
                        menu.classList.remove('show');
                        toggle.setAttribute('aria-expanded', 'false');
                    }
                }
            });
        });
    });
    </script>
