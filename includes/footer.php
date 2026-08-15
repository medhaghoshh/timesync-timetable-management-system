</div><!-- /app-shell -->
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<?php if (!empty($extraScripts)) foreach ($extraScripts as $src): ?>
<script src="<?= BASE_URL . $src ?>"></script>
<?php endforeach; ?>
<?php
$flash = getFlash();
if ($flash):
?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    showToast(<?= json_encode($flash['message']) ?>, <?= json_encode($flash['type']) ?>);
});
</script>
<?php endif; ?>
</body>
</html>
