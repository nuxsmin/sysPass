<?php
/**
 * @var ThemeIcons $icons
 * @var \SP\Domain\Config\In\ConfigDataInterface $configData
 * @var callable $_getvar
 * @var \SP\Mvc\View\TemplateInterface $this
 */

use SP\Core\UI\ThemeIcons;

?>
<form method="post" name="frmConfig" id="frmConfig" class="form-action"
      data-onsubmit="config/save"
      data-action-route="configGeneral/save"
      data-hash="">

    <?php include $this->includeTemplate('general-site'); ?>
    <?php include $this->includeTemplate('general-events'); ?>
    <?php include $this->includeTemplate('general-proxy'); ?>
    <?php include $this->includeTemplate('general-auth'); ?>
</form>

<div class="tab-actions">
    <ul>
        <li>
            <button id="btnBack" type="button"
                    class="btn-back mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconBack()->getClassButton(); ?>"
                    title="<?php echo __('Back'); ?>">
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
