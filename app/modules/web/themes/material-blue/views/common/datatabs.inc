<?php
/**
 * @var $icons   SP\Core\UI\ThemeIcons
 * @var $this    \SP\Mvc\View\Template
 * @var $tabs    \SP\Mvc\View\Components\DataTab[]
 */
?>
<!-- Start Tabs-->
<div class="mdl-tabs mdl-js-tabs mdl-js-ripple-effect">
    <div id="tabsHeader" class="mdl-tabs__tab-bar"></div>

    <?php foreach ($tabs as $index => $tab): ?>
        <div id="tabs-<?php echo $index; ?>"
             class="mdl-tabs__panel"
             data-tab-route="<?php echo $tabsRoute; ?>"
             data-tab-index="<?php echo $index; ?>">
            <div class="tab-data">
                <?php echo $tab->render(); ?>
            </div>
        </div>

        <script type="text/javascript">
            sysPassApp.theme().html.tabs.add("#tabsHeader", <?php echo $index; ?>, "<?php echo $tab->getTitle(); ?>", <?php echo $index === $activeTab ? 1 : 0; ?>);
        </script>
    <?php endforeach; ?>
</div>
<!-- End Tabs-->