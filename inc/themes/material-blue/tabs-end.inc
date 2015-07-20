</div> <!-- End Tabs-->

<script>
    $("#tabs").tabs({
        active: <?php echo $activeTabId; ?>,
        create: function( event, ui ) {
            $("input:visible:first").focus();
        },
        activate: function( event, ui ) {
            setContentSize();
        }
    });
</script>