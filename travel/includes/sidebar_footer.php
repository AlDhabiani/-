<?php
/**
 * ============================================================
 *  إغلاق تخطيط اللوحات (القائمة الجانبية) — sidebar_footer.php
 * ============================================================
 */
?>
    </main>
</div>

<script>
// فتح/إغلاق القائمة الجانبية على الشاشات الصغيرة
(function () {
    var sidebar = document.getElementById('dashSidebar');
    var toggle = document.getElementById('sidebarToggle');
    var backdrop = document.getElementById('sidebarBackdrop');
    if (!sidebar || !toggle) return;

    function closeSidebar() {
        sidebar.classList.remove('open');
        if (backdrop) backdrop.classList.remove('show');
    }

    toggle.addEventListener('click', function () {
        sidebar.classList.toggle('open');
        if (backdrop) backdrop.classList.toggle('show');
    });
    if (backdrop) backdrop.addEventListener('click', closeSidebar);
})();
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
