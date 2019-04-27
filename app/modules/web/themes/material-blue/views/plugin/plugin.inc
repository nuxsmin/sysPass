<?php
/**
 * @var  PluginModel     $plugin
 * @var  PluginInterface $pluginInfo
 * @var ThemeIcons       $icons
 * @var ConfigData       $configData
 * @var callable         $_getvar
 * @var Template         $this
 */

use SP\Config\ConfigData;
use SP\Core\UI\ThemeIcons;
use SP\Mvc\View\Template;
use SP\Plugin\PluginInterface;
use SP\Repositories\Plugin\PluginModel;

$plugin = $_getvar('plugin');
$pluginInfo = $_getvar('pluginInfo');
?>

<div id="box-popup">
    <h2 class="center"><?php echo $_getvar('header'); ?><i
                class="btn-popup-close material-icons">close</i></h2>

    <form method="post" name="frmCategories" id="frmPlugins">
        <table class="popup-data">
            <tbody>
            <tr>
                <td class="descField"><?php echo __('Name'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="name" name="name" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               value="<?php echo $plugin->getName(); ?>" <?php echo $_getvar('readonly'); ?>>
                        <label class="mdl-textfield__label"
                               for="name"><?php echo __('Plugin name'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo __('Version'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="version" name="version" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               value="<?php echo implode('.', $pluginInfo->getVersion()); ?>" <?php echo $_getvar('readonly'); ?>>
                        <label class="mdl-textfield__label"
                               for="version"><?php echo __('Plugin version'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo __('Compatible Version'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="compatible_version" name="compatible_version"
                               type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               value="<?php echo implode('.', $pluginInfo->getCompatibleVersion()); ?>" <?php echo $_getvar('readonly'); ?>>
                        <label class="mdl-textfield__label"
                               for="compatible_version"><?php echo __('sysPass compatible version'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo __('Author'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="author" name="author" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               value="<?php echo $pluginInfo->getAuthor(); ?>" <?php echo $_getvar('readonly'); ?>>
                        <label class="mdl-textfield__label"
                               for="author"><?php echo __('Plugin author'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo __('Events'); ?></td>
                <td class="valField">
                    <div class="lowres-title"><?php echo __('Events'); ?></div>

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