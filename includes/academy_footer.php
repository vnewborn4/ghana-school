</main>
<footer class="academy-foot">
    <p>Mill Creek-AR Learning Center &middot; Accra, Ghana</p>
    <?php if (($portalKind ?? 'student') === 'student'): ?>
        <p class="academy-foot-safety"><?= e('site.safety_reminder') ?></p>
    <?php endif; ?>
</footer>
</body>
</html>
