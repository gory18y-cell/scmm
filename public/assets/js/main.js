/**
 * ==============================================================================
 * SISTEM SUPPLY CHAIN MANAGEMENT (SCM) INDUSTRI MANUFAKTUR OTOMOTIF
 * GLOBAL JAVASCRIPT - INTERAKSI SIDEBAR RESPONSIF
 * ==============================================================================
 */

document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('sidebarToggle');
    const backdrop = document.getElementById('sidebarBackdrop');

    if (toggleBtn && sidebar && backdrop) {
        toggleBtn.addEventListener('click', function () {
            sidebar.classList.toggle('open');
            backdrop.classList.toggle('active');
        });

        backdrop.addEventListener('click', function () {
            sidebar.classList.remove('open');
            backdrop.classList.remove('active');
        });
    }
});

