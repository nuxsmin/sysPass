<!-- Start Tabs-->

<?php
/**
 * @var $icons   SP\Core\UI\ThemeIcons
 * @var $data    SP\Html\DataGrid\DataGridTab
 * @var $this    \SP\Mvc\View\Template
 * @var $action  SP\Html\DataGrid\DataGridAction|SP\Html\DataGrid\DataGridActionSearch
 */
?>
<div class="mdl-tabs mdl-js-tabs mdl-js-ripple-effect">
    <div id="tabsHeader" class="mdl-tabs__tab-bar"></div>

    <?php foreach ($tabs as $index => $data): ?>
        <div id="tabs-<?php echo $index; ?>"
             class="mdl-tabs__panel"
             data-tab-route="<?php echo $tabsRoute; ?>"
             data-tab-index="<?php echo $index; ?>">

            <?php include __DIR__ . DIRECTORY_SEPARATOR . 'datagrid-grid.inc'; ?>
        </div>

        <script type="text/javascript">
            sysPassApp.theme.html.tabs.add("#tabsHeader", <?php echo $index; ?>, "<?php echo $data->getTitle(); ?>", <?php echo $index === $activeTab ? 1 : 0; ?>);
        </script>
    <?php endforeach; ?>
</div> <!-- End Tabs-->

<script type="text/javascript">
    sysPassApp.sk.set("<?php echo $sk; ?>");
</script>