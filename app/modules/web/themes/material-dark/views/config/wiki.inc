<?php
/**
 * @var ThemeIcons $icons
 * @var ConfigData $configData
 * @var callable   $_getvar
 * @var Template   $this
 */

use SP\Config\ConfigData;
use SP\Core\UI\ThemeIcons;
use SP\Mvc\View\Template;

?>

<form method="post" name="frmWiki" id="frmWiki" class="form-action"
      data-onsubmit="config/save"
      data-action-route="configWiki/save"
      data-hash="">

    <div id="title" class="titleNormal">
        <i class="material-icons">folder</i>
        <?php echo __('Wiki'); ?>
    </div>

    <table id="tblWiki" class="data tblConfig">
        <tr>
            <td class="descField"></td>
            <td class="valField">
                <ul class="config-wiki-list-action mdl-list">
                    <li class="mdl-list__item mdl-list__item--two-line">
                        <div class="mdl-switch__box">
                            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                                   for="wiki_enabled">
                                <input type="checkbox" id="wiki_enabled"
                                       class="mdl-switch__input"
                                       name="wiki_enabled" <?php echo $configData->isWikiEnabled() ? 'checked' : ''; ?>/>
                            </label>
                        </div>

                        <span class="mdl-list__item-primary-content">
                            <span><?php echo __('Enable Wiki links'); ?></span>
                            <span class="mdl-list__item-sub-title"><?php echo __('Enables the option to add a link to search for results in an external Wiki.'); ?></span>
                        </span>
                    </li>
                </ul>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo __('Wiki search URL'); ?>
                <div id="help-wiki_searchurl"
                     class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                <div class="mdl-tooltip mdl-tooltip--large"
                     for="help-wiki_searchurl">
                    <p>
                        <?php echo __('URL that Wiki uses for making an entry searching.'); ?>
                    </p>

                    <p>
                        <?php echo __('The client\'s name is used as parameter.'); ?>
                    </p>

                    <p>
                        <?php __('Example:'); ?>
                    </p>

                    <p>
                        https://wiki.example.org/search.php?phrase=
                    </p>
                </div>
            </td>
            <td class="valField">
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="wiki_searchurl" name="wiki_searchurl" type="text"
                           class="mdl-textfield__input mdl-color-text--indigo-400"
                           maxlength="255"
                           value="<?php echo $configData->getWikiSearchurl(); ?>">
                    <label class="mdl-textfield__label"
                           for="wiki_searchurl"><?php echo __('Wiki search URL'); ?></label>
                </div>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo __('Wiki page URL'); ?>
                <div id="help-wiki_pageurl"
                     class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                <div class="mdl-tooltip mdl-tooltip--large"
                     for="help-wiki_pageurl">
                    <p>
                        <?php echo __('URL that Wiki uses for accessing to the entry details.'); ?>
                    </p>

                    <p>
                        <?php echo __('Account\'s name is used as parameter of Wiki search variable.'); ?>
                    </p>

                    <p>
                        <?php echo __('Example:'); ?>
                    </p>

                    <p>
                        https://wiki.example.org/show.php?name=
                    </p>
                </div>
            </td>
            <td class="valField">
                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="wiki_pageurl" name="wiki_pageurl" type="text"
                           class="mdl-textfield__input mdl-color-text--indigo-400"
                           maxlength="255"
                           value="<?php echo $configData->getWikiPageurl(); ?>">
                    <label class="mdl-textfield__label"
                           for="wiki_pageurl"><?php echo __('Wiki page URL'); ?></label>
                </div>
            </td>
        </tr>
        <tr>
            <td class="descField">
                <?php echo __('Account name prefix'); ?>
                <div id="help-wikifilter"
                     class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
                <div class="mdl-tooltip mdl-tooltip--large"
                     for="help-wikifilter">
                    <p>
                        <?php echo __('Prefix to determine which accounts have a link to the Wiki.'); ?>
                    </p>

                    <p>
                        <?php echo __('Examples:') . ' serv- | srv- | vm-'; ?>
                    </p>
                </div>
            </td>
            <td class="valField">
                <div class="lowres-title"><?php echo __('Account name prefix'); ?></div>

                <input type="text" name="wiki_filter" id="wikifilter"
                       value="<?php echo implode(',', $configData->getWikiFilter()); ?>"/>
            </td>
        </tr>
    </table>
</form>

<div class="tab-actions">
    <ul>
        <li>
            <button id="btnBack" type="button"
                    class="btn-back mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconBack()->getClassButton(); ?>"
                    title="<?php echo __('Back'); ?>">
                <i class="material-icons"><?php echo $icons->getIconBack()->getIcon(); ?></i>
            </button>
        </li>
        <li>
            <button form="frmWiki"
                    class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconSave()->getClassButton(); ?>"
                    title="<?php echo $icons->getIconSave()->getTitle(); ?>">
                <i class="material-icons"><?php echo $icons->getIconSave()->getIcon(); ?></i>
            </button>
        </li>
    </ul>
</div>
