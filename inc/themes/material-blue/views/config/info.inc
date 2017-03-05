<!-- Start Tab - Info -->
<div id="tabs-<?php echo $info_tabIndex; ?>" class="mdl-tabs__panel">
    <div class="tab-data">
        <div id="title" class="midroundup titleNormal">
            <?php echo __('Información de la Aplicación'); ?>
        </div>

        <table class="data round">
            <tr>
                <td class="descField">
                    <?php echo __('Versión sysPass'); ?>
                </td>
                <td class="valField">
                    <div class="lowres-title"><?php echo __('Versión sysPass'); ?></div>

                    <?php printf('%s (%s)', \SP\Util\Util::getVersionString(), \SP\Html\Html::strongText(implode('.', \SP\Util\Util::getVersion(true)))); ?>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo __('Base de Datos'); ?>
                </td>
                <td class="valField">
                    <div class="lowres-title"><?php echo __('Base de Datos'); ?></div>

                    <?php foreach ($dbInfo as $infoattr => $infoval): ?>
                        <?php printf('%s : %s', \SP\Html\Html::strongText($infoattr), $infoval); ?>
                        <br>
                    <?php endforeach; ?>
                    <?php printf('%s: %s', \SP\Html\Html::strongText(__('Nombre')), (!$isDemoMode) ? $dbName : '***'); ?>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo __('PHP'); ?>
                </td>
                <td class="valField">
                    <div class="lowres-title"><?php echo __('PHP'); ?></div>

                    <?php printf('%s: %s', \SP\Html\Html::strongText(__('Versión')), (!$isDemoMode) ? phpversion() : '***'); ?>
                    <br>
                    <?php printf('%s: %s', \SP\Html\Html::strongText(__('Extensiones')), implode(', ', get_loaded_extensions())); ?>
                    <br>
                    <?php printf('%s: %d KB', \SP\Html\Html::strongText(__('Memoria Usada')), (memory_get_usage(true) / 1024)); ?>
                    <br>
                    <?php printf('%s: %s', \SP\Html\Html::strongText(__('Usuario')), (!$isDemoMode) ? get_current_user() : '***'); ?>
                    <?php if (function_exists('opcache_get_status') && version_compare(PHP_VERSION, '5.6.0', '>=')): ?>
                        <br>
                        <?php echo \SP\Html\Html::strongText(__('OP Cache')); ?>
                        <?php foreach (opcache_get_status(false)['opcache_statistics'] as $key => $value): ?>
                            <br><?php printf('%s : %s', $key, $value); ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo __('Servidor'); ?>
                </td>
                <td class="valField">
                    <div class="lowres-title"><?php echo __('Servidor'); ?></div>

                    <?php echo (!$isDemoMode) ? \SP\Html\Html::strongText($_SERVER['SERVER_SOFTWARE']) : '***'; ?>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo __('Copia de Configuración'); ?>
                </td>
                <td class="valField">
                    <div class="lowres-title"><?php echo __('Copia de Configuración'); ?></div>

                    <?php echo $configBackupDate; ?>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo __('Idioma'); ?>
                    <div id="help-infolang"
                         class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                    <div class="mdl-tooltip mdl-tooltip--large" for="help-infolang">
                        <p>
                            <?php echo __('Indica si el idioma se encuentra disponible'); ?>
                        </p>

                        <p>
                            <?php echo __('Si no está instalado, es necesario instalar las locales en el sistema operativo. Más información en Wiki.'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <div class="lowres-title"><?php echo __('Idioma'); ?></div>

                    <?php echo $locale; ?>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo __('Sesión Encriptada'); ?>
                    <div id="help-infosession"
                         class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                    <div class="mdl-tooltip mdl-tooltip--large" for="help-infosession">
                        <p>
                            <?php echo __('Indica si los datos de la sesión están encriptados en el servidor'); ?>
                        </p>
                    </div>
                </td>
                <td class="valField">
                    <div class="lowres-title"><?php echo __('Sesión Encriptada'); ?></div>

                    <?php echo $securedSession ? __('Sí') : __('No'); ?>
                </td>
            </tr>
            <tr>
                <td class="descField">
                    <?php echo __('Plugins Cargados'); ?>
                </td>
                <td class="valField">
                    <div class="lowres-title"><?php echo __('Plugins Cargados'); ?></div>

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
    <div class="tab-actions">
        <ul>
            <li>
                <button id="btnBack" type="button"
                        class="btn-back mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconBack()->getClassButton(); ?>"
                        title="<?php echo __('Atrás'); ?>">
                    <i class="material-icons"><?php echo $icons->getIconBack()->getIcon(); ?></i>
                </button>
            </li>
        </ul>
    </div>
</div> <!-- End Tab - Info -->