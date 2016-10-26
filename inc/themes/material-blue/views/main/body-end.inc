<?php foreach ($jsLinks as $jsLink): ?>
    <script type="text/javascript" src="<?php echo $jsLink; ?>"></script>
<?php endforeach; ?>

<?php if ($loggedIn): ?>
    <script type="text/javascript">
        $(document).on("DOMContentLoaded", function (e) {
            "use strict";

            console.info("DOMContentLoaded");
            sysPassApp.triggers().views.main();
        });
    </script>
<?php endif; ?>
</body> <!-- Close BODY...almost done..go..go..go -->
</html> <!-- Close HTML...ufff too much work!! :)) -->
<!-- Insert coin . . . -->
