
document.addEventListener('DOMContentLoaded', function () {
    var toggleBtn = document.getElementById('sidebarToggle');
    var sidebar   = document.getElementById('sidebar');

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function () {
            if (window.innerWidth <= 1024) {

                sidebar.classList.toggle('open');
            } else {

                sidebar.classList.toggle('collapsed');

                var main = document.querySelector('.main-content');
                var topbar = document.querySelector('.topbar');

                if (sidebar.classList.contains('collapsed')) {
                    if (main) main.style.marginLeft = '70px';
                    if (topbar) topbar.style.left = '70px';
                } else {
                    if (main) main.style.marginLeft = '260px';
                    if (topbar) topbar.style.left = '260px';
                }
            }
        });

        sidebar.querySelectorAll('.nav-link').forEach(function (link) {
            link.addEventListener('click', function () {
                if (window.innerWidth <= 1024) {
                    sidebar.classList.remove('open');
                }
            });
        });

        document.addEventListener('click', function (e) {
            if (window.innerWidth > 1024) return;
            if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });
    }

    document.querySelectorAll('.session-card-menu-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();

            document.querySelectorAll('.session-card-dropdown').forEach(function (drop) {
                if (drop !== btn.nextElementSibling) {
                    drop.style.display = 'none';
                }
            });

            var dropdown = btn.nextElementSibling;
            if (dropdown) {
                dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
            }
        });
    });

    document.addEventListener('click', function () {
        document.querySelectorAll('.session-card-dropdown').forEach(function (drop) {
            drop.style.display = 'none';
        });
    });

    var searchInput = document.querySelector('.topbar-search-input');
    if (searchInput) {
        document.addEventListener('keydown', function (e) {
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                searchInput.focus();
            }
        });
    }

    document.querySelectorAll('.alert').forEach(function (el) {
        setTimeout(function () {
            el.style.transition = 'opacity 0.4s ease';
            el.style.opacity    = '0';
            setTimeout(function () { el.remove(); }, 400);
        }, 6000);
    });
});
