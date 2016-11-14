<?php
/**
 * @var $link SP\DataModel\PublicLinkListData
 */
?>
<div id="fancyContainer" align="center">
    <h2><?php echo $header; ?></H2>
    <table class="fancydata">
        <tbody>
        <tr>
            <td class="descField"><?php echo _('Cuenta'); ?></td>
            <td class="valField"><?php echo $link->getAccountName(); ?></td>
        </tr>
        <tr>
            <td class="descField"><?php echo _('Fecha Creación'); ?></td>
            <td class="valField"><?php echo $link->getDateAdd(); ?></td>
        </tr>
        <tr>
            <td class="descField"><?php echo _('Fecha Caducidad'); ?></td>
            <td class="valField"><?php echo $link->getDateExpire(); ?></td>
        </tr>
        <tr>
            <td class="descField"><?php echo _('Usuario'); ?></td>
            <td class="valField"><?php echo $link->getUserLogin(); ?></td>
        </tr>
        <tr>
            <td class="descField"><?php echo _('Notificar'); ?></td>
            <td class="valField"><?php echo $link->getNotify(); ?></td>
        </tr>
        <tr>
            <td class="descField"><?php echo _('Visitas'); ?></td>
            <td class="valField"><?php echo $link->getCountViews(); ?></td>
        </tr>
        <tr>
            <td class="descField"><?php echo _('Uso'); ?></td>
            <td class="valField">
                <?php foreach ($link->getUseInfo() as $useInfo): ?>
                    <div class="link-use-info"><?php echo $useInfo['who'], ' - ' , date('Y-m-d H:i', $useInfo['time']); ?></div>
                <?php endforeach; ?>
            </td>
        </tr>
        <tr>
            <td class="descField"><?php echo _('Hash'); ?></td>
            <td class="valField"><?php echo $link->getPublicLinkHash(); ?></td>
        </tr>
        </tbody>
    </table>
</div>