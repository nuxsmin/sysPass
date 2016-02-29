<?php
/** @var $tag \SP\DataModel\TagData */
/** @var $icons \SP\Core\ThemeIconsBase */
?>
<div id="fancyContainer" align="center">
    <h2 class="midround"><?php echo $header; ?></H2>

    <form method="post" name="frmTags" id="frmCategories" onsubmit="sysPassUtil.Common.appMgmtSave('frmTags'); return false;">
        <table class="fancydata">
            <tbody>
            <tr>
                <td class="descField"><?php echo _('Nombre'); ?></td>
                <td class="valField">
                    <input type="text" id="grpname" name="name" title="<?php echo _('Nombre de la categoría'); ?>"
                           value="<?php echo $tag->getTagName() ?>"/>
                </td>
            </tr>
            </tbody>
        </table>

        <input type="hidden" name="activeTab" value="<?php echo $activeTab ?>"/>
        <input type="hidden" name="onCloseAction" value="<?php echo $onCloseAction ?>"/>
        <input type="hidden" name="itemId" value="<?php echo $tag->getTagId(); ?>"/>
        <input type="hidden" name="actionId" value="<?php echo $actionId; ?>"/>
        <input type="hidden" name="sk" value="<?php echo $sk; ?>">
        <input type="hidden" name="isAjax" value="1">
    </form>
    <div id="resCheck"><span id="resFancyAccion"></span></div>
    <div class="action-in-box">
        <button type="submit" form="frmTags" title="<<?php echo $icons->getIconSave()->getTitle(); ?>" class="button-action">
            <img src="<?php echo $icons->getIconSave()->getIcon(); ?>" alt="save"/>
        </button>
    </div>
</div>