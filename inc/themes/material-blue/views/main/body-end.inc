<?php foreach ($jsLinks as $jsLink): ?>
    <script type="text/javascript" src="<?php echo $jsLink; ?>"></script>
<?php endforeach; ?>

<script type="text/javascript">
    $(document).on("DOMContentLoaded", function (e) {
        "use strict";

        sysPassApp.log.info("DOMContentLoaded");

        sysPassApp.sk.set("<?php echo $sk; ?>");
    });
</script>

</body> <!-- Close BODY...almost done..go..go..go -->
</html> <!-- Close HTML...ufff too much work!! :)) -->
<!-- Insert coin . . . -->
