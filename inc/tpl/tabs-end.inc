</div> <!-- End Tabs-->

<script>
    $("#tabs").tabs({
        active: <?php echo $id; ?>,
        create: function( event, ui ) {
            $("input:visible:first").focus();
        },
        activate: function( event, ui ) {
            setContentSize();
        }
    });
</script>