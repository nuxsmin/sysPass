<?php
/**
 * @var DataTab[]  $tabs
 * @var ThemeIcons $icons
 * @var callable   $_getvar
 * @var Template   $this
 */

use SP\Core\UI\ThemeIcons;
use SP\Mvc\View\Components\DataTab;
use SP\Mvc\View\Template;

$tabs = $_getvar('tabs');
?>

<!-- Start Tabs-->
<div class="mdl-tabs mdl-js-tabs mdl-js-ripple-effect">
    <div id="tabsHeader" class="mdl-tabs__tab-bar"></div>

    <?php foreach ($tabs as $index => $tab): ?>
        <div id="tabs-<?php echo $index; ?>"
             class="mdl-tabs__panel"
             data-tab-route="<?php echo $_getvar('tabsRoute'); ?>"
             data-tab-index="<?php echo $index; ?>">
            <div class="tab-data">
                <?php echo $tab->render(); ?>
            </div>
        </div>

        <script type="text/javascript">
            sysPassApp.theme.html.tabs.add("#tabsHeader", <?php echo $index; ?>, "<?php echo $tab->getTitle(); ?>", <?php echo $index === $_getvar('activeTab') ? 1 : 0; ?>);
        </script>
    <?php endforeach; ?>
</div>
<!-- End Tabs-->