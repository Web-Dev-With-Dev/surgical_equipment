<?php

$current_page = $current_page ?? basename($_SERVER['PHP_SELF'], '.php');
?>
<?php if ($current_page !== 'login'): ?>
</div><!-- /.app-layout -->
<?php endif; ?>
<script src="/surgical_equipment/assets/js/main.js"></script>
</body>
</html>
