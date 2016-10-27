<?php
/** @var $tag \SP\DataModel\TagData */
/** @var $icons \SP\Core\UI\ThemeIconsBase */
?>
<div id="fancyContainer" align="center">
    <h2 class="midround"><?php echo $header; ?></h2>

    <form method="post" name="frmTags" id="frmTags" class="form-action"
          data-onsubmit="appMgmt/save"
          data-activetab="<?php echo isset($activeTab) ? $activeTab : ''; ?>"
          data-nextaction-id="<?php echo \SP\Core\ActionsInterface::ACTION_MGM_TAGS; ?>">
        <table class="fancydata">
            <tbody>
            <tr>
                <td class="descField"><?php echo _('Nombre'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="name" name="name" type="text" required
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="50" value="<?php echo $tag->getTagName(); ?>">
                        <label class="mdl-textfield__label"
                               for="name"><?php echo _('Nombre de la etiqueta'); ?></label>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>

        <input type="hidden" name="itemId" value="<?php echo $tag->getTagId(); ?>"/>
        <input type="hidden" name="actionId" value="<?php echo $actionId; ?>"/>
        <input type="hidden" name="sk" value="">
        <input type="hidden" name="isAjax" value="1">
    </form>
    <div id="resCheck"><span id="resFancyAccion"></span></div>
    <div class="action-in-box">
        <button
            class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconSave()->getClassButton(); ?>"
            form="frmTags" title="<?php echo $icons->getIconSave()->getTitle(); ?>">
            <i class="material-icons"><?php echo $icons->getIconSave()->getIcon(); ?></i>
        </button>
    </div>
</div>
<script type="text/javascript">
    sysPassApp.sk.set("<?php echo $sk; ?>");
</script>