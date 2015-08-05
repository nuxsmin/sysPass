<!-- Start Tab - Info -->
<div id="tabs-<?php echo $info_tabIndex; ?>">
    <div id="title" class="midroundup titleNormal">
        <?php echo _('Información de la Aplicación'); ?>
    </div>

    <table class="data round">
        <tr>
            <td class="descField">
                <?php echo _('Versión sysPass'); ?>
            </td>
            <td class="valField">
                <?php echo SP\Html::strongText(implode('.', \SP\Util::getVersion(true))); ?>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo _('Base de Datos'); ?>
            </td>
            <td class="valField">
                <?php foreach ($dbInfo as $infoattr => $infoval): ?>
                    <?php printf('%s : %s', SP\Html::strongText($infoattr), $infoval); ?>
                    <br>
                <?php endforeach; ?>
                <?php printf('%s: %s', SP\Html::strongText(_('Nombre')), $dbName); ?>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo _('PHP'); ?>
            </td>
            <td class="valField">
                <?php printf('%s: %s', SP\Html::strongText(_('Versión')), phpversion()); ?>
                <br>
                <?php printf('%s: %s', SP\Html::strongText(_('Extensiones')), implode(', ', get_loaded_extensions())); ?>
                <br>
                <?php printf('%s: %d KB', SP\Html::strongText(_('Memoria Usada')), (memory_get_usage(true) / 1024)); ?>
                <br>
                <?php  printf('%s: %s', SP\Html::strongText(_('Usuario')), get_current_user()); ?>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo _('Servidor'); ?>
            </td>
            <td class="valField">
                <?php echo SP\Html::strongText($_SERVER['SERVER_SOFTWARE']); ?>
            </td>
        </tr>
    </table>
</div> <!-- End Tab - Info -->