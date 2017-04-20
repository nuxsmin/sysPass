<?php
/**
 * @var $link SP\DataModel\PublicLinkListData
 */
?>
<div id="box-popup">
    <h2 class="center"><?php echo $header; ?><i class="btn-popup-close material-icons">close</i></h2>
    <table class="popup-data">
        <tbody>
        <tr>
            <td class="descField"><?php echo __('Cuenta'); ?></td>
            <td class="valField">
                <div class="lowres-title"><?php echo __('Cuenta'); ?></div>

                <?php echo $link->getAccountName(); ?>
            </td>
        </tr>
        <tr>
            <td class="descField"><?php echo __('Fecha Creación'); ?></td>
            <td class="valField">
                <div class="lowres-title"><?php echo __('Fecha Creación'); ?></div>

                <?php echo $link->getDateAdd(); ?>
            </td>
        </tr>
        <tr>
            <td class="descField"><?php echo __('Fecha Caducidad'); ?></td>
            <td class="valField">
                <div class="lowres-title"><?php echo __('Fecha Caducidad'); ?></div>

                <?php echo $link->getDateExpire(); ?>
            </td>
        </tr>
        <tr>
            <td class="descField"><?php echo __('Usuario'); ?></td>
            <td class="valField">
                <div class="lowres-title"><?php echo __('Usuario'); ?></div>

                <?php echo $link->getUserLogin(); ?>
            </td>
        </tr>
        <tr>
            <td class="descField"><?php echo __('Notificar'); ?></td>
            <td class="valField">
                <div class="lowres-title"><?php echo __('Notificar'); ?></div>

                <?php echo $link->getNotify(); ?>
            </td>
        </tr>
        <tr>
            <td class="descField"><?php echo __('Visitas'); ?></td>
            <td class="valField">
                <div class="lowres-title"><?php echo __('Visitas'); ?></div>

                <?php echo $link->getCountViews(); ?>
            </td>
        </tr>
        <tr>
            <td class="descField"><?php echo __('Hash'); ?></td>
            <td class="valField">
                <div class="lowres-title"><?php echo __('Hash'); ?></div>

                <?php echo $link->getPublicLinkHash(); ?>
            </td>
        </tr>
        <tr>
            <td class="descField"><?php echo __('Uso'); ?></td>
            <td class="valField">
                <div class="lowres-title"><?php echo __('Uso'); ?></div>

                <div class="list-wrap">
                    <ul class="mdl-list">
                        <?php foreach ($link->getUseInfo() as $useInfo): ?>
                            <li class="mdl-list__item">
                                <span class="mdl-list__item-primary-content">
                                <i class="material-icons mdl-list__item-icon">access_time</i>
                                    <?php printf('%s - %s', $isDemo ? '*.*.*.*' : $useInfo['who'], date('Y-m-d H:i', $useInfo['time'])); ?>
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