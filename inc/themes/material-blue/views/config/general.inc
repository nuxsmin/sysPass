<!-- Start Tab - Config -->
<?php
/** @var $icons \Theme\Icons */
/** @var $this \SP\Core\Template */
?>
<div id="tabs-<?php echo $config_tabIndex; ?>" class="mdl-tabs__panel">
    <div class="tab-data">
        <form method="post" name="frmConfig" id="frmConfig" class="form-action"
              data-onsubmit="config/save"
              data-type="general"
              data-reload="1"
              data-hash="">

            <?php include $this->includeTemplate('general-site');; ?>
            <?php include $this->includeTemplate('general-events'); ?>
            <?php include $this->includeTemplate('general-proxy'); ?>

            <input type="hidden" name="activeTab" value="<?php echo $config_tabIndex; ?>"/>
            <input type="hidden" name="actionId" value="<?php echo $config_actionId; ?>"/>
            <input type="hidden" name="sk" value="">
            <input type="hidden" name="isAjax" value="1"/>
        </form>

        <div class="tab-actions">
            <ul>
                <li>
                    <button id="btnBack" type="button"
                            class="btn-back mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconBack()->getClassButton(); ?>"
                            title="<?php echo __('Atrás'); ?>">
                        <i class="material-icons"><?php echo $icons->getIconBack()->getIcon(); ?></i>
                    </button>
                </li>
                <li>
                    <button form="frmConfig"
                            class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconSave()->getClassButton(); ?>"
                            title="<?php echo $icons->getIconSave()->getTitle(); ?>">
                        <i class="material-icons"><?php echo $icons->getIconSave()->getIcon(); ?></i>
                    </button>
                </li>
            </ul>
        </div>
    </div>
</div> <!-- End Tab - Config -->