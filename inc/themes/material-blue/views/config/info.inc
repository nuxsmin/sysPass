<!-- Start Tab - Info -->
<div id="tabs-<?php echo $info_tabIndex; ?>" class="mdl-tabs__panel">
    <div class="tab-data">
        <div id="title" class="midroundup titleNormal">
            <?php echo _('Información de la Aplicación'); ?>
        </div>

        <table class="data round">
            <tr>
                <td class="descField">
                    <?php echo _('Versión sysPass'); ?>
                </td>
                <td class="valField">
                    <?php echo \SP\Html\Html::strongText(implode('.', \SP\Util\Util::getVersion(true))); ?>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Base de Datos'); ?>
                </td>
                <td class="valField">
                    <?php foreach ($dbInfo as $infoattr => $infoval): ?>
                        <?php printf('%s : %s', \SP\Html\Html::strongText($infoattr), $infoval); ?>
                        <br>
                    <?php endforeach; ?>
                    <?php printf('%s: %s', \SP\Html\Html::strongText(_('Nombre')), (!$isDemoMode) ? $dbName : '***'); ?>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('PHP'); ?>
                </td>
                <td class="valField">
                    <?php printf('%s: %s', \SP\Html\Html::strongText(_('Versión')), (!$isDemoMode) ? phpversion() : '***'); ?>
                    <br>
                    <?php printf('%s: %s', \SP\Html\Html::strongText(_('Extensiones')), implode(', ', get_loaded_extensions())); ?>
                    <br>
                    <?php printf('%s: %d KB', \SP\Html\Html::strongText(_('Memoria Usada')), (memory_get_usage(true) / 1024)); ?>
                    <br>
                    <?php printf('%s: %s', \SP\Html\Html::strongText(_('Usuario')), (!$isDemoMode) ? get_current_user() : '***'); ?>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Servidor'); ?>
                </td>
                <td class="valField">
                    <?php echo (!$isDemoMode) ? \SP\Html\Html::strongText($_SERVER['SERVER_SOFTWARE']) : '***'; ?>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Copia de Configuración'); ?>
                </td>
                <td class="valField">
                    <?php echo $configBackupDate; ?>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo _('Plugins Cargados'); ?>
                </td>
                <td class="valField">
                    <ul class="mdl-list">
                        <?php /** @var \SP\Core\Plugin\PluginInterface[] $plugins */
                        foreach ($plugins as $name => $plugin): ?>
                            <li class="mdl-list__item">
                                <span class="mdl-list__item-primary-content">
                                <i class="material-icons mdl-list__item-icon">extension</i>
                                    <?php echo $name; ?>
                                    <br>
                                    <?php printf('Version: %s', implode('.', $plugin->getVersion())); ?>
                                    <br>
                                    <?php printf('Autor: %s', $plugin->getAuthor()); ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </td>
            </tr>
        </table>
    </div>
</div> <!-- End Tab - Info -->