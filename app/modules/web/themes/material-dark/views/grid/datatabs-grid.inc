<?php
/**
 * @var SP\Html\DataGrid\DataGridTab $data
 * @var ThemeIcons                   $icons
 * @var ConfigData                   $configData
 * @var callable                     $_getvar
 * @var Template                     $this
 */

use SP\Config\ConfigData;
use SP\Core\UI\ThemeIcons;
use SP\Mvc\View\Template;

$tabs = $_getvar('tabs');
?>
<div class="mdl-tabs mdl-js-tabs mdl-js-ripple-effect">
    <div id="tabsHeader" class="mdl-tabs__tab-bar"></div>

    <?php foreach ($tabs as $index => $data): ?>
        <div id="tabs-<?php echo $index; ?>"
             class="mdl-tabs__panel"
             data-tab-route="<?php echo $_getvar('tabsRoute'); ?>"
             data-tab-index="<?php echo $index; ?>">

            <?php include __DIR__ . DIRECTORY_SEPARATOR . 'datagrid-grid.inc'; ?>
        </div>

        <script type="text/javascript">
            sysPassApp.theme.html.tabs.add("#tabsHeader", <?php echo $index; ?>, "<?php echo $data->getTitle(); ?>", <?php echo $index === $_getvar('activeTab') ? 1 : 0; ?>);
        </script>
    <?php endforeach; ?>
</div>
<!-- End Tabs-->