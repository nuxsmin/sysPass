</div> <!-- End Tabs-->

<script type="text/javascript">
    sysPassApp.sk.set("<?php echo $sk; ?>");

    <?php foreach($tabs as $index => $tab): ?>
        sysPassApp.theme().html.tabs.add("#tabsHeader", <?php echo $index; ?>, "<?php echo $tab['title']; ?>", <?php echo $index === $activeTabId ? 1 : 0; ?>);
    <?php endforeach; ?>
</script>