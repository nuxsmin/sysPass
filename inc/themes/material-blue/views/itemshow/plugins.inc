<?php
/** @var  \SP\DataModel\PluginData $plugin */
/** @var  \SP\Core\Plugin\PluginInterface $pluginInfo */
/** @var  \SP\Core\UI\ThemeIconsBase $icons */
?>
<div id="box-popup">
    <h2 class="center"><?php echo $header; ?><i class="btn-popup-close material-icons">close</i></h2>

    <form method="post" name="frmCategories" id="frmPlugins">
        <table class="popup-data">
            <tbody>
            <tr>
                <td class="descField"><?php echo __('Nombre'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="name" name="name" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               value="<?php echo $plugin->getPluginName(); ?>" <?php echo $isReadonly; ?>>
                        <label class="mdl-textfield__label"
                               for="name"><?php echo __('Nombre del plugin'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo __('Versión'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="version" name="version" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               value="<?php echo implode('.', $pluginInfo->getVersion()); ?>" <?php echo $isReadonly; ?>>
                        <label class="mdl-textfield__label"
                               for="version"><?php echo __('Versión del plugin'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo __('Versión Compatible'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="compatible_version" name="compatible_version" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               value="<?php echo implode('.', $pluginInfo->getCompatibleVersion()); ?>" <?php echo $isReadonly; ?>>
                        <label class="mdl-textfield__label"
                               for="compatible_version"><?php echo __('Versión de sysPass compatible'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo __('Autor'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="author" name="author" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               value="<?php echo $pluginInfo->getAuthor(); ?>" <?php echo $isReadonly; ?>>
                        <label class="mdl-textfield__label"
                               for="author"><?php echo __('Autor del plugin'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo __('Eventos'); ?></td>
                <td class="valField">
                    <div class="lowres-title"><?php echo __('Eventos'); ?></div>

                    <div class="list-wrap">
                        <ul class="mdl-list">
                            <?php foreach ($pluginInfo->getEvents() as $event): ?>
                                <li class="mdl-list__item">
                                <span class="mdl-list__item-primary-content">
                                <i class="material-icons mdl-list__item-icon">flash_on</i>
                                    <?php echo $event; ?>
                                </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </td>
            </tr>
        </table>
    </form>
</div>
<script>
    sysPassApp.sk.set("<?php echo $sk; ?>");
</script>