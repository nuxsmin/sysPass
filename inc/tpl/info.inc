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
                <?php echo implode('.', SP_Util::getVersion(true)); ?>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo _('Base de Datos'); ?>
            </td>
            <td class="valField">
                <?php foreach ($dbInfo as $infoattr => $infoval): ?>
                <?php echo $infoattr, ': ', $infoval, '<br><br>'; ?>
                <?php endforeach; ?>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo _('PHP'); ?>
            </td>
            <td class="valField">
                <?php
                echo _('Versión'), ': ', phpversion(), '<br><br>';
                echo _('Extensiones'), ': ', wordwrap(implode(', ', get_loaded_extensions()), 75, '<br>'), '<br><br>';
                echo _('Memoria'), ': ', (memory_get_usage(true) / 1024), ' KB<br><br>';
                echo _('Usuario'), ': ', get_current_user(), '<br><br>';
                ?>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo _('Servidor'); ?>
            </td>
            <td class="valField">
                <?php echo $_SERVER['SERVER_SOFTWARE']; ?>
            </td>
        </tr>
    </table>
</div> <!-- End Tab - Info -->