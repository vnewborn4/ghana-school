</main>
<footer class="academy-foot">
    <p>Mill Creek-AR Learning Center &middot; Accra, Ghana</p>
    <?php if (($portalKind ?? 'student') === 'student'): ?>
        <p class="academy-foot-safety"><?= e('site.safety_reminder') ?></p>
    <?php endif; ?>
</footer>
<?php if (($portalKind ?? 'student') === 'student'): ?>
<script src="<?= app_url('assets/js/academy-offline.js') ?>" defer></script>
<?php endif; ?>
</body>
</html>
