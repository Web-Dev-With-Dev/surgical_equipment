
document.addEventListener('DOMContentLoaded', function () {
    var toggleBtn = document.getElementById('sidebarToggle');
    var sidebar   = document.getElementById('sidebar');

    if (!toggleBtn || !sidebar) return;

    toggleBtn.addEventListener('click', function () {
        if (window.innerWidth <= 1024) {

            sidebar.classList.toggle('open');
        } else {

            sidebar.classList.toggle('collapsed');

            var main = document.querySelector('.main-content');
            if (main) {
                main.style.marginLeft = sidebar.classList.contains('collapsed') ? '56px' : '240px';
            }
        }
    });

    sidebar.querySelectorAll('.nav-link').forEach(function (link) {
        link.addEventListener('click', function () {
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
