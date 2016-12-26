<?php
/**
 * @var $link SP\DataModel\PublicLinkListData
 */
?>
<div id="box-popup">
    <h2 class="center"><?php echo $header; ?></H2>
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
            <td class="descField"><?php echo _('Hash'); ?></td>
            <td class="valField"><?php echo $link->getPublicLinkHash(); ?></td>
        </tr>
        <tr>
            <td class="descField"><?php echo _('Uso'); ?></td>
            <td class="valField">
                <div class="list-wrap">
                    <ul class="mdl-list">
                        <?php foreach ($link->getUseInfo() as $useInfo): ?>
                            <li class="mdl-list__item">
                                <span class="mdl-list__item-primary-content">
                                <i class="material-icons mdl-list__item-icon">access_time</i>
                                    <?php printf('%s - %s', $useInfo['who'], date('Y-m-d H:i', $useInfo['time'])); ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </td>
        </tr>
        </tbody>
    </table>
</div>
<script type="text/javascript">
    sysPassApp.sk.set("<?php echo $sk; ?>");
</script>