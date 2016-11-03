<?php foreach ($jsLinks as $jsLink): ?>
    <script type="text/javascript" src="<?php echo $jsLink; ?>"></script>
<?php endforeach; ?>

<script type="text/javascript">
    $(document).on("DOMContentLoaded", function (e) {
        "use strict";

        console.info("DOMContentLoaded");
        <?php if ($loggedIn): ?>
            sysPassApp.triggers().views.main();
        <?php elseif ($isInstalled === false): ?>
            sysPassApp.triggers().views.install();
        <?php endif; ?>
    });
</script>

</body> <!-- Close BODY...almost done..go..go..go -->
</html> <!-- Close HTML...ufff too much work!! :)) -->
<!-- Insert coin . . . -->
